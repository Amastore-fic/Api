<?php

namespace App\Http\Requests\Vendedor;

use Illuminate\Foundation\Http\FormRequest;

class GuardarProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $esEdicion = (bool) $this->route('id') || $this->input('_method') === 'PUT';
        $requerido = $esEdicion ? 'sometimes' : 'required';

        return [
            'titulo'       => [$requerido, 'string', 'max:255'],
            'descripcion'  => [$requerido, 'string', 'min:20'],
            'precio'       => [$requerido, 'numeric', 'min:0.01', 'max:99999999.99'],
            'existencias'  => [$requerido, 'integer', 'min:0'],
            'id_categoria' => [$requerido, 'integer', 'exists:categorias,id'],
            'imagen'       => [$esEdicion ? 'nullable' : 'required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $preparar = [];

        if ($this->has('precio')) {
            $preparar['precio'] = filter_var($this->precio, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE) ?? $this->precio;
        }

        if ($this->has('existencias')) {
            $preparar['existencias'] = filter_var($this->existencias, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE) ?? $this->existencias;
        }

        if ($this->has('id_categoria')) {
            $preparar['id_categoria'] = filter_var($this->id_categoria, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE) ?? $this->id_categoria;
        }

        $this->merge($preparar);
    }
}