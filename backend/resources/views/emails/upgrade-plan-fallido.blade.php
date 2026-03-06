@component('mail::message')
# Problema con tu pago

Hola **{{ $nombre }}**, no pudimos procesar el pago para actualizar tu plan después de 3 intentos.

Por favor intenta nuevamente con otra tarjeta.

@component('mail::button', ['url' => $configuracionUrl])
Intentar nuevamente
@endcomponent

Si el problema persiste, contáctanos.<br>
El equipo de OperaAI
@endcomponent
