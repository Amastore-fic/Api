<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// Verifica que cada rol solo alcance sus propias rutas.
class PermisosTest extends TestCase
{
    use RefreshDatabase;

    public function test_sin_token_no_se_accede_al_carrito(): void
    {
        $this->getJson('/api/v1/carrito')->assertUnauthorized();
    }

    public function test_un_cliente_no_entra_a_rutas_de_vendedor_ni_admin(): void
    {
        Sanctum::actingAs(Usuario::factory()->create());

        $this->getJson('/api/v1/vendedor/productos')->assertForbidden();
        $this->getJson('/api/v1/admin/usuarios')->assertForbidden();
    }

    public function test_un_vendedor_no_entra_a_rutas_de_cliente(): void
    {
        Sanctum::actingAs(Usuario::factory()->vendedor()->create());

        $this->getJson('/api/v1/carrito')->assertForbidden();
    }

    public function test_el_catalogo_es_publico(): void
    {
        $this->getJson('/api/v1/productos')->assertOk();
        $this->getJson('/api/v1/categorias')->assertOk();
    }
}
