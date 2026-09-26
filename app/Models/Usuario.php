<?php

namespace App\Models;

use App\Enums\RolUsuario;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory;

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
}
