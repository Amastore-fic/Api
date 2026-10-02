<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

// Verifica que el registro rechace datos invalidos y que los mensajes lleguen
// traducidos. No se compara el texto exacto: cada mensaje puede ajustarse en
// lang/es/validation.php o en messages() del FormRequest sin romper estas pruebas.
class RegistroValidacionTest extends TestCase
{
    use RefreshDatabase;

    private function datosValidos(array $cambios = []): array
    {
        return array_merge([
            'nombre' => 'José María',
            'apellido' => 'Núñez',
            'correo' => 'jose@correo.com',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
        ], $cambios);
    }

    // El campo trae un error y su mensaje esta en espanol (no una clave como "validation.min.string")
    private function assertErrorTraducido(TestResponse $respuesta, string $campo): void
    {
        $respuesta->assertUnprocessable()->assertJsonValidationErrorFor($campo);

        $mensaje = $respuesta->json("errors.$campo.0");
        $this->assertStringNotContainsString('validation.', $mensaje, "Mensaje sin traducir en $campo");
    }

    public function test_acepta_datos_validos_con_acentos(): void
    {
        $this->postJson('/api/v1/auth/registro', $this->datosValidos())->assertCreated();
    }

    public static function datosInvalidos(): array
    {
        return [
            'correo sin arroba' => ['correo', 'correo-invalido'],
            'password corta' => ['password', 'abc1'],
            'password sin numeros' => ['password', 'abcdefgh'],
            'password sin letras' => ['password', '12345678'],
        ];
    }

    #[DataProvider('datosInvalidos')]
    public function test_rechaza_datos_invalidos_con_mensaje_traducido(string $campo, string $valor): void
    {
        $datos = $this->datosValidos([$campo => $valor]);
        if ($campo === 'password') {
            $datos['password_confirmation'] = $valor;
        }

        $this->assertErrorTraducido($this->postJson('/api/v1/auth/registro', $datos), $campo);
    }

    public function test_contrasenas_distintas(): void
    {
        $respuesta = $this->postJson('/api/v1/auth/registro', $this->datosValidos([
            'password_confirmation' => 'otra12345',
        ]));

        $this->assertErrorTraducido($respuesta, 'password');
    }

    public function test_correo_ya_registrado(): void
    {
        Usuario::factory()->create(['correo' => 'jose@correo.com']);

        $this->assertErrorTraducido(
            $this->postJson('/api/v1/auth/registro', $this->datosValidos()),
            'correo'
        );
    }

    public function test_campos_obligatorios(): void
    {
        $respuesta = $this->postJson('/api/v1/auth/registro', []);

        foreach (['nombre', 'apellido', 'correo', 'password'] as $campo) {
            $this->assertErrorTraducido($respuesta, $campo);
        }
    }
}
