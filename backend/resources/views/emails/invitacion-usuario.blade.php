<x-mail::message>
# Te invitaron a unirte a {{ $empresa }}

Has sido invitado a colaborar en **{{ $empresa }}** en OperaAI como **{{ $rol }}**.

Para activar tu cuenta y crear tu contraseña, haz clic en el botón:

<x-mail::button :url="$activarUrl">
Activar mi cuenta
</x-mail::button>

Este enlace es válido hasta el **{{ $expiresAt }}** (48 horas).

Si no esperabas esta invitación, puedes ignorar este email.

Saludos,<br>
El equipo de OperaAI
</x-mail::message>
