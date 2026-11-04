<x-mail::message>
# Hola, {{ $nombre }}

Recibimos una solicitud para recuperar el acceso a tu cuenta, así que te asignamos una contraseña nueva:

<x-mail::panel>
<span class="dato-destacado">{{ $password }}</span>
</x-mail::panel>

Tu contraseña anterior ya no funciona y se cerraron las sesiones que tenías abiertas.

<x-mail::button :url="$urlLogin">
Iniciar sesión
</x-mail::button>

Al entrar, cámbiala por una que puedas recordar desde **Mi cuenta → Inicio de sesión y seguridad**.

**¿No fuiste tú?** Entra con esta contraseña y cámbiala para proteger tu cuenta.

Saludos,<br>
El equipo de {{ config('app.name') }}

<x-slot:subcopy>
Si el botón "Iniciar sesión" no funciona, copia y pega este enlace en tu navegador: <span class="break-all">[{{ $urlLogin }}]({{ $urlLogin }})</span>
</x-slot:subcopy>
</x-mail::message>
