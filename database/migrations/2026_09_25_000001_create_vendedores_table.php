<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Extension del usuario con rol "vendedor": datos de negocio que se muestran
// en la ficha del producto para que el vendedor sea identificable y trazable.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendedores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_usuario')->unique()->constrained('usuarios')->cascadeOnDelete();
            $table->string('nombre_comercial');
            $table->string('codigo_vendedor')->unique(); // identificador publico, p. ej. VND-000123
            $table->string('sucursal')->nullable();
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendedores');
    }
};
