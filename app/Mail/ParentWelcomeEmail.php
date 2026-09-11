<?php

namespace App\Mail;

use App\Models\ParentUser;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ParentWelcomeEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $parentUser;
    public $overdueChildren;

    /**
     * Create a new message instance.
     */
    public function __construct(ParentUser $parentUser, $overdueChildren = [])
    {
        $this->parentUser = $parentUser;
        $this->overdueChildren = $overdueChildren;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'مرحباً بك في نظام لقاحي',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.parents.welcome',
            with: [
                'parentUser' => $this->parentUser,
                'overdueChildren' => $this->overdueChildren,
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
