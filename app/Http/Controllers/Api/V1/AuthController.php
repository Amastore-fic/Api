<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EventoBitacora;
use App\Enums\RolUsuario;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ActualizarPerfilRequest;
use App\Http\Requests\Auth\CambiarPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegistrarClienteRequest;
use App\Http\Requests\Auth\RegistrarVendedorRequest;
use App\Http\Requests\Auth\RestablecerPasswordRequest;
use App\Models\Usuario;
use App\Models\Vendedor;
use App\Services\BitacoraService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use App\Notifications\PasswordTemporalNotification;

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

       
        $conPasswordNormal = $usuario && Hash::check($request->password, $usuario->password);
        $conPasswordTemporal = $usuario && ! $conPasswordNormal && $usuario->passwordTemporalValida($request->password);

        if (! $conPasswordNormal && ! $conPasswordTemporal) {
            
            RateLimiter::hit($throttleKey, 15);
            $bitacora->registrar(EventoBitacora::LoginFallido, $request, $usuario);

            return response()->json(['message' => 'Credenciales incorrectas.'], 401);
        }

        
        if (! $usuario->activo) {
            $bitacora->registrar(EventoBitacora::LoginCuentaInactiva, $request, $usuario);

            return response()->json(['message' => 'La cuenta esta bloqueada.'], 403);
        }

        
        RateLimiter::clear($throttleKey);

        if ($conPasswordTemporal) {
            // La temporal reemplaza a la anterior. Va despues de revisar que la cuenta este activa
            $this->guardarPassword($usuario, $request->password, [
                'cambio_password_hasta' => now()->addMinutes(config('auth.password_temporal.cambio_directo_minutos')),
            ]);
            $bitacora->registrar(EventoBitacora::LoginPasswordTemporal, $request, $usuario);
        } elseif ($usuario->password_temporal) {
            // Recordo su contrasena: la temporal que seguia pendiente ya no hace falta
            $usuario->forceFill(['password_temporal' => null, 'password_temporal_vence' => null])->save();
        }

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

    // Solo cambia los campos enviados; responde igual que GET /auth/perfil
    public function actualizarPerfil(ActualizarPerfilRequest $request): JsonResponse
    {
        $usuario = $request->user();

        DB::transaction(function () use ($request, $usuario) {
            $usuario->update($request->safe()->only(['nombre', 'apellido', 'correo', 'telefono']));

            // Las reglas de vendedor solo existen para ese rol, asi que a otros les llega vacio
            $datosVendedor = $request->safe()->only(['nombre_comercial', 'sucursal', 'descripcion']);
            if ($datosVendedor && $usuario->vendedor) {
                $usuario->vendedor->update($datosVendedor);
            }
        });

        return response()->json($usuario->refresh()->load('vendedor'));
    }

    public function cambiarPassword(CambiarPasswordRequest $request): JsonResponse
    {
        $usuario = $request->user();

        $this->guardarPassword($usuario, $request->password);

        return response()->json([
            'message' => 'Contraseña actualizada.',
            'usuario' => $usuario->load('vendedor'),
        ]);
    }

    // Cambio sin pedir la contrasena actual, solo poco despues de entrar con una temporal.
    // Pasado ese tiempo hay que usar cambiarPassword: una sesion olvidada abierta no debe
    // bastar para quedarse con la cuenta.
    public function restablecerPassword(RestablecerPasswordRequest $request, BitacoraService $bitacora): JsonResponse
    {
        $usuario = $request->user();

        if (! $usuario->puedeCambiarPasswordSinLaActual()) {
            return response()->json([
                'message' => 'Se terminó el tiempo para cambiarla sin tu contraseña actual. Hazlo desde Mi cuenta, en Inicio de sesión y seguridad.',
            ], 403);
        }

        $this->guardarPassword($usuario, $request->password);
        $bitacora->registrar(EventoBitacora::PasswordRestablecida, $request, $usuario);

        return response()->json([
            'message' => 'Contraseña actualizada.',
            'usuario' => $usuario->load('vendedor'),
        ]);
    }

    // Deja $password como la contrasena del usuario, anula cualquier temporal y cierra las
    // demas sesiones. La sesion de esta peticion (si hay una) se conserva.
    private function guardarPassword(Usuario $usuario, string $password, array $otrosCampos = []): void
    {
        DB::transaction(function () use ($usuario, $password, $otrosCampos) {
            $usuario->forceFill([
                'password' => $password, // el cast 'hashed' aplica bcrypt
                'password_temporal' => null,
                'password_temporal_vence' => null,
                'cambio_password_hasta' => null,
                ...$otrosCampos,
            ]);
            $usuario->setRememberToken(Str::random(60));
            $usuario->save();

            $tokens = $usuario->tokens();
            $actual = $usuario->currentAccessToken();
            if ($actual instanceof PersonalAccessToken) {
                $tokens->whereKeyNot($actual->getKey());
            }
            $tokens->delete();
        });
    }


    public function solicitarPasswordTemporal(Request $request, BitacoraService $bitacora): JsonResponse
    {
        $request->validate(['correo' => 'required|email']);

        $correo = Str::lower(trim($request->correo));
        $respuesta = ['message' => 'Si el correo está registrado, recibirás una contraseña temporal.'];

        //permite una  solicitud por correo cada 60 segundos
        $key = 'olvide-password|'.$correo;
        if (RateLimiter::tooManyAttempts($key, 1)) {
            return response()->json($respuesta);
        }
        RateLimiter::hit($key, 60);

        try {
            $usuario = Usuario::where('correo', $correo)->first();

            if ($usuario && $usuario->activo) {
                $temporal = $this->generarPassword();
                $minutos = config('auth.password_temporal.vigencia_minutos');

                //la contraseña actual no se toca: conviven hasta que se use la temporal o venza.
                //si el envío falla, se revierte y queda la temporal anterior (si había)
                DB::transaction(function () use ($usuario, $temporal, $minutos) {
                    $usuario->forceFill([
                        'password_temporal' => Hash::make($temporal),
                        'password_temporal_vence' => now()->addMinutes($minutos),
                    ])->save();

                    $usuario->notify(new PasswordTemporalNotification($temporal, $minutos));
                });

                $bitacora->registrar(EventoBitacora::PasswordTemporalSolicitada, $request, $usuario);
            }
        } catch (\Throwable $e) {
            Log::error('No se pudo enviar la contraseña temporal.', ['error' => $e->getMessage()]);
        }

        //misma respuesta si existe o no la cuenta con ese correo
        return response()->json($respuesta);
    }

    private function generarPassword(int $longitud = 16): string
    {
        //el bucle garantiza letra + número
        do {
            $password = Str::password($longitud, symbols: false);
        } while (! preg_match('/[A-Za-z]/', $password) || ! preg_match('/\d/', $password));

        return $password;
    }
}
