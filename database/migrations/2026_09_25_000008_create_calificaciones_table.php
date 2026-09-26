<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Calificacion ligada obligatoriamente a un pedido pagado (compra verificada).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_producto')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('id_cliente')->constrained('usuarios');
            $table->foreignId('id_pedido')->constrained('pedidos');
            $table->unsignedTinyInteger('puntuacion'); // 1 a 5
            $table->text('comentario')->nullable();
            $table->timestamps();

            // Una calificacion por producto por cliente
            $table->unique(['id_producto', 'id_cliente']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calificaciones');
    }
};
