<?php

namespace App\Enums;

// Eventos que se registran en la bitacora (coleccion "bitacora" de MongoDB)
enum EventoBitacora: string
{
    case LoginExitoso = 'login_exitoso';
    case LoginFallido = 'login_fallido';             // correo o contrasena incorrectos
    case LoginBloqueado = 'login_bloqueado';         // demasiados intentos fallidos seguidos
    case LoginCuentaInactiva = 'login_cuenta_inactiva'; // la cuenta la bloqueo un administrador
    case Logout = 'logout';
}
