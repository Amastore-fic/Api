<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'correo' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            // Nombre del dispositivo para identificar el token (p. ej. "web" o "android")
            'dispositivo' => ['nullable', 'string', 'max:100'],
        ];
    }
}
