<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// No va por la cola: la contrasena quedaria legible en la tabla jobs y, si el envio falla,
// AuthController necesita enterarse para revertirla
class PasswordTemporalNotification extends Notification
{
    public function __construct(public string $password, public int $minutos) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tu contraseña temporal')
            ->markdown('correos.password-temporal', [
                'nombre' => $notifiable->nombre,
                'password' => $this->password,
                'minutos' => $this->minutos,
                'urlLogin' => rtrim(config('app.frontend_url'), '/').'/login',
            ]);
    }
}
