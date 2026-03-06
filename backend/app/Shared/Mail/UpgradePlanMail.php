<?php

namespace App\Shared\Mail;

use App\Modules\Core\Models\Plan;
use App\Modules\Core\Models\Usuario;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UpgradePlanMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Usuario $usuario,
        public Plan $plan,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Plan actualizado ✅ - OperaAI');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.upgrade-plan', with: [
            'nombre'       => $this->usuario->nombre,
            'plan'         => $this->plan->nombre_display,
            'dashboardUrl' => config('app.frontend_url') . '/dashboard',
        ]);
    }
}
