<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\BitacoraService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;
use Tests\TestCase;

// Tarea #55: cada intento de inicio de sesion queda en la bitacora (MongoDB).
// Aqui la bitacora es la de memoria (ver Tests\TestCase), no la MongoDB real.
class BitacoraLoginTest extends TestCase
{
    use RefreshDatabase;

    private const RUTA = '/api/v1/auth/login';

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('ana@correo.test|127.0.0.1');
    }

    private function intentar(string $password, string $correo = 'ana@correo.test')
    {
        return $this->postJson(self::RUTA, ['correo' => $correo, 'password' => $password, 'dispositivo' => 'web-tienda']);
    }

    public function test_un_login_exitoso_queda_registrado_con_el_usuario_y_su_rol(): void
    {
        $usuario = Usuario::factory()->create(['correo' => 'ana@correo.test']);

        $this->intentar('password123')->assertOk();

        $registro = $this->bitacora->deEvento('login_exitoso')[0];
        $this->assertSame($usuario->id, $registro['id_usuario']);
        $this->assertSame('cliente', $registro['rol']);
        $this->assertSame('web-tienda', $registro['dispositivo']);
        $this->assertSame('127.0.0.1', $registro['ip']);
        $this->assertArrayHasKey('fecha', $registro);
    }

    public function test_nunca_se_guarda_la_contrasena_el_token_ni_el_correo_en_claro(): void
    {
        Usuario::factory()->create(['correo' => 'ana@correo.test']);

        $token = $this->intentar('password123')->json('token');
        $this->intentar('otra-contrasena-1');

        $todo = json_encode($this->bitacora->documentos);
        $this->assertStringNotContainsString('password123', $todo);
        $this->assertStringNotContainsString('otra-contrasena-1', $todo);
        $this->assertStringNotContainsString($token, $todo);
        $this->assertStringNotContainsString('ana@correo.test', $todo);

        // En su lugar va el hash del correo, igual en todos los eventos de esa cuenta
        $this->assertSame(hash('sha256', 'ana@correo.test'), $this->bitacora->documentos[0]['correo_hash']);
        $this->assertSame($this->bitacora->documentos[0]['correo_hash'], $this->bitacora->documentos[1]['correo_hash']);
    }

    public function test_una_contrasena_equivocada_queda_como_login_fallido(): void
    {
        $usuario = Usuario::factory()->create(['correo' => 'ana@correo.test']);

        $this->intentar('equivocada1')->assertUnauthorized();

        $registro = $this->bitacora->deEvento('login_fallido')[0];
        $this->assertSame($usuario->id, $registro['id_usuario']);
    }

    public function test_un_correo_inexistente_queda_como_login_fallido_sin_usuario(): void
    {
        $this->intentar('password123', 'nadie@correo.test')->assertUnauthorized();

        $registro = $this->bitacora->deEvento('login_fallido')[0];
        $this->assertArrayNotHasKey('id_usuario', $registro);
        $this->assertSame(hash('sha256', 'nadie@correo.test'), $registro['correo_hash']);
    }

    public function test_el_bloqueo_por_intentos_queda_registrado(): void
    {
        Usuario::factory()->create(['correo' => 'ana@correo.test']);

        for ($i = 0; $i < 5; $i++) {
            $this->intentar('equivocada1')->assertUnauthorized();
        }
        $this->intentar('password123')->assertStatus(429);

        $this->assertCount(5, $this->bitacora->deEvento('login_fallido'));
        $bloqueo = $this->bitacora->deEvento('login_bloqueado')[0];
        $this->assertArrayHasKey('segundos_restantes', $bloqueo['detalles']);
        $this->assertSame([], $this->bitacora->deEvento('login_exitoso'));
    }

    public function test_una_cuenta_inactiva_queda_registrada(): void
    {
        $usuario = Usuario::factory()->bloqueado()->create(['correo' => 'ana@correo.test']);

        $this->intentar('password123')->assertForbidden();

        $this->assertSame($usuario->id, $this->bitacora->deEvento('login_cuenta_inactiva')[0]['id_usuario']);
    }

    public function test_cerrar_sesion_queda_registrado(): void
    {
        $usuario = Usuario::factory()->create(['correo' => 'ana@correo.test']);
        $token = $this->intentar('password123')->json('token');

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();

        $this->assertSame($usuario->id, $this->bitacora->deEvento('logout')[0]['id_usuario']);
    }

    public function test_si_mongodb_falla_el_login_funciona_igual(): void
    {
        Usuario::factory()->create(['correo' => 'ana@correo.test']);

        // Bitacora cuya escritura siempre falla, como si MongoDB estuviera caido
        $this->app->instance(BitacoraService::class, new class extends BitacoraService
        {
            protected function guardar(array $documento): void
            {
                throw new RuntimeException('MongoDB no responde');
            }
        });

        $this->intentar('password123')->assertOk()->assertJsonStructure(['token']);
    }
}
