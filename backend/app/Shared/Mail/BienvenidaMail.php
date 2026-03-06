<?php

namespace App\Shared\Mail;

use App\Modules\Core\Models\Empresa;
use App\Modules\Core\Models\Plan;
use App\Modules\Core\Models\Usuario;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BienvenidaMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Usuario $usuario,
        public Empresa $empresa,
        public Plan $plan,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Bienvenido a OperaAI 🎉');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.bienvenida', with: [
            'nombre'        => $this->usuario->nombre,
            'empresa'       => $this->empresa->nombre_comercial,
            'plan'          => $this->plan->nombre_display,
            'vencimiento'   => now()->addDays(30)->format('d/m/Y'),
            'dashboardUrl'  => config('app.frontend_url') . '/dashboard',
        ]);
    }
}
