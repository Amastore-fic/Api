<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// Rol superadmin: todo lo del administrador y, ademas, bloquear administradores
class SuperadminTest extends TestCase
{
    use RefreshDatabase;

    private function estado(Usuario $usuario, bool $activo)
    {
        return $this->patchJson("/api/v1/admin/usuarios/{$usuario->id}/estado", ['activo' => $activo]);
    }

    public function test_el_superadmin_entra_a_las_rutas_de_administracion(): void
    {
        Sanctum::actingAs(Usuario::factory()->superadmin()->create());

        $this->getJson('/api/v1/admin/usuarios')->assertOk();
    }

    public function test_el_superadmin_no_entra_a_las_rutas_de_cliente_ni_de_vendedor(): void
    {
        Sanctum::actingAs(Usuario::factory()->superadmin()->create());

        $this->getJson('/api/v1/carrito')->assertForbidden();
        $this->getJson('/api/v1/vendedor/productos')->assertForbidden();
    }

    public function test_el_superadmin_bloquea_y_desbloquea_a_un_administrador(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $admin->createToken('web');
        Sanctum::actingAs(Usuario::factory()->superadmin()->create());

        $this->estado($admin, false)->assertOk()->assertJsonPath('usuario.activo', false);
        $this->assertSame(0, $admin->tokens()->count());

        $this->estado($admin, true)->assertOk()->assertJsonPath('usuario.activo', true);
    }

    public function test_un_administrador_bloqueado_ya_no_entra_al_panel(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        Sanctum::actingAs(Usuario::factory()->superadmin()->create());
        $this->estado($admin, false)->assertOk();

        Sanctum::actingAs($admin->fresh());
        $this->getJson('/api/v1/admin/usuarios')->assertForbidden();
    }

    public function test_el_superadmin_bloquea_clientes_y_vendedores(): void
    {
        Sanctum::actingAs(Usuario::factory()->superadmin()->create());

        $this->estado(Usuario::factory()->create(), false)->assertOk();
        $this->estado(Usuario::factory()->vendedor()->create(), false)->assertOk();
    }

    public function test_un_administrador_no_puede_bloquear_a_otro_administrador(): void
    {
        $otro = Usuario::factory()->administrador()->create();
        Sanctum::actingAs(Usuario::factory()->administrador()->create());

        $this->estado($otro, false)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Solo un superadministrador puede bloquear a un administrador.');
        $this->assertTrue($otro->fresh()->activo);
    }

    public function test_nadie_puede_bloquear_a_un_superadmin(): void
    {
        $superadmin = Usuario::factory()->superadmin()->create();

        Sanctum::actingAs(Usuario::factory()->administrador()->create());
        $this->estado($superadmin, false)->assertUnprocessable();

        Sanctum::actingAs(Usuario::factory()->superadmin()->create());
        $this->estado($superadmin, false)->assertUnprocessable();

        $this->assertTrue($superadmin->fresh()->activo);
    }

    public function test_el_superadmin_no_puede_bloquearse_a_si_mismo(): void
    {
        $superadmin = Usuario::factory()->superadmin()->create();
        Sanctum::actingAs($superadmin);

        $this->estado($superadmin, false)->assertUnprocessable();
    }

    public function test_la_lista_se_filtra_por_superadmin(): void
    {
        Usuario::factory()->superadmin()->create();
        Usuario::factory()->administrador()->create();
        Sanctum::actingAs(Usuario::factory()->superadmin()->create());

        $this->getJson('/api/v1/admin/usuarios?rol=superadmin')
            ->assertOk()
            ->assertJsonPath('total', 2);
    }

    public function test_no_se_puede_registrar_una_cuenta_de_superadmin(): void
    {
        $this->postJson('/api/v1/auth/registro', [
            'nombre' => 'Ana',
            'apellido' => 'Lopez',
            'correo' => 'ana@correo.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'rol' => 'superadmin',
        ])->assertCreated();

        $this->assertSame(RolUsuario::Cliente, Usuario::where('correo', 'ana@correo.test')->first()->rol);
    }

    public function test_el_comando_convierte_una_cuenta_en_superadmin_y_cierra_sus_sesiones(): void
    {
        $usuario = Usuario::factory()->administrador()->create(['correo' => 'jefa@correo.test']);
        $usuario->createToken('web');

        $this->artisan('usuarios:superadmin', ['correo' => 'JEFA@correo.test'])->assertSuccessful();

        $this->assertSame(RolUsuario::Superadmin, $usuario->fresh()->rol);
        $this->assertSame(0, $usuario->tokens()->count());
    }

    public function test_el_comando_no_convierte_vendedores_ni_correos_inexistentes(): void
    {
        $vendedor = Usuario::factory()->vendedor()->create(['correo' => 'tienda@correo.test']);

        $this->artisan('usuarios:superadmin', ['correo' => 'tienda@correo.test'])->assertFailed();
        $this->artisan('usuarios:superadmin', ['correo' => 'nadie@correo.test'])->assertFailed();

        $this->assertSame(RolUsuario::Vendedor, $vendedor->fresh()->rol);
    }
}
