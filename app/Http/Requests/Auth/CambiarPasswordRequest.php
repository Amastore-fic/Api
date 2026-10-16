<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class CambiarPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // la ruta ya exige sesion
    }

    public function rules(): array
    {
        return [
            // current_password compara contra el hash del usuario de la sesion (guard sanctum)
            'password_actual' => ['required', 'string', 'current_password:sanctum'],
            // Mismas reglas que al registrarse
            'password' => ['required', 'string', 'confirmed', 'different:password_actual',
                Password::min(8)->letters()->numbers()],
        ];
    }

    public function messages(): array
    {
        return [
            'password_actual.required' => 'Escribe tu contraseña actual.',
            'password_actual.current_password' => 'La contraseña actual no es correcta.',
            'password.required' => 'La contraseña nueva es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.different' => 'La contraseña nueva debe ser distinta de la actual.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.letters' => 'La contraseña debe incluir al menos una letra.',
            'password.numbers' => 'La contraseña debe incluir al menos un número.',
        ];
    }
}
