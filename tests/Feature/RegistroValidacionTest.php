<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegistroValidacionTest extends TestCase
{
    use RefreshDatabase;

    private function datosValidos(array $cambios = []): array
    {
        return array_merge([
            'nombre' => 'José María',
            'apellido' => 'Núñez',
            'correo' => 'jose@correo.com',
            'telefono' => '6671234567',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
        ], $cambios);
    }

    public function test_acepta_datos_validos_con_acentos_y_sin_telefono(): void
    {
        $this->postJson('/api/v1/auth/registro', $this->datosValidos(['telefono' => null]))
            ->assertCreated();
    }

    public static function datosInvalidos(): array
    {
        return [
            'nombre con numeros' => ['nombre', 'Ana123', 'El nombre solo puede tener letras y espacios.'],
            'apellido con simbolos' => ['apellido', 'Lopez!', 'El apellido solo puede tener letras y espacios.'],
            'nombre de una letra' => ['nombre', 'A', 'El campo nombre debe tener al menos 2 caracteres.'],
            'telefono con letras' => ['telefono', '66abc71234', 'El campo teléfono debe tener 10 dígitos.'],
            'telefono corto' => ['telefono', '6671234', 'El campo teléfono debe tener 10 dígitos.'],
            'correo sin dominio completo' => ['correo', 'ana@correo', 'Escribe un correo válido, por ejemplo nombre@dominio.com.'],
            'password corta' => ['password', 'abc1', 'El campo contraseña debe tener al menos 8 caracteres.'],
            'password sin numeros' => ['password', 'abcdefgh', 'El campo contraseña debe tener al menos un número.'],
        ];
    }

    #[DataProvider('datosInvalidos')]
    public function test_rechaza_datos_invalidos_con_mensaje_en_espanol(string $campo, string $valor, string $mensaje): void
    {
        $datos = $this->datosValidos([$campo => $valor]);
        if ($campo === 'password') {
            $datos['password_confirmation'] = $valor;
        }

        $this->postJson('/api/v1/auth/registro', $datos)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$campo => $mensaje]);
    }

    public function test_contrasenas_distintas(): void
    {
        $this->postJson('/api/v1/auth/registro', $this->datosValidos(['password_confirmation' => 'otra12345']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password' => 'Las contraseñas no coinciden.']);
    }

    public function test_correo_ya_registrado(): void
    {
        Usuario::factory()->create(['correo' => 'jose@correo.com']);

        $this->postJson('/api/v1/auth/registro', $this->datosValidos())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['correo' => 'Ya existe una cuenta con este correo.']);
    }

    public function test_campos_obligatorios(): void
    {
        $this->postJson('/api/v1/auth/registro', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'nombre' => 'El campo nombre es obligatorio.',
                'correo' => 'El campo correo es obligatorio.',
                'password' => 'El campo contraseña es obligatorio.',
            ]);
    }
}
