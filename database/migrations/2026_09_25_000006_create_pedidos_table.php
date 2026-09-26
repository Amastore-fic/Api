<?php

use App\Enums\EstadoPedido;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->uuid('folio')->unique(); // identificador unico que ve el cliente
            $table->foreignId('id_cliente')->constrained('usuarios');
            // Supuesto del MVP: un pedido corresponde a un solo vendedor
            $table->foreignId('id_vendedor')->constrained('vendedores');
            $table->foreignId('id_direccion')->constrained('direcciones');
            // Copia de la direccion al momento de comprar, por si despues se edita o borra
            $table->json('direccion_envio');
            $table->decimal('total', 12, 2);
            $table->string('estado')->default(EstadoPedido::PendientePago->value);
            $table->timestamp('pagado_en')->nullable();
            $table->timestamps();

            $table->index(['id_cliente', 'estado']);
            $table->index(['id_vendedor', 'estado']);
        });

        Schema::create('pedido_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pedido')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('id_producto')->constrained('productos');
            $table->string('titulo_producto'); // copia del titulo al momento de la compra
            $table->unsignedInteger('cantidad');
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_detalles');
        Schema::dropIfExists('pedidos');
    }
};
