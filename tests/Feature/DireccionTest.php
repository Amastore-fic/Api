<?php

namespace Tests\Feature;

use App\Models\Direccion;
use App\Models\Pedido;
use App\Models\Usuario;
use App\Models\Vendedor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// PUT y DELETE /direcciones/{id}: solo sobre direcciones propias del cliente
class DireccionTest extends TestCase
{
    use RefreshDatabase;

    private function datos(array $cambios = []): array
    {
        return [
            'destinatario' => 'Ana Lopez',
            'calle' => 'Av. Obregón',
            'numero_exterior' => '123',
            'numero_interior' => null,
            'colonia' => 'Centro',
            'ciudad' => 'Culiacán',
            'estado' => 'Sinaloa',
            'codigo_postal' => '80000',
            'telefono' => '6671234567',
            'referencias' => null,
            ...$cambios,
        ];
    }

    private function direccionDe(Usuario $usuario): Direccion
    {
        return $usuario->direcciones()->create($this->datos());
    }

    public function test_el_cliente_edita_su_direccion(): void
    {
        $cliente = Usuario::factory()->create();
        $direccion = $this->direccionDe($cliente);
        Sanctum::actingAs($cliente);

        $this->putJson("/api/v1/direcciones/{$direccion->id}", $this->datos(['calle' => 'Calle Nueva', 'codigo_postal' => '80100']))
            ->assertOk()
            ->assertJsonPath('id', $direccion->id)
            ->assertJsonPath('calle', 'Calle Nueva')
            ->assertJsonPath('codigo_postal', '80100');

        $this->assertDatabaseHas('direcciones', ['id' => $direccion->id, 'calle' => 'Calle Nueva']);
    }

    public function test_el_codigo_postal_debe_tener_5_digitos(): void
    {
        $cliente = Usuario::factory()->create();
        $direccion = $this->direccionDe($cliente);
        Sanctum::actingAs($cliente);

        foreach (['8000', '800001', '8000A'] as $cp) {
            $this->putJson("/api/v1/direcciones/{$direccion->id}", $this->datos(['codigo_postal' => $cp]))
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['codigo_postal' => 'El código postal debe tener 5 dígitos.']);
        }

        $this->postJson('/api/v1/direcciones', $this->datos(['codigo_postal' => '123']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('codigo_postal');
    }

    public function test_al_editar_se_exigen_los_campos_obligatorios(): void
    {
        $cliente = Usuario::factory()->create();
        $direccion = $this->direccionDe($cliente);
        Sanctum::actingAs($cliente);

        $this->putJson("/api/v1/direcciones/{$direccion->id}", ['calle' => 'Solo calle'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['destinatario', 'numero_exterior', 'colonia', 'ciudad', 'estado', 'codigo_postal', 'telefono']);
    }

    public function test_no_se_edita_ni_borra_la_direccion_de_otro_cliente(): void
    {
        $ajena = $this->direccionDe(Usuario::factory()->create());
        Sanctum::actingAs(Usuario::factory()->create());

        $this->putJson("/api/v1/direcciones/{$ajena->id}", $this->datos(['calle' => 'Robada']))->assertNotFound();
        $this->deleteJson("/api/v1/direcciones/{$ajena->id}")->assertNotFound();

        $this->assertDatabaseHas('direcciones', ['id' => $ajena->id, 'calle' => 'Av. Obregón', 'deleted_at' => null]);
    }

    public function test_un_vendedor_no_usa_las_rutas_de_direcciones(): void
    {
        Sanctum::actingAs(Usuario::factory()->vendedor()->create());

        $this->putJson('/api/v1/direcciones/1', $this->datos())->assertForbidden();
        $this->deleteJson('/api/v1/direcciones/1')->assertForbidden();
    }

    public function test_al_borrar_desaparece_del_listado(): void
    {
        $cliente = Usuario::factory()->create();
        $borrada = $this->direccionDe($cliente);
        $otra = $this->direccionDe($cliente);
        Sanctum::actingAs($cliente);

        $this->deleteJson("/api/v1/direcciones/{$borrada->id}")->assertOk();

        $this->getJson('/api/v1/direcciones')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $otra->id);

        $this->assertSoftDeleted('direcciones', ['id' => $borrada->id]);

        // Ya borrada, no se puede editar ni borrar otra vez
        $this->putJson("/api/v1/direcciones/{$borrada->id}", $this->datos())->assertNotFound();
        $this->deleteJson("/api/v1/direcciones/{$borrada->id}")->assertNotFound();
    }

    public function test_se_puede_borrar_una_direccion_usada_en_un_pedido(): void
    {
        $cliente = Usuario::factory()->create();
        $direccion = $this->direccionDe($cliente);

        $usuarioVendedor = Usuario::factory()->vendedor()->create();
        $vendedor = Vendedor::create([
            'id_usuario' => $usuarioVendedor->id,
            'nombre_comercial' => 'Tienda Luis',
            'codigo_vendedor' => 'VND-TEST0001',
        ]);
        $pedido = Pedido::create([
            'folio' => (string) Str::uuid(),
            'id_cliente' => $cliente->id,
            'id_vendedor' => $vendedor->id,
            'id_direccion' => $direccion->id,
            'direccion_envio' => $direccion->only(['calle', 'numero_exterior', 'colonia', 'ciudad', 'estado', 'codigo_postal']),
            'total' => 100,
        ]);

        Sanctum::actingAs($cliente);
        $this->deleteJson("/api/v1/direcciones/{$direccion->id}")->assertOk();

        // El pedido conserva su referencia y su copia de la direccion
        $this->assertDatabaseHas('pedidos', ['id' => $pedido->id, 'id_direccion' => $direccion->id]);
        $this->assertSame('Av. Obregón', $pedido->fresh()->direccion_envio['calle']);
    }
}
