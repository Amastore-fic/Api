<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegistrarClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // registro publico
    }

    //preparacion de correo para validacion

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
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'correo' => ['required', 'string', 'email', 'max:255', 'unique:usuarios,correo'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
            'telefono' => ['nullable', 'string', 'max:15'],
            // Nombre del dispositivo para el token con el que entra al registrarse (p. ej. "web-tienda")
            'dispositivo' => ['nullable', 'string', 'max:100'],
        ];
    }

    //mensajes en español para errores de crear un usuario

    public function messages(): array
    {
        return [
            'nombre.required'    => 'El nombre es obligatorio.',
            'apellido.required'  => 'El apellido es obligatorio.',
            'correo.required'    => 'El correo es obligatorio.',
            'correo.email'       => 'Escribe un correo válido.',
            'correo.unique'      => 'Este correo ya está registrado.',
            'password.required'  => 'La contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
            'password.letters'   => 'La contraseña debe incluir al menos una letra.',
            'password.numbers'   => 'La contraseña debe incluir al menos un número.',
        ];
    }

}

