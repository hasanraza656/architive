@php
    $tone = ['paid' => 'green', 'open' => 'amber', 'draft' => 'grey', 'void' => 'grey', 'uncollectible' => 'amber'][$status] ?? 'grey';
    $label = \App\Services\Payments\StripeInvoices::STATUSES[$status] ?? ucfirst($status);
@endphp
<span class="pbadge pbadge--{{ $tone }}">{{ $label }}</span>
