<x-mail::message>
# Tu prueba gratuita vence pronto

Hola **{{ $nombre }}**,

Tu período de prueba gratuita en OperaAI vence en **{{ $diasRestantes }} {{ $diasRestantes === 1 ? 'día' : 'días' }}**.

Para continuar usando todos los módulos sin interrupciones, activa tu plan ahora:

<x-mail::button :url="$planUrl">
Ver planes y precios
</x-mail::button>

Si no activas un plan, tu cuenta quedará en modo restringido al vencer el trial.

Saludos,<br>
El equipo de OperaAI
</x-mail::message>
