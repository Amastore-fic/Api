<?php

namespace Tests\Feature;

use App\Enums\EstadoProducto;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Usuario;
use App\Models\Vendedor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// HU-21 y HU-22: el cliente agrega productos a su carrito, cambia cantidades y los quita
class CarritoTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $cliente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cliente = Usuario::factory()->create();
        Sanctum::actingAs($this->cliente);
    }

    private function producto(array $cambios = [], ?Usuario $usuarioVendedor = null): Producto
    {
        $usuarioVendedor ??= Usuario::factory()->vendedor()->create();
        $vendedor = Vendedor::firstOrCreate(['id_usuario' => $usuarioVendedor->id], [
            'nombre_comercial' => 'Tienda '.Str::random(5),
            'codigo_vendedor' => 'VND-'.Str::upper(Str::random(8)),
        ]);
        $categoria = Categoria::firstOrCreate(['slug' => 'hogar'], ['nombre' => 'Hogar']);

        return Producto::create([
            'id_vendedor' => $vendedor->id,
            'id_categoria' => $categoria->id,
            'titulo' => 'Lámpara',
            'descripcion' => 'Lámpara de escritorio',
            'precio' => '250.50',
            'existencias' => 5,
            ...$cambios,
        ]);
    }

    private function agregar(Producto $producto, int $cantidad = 1)
    {
        return $this->postJson('/api/v1/carrito/items', ['id_producto' => $producto->id, 'cantidad' => $cantidad]);
    }

    public function test_un_cliente_nuevo_tiene_el_carrito_vacio(): void
    {
        $this->getJson('/api/v1/carrito')
            ->assertOk()
            ->assertJsonPath('items', [])
            ->assertJsonPath('total', '0.00')
            ->assertJsonPath('cantidad_articulos', 0);
    }

    public function test_agrega_un_producto_y_el_servidor_calcula_subtotal_y_total(): void
    {
        $producto = $this->producto();

        $this->agregar($producto, 2)
            ->assertOk()
            ->assertJsonPath('items.0.id_producto', $producto->id)
            ->assertJsonPath('items.0.cantidad', 2)
            ->assertJsonPath('items.0.subtotal', '501.00')
            ->assertJsonPath('items.0.disponible', true)
            ->assertJsonPath('items.0.producto.titulo', 'Lámpara')
            ->assertJsonPath('total', '501.00')
            ->assertJsonPath('cantidad_articulos', 2);

        $this->getJson('/api/v1/carrito')->assertJsonPath('total', '501.00');
    }

    public function test_sin_cantidad_agrega_una_pieza(): void
    {
        $producto = $this->producto();

        $this->postJson('/api/v1/carrito/items', ['id_producto' => $producto->id])
            ->assertOk()
            ->assertJsonPath('items.0.cantidad', 1);
    }

    public function test_agregar_el_mismo_producto_suma_en_la_misma_linea(): void
    {
        $producto = $this->producto();

        $this->agregar($producto, 2);
        $this->agregar($producto, 3)
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.cantidad', 5);
    }

    public function test_no_se_puede_pasar_de_las_existencias(): void
    {
        $producto = $this->producto(['existencias' => 3]);

        $this->agregar($producto, 4)->assertUnprocessable()->assertJsonValidationErrors('cantidad');

        $this->agregar($producto, 2)->assertOk();
        $this->agregar($producto, 2)
            ->assertUnprocessable()
            ->assertJsonPath('errors.cantidad.0', 'Solo hay 3 disponibles y ya tienes 2 en tu carrito.');
    }

    public function test_no_se_agrega_un_producto_retirado_inexistente_o_de_vendedor_bloqueado(): void
    {
        $retirado = $this->producto(['estado' => EstadoProducto::Retirado]);
        $deBloqueado = $this->producto([], Usuario::factory()->vendedor()->bloqueado()->create());

        $this->agregar($retirado)->assertUnprocessable()->assertJsonValidationErrors('id_producto');
        $this->agregar($deBloqueado)->assertUnprocessable()->assertJsonValidationErrors('id_producto');
        $this->postJson('/api/v1/carrito/items', ['id_producto' => 999999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('id_producto');
    }

    public function test_la_cantidad_debe_ser_al_menos_uno(): void
    {
        $producto = $this->producto();

        $this->agregar($producto, 0)->assertUnprocessable()->assertJsonValidationErrors('cantidad');
        $this->postJson('/api/v1/carrito/items', [])->assertUnprocessable()->assertJsonValidationErrors('id_producto');
    }

    public function test_el_precio_que_mande_el_cliente_se_ignora(): void
    {
        $producto = $this->producto();

        $this->postJson('/api/v1/carrito/items', ['id_producto' => $producto->id, 'cantidad' => 1, 'precio' => '1.00'])
            ->assertJsonPath('total', '250.50');
    }

    public function test_cambia_la_cantidad_de_una_linea(): void
    {
        $producto = $this->producto();
        $item = $this->agregar($producto, 1)->json('items.0.id');

        $this->putJson("/api/v1/carrito/items/{$item}", ['cantidad' => 4])
            ->assertOk()
            ->assertJsonPath('items.0.cantidad', 4)
            ->assertJsonPath('total', '1002.00');

        $this->putJson("/api/v1/carrito/items/{$item}", ['cantidad' => 6])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cantidad');
        $this->putJson("/api/v1/carrito/items/{$item}", ['cantidad' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cantidad');
    }

    public function test_quita_una_linea_del_carrito(): void
    {
        $producto = $this->producto();
        $item = $this->agregar($producto)->json('items.0.id');

        $this->deleteJson("/api/v1/carrito/items/{$item}")
            ->assertOk()
            ->assertJsonPath('items', [])
            ->assertJsonPath('total', '0.00');
    }

    public function test_no_se_puede_tocar_la_linea_del_carrito_de_otro_cliente(): void
    {
        $producto = $this->producto();
        $item = $this->agregar($producto)->json('items.0.id');

        Sanctum::actingAs(Usuario::factory()->create());

        $this->putJson("/api/v1/carrito/items/{$item}", ['cantidad' => 2])->assertNotFound();
        $this->deleteJson("/api/v1/carrito/items/{$item}")->assertNotFound();
        $this->assertDatabaseHas('carrito_items', ['id' => $item, 'cantidad' => 1]);
    }

    public function test_un_producto_que_se_retira_despues_se_muestra_pero_no_cuenta_en_el_total(): void
    {
        $sigue = $this->producto(['precio' => '100.00']);
        $retirado = $this->producto(['precio' => '50.00']);
        $this->agregar($sigue);
        $this->agregar($retirado);

        $retirado->update(['estado' => EstadoProducto::Retirado]);

        $this->getJson('/api/v1/carrito')
            ->assertJsonCount(2, 'items')
            ->assertJsonPath('items.1.disponible', false)
            ->assertJsonPath('total', '100.00')
            ->assertJsonPath('cantidad_articulos', 1);
    }

    public function test_solo_los_clientes_tienen_carrito(): void
    {
        Sanctum::actingAs(Usuario::factory()->vendedor()->create());
        $this->getJson('/api/v1/carrito')->assertForbidden();
    }

    public function test_sin_sesion_no_hay_carrito(): void
    {
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/v1/carrito')->assertUnauthorized();
    }
}
