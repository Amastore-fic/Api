<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// El carrito vive en el servidor (no en el cliente) para que web y Android
// vean siempre el mismo estado.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carritos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_usuario')->unique()->constrained('usuarios')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('carrito_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_carrito')->constrained('carritos')->cascadeOnDelete();
            $table->foreignId('id_producto')->constrained('productos')->cascadeOnDelete();
            $table->unsignedInteger('cantidad');
            $table->timestamps();

            $table->unique(['id_carrito', 'id_producto']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrito_items');
        Schema::dropIfExists('carritos');
    }
};
