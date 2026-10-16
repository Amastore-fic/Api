<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Borrado logico de direcciones: pedidos.id_direccion tiene FK sin cascada,
// asi que la fila se conserva y solo se oculta al cliente.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('direcciones', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('direcciones', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
