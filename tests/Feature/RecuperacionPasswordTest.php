<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Notifications\PasswordTemporalNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;
use Tests\TestCase;

// Recuperacion con contrasena temporal: convive con la actual, vence a los 10 minutos
// y, si se usa, reemplaza a la anterior.
class RecuperacionPasswordTest extends TestCase
{
    use RefreshDatabase;

    private const SOLICITAR = '/api/v1/auth/forgot-password';

    private const LOGIN = '/api/v1/auth/login';

    private const RESTABLECER = '/api/v1/auth/password/restablecer';

    private Usuario $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        RateLimiter::clear('olvide-password|ana@correo.test');
        RateLimiter::clear('ana@correo.test|127.0.0.1');
        $this->usuario = Usuario::factory()->create(['correo' => 'ana@correo.test']); // contrasena: password123
    }

    // Pide una temporal y devuelve la que llego por correo
    private function pedirTemporal(): string
    {
        RateLimiter::clear('olvide-password|ana@correo.test');
        $this->postJson(self::SOLICITAR, ['correo' => 'ana@correo.test'])->assertOk();

        return Notification::sent($this->usuario, PasswordTemporalNotification::class)->last()->password;
    }

    private function entrar(string $password)
    {
        return $this->postJson(self::LOGIN, ['correo' => 'ana@correo.test', 'password' => $password]);
    }

    private function datosNuevos(array $cambios = []): array
    {
        return ['password' => 'nueva12345', 'password_confirmation' => 'nueva12345', ...$cambios];
    }

    public function test_la_respuesta_es_identica_exista_o_no_la_cuenta(): void
    {
        $conCuenta = $this->postJson(self::SOLICITAR, ['correo' => 'ana@correo.test'])->assertOk();
        $sinCuenta = $this->postJson(self::SOLICITAR, ['correo' => 'nadie@correo.test'])->assertOk();

        $this->assertSame($conCuenta->json(), $sinCuenta->json());
        Notification::assertSentTimes(PasswordTemporalNotification::class, 1);
    }

    public function test_pedirla_no_cambia_la_contrasena_ni_cierra_sesiones(): void
    {
        $this->usuario->createToken('web');

        $this->pedirTemporal();

        $this->assertTrue(Hash::check('password123', $this->usuario->fresh()->password));
        $this->assertSame(1, $this->usuario->tokens()->count());
    }

    public function test_la_temporal_se_guarda_hasheada_y_no_sale_en_el_json(): void
    {
        $temporal = $this->pedirTemporal();
        $guardada = $this->usuario->fresh();

        $this->assertNotSame($temporal, $guardada->password_temporal);
        $this->assertTrue(Hash::check($temporal, $guardada->password_temporal));
        $this->assertArrayNotHasKey('password_temporal', $guardada->toArray());
        $this->assertArrayNotHasKey('password_temporal_vence', $guardada->toArray());
    }

    public function test_solo_se_atiende_una_solicitud_por_minuto(): void
    {
        $this->postJson(self::SOLICITAR, ['correo' => 'ana@correo.test'])->assertOk();
        $this->postJson(self::SOLICITAR, ['correo' => 'ana@correo.test'])->assertOk();

        Notification::assertSentTimes(PasswordTemporalNotification::class, 1);
    }

    public function test_una_cuenta_bloqueada_no_recibe_temporal(): void
    {
        $this->usuario->update(['activo' => false]);

        $this->postJson(self::SOLICITAR, ['correo' => 'ana@correo.test'])->assertOk();

        Notification::assertNothingSent();
    }

    public function test_si_el_correo_falla_no_queda_temporal_y_la_respuesta_es_la_misma(): void
    {
        Notification::swap(new class
        {
            public function send(): void
            {
                throw new RuntimeException('SMTP caído');
            }

            public function __call($metodo, $argumentos)
            {
                throw new RuntimeException('SMTP caído');
            }
        });

        $this->postJson(self::SOLICITAR, ['correo' => 'ana@correo.test'])->assertOk();

        $this->assertNull($this->usuario->fresh()->password_temporal);
    }

    public function test_con_la_temporal_se_entra_y_reemplaza_a_la_anterior(): void
    {
        $temporal = $this->pedirTemporal();

        $this->entrar($temporal)->assertOk()->assertJsonStructure(['token', 'usuario' => ['cambio_password_hasta']]);

        $guardado = $this->usuario->fresh();
        $this->assertTrue(Hash::check($temporal, $guardado->password));
        $this->assertNull($guardado->password_temporal);
        $this->entrar('password123')->assertUnauthorized();
    }

    public function test_al_entrar_con_la_temporal_se_cierran_las_demas_sesiones(): void
    {
        $this->usuario->createToken('otro-dispositivo');

        $this->entrar($this->pedirTemporal())->assertOk();

        $this->assertSame(1, $this->usuario->tokens()->count());
    }

    public function test_la_temporal_vence_a_los_10_minutos_y_la_anterior_sigue_sirviendo(): void
    {
        $temporal = $this->pedirTemporal();

        $this->travel(10)->minutes();
        $this->travel(1)->seconds();

        $this->entrar($temporal)->assertUnauthorized();
        RateLimiter::clear('ana@correo.test|127.0.0.1');
        $this->entrar('password123')->assertOk();
    }

    public function test_la_temporal_sirve_justo_antes_de_vencer(): void
    {
        $temporal = $this->pedirTemporal();

        $this->travel(9)->minutes();

        $this->entrar($temporal)->assertOk();
    }

    public function test_si_se_pide_otra_solo_vale_la_mas_reciente(): void
    {
        $primera = $this->pedirTemporal();
        $segunda = $this->pedirTemporal();

        $this->entrar($primera)->assertUnauthorized();
        $this->entrar($segunda)->assertOk();
    }

    public function test_entrar_con_la_contrasena_anterior_anula_la_temporal(): void
    {
        $temporal = $this->pedirTemporal();

        $this->entrar('password123')->assertOk();

        $this->assertNull($this->usuario->fresh()->password_temporal);
        $this->entrar($temporal)->assertUnauthorized();
    }

    public function test_una_cuenta_bloqueada_no_entra_ni_reemplaza_con_la_temporal(): void
    {
        $temporal = $this->pedirTemporal();
        $this->usuario->update(['activo' => false]);

        $this->entrar($temporal)->assertForbidden();

        $this->assertTrue(Hash::check('password123', $this->usuario->fresh()->password));
    }

    public function test_despues_de_entrar_con_la_temporal_se_cambia_sin_la_actual(): void
    {
        $token = $this->entrar($this->pedirTemporal())->assertOk()->json('token');

        $this->withToken($token)->putJson(self::RESTABLECER, $this->datosNuevos())
            ->assertOk()
            ->assertJsonPath('usuario.cambio_password_hasta', null);

        $this->assertTrue(Hash::check('nueva12345', $this->usuario->fresh()->password));
        // La sesion actual se conserva
        $this->assertSame(1, $this->usuario->tokens()->count());
    }

    public function test_el_cambio_sin_la_actual_solo_se_puede_una_vez(): void
    {
        $token = $this->entrar($this->pedirTemporal())->json('token');
        $this->withToken($token)->putJson(self::RESTABLECER, $this->datosNuevos())->assertOk();

        $this->withToken($token)
            ->putJson(self::RESTABLECER, $this->datosNuevos(['password' => 'otra12345', 'password_confirmation' => 'otra12345']))
            ->assertForbidden();
    }

    public function test_el_cambio_sin_la_actual_vence_a_los_30_minutos(): void
    {
        $token = $this->entrar($this->pedirTemporal())->json('token');

        $this->travel(31)->minutes();

        $this->withToken($token)->putJson(self::RESTABLECER, $this->datosNuevos())->assertForbidden();
    }

    public function test_sin_haber_usado_una_temporal_no_se_puede_cambiar_sin_la_actual(): void
    {
        $token = $this->entrar('password123')->json('token');

        $this->withToken($token)->putJson(self::RESTABLECER, $this->datosNuevos())->assertForbidden();
        $this->assertTrue(Hash::check('password123', $this->usuario->fresh()->password));
    }

    public function test_sin_sesion_no_se_puede_restablecer(): void
    {
        $this->putJson(self::RESTABLECER, $this->datosNuevos())->assertUnauthorized();
    }

    public function test_el_cambio_sin_la_actual_exige_las_mismas_reglas(): void
    {
        $token = $this->entrar($this->pedirTemporal())->json('token');

        $this->withToken($token)
            ->putJson(self::RESTABLECER, $this->datosNuevos(['password' => 'corta', 'password_confirmation' => 'corta']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    public function test_cambiar_la_contrasena_desde_el_perfil_termina_el_cambio_directo(): void
    {
        $temporal = $this->pedirTemporal();
        $token = $this->entrar($temporal)->json('token');

        $this->withToken($token)->putJson('/api/v1/auth/password', [
            'password_actual' => $temporal,
            ...$this->datosNuevos(),
        ])->assertOk()->assertJsonPath('usuario.cambio_password_hasta', null);

        $this->withToken($token)
            ->putJson(self::RESTABLECER, $this->datosNuevos(['password' => 'otra12345', 'password_confirmation' => 'otra12345']))
            ->assertForbidden();
    }

    public function test_la_bitacora_registra_el_flujo_sin_guardar_la_contrasena(): void
    {
        $temporal = $this->pedirTemporal();
        $token = $this->entrar($temporal)->json('token');
        $this->withToken($token)->putJson(self::RESTABLECER, $this->datosNuevos())->assertOk();

        $this->assertCount(1, $this->bitacora->deEvento('password_temporal_solicitada'));
        $this->assertCount(1, $this->bitacora->deEvento('login_password_temporal'));
        $this->assertCount(1, $this->bitacora->deEvento('password_restablecida'));
        $this->assertStringNotContainsString($temporal, json_encode($this->bitacora->documentos));
    }
}
