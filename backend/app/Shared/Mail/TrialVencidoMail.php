<?php

namespace App\Shared\Mail;

use App\Modules\Core\Models\Usuario;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrialVencidoMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Usuario $usuario) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Tu prueba gratuita ha vencido - OperaAI');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.trial-vencido', with: [
            'nombre'  => $this->usuario->nombre,
            'planUrl' => config('app.frontend_url') . '/configuracion/plan',
        ]);
    }
}
