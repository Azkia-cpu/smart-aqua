<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Pond;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SensorAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public readonly Pond $pond,
        public readonly string $alertType,
        public readonly string $alertTitle,
        public readonly string $alertMessage,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[SmartAqua] Alert: {$this->alertTitle}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.sensor-alert',
            with: [
                'pond' => $this->pond,
                'alertType' => $this->alertType,
                'alertTitle' => $this->alertTitle,
                'alertMessage' => $this->alertMessage,
                'timestamp' => now()->format('Y-m-d H:i:s T'),
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
