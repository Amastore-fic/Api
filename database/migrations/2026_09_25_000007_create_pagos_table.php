<?php

use App\Enums\EstadoPago;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Un pedido puede tener varios intentos de pago (si uno se rechaza se reintenta).
// Nunca se guardan datos de tarjeta: solo la referencia que devuelve la pasarela.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pedido')->constrained('pedidos')->cascadeOnDelete();
            $table->string('pasarela');
            $table->string('referencia_pasarela')->nullable()->unique();
            $table->decimal('monto', 12, 2);
            $table->string('estado')->default(EstadoPago::Pendiente->value);
            $table->json('respuesta_pasarela')->nullable();
            $table->timestamps();

            $table->index(['id_pedido', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
