<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            'Electronica',
            'Hogar',
            'Ropa y calzado',
            'Deportes',
            'Libros',
            'Juguetes',
        ];

        foreach ($categorias as $nombre) {
            Categoria::create([
                'nombre' => $nombre,
                'slug' => Str::slug($nombre),
            ]);
        }
    }
}
