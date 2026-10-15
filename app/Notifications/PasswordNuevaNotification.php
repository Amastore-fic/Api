<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordNuevaNotification extends Notification
{
    //use Queueable;

    public function __construct(public string $password) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tu nueva contraseña')
            ->greeting('Hola '.$notifiable->nombre)
            ->line('Estás recibiendo este correo porque solicitaste restablecer la contraseña de tu cuenta.')
            ->line('Esta es tu nueva contraseña:')
            ->line('**'.$this->password.'**')
            ->line('Inicia sesión con ella y cámbiala desde tu perfil lo antes posible.')
            ->line('Si no solicitaste esto, contacta a la pagina.');
            
    }

}
