<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_cliente_puede_registrarse(): void
    {
        $this->postJson('/api/v1/auth/registro', [
            'nombre' => 'Ana',
            'apellido' => 'Lopez',
            'correo' => 'ana@correo.test',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
        ])->assertCreated()->assertJsonPath('usuario.rol', 'cliente');

        $this->assertDatabaseHas('usuarios', ['correo' => 'ana@correo.test']);
    }

    public function test_un_vendedor_se_registra_con_sus_datos_de_negocio(): void
    {
        $this->postJson('/api/v1/auth/registro-vendedor', [
            'nombre' => 'Luis',
            'apellido' => 'Perez',
            'correo' => 'luis@correo.test',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
            'nombre_comercial' => 'Tienda Luis',
        ])->assertCreated()->assertJsonPath('usuario.vendedor.nombre_comercial', 'Tienda Luis');
    }

    public function test_login_devuelve_token_y_perfil_lo_acepta(): void
    {
        Usuario::factory()->create(['correo' => 'ana@correo.test']);

        $token = $this->postJson('/api/v1/auth/login', [
            'correo' => 'ana@correo.test',
            'password' => 'password123',
        ])->assertOk()->json('token');

        $this->withToken($token)->getJson('/api/v1/auth/perfil')
            ->assertOk()
            ->assertJsonPath('correo', 'ana@correo.test');
    }

    public function test_login_rechaza_credenciales_incorrectas(): void
    {
        Usuario::factory()->create(['correo' => 'ana@correo.test']);

        $this->postJson('/api/v1/auth/login', [
            'correo' => 'ana@correo.test',
            'password' => 'otra-cosa',
        ])->assertUnauthorized();
    }

    public function test_una_cuenta_bloqueada_no_puede_iniciar_sesion(): void
    {
        Usuario::factory()->bloqueado()->create(['correo' => 'ana@correo.test']);

        $this->postJson('/api/v1/auth/login', [
            'correo' => 'ana@correo.test',
            'password' => 'password123',
        ])->assertForbidden();
    }
}
