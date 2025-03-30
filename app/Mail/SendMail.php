<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class SendMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;
    public $collections;
    public $template_name;
    /**
     * Create a new message instance.
     */
    public function __construct($subject,$collections, $template_name = 'notification')
    {
        $this->subject = $subject;
        $this->collections = $collections;
        $this->template_name = $template_name;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
            from: "no-reply@example.com"
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {

        return new Content(
            view: 'emails.'.$this->template_name,
        );
    }

}
