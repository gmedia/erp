<?php

namespace App\Notifications;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GenericAppNotification extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly string $eventKey,
        public readonly array $payload,
        public readonly string $targetUrl,
        public readonly string $recipientEmail,
        public readonly string $companyName = '',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [$this->recipientEmail],
            subject: (string) ($this->payload['title'] ?? $this->eventKey),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.generic-notification',
            with: [
                'title' => (string) ($this->payload['title'] ?? $this->eventKey),
                'body' => (string) ($this->payload['body'] ?? ''),
                'url' => $this->targetUrl,
                'companyName' => $this->companyName !== ''
                    ? $this->companyName
                    : (string) (Setting::get('company_name') ?? config('app.name', 'ERP')),
            ],
        );
    }
}
