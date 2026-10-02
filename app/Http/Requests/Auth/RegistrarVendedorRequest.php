<?php

namespace App\Http\Requests\Auth;

// Mismos datos que un cliente (reglas, mensajes y limpieza del correo se heredan)
// mas los datos de negocio que identifican al vendedor en el catalogo.
class RegistrarVendedorRequest extends RegistrarClienteRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            // Unico: es el nombre con el que el comprador identifica al vendedor
            'nombre_comercial' => ['required', 'string', 'min:3', 'max:100', 'unique:vendedores,nombre_comercial'],
            'sucursal' => ['nullable', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'nombre_comercial.required' => 'El nombre comercial es obligatorio.',
            'nombre_comercial.min' => 'El nombre comercial debe tener al menos 3 caracteres.',
            'nombre_comercial.max' => 'El nombre comercial no puede tener más de 100 caracteres.',
            'nombre_comercial.unique' => 'Ya existe un vendedor con ese nombre comercial.',
            'sucursal.max' => 'La sucursal no puede tener más de 100 caracteres.',
            'descripcion.max' => 'La descripción no puede tener más de 1000 caracteres.',
        ]);
    }
}
