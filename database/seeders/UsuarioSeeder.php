<?php

namespace Database\Seeders;

use App\Enums\RolUsuario;
use App\Models\Usuario;
use App\Models\Vendedor;
use Illuminate\Database\Seeder;

// Usuarios de prueba (solo desarrollo). Contrasena de todos: password123
class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        Usuario::create([
            'nombre' => 'Admin',
            'apellido' => 'Sistema',
            'correo' => 'admin@comprasegura.test',
            'password' => 'password123',
            'rol' => RolUsuario::Administrador,
        ]);

        $vendedores = [
            ['nombre' => 'Laura', 'apellido' => 'Medina', 'correo' => 'vendedor1@comprasegura.test', 'negocio' => 'TecnoMedina', 'codigo' => 'VND-0001'],
            ['nombre' => 'Jorge', 'apellido' => 'Salas', 'correo' => 'vendedor2@comprasegura.test', 'negocio' => 'Casa Salas', 'codigo' => 'VND-0002'],
        ];

        foreach ($vendedores as $datos) {
            $usuario = Usuario::create([
                'nombre' => $datos['nombre'],
                'apellido' => $datos['apellido'],
                'correo' => $datos['correo'],
                'password' => 'password123',
                'rol' => RolUsuario::Vendedor,
            ]);

            Vendedor::create([
                'id_usuario' => $usuario->id,
                'nombre_comercial' => $datos['negocio'],
                'codigo_vendedor' => $datos['codigo'],
                'sucursal' => 'Culiacan',
            ]);
        }

        Usuario::create([
            'nombre' => 'Cliente',
            'apellido' => 'Prueba',
            'correo' => 'cliente@comprasegura.test',
            'password' => 'password123',
            'rol' => RolUsuario::Cliente,
        ]);
    }
}
