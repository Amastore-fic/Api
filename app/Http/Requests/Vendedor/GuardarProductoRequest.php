<?php

namespace App\Http\Requests\Vendedor;

use Illuminate\Foundation\Http\FormRequest;

// Informacion minima obligatoria de toda publicacion (catalogo estandarizado).
// En PUT todos los campos pasan a ser opcionales ("sometimes").
class GuardarProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el rol ya lo valida el middleware 'rol:vendedor'
    }

    public function rules(): array
    {
        $requerido = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'titulo' => [$requerido, 'string', 'max:255'],
            'descripcion' => [$requerido, 'string', 'min:20'],
            'precio' => [$requerido, 'numeric', 'min:0.01', 'max:99999999.99'],
            'existencias' => [$requerido, 'integer', 'min:0'],
            'id_categoria' => [$requerido, 'integer', 'exists:categorias,id'],
            'imagen_url' => ['nullable', 'url', 'max:2048'],
        ];
    }
}
