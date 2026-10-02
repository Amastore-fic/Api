<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Models\Vendedor;
use App\Services\RegistroVendedorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

// Tarea #41: POST /auth/registro-vendedor crea el usuario y su perfil de negocio.
class RegistroVendedorTest extends TestCase
{
    use RefreshDatabase;

    private const RUTA = '/api/v1/auth/registro-vendedor';

    private function datos(array $cambios = []): array
    {
        return array_merge([
            'nombre' => 'Laura',
            'apellido' => 'Medina',
            'correo' => 'laura@tecnomedina.com',
            'telefono' => '6671234567',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
            'nombre_comercial' => 'TecnoMedina',
            'sucursal' => 'Culiacán',
            'descripcion' => 'Accesorios de cómputo y electrónica.',
        ], $cambios);
    }

    public function test_crea_el_usuario_vendedor_y_su_perfil_de_negocio(): void
    {
        $respuesta = $this->postJson(self::RUTA, $this->datos())
            ->assertCreated()
            ->assertJsonPath('usuario.rol', 'vendedor')
            ->assertJsonPath('usuario.vendedor.nombre_comercial', 'TecnoMedina')
            ->assertJsonPath('usuario.vendedor.sucursal', 'Culiacán')
            ->assertJsonMissingPath('usuario.password');

        $usuario = Usuario::where('correo', 'laura@tecnomedina.com')->firstOrFail();
        $this->assertTrue(Hash::check('secreto123', $usuario->password), 'La contraseña se guarda cifrada');
        $this->assertSame($usuario->id, $respuesta->json('usuario.vendedor.id_usuario'));
        $this->assertDatabaseCount('vendedores', 1);
    }

    public function test_asigna_un_codigo_de_vendedor_trazable_y_unico(): void
    {
        $primero = $this->postJson(self::RUTA, $this->datos())->json('usuario.vendedor');
        $segundo = $this->postJson(self::RUTA, $this->datos([
            'correo' => 'jorge@casasalas.com',
            'nombre_comercial' => 'Casa Salas',
        ]))->json('usuario.vendedor');

        $this->assertSame(RegistroVendedorService::codigoPara($primero['id']), $primero['codigo_vendedor']);
        $this->assertMatchesRegularExpression('/^VND-\d{6}$/', $segundo['codigo_vendedor']);
        $this->assertNotSame($primero['codigo_vendedor'], $segundo['codigo_vendedor']);
    }

    public function test_guarda_el_correo_en_minusculas(): void
    {
        $this->postJson(self::RUTA, $this->datos(['correo' => 'Laura@TecnoMedina.com']))->assertCreated();

        $this->assertDatabaseHas('usuarios', ['correo' => 'laura@tecnomedina.com']);
    }

    public function test_sucursal_descripcion_y_telefono_son_opcionales(): void
    {
        $this->postJson(self::RUTA, $this->datos([
            'sucursal' => null,
            'descripcion' => null,
            'telefono' => null,
        ]))->assertCreated();
    }

    public function test_el_nombre_comercial_es_obligatorio(): void
    {
        $this->postJson(self::RUTA, $this->datos(['nombre_comercial' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre_comercial' => 'El nombre comercial es obligatorio.']);

        $this->assertDatabaseCount('usuarios', 0);
    }

    public function test_rechaza_un_nombre_comercial_repetido(): void
    {
        $this->postJson(self::RUTA, $this->datos())->assertCreated();

        $this->postJson(self::RUTA, $this->datos(['correo' => 'otra@correo.com']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre_comercial' => 'Ya existe un vendedor con ese nombre comercial.']);
    }

    public function test_un_correo_ya_registrado_no_crea_un_perfil_de_vendedor(): void
    {
        Usuario::factory()->create(['correo' => 'laura@tecnomedina.com']);

        $this->postJson(self::RUTA, $this->datos())
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('correo');

        $this->assertDatabaseCount('vendedores', 0);
    }

    public function test_aplica_las_mismas_reglas_de_contrasena_que_el_cliente(): void
    {
        $this->postJson(self::RUTA, $this->datos([
            'password' => 'abcdefgh',
            'password_confirmation' => 'abcdefgh',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('password');
    }

    public function test_si_falla_el_perfil_no_queda_un_usuario_suelto(): void
    {
        // Fuerza un error al guardar el perfil (despues de crear el usuario)
        Vendedor::creating(fn () => throw new \RuntimeException('falla simulada'));

        $this->withoutExceptionHandling();
        try {
            app(RegistroVendedorService::class)->registrar($this->datos());
            $this->fail('Se esperaba una excepción');
        } catch (\RuntimeException) {
            // esperado
        }

        $this->assertDatabaseCount('usuarios', 0);
        $this->assertDatabaseCount('vendedores', 0);
    }

    public function test_el_vendedor_registrado_puede_iniciar_sesion_y_entrar_a_su_panel(): void
    {
        $this->postJson(self::RUTA, $this->datos())->assertCreated();

        $token = $this->postJson('/api/v1/auth/login', [
            'correo' => 'laura@tecnomedina.com',
            'password' => 'secreto123',
        ])->assertOk()->json('token');

        $this->withToken($token)->getJson('/api/v1/auth/perfil')
            ->assertOk()
            ->assertJsonPath('rol', 'vendedor')
            ->assertJsonPath('vendedor.nombre_comercial', 'TecnoMedina');

        // Las rutas de vendedor lo dejan pasar (no 403); hoy responden 501 porque estan pendientes
        $this->withToken($token)->getJson('/api/v1/vendedor/productos')->assertStatus(501);
        // Y no entra a las de cliente
        $this->withToken($token)->getJson('/api/v1/carrito')->assertForbidden();
    }
}
