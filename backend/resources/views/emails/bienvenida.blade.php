@component('mail::message')
# ¡Bienvenido a OperaAI, {{ $nombre }}! 🎉

Tu empresa **{{ $empresa }}** ya está registrada en el plan **{{ $plan }}**.

Tu período de prueba gratuito estará disponible hasta el **{{ $vencimiento }}**.

@component('mail::button', ['url' => $dashboardUrl])
Ir al Dashboard
@endcomponent

Gracias por elegir OperaAI,<br>
El equipo de OperaAI
@endcomponent
