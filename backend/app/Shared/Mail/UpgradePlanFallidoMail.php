<?php

namespace App\Shared\Mail;

use App\Modules\Core\Models\Usuario;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UpgradePlanFallidoMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Usuario $usuario) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Problema con tu pago - OperaAI');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.upgrade-plan-fallido', with: [
            'nombre'      => $this->usuario->nombre,
            'configuracionUrl' => config('app.frontend_url') . '/configuracion/plan',
        ]);
    }
}
