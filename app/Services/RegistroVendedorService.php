<?php

namespace App\Services;

use App\Enums\RolUsuario;
use App\Models\Usuario;
use App\Models\Vendedor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegistroVendedorService
{
    // Crea la cuenta (rol vendedor) y su perfil de negocio en una sola transaccion:
    // si algo falla, no queda un usuario vendedor sin perfil ni un perfil sin usuario.
    public function registrar(array $datos): Usuario
    {
        return DB::transaction(function () use ($datos) {
            $usuario = Usuario::create([
                'nombre' => $datos['nombre'],
                'apellido' => $datos['apellido'],
                'telefono' => $datos['telefono'] ?? null,
                'correo' => $datos['correo'],
                'password' => $datos['password'], // el cast 'hashed' aplica bcrypt
                'rol' => RolUsuario::Vendedor,
            ]);

            $vendedor = Vendedor::create([
                'id_usuario' => $usuario->id,
                'nombre_comercial' => $datos['nombre_comercial'],
                // Temporal: el codigo definitivo depende del id, que aun no existe
                'codigo_vendedor' => 'TMP-'.Str::uuid(),
                'sucursal' => $datos['sucursal'] ?? null,
                'descripcion' => $datos['descripcion'] ?? null,
            ]);

            $vendedor->update(['codigo_vendedor' => self::codigoPara($vendedor->id)]);

            return $usuario->load('vendedor');
        });
    }

    // Identificador publico y trazable del vendedor: VND-000123.
    // Se basa en el id, asi nunca se repite.
    public static function codigoPara(int $idVendedor): string
    {
        return 'VND-'.str_pad((string) $idVendedor, 6, '0', STR_PAD_LEFT);
    }
}
