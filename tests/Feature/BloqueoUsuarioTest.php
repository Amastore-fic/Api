<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BloqueoUsuarioTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Usuario
    {
        return Usuario::factory()->create(['rol' => RolUsuario::Administrador]);
    }

    private function estado(int $id, bool $activo)
    {
        return $this->patchJson("/api/v1/admin/usuarios/{$id}/estado", ['activo' => $activo]);
    }

    public function test_el_administrador_bloquea_a_un_cliente(): void
    {
        $cliente = Usuario::factory()->create(['rol' => RolUsuario::Cliente]);
        Sanctum::actingAs($this->admin());

        $this->estado($cliente->id, false)->assertOk();

        $this->assertFalse($cliente->fresh()->activo);
    }

    public function test_un_usuario_bloqueado_no_puede_iniciar_sesion(): void
    {
        $cliente = Usuario::factory()->create(['correo' => 'ana@correo.test', 'rol' => RolUsuario::Cliente]);
        Sanctum::actingAs($this->admin());
        $this->estado($cliente->id, false)->assertOk();

        $this->app['auth']->forgetGuards();
        $this->postJson('/api/v1/auth/login', ['correo' => 'ana@correo.test', 'password' => 'password123'])
            ->assertForbidden();
    }

    public function test_al_bloquear_se_cierran_sus_sesiones_abiertas(): void
    {
        $cliente = Usuario::factory()->create(['rol' => RolUsuario::Cliente]);
        $tokenAnterior = $cliente->createToken('web')->plainTextToken;

        Sanctum::actingAs($this->admin());
        $this->estado($cliente->id, false)->assertOk();

        $this->assertSame(0, $cliente->tokens()->count());

        $this->app['auth']->forgetGuards();
        $this->withToken($tokenAnterior)->getJson('/api/v1/auth/perfil')->assertUnauthorized();
    }

    public function test_al_desbloquear_el_usuario_vuelve_a_entrar(): void
    {
        $cliente = Usuario::factory()->create(['correo' => 'ana@correo.test', 'rol' => RolUsuario::Cliente, 'activo' => false]);
        Sanctum::actingAs($this->admin());

        $this->estado($cliente->id, true)->assertOk();

        $this->app['auth']->forgetGuards();
        $this->postJson('/api/v1/auth/login', ['correo' => 'ana@correo.test', 'password' => 'password123'])
            ->assertOk();
    }

    public function test_un_cliente_no_puede_bloquear_a_nadie(): void
    {
        $victima = Usuario::factory()->create(['rol' => RolUsuario::Cliente]);
        Sanctum::actingAs(Usuario::factory()->create(['rol' => RolUsuario::Cliente]));

        $this->estado($victima->id, false)->assertForbidden();
        $this->assertTrue($victima->fresh()->activo);
    }

    public function test_un_vendedor_no_puede_bloquear_a_nadie(): void
    {
        $victima = Usuario::factory()->create(['rol' => RolUsuario::Cliente]);
        Sanctum::actingAs(Usuario::factory()->create(['rol' => RolUsuario::Vendedor]));

        $this->estado($victima->id, false)->assertForbidden();
        $this->assertTrue($victima->fresh()->activo);
    }

    public function test_sin_sesion_no_se_puede_bloquear(): void
    {
        $victima = Usuario::factory()->create(['rol' => RolUsuario::Cliente]);

        $this->estado($victima->id, false)->assertUnauthorized();
    }

    public function test_el_administrador_no_puede_bloquearse_a_si_mismo(): void
    {
        $admin = $this->admin();
        Sanctum::actingAs($admin);

        $this->estado($admin->id, false)->assertUnprocessable();
        $this->assertTrue($admin->fresh()->activo);
    }

    public function test_solo_el_administrador_puede_listar_usuarios(): void
    {
        Usuario::factory()->count(3)->create(['rol' => RolUsuario::Cliente]);

        Sanctum::actingAs(Usuario::factory()->create(['rol' => RolUsuario::Cliente]));
        $this->getJson('/api/v1/admin/usuarios')->assertForbidden();

        Sanctum::actingAs($this->admin());
        $this->getJson('/api/v1/admin/usuarios')->assertOk()->assertJsonStructure(['data', 'total']);
    }

    public function test_la_lista_se_filtra_por_rol(): void
    {
        Usuario::factory()->count(2)->create(['rol' => RolUsuario::Cliente]);
        Usuario::factory()->create(['rol' => RolUsuario::Vendedor]);
        Sanctum::actingAs($this->admin());

        $this->getJson('/api/v1/admin/usuarios?rol=vendedor')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}