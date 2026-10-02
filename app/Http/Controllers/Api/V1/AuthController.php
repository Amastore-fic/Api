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
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function registro(RegistrarClienteRequest $request): JsonResponse
    {
        $usuario = Usuario::create([
            ...$request->safe()->only(['nombre', 'apellido', 'telefono']),
            'correo' => strtolower($request->correo),
            'password' => $request->password, 
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
        
        $throttleKey = Str::lower($request->correo) . '|' . $request->ip();

        
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'message' => "Demasiados intentos fallidos. Inténtalo de nuevo en {$seconds} segundos."
            ], 429); // 
        }

        $usuario = Usuario::where('correo', strtolower($request->correo))->first();

       
        if (! $usuario || ! Hash::check($request->password, $usuario->password)) {
            
            RateLimiter::hit($throttleKey, 15);

            return response()->json(['message' => 'Credenciales incorrectas.'], 401);
        }

        
        if (! $usuario->activo) {
            return response()->json(['message' => 'La cuenta esta bloqueada.'], 403);
        }

        
        RateLimiter::clear($throttleKey);

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

    public function sendResetLinkEmail(Request $request): JsonResponse
    {
        $request->validate(['correo' => 'required|email']);

        $correoBuscado = strtolower($request->correo);
        
        \Illuminate\Support\Facades\Log::info("1. Buscando correo para password reset: " . $correoBuscado);

        $usuario = Usuario::where('correo', $correoBuscado)->first();
        
        $urlEnlace = null;

        if ($usuario) {
            \Illuminate\Support\Facades\Log::info("2. ¡Usuario encontrado! ID: " . $usuario->id);
            
            try {
                $token = Password::broker()->createToken($usuario);
                \Illuminate\Support\Facades\Log::info("3. Token generado con éxito: " . $token);
                
                $urlEnlace = "http://localhost:5173/restablecer-password?token={$token}&correo=" . urlencode($usuario->correo);
                \Illuminate\Support\Facades\Log::info("ENLACE DE RECUPERACIÓN DIRECTO: " . $urlEnlace);

                $usuario->sendPasswordResetNotification($token);
                \Illuminate\Support\Facades\Log::info("4. Notificación disparada correctamente.");
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("ERROR CRÍTICO AL ENVIAR NOTIFICACIÓN: " . $e->getMessage());
            }

        } else {
            \Illuminate\Support\Facades\Log::warning("No se encontró ningún usuario con el correo: " . $correoBuscado);
        }

        return response()->json([
            'message' => 'Si el correo está registrado, recibirás las instrucciones.',
            'debug_url' => $urlEnlace,
        ], 200);
    }
    
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'correo' => 'required|email',
            'password' => 'required|min:8|confirmed', 
        ]);

        $response = Password::broker()->reset(
            [
                'email' => $request->correo,
                'password' => $request->password,
                'password_confirmation' => $request->password_confirmation,
                'token' => $request->token,
            ],
            function ($usuario, $password) {
                $usuario->password = Hash::make($password);
                $usuario->setRememberToken(Str::random(60));
                $usuario->save();

                event(new PasswordReset($usuario));
            }
        );

        if ($response == Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Contraseña restablecida con éxito.'], 200);
        }

        return response()->json(['message' => 'El enlace de recuperación ha expirado o es inválido.'], 422);
    }
}