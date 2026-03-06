<?php

namespace App\Shared\Mail;

use App\Modules\Core\Models\Usuario;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecuperarPasswordMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Usuario $usuario,
        public string $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Recupera tu contraseña - OperaAI');
    }

    public function content(): Content
    {
        $link = config('app.frontend_url')
            . '/reset-password?token=' . $this->token
            . '&email=' . urlencode($this->usuario->email);

        return new Content(markdown: 'emails.recuperar-password', with: [
            'nombre'  => $this->usuario->nombre,
            'link'    => $link,
        ]);
    }
}
