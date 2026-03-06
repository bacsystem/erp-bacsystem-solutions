<?php

namespace App\Shared\Mail;

use App\Modules\Core\Models\Empresa;
use App\Modules\Core\Models\InvitacionUsuario;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitacionUsuarioMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public InvitacionUsuario $invitacion,
        public Empresa $empresa
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Te invitaron a unirte a {$this->empresa->razon_social} en OperaAI");
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.invitacion-usuario', with: [
            'empresa'      => $this->empresa->razon_social,
            'rol'          => $this->invitacion->rol,
            'activarUrl'   => config('app.frontend_url') . '/activar?token=' . $this->invitacion->token,
            'expiresAt'    => $this->invitacion->expires_at->format('d/m/Y H:i'),
        ]);
    }
}
