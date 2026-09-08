<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VaccineReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array<int, array{label: string, value: string}> $details صفوف تفاصيل إضافية تظهر في البريد
     */
    public function __construct(
        public string $parentName,
        public string $childName,
        public string $bodyMessage,
        public array $details = [],
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'تنبيه من منصة لقاحي',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.vaccine-reminder',
        );
    }
}