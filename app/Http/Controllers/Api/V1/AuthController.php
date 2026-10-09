<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EventoBitacora;
use App\Enums\RolUsuario;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegistrarClienteRequest;
use App\Http\Requests\Auth\RegistrarVendedorRequest;
use App\Models\Usuario;
use App\Models\Vendedor;
use App\Services\BitacoraService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

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

        // Queda con sesion iniciada: misma respuesta que el login, sin pedir la contrasena otra vez
        return response()->json([
            'message' => 'Cuenta de cliente creada.',
            ...$this->sesionPara($usuario, $request),
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
            ...$this->sesionPara($usuario, $request),
        ], 201);
    }

    // Token y datos de sesion, con la misma forma que la respuesta del login
    private function sesionPara(Usuario $usuario, Request $request): array
    {
        $token = $usuario->createToken(
            $request->input('dispositivo', 'web'),
            [$usuario->rol->value],
        );

        return [
            'token' => $token->plainTextToken,
            'tipo' => 'Bearer',
            'usuario' => $usuario->load('vendedor'),
        ];
    }

    public function login(LoginRequest $request, BitacoraService $bitacora): JsonResponse
    {
        
        $throttleKey = Str::lower($request->correo) . '|' . $request->ip();

        
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $bitacora->registrar(EventoBitacora::LoginBloqueado, $request, detalles: ['segundos_restantes' => $seconds]);
            return response()->json([
                'message' => "Demasiados intentos fallidos. Inténtalo de nuevo en {$seconds} segundos."
            ], 429); // 
        }

        $usuario = Usuario::where('correo', strtolower($request->correo))->first();

       
        if (! $usuario || ! Hash::check($request->password, $usuario->password)) {
            
            RateLimiter::hit($throttleKey, 15);
            $bitacora->registrar(EventoBitacora::LoginFallido, $request, $usuario);

            return response()->json(['message' => 'Credenciales incorrectas.'], 401);
        }

        
        if (! $usuario->activo) {
            $bitacora->registrar(EventoBitacora::LoginCuentaInactiva, $request, $usuario);

            return response()->json(['message' => 'La cuenta esta bloqueada.'], 403);
        }

        
        RateLimiter::clear($throttleKey);

        $token = $usuario->createToken(
            $request->input('dispositivo', 'web'),
            [$usuario->rol->value],
        );

        $bitacora->registrar(EventoBitacora::LoginExitoso, $request, $usuario);

        return response()->json([
            'token' => $token->plainTextToken,
            'tipo' => 'Bearer',
            'usuario' => $usuario->load('vendedor'),
        ]);
    }

    public function logout(Request $request, BitacoraService $bitacora): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        $bitacora->registrar(EventoBitacora::Logout, $request, $request->user());

        return response()->json(['message' => 'Sesion cerrada.']);
    }

    public function perfil(Request $request): JsonResponse
    {
        return response()->json($request->user()->load('vendedor'));
    }

    public function sendResetLinkEmail(Request $request): JsonResponse
    {
        $request->validate(['correo' => 'required|email']);

        // Envia el correo con el enlace si la cuenta existe. El broker de Laravel guarda
        // el token cifrado y no deja pedir otro para la misma cuenta antes de 60 s.
        try {
            Password::broker()->sendResetLink(['email' => Str::lower(trim($request->correo))]);
        } catch (\Throwable $e) {
            // Nunca se escriben en el log el token ni el enlace
            Log::error('No se pudo enviar el correo de recuperación de contraseña.', ['error' => $e->getMessage()]);
        }

        // Misma respuesta exista o no la cuenta: asi no se revela que correos estan registrados.
        // El enlace solo viaja por correo; jamas en esta respuesta.
        return response()->json([
            'message' => 'Si el correo está registrado, recibirás las instrucciones.',
        ]);
    }
    
    public function reset(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'correo' => 'required|email',
            // Mismas reglas que al registrarse
            'password' => ['required', 'string', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        $response = Password::broker()->reset(
            [
                'email' => Str::lower(trim($request->correo)),
                'password' => $request->password,
                'password_confirmation' => $request->password_confirmation,
                'token' => $request->token,
            ],
            function ($usuario, $password) {
                $usuario->password = $password; // el cast 'hashed' aplica bcrypt
                $usuario->setRememberToken(Str::random(60));
                $usuario->save();

                // Cierra todas las sesiones abiertas: si alguien mas tenia acceso, lo pierde
                $usuario->tokens()->delete();

                event(new PasswordReset($usuario));
            }
        );

        if ($response === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Contraseña restablecida con éxito.']);
        }

        return response()->json(['message' => 'El enlace de recuperación ha expirado o es inválido.'], 422);
    }
}