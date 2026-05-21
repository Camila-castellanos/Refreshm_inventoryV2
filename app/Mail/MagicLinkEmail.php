<?php

namespace App\Mail;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MagicLinkEmail extends Mailable
{
    use Queueable, SerializesModels;

    public Customer $customer;
    public string $magicLinkUrl;
    public string $type;

    /**
     * Create a new message instance.
     * @param string $type 'activation' | 'reset' | 'access'
     */
    public function __construct(Customer $customer, string $magicLinkUrl, string $type = 'access')
    {
        $this->customer = $customer;
        $this->magicLinkUrl = $magicLinkUrl;
        $this->type = $type;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subjects = [
            'activation' => 'Activate your Customer Account',
            'reset'      => 'Reset your password',
            'access'     => 'Access your account',
        ];

        return new Envelope(
            subject: $subjects[$this->type] ?? $subjects['access'],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.portal.magic-link',
            with: [
                'type' => $this->type,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}