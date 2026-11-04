<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// No va por la cola: la contrasena quedaria legible en la tabla jobs y, si el envio falla,
// AuthController necesita enterarse para revertir el cambio
class PasswordNuevaNotification extends Notification
{
    public function __construct(public string $password) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tu nueva contraseña')
            ->markdown('correos.password-nueva', [
                'nombre' => $notifiable->nombre,
                'password' => $this->password,
                'urlLogin' => rtrim(config('app.frontend_url'), '/').'/login',
            ]);
    }
}
