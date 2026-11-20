<?php

namespace App\Models;

use App\Enums\RolUsuario;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
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
        'password_temporal',
        'password_temporal_vence',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'activo' => 'boolean',
            'password_temporal_vence' => 'datetime',
            'cambio_password_hasta' => 'datetime',
            'rol' => RolUsuario::class,
        ];
    }

    // El superadmin cuenta como administrador en cualquier ruta o regla que lo pida
    public function tieneRol(RolUsuario ...$roles): bool
    {
        if ($this->rol === RolUsuario::Superadmin && in_array(RolUsuario::Administrador, $roles, true)) {
            return true;
        }

        return in_array($this->rol, $roles, true);
    }

    // Contrasena que llego por correo: sirve para entrar solo mientras no venza
    public function passwordTemporalValida(string $password): bool
    {
        return $this->password_temporal
            && $this->password_temporal_vence?->isFuture()
            && Hash::check($password, $this->password_temporal);
    }

    public function puedeCambiarPasswordSinLaActual(): bool
    {
        return (bool) $this->cambio_password_hasta?->isFuture();
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

    // Direccion a la que se envian los correos (Laravel busca "email" y aqui es "correo").
    // Sin esto el correo de recuperacion nunca se enviaba: la direccion quedaba vacia.
    public function routeNotificationForMail(): string
    {
        return $this->correo;
    }
}
