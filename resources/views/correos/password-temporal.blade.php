<x-mail::message>
# Hola, {{ $nombre }}

Recibimos una solicitud para recuperar el acceso a tu cuenta. Esta es tu contraseña temporal:

<x-mail::panel>
<span class="dato-destacado">{{ $password }}</span>
</x-mail::panel>

**Vence en {{ $minutos }} minutos.** Tu contraseña actual sigue funcionando hasta que uses esta.

<x-mail::button :url="$urlLogin">
Iniciar sesión
</x-mail::button>

Si entras con ella, reemplazará a tu contraseña anterior y se cerrarán las sesiones abiertas en otros dispositivos. Al entrar podrás cambiarla por una que recuerdes.

**¿No fuiste tú?** Ignora este correo: tu contraseña no ha cambiado y esta dejará de funcionar sola.

Saludos,<br>
El equipo de {{ config('app.name') }}

<x-slot:subcopy>
Si el botón "Iniciar sesión" no funciona, copia y pega este enlace en tu navegador: <span class="break-all">[{{ $urlLogin }}]({{ $urlLogin }})</span>
</x-slot:subcopy>
</x-mail::message>
