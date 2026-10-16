<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

// PUT /auth/password: cambio de contrasena con la sesion iniciada
class CambiarPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function datos(array $cambios = []): array
    {
        return [
            'password_actual' => 'password123',
            'password' => 'nueva12345',
            'password_confirmation' => 'nueva12345',
            ...$cambios,
        ];
    }

    public function test_sin_token_no_se_cambia(): void
    {
        $this->putJson('/api/v1/auth/password', $this->datos())->assertUnauthorized();
    }

    public function test_cambia_la_contrasena_y_cierra_las_demas_sesiones(): void
    {
        $usuario = Usuario::factory()->create(); // contrasena: password123
        $actual = $usuario->createToken('web')->plainTextToken;
        $usuario->createToken('android');

        $this->withToken($actual)->putJson('/api/v1/auth/password', $this->datos())
            ->assertOk()
            ->assertJsonPath('message', 'Contraseña actualizada.');

        $this->assertTrue(Hash::check('nueva12345', $usuario->fresh()->password));

        // Solo queda el token con el que se hizo el cambio
        $this->assertSame(['web'], $usuario->tokens()->pluck('name')->all());

        $this->app['auth']->forgetGuards();
        $this->withToken($actual)->getJson('/api/v1/auth/perfil')->assertOk();
    }

    public function test_funciona_para_vendedor_y_administrador(): void
    {
        foreach ([Usuario::factory()->vendedor()->create(), Usuario::factory()->administrador()->create()] as $usuario) {
            $this->app['auth']->forgetGuards();
            $token = $usuario->createToken('web')->plainTextToken;

            $this->withToken($token)->putJson('/api/v1/auth/password', $this->datos())->assertOk();
        }
    }

    public function test_rechaza_una_contrasena_actual_incorrecta(): void
    {
        $usuario = Usuario::factory()->create();
        $token = $usuario->createToken('web')->plainTextToken;

        $this->withToken($token)->putJson('/api/v1/auth/password', $this->datos(['password_actual' => 'otra12345']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password_actual' => 'La contraseña actual no es correcta.'])
            ->assertJsonMissingValidationErrors('password');

        $this->assertTrue(Hash::check('password123', $usuario->fresh()->password));
    }

    public function test_la_nueva_debe_ser_distinta_de_la_actual(): void
    {
        $token = Usuario::factory()->create()->createToken('web')->plainTextToken;

        $this->withToken($token)->putJson('/api/v1/auth/password', $this->datos([
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password' => 'La contraseña nueva debe ser distinta de la actual.']);
    }

    public function test_valida_confirmacion_y_reglas_de_la_nueva(): void
    {
        $token = Usuario::factory()->create()->createToken('web')->plainTextToken;

        $this->withToken($token)->putJson('/api/v1/auth/password', $this->datos(['password_confirmation' => 'otra12345']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password' => 'Las contraseñas no coinciden.']);

        $this->withToken($token)->putJson('/api/v1/auth/password', $this->datos([
            'password' => 'soloLetras',
            'password_confirmation' => 'soloLetras',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password' => 'La contraseña debe incluir al menos un número.']);
    }
}
