<?php

namespace App\Mail;

use App\Models\ApprovalRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApprovalRequestNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly ApprovalRequest $approvalRequest,
        public readonly User $approver,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New approval request waiting for you',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.approval-request-notification',
        );
    }
}
