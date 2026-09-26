<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Vendedor;
use Illuminate\Database\Seeder;

// Catalogo inicial suficiente para demostrar busqueda y filtrado.
class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        $tecno = Vendedor::where('codigo_vendedor', 'VND-0001')->firstOrFail();
        $casa = Vendedor::where('codigo_vendedor', 'VND-0002')->firstOrFail();
        $categoria = fn (string $slug) => Categoria::where('slug', $slug)->value('id');

        $productos = [
            [$tecno, 'electronica', 'Audifonos inalambricos BT 5.3', 'Audifonos in-ear con estuche de carga, 30 h de bateria y cancelacion pasiva de ruido.', 649.00, 25],
            [$tecno, 'electronica', 'Cargador USB-C 65 W', 'Cargador GaN con dos puertos USB-C y uno USB-A, compatible con laptops y telefonos.', 499.00, 40],
            [$tecno, 'electronica', 'Teclado mecanico compacto', 'Teclado 75 % con switches rojos, retroiluminacion blanca y cable desmontable.', 1199.00, 12],
            [$casa, 'hogar', 'Juego de sartenes antiadherentes', 'Tres sartenes de aluminio forjado (20, 24 y 28 cm) aptas para estufa de induccion.', 899.00, 15],
            [$casa, 'hogar', 'Lampara de escritorio LED', 'Lampara con brazo articulado, tres temperaturas de color y puerto USB de carga.', 379.00, 30],
            [$casa, 'deportes', 'Tapete de yoga 6 mm', 'Tapete antiderrapante de TPE con correa para transportar, 183 x 61 cm.', 329.00, 50],
        ];

        foreach ($productos as [$vendedor, $slug, $titulo, $descripcion, $precio, $existencias]) {
            Producto::create([
                'id_vendedor' => $vendedor->id,
                'id_categoria' => $categoria($slug),
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'precio' => $precio,
                'existencias' => $existencias,
            ]);
        }
    }
}
