<?php

namespace App\Models;

use App\Enums\RolUsuario;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable implements CanResetPassword
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usuarios';

    protected $fillable = [
        'nombre',
        'apellido',
        'correo',
        'password',
        'telefono',
        'rol',
        'activo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'activo' => 'boolean',
            'rol' => RolUsuario::class,
        ];
    }

    public function tieneRol(RolUsuario ...$roles): bool
    {
        return in_array($this->rol, $roles, true);
    }

    public function vendedor(): HasOne
    {
        return $this->hasOne(Vendedor::class, 'id_usuario');
    }

    public function carrito(): HasOne
    {
        return $this->hasOne(Carrito::class, 'id_usuario');
    }

    public function direcciones(): HasMany
    {
        return $this->hasMany(Direccion::class, 'id_usuario');
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class, 'id_cliente');
    }

    public function calificaciones(): HasMany
    {
        return $this->hasMany(Calificacion::class, 'id_cliente');
    }

    public function getEmailForPasswordReset()
    {
        return $this->correo;
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new class($token) extends ResetPasswordNotification {
            public $token;

            public function __construct($token)
            {
                $this->token = $token;
            }

            public function toMail($notifiable)
            {
                $url = "http://localhost:5173/restablecer-password?token={$this->token}&correo=" . urlencode($notifiable->correo);

                return (new MailMessage)
                    ->subject('Restablecer contraseña')
                    ->line('Estás recibiendo este correo porque solicitaste un restablecimiento de contraseña para tu cuenta.')
                    ->action('Restablecer contraseña', $url)
                    ->line('Si no solicitaste esto, puedes ignorar este mensaje.');
            }
        });
    }

    public function findForPasswordReset($credentials)
    {
        return $this->where('correo', $credentials['email'])->first();
    }
}