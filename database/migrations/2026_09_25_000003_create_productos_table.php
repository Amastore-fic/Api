<?php

use App\Enums\EstadoProducto;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_vendedor')->constrained('vendedores');
            $table->foreignId('id_categoria')->constrained('categorias');
            // Informacion minima obligatoria para publicar (catalogo estandarizado)
            $table->string('titulo');
            $table->text('descripcion');
            $table->decimal('precio', 10, 2);
            $table->unsignedInteger('existencias')->default(0);
            $table->string('imagen_url')->nullable();
            // "retirado" = baja logica; se conserva para no romper pedidos historicos
            $table->string('estado')->default(EstadoProducto::Activo->value);
            $table->timestamps();

            $table->index(['id_categoria', 'estado']);
            $table->index('id_vendedor');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
