<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TurnoCanceladoMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $turno;
    public $motivoBloqueo;

    /**
     * Create a new message instance.
     */
    public function __construct($turno, $motivoBloqueo)
    {
        $this->turno = $turno;
        $this->motivoBloqueo = $motivoBloqueo;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Notificación de Cancelación de Turno Médico',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.turno_cancelado',
            with: [
                'turno' => $this->turno,
                'motivoBloqueo' => $this->motivoBloqueo,
            ],
        );
    }
}