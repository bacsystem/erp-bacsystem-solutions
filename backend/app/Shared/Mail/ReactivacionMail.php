<?php

namespace App\Shared\Mail;

use App\Modules\Core\Models\Empresa;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReactivacionMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Empresa $empresa)
    {
    }

    public function build(): static
    {
        return $this->subject('Tu cuenta ha sido reactivada')
            ->view('emails.reactivacion');
    }
}
