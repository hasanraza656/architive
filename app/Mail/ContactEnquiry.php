<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Admin notification for a website project enquiry (contact form).
 * Reply-To is the visitor, so "Reply" in the inbox answers them directly.
 */
class ContactEnquiry extends Mailable
{
    public const SERVICES = [
        'visualization' => 'Architectural visualization',
        'bim'           => 'BIM and Revit',
        'cad'           => 'CAD drafting',
        'outsourcing'   => 'Ongoing production support',
        'unsure'        => 'Not sure yet',
    ];

    /** Services that only exist as their own page/wizard (not offered in the general forms). */
    public const EXTRA_SERVICES = [
        'permit-revisions' => 'Permit revisions',
    ];

    public static function serviceLabel(?string $key): ?string
    {
        return self::SERVICES[$key ?? ''] ?? self::EXTRA_SERVICES[$key ?? ''] ?? null;
    }

    public const AUDIENCES = [
        'firm'       => 'Architecture firm',
        'interior'   => 'Interior design studio',
        'developer'  => 'Developer / contractor',
        'homeowner'  => 'Homeowner',
    ];

    public function __construct(public array $enquiry, public array $meta = [])
    {
    }

    public function envelope(): Envelope
    {
        $service = self::serviceLabel($this->enquiry['service'] ?? null) ?? 'General enquiry';
        $ref = ! empty($this->meta['order']) ? ' ' . $this->meta['order']->number : '';

        return new Envelope(
            replyTo: [new Address($this->enquiry['email'], $this->enquiry['name'])],
            subject: (($ref !== '') ? 'New project request' . $ref : 'New project enquiry') . ': ' . $service . ' · ' . $this->enquiry['name']
                . (! empty($this->enquiry['nda']) ? ' · NDA REQUESTED' : ''),      // no detailed files are shared until the NDA is signed
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-enquiry',
            text: 'emails.contact-enquiry-text',
            with: [
                'e'        => $this->enquiry,
                'meta'     => $this->meta,
                'service'  => self::serviceLabel($this->enquiry['service'] ?? null),
                'audience' => self::AUDIENCES[$this->enquiry['audience'] ?? ''] ?? null,
                'siteName' => config('site.name'),
            ],
        );
    }
}
