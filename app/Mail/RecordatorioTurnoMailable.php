<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecordatorioTurnoMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $turno;

    public function __construct($turno)
    {
        $this->turno = $turno;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Recordatorio de Turno Médico - Mañana');
    }

    public function content(): Content
    {
        // Asegúrate de crear la vista: resources/views/emails/recordatorio_turno.blade.php
        return new Content(view: 'emails.recordatorio_turno');
    }
}