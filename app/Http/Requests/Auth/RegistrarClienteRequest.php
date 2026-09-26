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

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'correo' => ['required', 'string', 'email', 'max:255', 'unique:usuarios,correo'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
            'telefono' => ['nullable', 'string', 'max:15'],
        ];
    }
}
