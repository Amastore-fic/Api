<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegistrarClienteRequest extends FormRequest
{
    // Letras (con acentos y ñ), espacios, apostrofo y guion; debe empezar con letra
    public const REGEX_NOMBRE = "/^\p{L}[\p{L} '\-]*$/u";

    public function authorize(): bool
    {
        return true; // registro publico
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'min:2', 'max:50', 'regex:'.self::REGEX_NOMBRE],
            'apellido' => ['required', 'string', 'min:2', 'max:50', 'regex:'.self::REGEX_NOMBRE],
            // rfc: sintaxis del estandar; filter: exige dominio completo (rechaza "ana@correo")
            'correo' => ['required', 'string', 'email:rfc,filter', 'max:255', 'unique:usuarios,correo'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
            // Opcional; si viene, 10 digitos (numero de Mexico)
            'telefono' => ['nullable', 'digits:10'],
        ];
    }
}
