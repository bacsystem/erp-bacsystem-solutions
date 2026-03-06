@component('mail::message')
# ¡Plan actualizado! ✅

Hola **{{ $nombre }}**, tu plan ha sido actualizado a **{{ $plan }}** exitosamente.

@component('mail::button', ['url' => $dashboardUrl])
Ir al Dashboard
@endcomponent

Gracias por confiar en OperaAI,<br>
El equipo de OperaAI
@endcomponent
