<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\RolUsuario;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegistrarClienteRequest;
use App\Http\Requests\Auth\RegistrarVendedorRequest;
use App\Models\Usuario;
use App\Models\Vendedor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function registro(RegistrarClienteRequest $request): JsonResponse
    {
        $usuario = Usuario::create([
            ...$request->safe()->only(['nombre', 'apellido', 'telefono']),
            'correo' => strtolower($request->correo),
            'password' => $request->password, // el cast 'hashed' aplica bcrypt
            'rol' => RolUsuario::Cliente,
        ]);

        return response()->json([
            'message' => 'Cuenta de cliente creada.',
            'usuario' => $usuario,
        ], 201);
    }

    public function registroVendedor(RegistrarVendedorRequest $request): JsonResponse
    {
        $usuario = DB::transaction(function () use ($request) {
            $usuario = Usuario::create([
                ...$request->safe()->only(['nombre', 'apellido', 'telefono']),
                'correo' => strtolower($request->correo),
                'password' => $request->password,
                'rol' => RolUsuario::Vendedor,
            ]);

            Vendedor::create([
                'id_usuario' => $usuario->id,
                'nombre_comercial' => $request->nombre_comercial,
                'codigo_vendedor' => 'VND-'.strtoupper(Str::random(8)),
                'sucursal' => $request->sucursal,
                'descripcion' => $request->descripcion,
            ]);

            return $usuario->load('vendedor');
        });

        return response()->json([
            'message' => 'Cuenta de vendedor creada.',
            'usuario' => $usuario,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $usuario = Usuario::where('correo', strtolower($request->correo))->first();

        if (! $usuario || ! Hash::check($request->password, $usuario->password)) {
            return response()->json(['message' => 'Credenciales incorrectas.'], 401);
        }

        if (! $usuario->activo) {
            return response()->json(['message' => 'La cuenta esta bloqueada.'], 403);
        }

        // El rol viaja como "ability" del token por si despues se quiere usar tokenCan()
        $token = $usuario->createToken(
            $request->input('dispositivo', 'web'),
            [$usuario->rol->value],
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'tipo' => 'Bearer',
            'usuario' => $usuario->load('vendedor'),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesion cerrada.']);
    }

    public function perfil(Request $request): JsonResponse
    {
        return response()->json($request->user()->load('vendedor'));
    }
}
