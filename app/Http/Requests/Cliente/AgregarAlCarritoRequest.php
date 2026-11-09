<?php

namespace App\Http\Requests\Cliente;

use Illuminate\Foundation\Http\FormRequest;

// POST /carrito/items. Que el producto este a la venta y haya existencias lo revisa CarritoService.
class AgregarAlCarritoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // la ruta ya exige rol cliente
    }

    public function rules(): array
    {
        return [
            'id_producto' => ['required', 'integer'],
            'cantidad' => ['sometimes', 'integer', 'min:1', 'max:999'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_producto.required' => 'Indica qué producto quieres agregar.',
            'id_producto.integer' => 'El producto no es válido.',
            'cantidad.integer' => 'La cantidad debe ser un número entero.',
            'cantidad.min' => 'La cantidad debe ser al menos 1.',
            'cantidad.max' => 'La cantidad no puede ser mayor a 999.',
        ];
    }
}
