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
        $service = self::SERVICES[$this->enquiry['service'] ?? ''] ?? 'General enquiry';
        $ref = ! empty($this->meta['order']) ? ' ' . $this->meta['order']->number : '';

        return new Envelope(
            replyTo: [new Address($this->enquiry['email'], $this->enquiry['name'])],
            subject: (($ref !== '') ? 'New project request' . $ref : 'New project enquiry') . ': ' . $service . ' · ' . $this->enquiry['name'],
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
                'service'  => self::SERVICES[$this->enquiry['service'] ?? ''] ?? null,
                'audience' => self::AUDIENCES[$this->enquiry['audience'] ?? ''] ?? null,
                'siteName' => config('site.name'),
            ],
        );
    }
}
