<?php

namespace App\Shared\Mail;

use App\Modules\Core\Models\Usuario;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrialVencimientoMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Usuario $usuario,
        public int $diasRestantes
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Tu prueba gratuita vence en {$this->diasRestantes} días - OperaAI");
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.trial-vencimiento', with: [
            'nombre'        => $this->usuario->nombre,
            'diasRestantes' => $this->diasRestantes,
            'planUrl'       => config('app.frontend_url') . '/configuracion/plan',
        ]);
    }
}
