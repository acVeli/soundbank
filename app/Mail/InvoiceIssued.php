<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceIssued extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Invoice $invoice) {}

    public function envelope(): Envelope
    {
        $seller = $this->invoice->seller;

        return new Envelope(
            replyTo: filled($seller['email'] ?? null) ? [new Address((string) $seller['email'], (string) ($seller['name'] ?? ''))] : [],
            subject: $this->invoice->label().' '.$this->invoice->number.' — '.($seller['name'] ?? config('app.name')),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.invoice');
    }
}
