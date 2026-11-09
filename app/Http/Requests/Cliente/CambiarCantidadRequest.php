<?php

namespace App\Http\Requests\Cliente;

use Illuminate\Foundation\Http\FormRequest;

// PUT /carrito/items/{id}. Las existencias las revisa CarritoService.
class CambiarCantidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // la ruta ya exige rol cliente; la propiedad de la linea se revisa en el servicio
    }

    public function rules(): array
    {
        return [
            'cantidad' => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }

    public function messages(): array
    {
        return [
            'cantidad.required' => 'Indica la cantidad.',
            'cantidad.integer' => 'La cantidad debe ser un número entero.',
            'cantidad.min' => 'La cantidad debe ser al menos 1. Para quitarlo, elimínalo del carrito.',
            'cantidad.max' => 'La cantidad no puede ser mayor a 999.',
        ];
    }
}
