<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

// HU-04 / tarea #32: recuperacion de contrasena con token de restablecimiento.
// En pruebas los correos no se envian: quedan en memoria (MAIL_MAILER=array) y de
// ahi se saca el enlace, como lo haria el usuario desde su bandeja.
class RecuperacionPasswordTest extends TestCase
{
    use RefreshDatabase;

    private const SOLICITAR = '/api/v1/auth/forgot-password';

    private const RESTABLECER = '/api/v1/auth/reset-password';

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.frontend_url' => 'https://tienda.comprasegura.test']);
    }

    private function correosEnviados(): array
    {
        return app('mailer')->getSymfonyTransport()->messages()->all();
    }

    // Devuelve [url, token, correo] del enlace que llego en el ultimo correo
    private function enlaceDelCorreo(): array
    {
        $correos = $this->correosEnviados();
        $this->assertNotEmpty($correos, 'No se envió ningún correo');

        $html = end($correos)->getOriginalMessage()->getHtmlBody();
        $this->assertMatchesRegularExpression('#href="([^"]+/restablecer-password\?[^"]+)"#', $html);
        preg_match('#href="([^"]+/restablecer-password\?[^"]+)"#', $html, $coincidencia);

        $url = html_entity_decode($coincidencia[1]);
        parse_str(parse_url($url, PHP_URL_QUERY), $parametros);

        return [$url, $parametros['token'], $parametros['correo']];
    }

    private function restablecer(string $token, string $correo, string $password = 'NuevaClave2026')
    {
        return $this->postJson(self::RESTABLECER, [
            'token' => $token,
            'correo' => $correo,
            'password' => $password,
            'password_confirmation' => $password,
        ]);
    }

    public function test_la_respuesta_es_identica_exista_o_no_la_cuenta_y_nunca_trae_el_enlace(): void
    {
        Usuario::factory()->create(['correo' => 'ana@correo.test']);

        $existente = $this->postJson(self::SOLICITAR, ['correo' => 'ana@correo.test'])->assertOk();
        $inexistente = $this->postJson(self::SOLICITAR, ['correo' => 'nadie@correo.test'])->assertOk();

        $this->assertSame($existente->json(), $inexistente->json());
        $this->assertSame(['message'], array_keys($existente->json()));

        [, $token] = $this->enlaceDelCorreo();
        $this->assertStringNotContainsString($token, $existente->getContent());
        $this->assertCount(1, $this->correosEnviados(), 'Solo se envía correo a la cuenta que existe');
    }

    public function test_el_correo_enlaza_a_la_tienda_configurada_y_dice_cuanto_dura(): void
    {
        Usuario::factory()->create(['correo' => 'ana@correo.test']);

        $this->postJson(self::SOLICITAR, ['correo' => 'ANA@correo.test'])->assertOk();

        [$url, , $correo] = $this->enlaceDelCorreo();
        $this->assertStringStartsWith('https://tienda.comprasegura.test/restablecer-password?token=', $url);
        $this->assertSame('ana@correo.test', $correo);

        $correos = $this->correosEnviados();
        $html = end($correos)->getOriginalMessage()->getHtmlBody();
        $this->assertStringContainsString('vence en 15 minutos', $html);
    }

    public function test_restablece_la_contrasena_y_se_puede_entrar_con_la_nueva(): void
    {
        Usuario::factory()->create(['correo' => 'ana@correo.test']);
        $this->postJson(self::SOLICITAR, ['correo' => 'ana@correo.test']);
        [, $token, $correo] = $this->enlaceDelCorreo();

        $this->restablecer($token, $correo)->assertOk();

        $this->postJson('/api/v1/auth/login', ['correo' => 'ana@correo.test', 'password' => 'NuevaClave2026'])->assertOk();
        $this->postJson('/api/v1/auth/login', ['correo' => 'ana@correo.test', 'password' => 'password123'])->assertUnauthorized();
    }

    public function test_al_restablecer_se_cierran_todas_las_sesiones_abiertas(): void
    {
        $usuario = Usuario::factory()->create(['correo' => 'ana@correo.test']);
        $sesionAnterior = $usuario->createToken('web')->plainTextToken;

        $this->postJson(self::SOLICITAR, ['correo' => 'ana@correo.test']);
        [, $token, $correo] = $this->enlaceDelCorreo();
        $this->restablecer($token, $correo)->assertOk();

        $this->app['auth']->forgetGuards();
        $this->withToken($sesionAnterior)->getJson('/api/v1/auth/perfil')->assertUnauthorized();
    }

    public function test_el_enlace_vence_a_los_15_minutos(): void
    {
        Usuario::factory()->create(['correo' => 'ana@correo.test']);
        $this->postJson(self::SOLICITAR, ['correo' => 'ana@correo.test']);
        [, $token, $correo] = $this->enlaceDelCorreo();

        $this->travel(16)->minutes();

        $this->restablecer($token, $correo)->assertUnprocessable();
    }

    public function test_el_enlace_sirve_antes_de_vencer(): void
    {
        Usuario::factory()->create(['correo' => 'ana@correo.test']);
        $this->postJson(self::SOLICITAR, ['correo' => 'ana@correo.test']);
        [, $token, $correo] = $this->enlaceDelCorreo();

        $this->travel(14)->minutes();

        $this->restablecer($token, $correo)->assertOk();
    }

    public function test_el_enlace_solo_funciona_una_vez(): void
    {
        Usuario::factory()->create(['correo' => 'ana@correo.test']);
        $this->postJson(self::SOLICITAR, ['correo' => 'ana@correo.test']);
        [, $token, $correo] = $this->enlaceDelCorreo();

        $this->restablecer($token, $correo)->assertOk();
        $this->restablecer($token, $correo, 'OtraClave2026')->assertUnprocessable();
    }

    public function test_un_token_inventado_no_sirve(): void
    {
        Usuario::factory()->create(['correo' => 'ana@correo.test']);

        $this->restablecer('token-inventado', 'ana@correo.test')->assertUnprocessable();
    }

    public function test_la_nueva_contrasena_cumple_las_mismas_reglas_que_el_registro(): void
    {
        Usuario::factory()->create(['correo' => 'ana@correo.test']);
        $this->postJson(self::SOLICITAR, ['correo' => 'ana@correo.test']);
        [, $token, $correo] = $this->enlaceDelCorreo();

        $this->restablecer($token, $correo, 'sololetras')
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('password');
    }

    public function test_el_token_y_el_enlace_nunca_se_escriben_en_el_log(): void
    {
        $registros = [];
        Log::listen(function (MessageLogged $evento) use (&$registros) {
            $registros[] = $evento->message.' '.json_encode($evento->context);
        });

        Usuario::factory()->create(['correo' => 'ana@correo.test']);
        $this->postJson(self::SOLICITAR, ['correo' => 'ana@correo.test']);
        [$url, $token] = $this->enlaceDelCorreo();

        $todo = implode("\n", $registros);
        $this->assertStringNotContainsString($token, $todo);
        $this->assertStringNotContainsString($url, $todo);
    }
}
