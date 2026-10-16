<?php

namespace App\Http\Requests\Cliente;

use Illuminate\Foundation\Http\FormRequest;

// Mismas reglas al crear y al editar una direccion de envio (HU-23)
class GuardarDireccionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // la ruta ya exige rol cliente; la propiedad se revisa en el controlador
    }

    public function rules(): array
    {
        return [
            'destinatario' => ['required', 'string', 'max:255'],
            'calle' => ['required', 'string', 'max:255'],
            'numero_exterior' => ['required', 'string', 'max:20'],
            'numero_interior' => ['nullable', 'string', 'max:20'],
            'colonia' => ['required', 'string', 'max:255'],
            'ciudad' => ['required', 'string', 'max:255'],
            'estado' => ['required', 'string', 'max:255'],
            'codigo_postal' => ['required', 'string', 'regex:/^\d{5}$/'],
            'telefono' => ['required', 'string', 'max:15'],
            'referencias' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'destinatario.required' => 'El destinatario es obligatorio.',
            'calle.required' => 'La calle es obligatoria.',
            'numero_exterior.required' => 'El número exterior es obligatorio.',
            'colonia.required' => 'La colonia es obligatoria.',
            'ciudad.required' => 'La ciudad es obligatoria.',
            'estado.required' => 'El estado es obligatorio.',
            'codigo_postal.required' => 'El código postal es obligatorio.',
            'codigo_postal.regex' => 'El código postal debe tener 5 dígitos.',
            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.max' => 'El teléfono no puede tener más de 15 caracteres.',
        ];
    }
}
