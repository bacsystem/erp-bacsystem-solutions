@component('mail::message')
# Recupera tu contraseña

Hola **{{ $nombre }}**, recibimos una solicitud para recuperar la contraseña de tu cuenta en OperaAI.

Este link es válido por **60 minutos**.

@component('mail::button', ['url' => $link])
Recuperar contraseña
@endcomponent

Si no solicitaste este cambio, ignora este correo.

Gracias,<br>
El equipo de OperaAI
@endcomponent
