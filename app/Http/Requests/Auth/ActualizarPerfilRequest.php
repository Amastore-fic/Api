<?php

namespace App\Http\Requests\Auth;

use App\Enums\RolUsuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Edicion parcial del perfil: solo se validan (y se cambian) los campos enviados.
class ActualizarPerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // la ruta ya exige sesion
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('correo')) {
            $this->merge([
                'correo' => strtolower(trim($this->correo)),
            ]);
        }
    }

    public function rules(): array
    {
        $usuario = $this->user();

        $reglas = [
            'nombre' => ['sometimes', 'required', 'string', 'max:255'],
            'apellido' => ['sometimes', 'required', 'string', 'max:255'],
            'correo' => ['sometimes', 'required', 'string', 'email', 'max:255',
                Rule::unique('usuarios', 'correo')->ignore($usuario->id)],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:15'],
        ];

        // Los datos de negocio solo aplican a vendedores; a otros roles se les ignoran
        if ($usuario->rol === RolUsuario::Vendedor) {
            $reglas += [
                'nombre_comercial' => ['sometimes', 'required', 'string', 'min:3', 'max:100',
                    Rule::unique('vendedores', 'nombre_comercial')->ignore($usuario->vendedor?->id)],
                'sucursal' => ['sometimes', 'nullable', 'string', 'max:100'],
                'descripcion' => ['sometimes', 'nullable', 'string', 'max:1000'],
            ];
        }

        return $reglas;
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede tener más de 255 caracteres.',
            'apellido.required' => 'El apellido es obligatorio.',
            'apellido.max' => 'El apellido no puede tener más de 255 caracteres.',
            'correo.required' => 'El correo es obligatorio.',
            'correo.email' => 'Escribe un correo válido.',
            'correo.unique' => 'Este correo ya está registrado.',
            'telefono.max' => 'El teléfono no puede tener más de 15 caracteres.',
            'nombre_comercial.required' => 'El nombre comercial es obligatorio.',
            'nombre_comercial.min' => 'El nombre comercial debe tener al menos 3 caracteres.',
            'nombre_comercial.max' => 'El nombre comercial no puede tener más de 100 caracteres.',
            'nombre_comercial.unique' => 'Ya existe un vendedor con ese nombre comercial.',
            'sucursal.max' => 'La sucursal no puede tener más de 100 caracteres.',
            'descripcion.max' => 'La descripción no puede tener más de 1000 caracteres.',
        ];
    }
}
