<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Models\Vendedor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// PUT /auth/perfil: edicion parcial del perfil para los tres roles
class PerfilTest extends TestCase
{
    use RefreshDatabase;

    private function vendedor(string $nombreComercial = 'Tienda Luis'): Usuario
    {
        $usuario = Usuario::factory()->vendedor()->create();
        Vendedor::create([
            'id_usuario' => $usuario->id,
            'nombre_comercial' => $nombreComercial,
            'codigo_vendedor' => 'VND-'.$usuario->id,
            'sucursal' => 'Centro',
        ]);

        return $usuario;
    }

    public function test_sin_token_no_se_edita_el_perfil(): void
    {
        $this->putJson('/api/v1/auth/perfil', ['nombre' => 'Ana'])->assertUnauthorized();
    }

    public function test_solo_cambia_los_campos_enviados(): void
    {
        $usuario = Usuario::factory()->create(['nombre' => 'Ana', 'apellido' => 'Lopez', 'telefono' => '6671234567']);
        Sanctum::actingAs($usuario);

        $this->putJson('/api/v1/auth/perfil', ['nombre' => 'Ana María'])
            ->assertOk()
            ->assertJsonPath('nombre', 'Ana María')
            ->assertJsonPath('apellido', 'Lopez')
            ->assertJsonPath('telefono', '6671234567')
            ->assertJsonPath('vendedor', null)
            ->assertJsonMissingPath('password');

        $this->assertDatabaseHas('usuarios', ['id' => $usuario->id, 'nombre' => 'Ana María', 'apellido' => 'Lopez']);
    }

    public function test_el_telefono_se_puede_borrar(): void
    {
        $usuario = Usuario::factory()->create();
        Sanctum::actingAs($usuario);

        $this->putJson('/api/v1/auth/perfil', ['telefono' => null])->assertOk()->assertJsonPath('telefono', null);
    }

    public function test_el_correo_se_guarda_en_minusculas_y_puede_repetirse_el_propio(): void
    {
        $usuario = Usuario::factory()->create(['correo' => 'ana@correo.test']);
        Sanctum::actingAs($usuario);

        $this->putJson('/api/v1/auth/perfil', ['correo' => 'ana@correo.test'])->assertOk();

        $this->putJson('/api/v1/auth/perfil', ['correo' => '  ANA.Nueva@Correo.TEST '])
            ->assertOk()
            ->assertJsonPath('correo', 'ana.nueva@correo.test');
    }

    public function test_no_se_puede_usar_el_correo_de_otro_usuario(): void
    {
        Usuario::factory()->create(['correo' => 'otro@correo.test']);
        Sanctum::actingAs(Usuario::factory()->create());

        $this->putJson('/api/v1/auth/perfil', ['correo' => 'OTRO@correo.test'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['correo' => 'Este correo ya está registrado.']);
    }

    public function test_valida_campos_vacios_y_telefono_largo(): void
    {
        Sanctum::actingAs(Usuario::factory()->create());

        $this->putJson('/api/v1/auth/perfil', [
            'nombre' => '',
            'correo' => 'no-es-correo',
            'telefono' => str_repeat('1', 16),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'nombre' => 'El nombre es obligatorio.',
                'correo' => 'Escribe un correo válido.',
                'telefono' => 'El teléfono no puede tener más de 15 caracteres.',
            ]);
    }

    public function test_el_vendedor_edita_sus_datos_de_negocio(): void
    {
        $usuario = $this->vendedor();
        Sanctum::actingAs($usuario);

        $this->putJson('/api/v1/auth/perfil', [
            'nombre_comercial' => 'Tienda Luis 2',
            'descripcion' => 'Artesanías',
        ])
            ->assertOk()
            ->assertJsonPath('vendedor.nombre_comercial', 'Tienda Luis 2')
            ->assertJsonPath('vendedor.descripcion', 'Artesanías')
            ->assertJsonPath('vendedor.sucursal', 'Centro');

        // Puede volver a mandar su propio nombre comercial
        $this->putJson('/api/v1/auth/perfil', ['nombre_comercial' => 'Tienda Luis 2'])->assertOk();
    }

    public function test_el_nombre_comercial_es_unico_entre_vendedores(): void
    {
        $this->vendedor('Tienda Ocupada');
        Sanctum::actingAs($this->vendedor());

        $this->putJson('/api/v1/auth/perfil', ['nombre_comercial' => 'Tienda Ocupada'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre_comercial' => 'Ya existe un vendedor con ese nombre comercial.']);

        $this->putJson('/api/v1/auth/perfil', ['nombre_comercial' => 'TL'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre_comercial' => 'El nombre comercial debe tener al menos 3 caracteres.']);
    }

    public function test_a_un_cliente_se_le_ignoran_los_datos_de_vendedor(): void
    {
        Sanctum::actingAs(Usuario::factory()->create());

        $this->putJson('/api/v1/auth/perfil', ['nombre' => 'Ana', 'nombre_comercial' => 'X'])
            ->assertOk()
            ->assertJsonPath('nombre', 'Ana')
            ->assertJsonPath('vendedor', null);

        $this->assertDatabaseCount('vendedores', 0);
    }

    public function test_el_administrador_tambien_edita_su_perfil(): void
    {
        Sanctum::actingAs(Usuario::factory()->administrador()->create());

        $this->putJson('/api/v1/auth/perfil', ['apellido' => 'Admin'])
            ->assertOk()
            ->assertJsonPath('apellido', 'Admin')
            ->assertJsonPath('rol', 'administrador');
    }
}
