<?php

namespace App\Http\Requests\Auth;

// Mismos datos que un cliente mas los datos de negocio del vendedor.
class RegistrarVendedorRequest extends RegistrarClienteRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'nombre_comercial' => ['required', 'string', 'max:255'],
            'sucursal' => ['nullable', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
