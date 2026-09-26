<?php

namespace Database\Factories;

use App\Enums\RolUsuario;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Usuario>
 */
class UsuarioFactory extends Factory
{
    protected $model = Usuario::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->firstName(),
            'apellido' => fake()->lastName(),
            'correo' => fake()->unique()->safeEmail(),
            'password' => 'password123',
            'telefono' => fake()->numerify('667#######'),
            'rol' => RolUsuario::Cliente,
            'activo' => true,
        ];
    }

    public function vendedor(): static
    {
        return $this->state(fn () => ['rol' => RolUsuario::Vendedor]);
    }

    public function administrador(): static
    {
        return $this->state(fn () => ['rol' => RolUsuario::Administrador]);
    }

    public function bloqueado(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}
