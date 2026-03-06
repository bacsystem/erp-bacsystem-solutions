<x-mail::message>
# Tu prueba gratuita ha vencido

Hola **{{ $nombre }}**,

Tu período de prueba gratuita en OperaAI ha finalizado.

Tu cuenta está actualmente en modo restringido. Para volver a tener acceso completo a todos tus módulos, activa un plan:

<x-mail::button :url="$planUrl">
Activar mi plan
</x-mail::button>

Si tienes preguntas, responde a este email y te ayudamos.

Saludos,<br>
El equipo de OperaAI
</x-mail::message>
