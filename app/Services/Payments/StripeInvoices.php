<?php

namespace App\Services\Payments;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Stripe\StripeClient;

/**
 * Read-only view of the invoices that live in Stripe (the ones created in the Stripe dashboard or by Stripe Billing).
 * Admin side: list + filter + open one. Customer side: only invoices whose Stripe customer e-mail is the signed-in
 * person's own (verified) e-mail address. Nothing is ever written to Stripe from here.
 *
 * Results are cached for a minute so the pages stay fast and Stripe's rate limits are never an issue.
 */
class StripeInvoices
{
    public const STATUSES = ['draft' => 'Draft', 'open' => 'Open', 'paid' => 'Paid', 'uncollectible' => 'Uncollectible', 'void' => 'Void'];

    private const TTL = 60;

    /** @param object|null $client anything shaped like Stripe\StripeClient (tests pass a fake) */
    public function __construct(private ?object $client = null)
    {
    }

    public function configured(): bool
    {
        return $this->client !== null || (bool) config('services.stripe.secret');
    }

    private function api(): object
    {
        if ($this->client) {
            return $this->client;
        }
        $secret = config('services.stripe.secret');
        if (! $secret) {
            throw new RuntimeException('Stripe is not connected yet (STRIPE_SECRET is missing in the server settings).');
        }

        return $this->client = new StripeClient($secret);
    }

    /**
     * Admin list.
     *
     * @param  array{status?:?string,from?:?string,to?:?string,customer?:?string,number?:?string,after?:?string}  $f
     * @return array{rows: array<int,array>, has_more: bool, next: ?string, mode: string, note: ?string}
     */
    public function search(array $f, bool $fresh = false): array
    {
        $key = 'stripe:invoices:' . md5(json_encode($f));
        if ($fresh) {
            Cache::forget($key);
        }

        return Cache::remember($key, self::TTL, fn () => $this->runSearch($f));
    }

    private function runSearch(array $f): array
    {
        $api = $this->api();
        $status = in_array($f['status'] ?? null, array_keys(self::STATUSES), true) ? $f['status'] : null;
        $created = array_filter([
            'gte' => ! empty($f['from']) ? Carbon::parse($f['from'])->startOfDay()->timestamp : null,
            'lte' => ! empty($f['to']) ? Carbon::parse($f['to'])->endOfDay()->timestamp : null,
        ]);
        $customer = $this->clean($f['customer'] ?? '');
        $number = $this->clean($f['number'] ?? '');

        // 1) one invoice number
        if ($number !== '') {
            $query = "number:'" . $number . "'" . ($status ? " AND status:'$status'" : '');
            $res = $api->invoices->search(['query' => $query, 'limit' => 20]);
            $rows = $this->rows($res->data ?? []);
            if ($created) {
                $rows = array_values(array_filter($rows, fn ($r) => (! isset($created['gte']) || $r['created_ts'] >= $created['gte']) && (! isset($created['lte']) || $r['created_ts'] <= $created['lte'])));
            }

            return ['rows' => $rows, 'has_more' => false, 'next' => null, 'mode' => 'number', 'note' => null];
        }

        // 2) a customer (name or e-mail, part of it is enough): find the Stripe customers first, then their invoices
        if ($customer !== '') {
            $ids = $this->customerIds($customer);
            $rows = [];
            foreach ($ids as $id) {
                $res = $api->invoices->all(array_filter(['customer' => $id, 'status' => $status, 'created' => $created ?: null, 'limit' => 40]));
                $rows = array_merge($rows, $this->rows($res->data ?? []));
            }
            usort($rows, fn ($a, $b) => $b['created_ts'] <=> $a['created_ts']);

            return [
                'rows' => array_slice($rows, 0, 100), 'has_more' => false, 'next' => null, 'mode' => 'customer',
                'note' => $ids ? null : 'No Stripe customer matches “' . $customer . '”.',
            ];
        }

        // 3) everything, newest first, page by page
        $res = $api->invoices->all(array_filter([
            'status' => $status, 'created' => $created ?: null, 'limit' => 25, 'starting_after' => ! empty($f['after']) && preg_match('/^in_[A-Za-z0-9_]+$/', $f['after']) ? $f['after'] : null,
        ]));
        $rows = $this->rows($res->data ?? []);

        return ['rows' => $rows, 'has_more' => (bool) ($res->has_more ?? false), 'next' => $rows ? end($rows)['id'] : null, 'mode' => 'all', 'note' => null];
    }

    /** One invoice with its lines (admin detail page). Returns null when Stripe does not know the id. */
    public function find(string $id): ?array
    {
        if (! preg_match('/^in_[A-Za-z0-9_]+$/', $id)) {
            return null;
        }

        return Cache::remember('stripe:invoice:' . $id, self::TTL, function () use ($id) {
            try {
                $inv = $this->api()->invoices->retrieve($id, []);
            } catch (\Stripe\Exception\InvalidRequestException) {
                return null;
            }
            $row = $this->row($inv);
            $lines = $this->arr($inv)['lines']['data'] ?? [];
            $row['lines'] = array_map(fn ($l) => [
                'description' => $l['description'] ?? '', 'quantity' => $l['quantity'] ?? 1, 'amount' => (int) ($l['amount'] ?? 0),
            ], $lines);
            $row['subtotal'] = (int) ($this->arr($inv)['subtotal'] ?? 0);
            $row['tax'] = (int) ($this->arr($inv)['tax'] ?? 0);
            $row['memo'] = $this->arr($inv)['description'] ?? null;

            return $row;
        });
    }

    /**
     * A customer's own invoices: every Stripe customer with exactly this e-mail address, drafts left out. Each invoice is
     * double-checked so it is only returned when its customer e-mail really is this address.
     */
    public function forEmail(string $email, bool $fresh = false): array
    {
        $email = mb_strtolower(trim($email));
        if ($email === '') {
            return [];
        }
        $key = 'stripe:mine:' . md5($email);
        if ($fresh) {
            Cache::forget($key);
        }

        return Cache::remember($key, self::TTL, function () use ($email) {
            $api = $this->api();
            $rows = [];
            foreach ($this->customerIdsByEmail($email) as $id) {
                $res = $api->invoices->all(['customer' => $id, 'limit' => 50]);
                foreach ($this->rows($res->data ?? []) as $r) {
                    if ($r['status'] !== 'draft' && mb_strtolower((string) $r['customer_email']) === $email) {
                        $rows[$r['id']] = $r;
                    }
                }
            }
            $rows = array_values($rows);
            usort($rows, fn ($a, $b) => $b['created_ts'] <=> $a['created_ts']);

            return $rows;
        });
    }

    /* ------------------------------------------------------------ helpers */

    /** Stripe customers whose name or e-mail contains the text (at most 10). */
    private function customerIds(string $text): array
    {
        $api = $this->api();
        $query = mb_strlen($text) >= 3 ? "email~'$text' OR name~'$text'" : "email:'$text'";
        $res = $api->customers->search(['query' => $query, 'limit' => 10]);

        return array_values(array_unique(array_map(fn ($c) => $this->arr($c)['id'], $res->data ?? [])));
    }

    private function customerIdsByEmail(string $email): array
    {
        $api = $this->api();
        $ids = [];
        try {
            foreach ($api->customers->search(['query' => "email:'" . $this->clean($email) . "'", 'limit' => 10])->data ?? [] as $c) {
                $c = $this->arr($c);
                if (mb_strtolower((string) ($c['email'] ?? '')) === $email) {
                    $ids[] = $c['id'];
                }
            }
        } catch (\Stripe\Exception\ApiErrorException) {
            // search can be unavailable for an account: fall back to the exact-match list below
        }
        foreach ($api->customers->all(['email' => $email, 'limit' => 10])->data ?? [] as $c) {
            $c = $this->arr($c);
            if (mb_strtolower((string) ($c['email'] ?? '')) === $email) {
                $ids[] = $c['id'];
            }
        }

        return array_values(array_unique($ids));
    }

    /** @param iterable<mixed> $invoices */
    private function rows(iterable $invoices): array
    {
        $out = [];
        foreach ($invoices as $inv) {
            $out[] = $this->row($inv);
        }

        return $out;
    }

    private function row(mixed $inv): array
    {
        $i = $this->arr($inv);
        $total = (int) ($i['total'] ?? 0);

        return [
            'id' => $i['id'] ?? '',
            'number' => $i['number'] ?? null,
            'status' => $i['status'] ?? 'draft',
            'customer_id' => is_array($i['customer'] ?? null) ? ($i['customer']['id'] ?? null) : ($i['customer'] ?? null),
            'customer_name' => $i['customer_name'] ?? null,
            'customer_email' => $i['customer_email'] ?? null,
            'currency' => $i['currency'] ?? 'usd',
            'total' => $total,
            'amount_paid' => (int) ($i['amount_paid'] ?? 0),
            'amount_due' => (int) ($i['amount_due'] ?? 0),
            'created_ts' => (int) ($i['created'] ?? 0),
            'created' => ! empty($i['created']) ? Carbon::createFromTimestamp($i['created']) : null,
            'due' => ! empty($i['due_date']) ? Carbon::createFromTimestamp($i['due_date']) : null,
            'hosted_url' => $i['hosted_invoice_url'] ?? null,
            'pdf_url' => $i['invoice_pdf'] ?? null,
            'description' => $i['description'] ?? null,
        ];
    }

    /** A Stripe object (or an array) as a plain array. */
    private function arr(mixed $o): array
    {
        if (is_array($o)) {
            return $o;
        }

        return is_object($o) && method_exists($o, 'toArray') ? $o->toArray() : (array) $o;
    }

    /** Remove the characters that could break out of a Stripe search query. */
    private function clean(string $v): string
    {
        return trim(str_replace(["'", '"', '\\', "\n", "\r"], '', $v));
    }
}
