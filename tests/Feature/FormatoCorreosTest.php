<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Notifications\PasswordTemporalNotification;
use Illuminate\Mail\Markdown;
use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

class FormatoCorreosTest extends TestCase
{
    public function test_el_correo_de_contrasena_temporal_muestra_lo_necesario_para_entrar(): void
    {
        config(['app.frontend_url' => 'https://tienda.test/']);

        $correo = (new PasswordTemporalNotification('Abc123Def456', 10))
            ->toMail(new Usuario(['nombre' => 'Ana']));
        $html = (string) $correo->render();

        $this->assertSame('Tu contraseña temporal', $correo->subject);
        $this->assertStringContainsString('Hola, Ana', $html);
        $this->assertStringContainsString('Abc123Def456', $html);
        $this->assertStringContainsString('Vence en 10 minutos', $html);
        $this->assertStringContainsString('https://tienda.test/login', $html);
    }

    public function test_el_nombre_del_usuario_no_se_interpreta_como_html(): void
    {
        $html = (string) (new PasswordTemporalNotification('Abc123Def456', 10))
            ->toMail(new Usuario(['nombre' => '<b>Ana</b>']))
            ->render();

        $this->assertStringNotContainsString('<b>Ana</b>', $html);
    }

    public function test_la_version_de_texto_plano_esta_en_espanol_y_enlaza_a_la_tienda(): void
    {
        config(['app.frontend_url' => 'https://tienda.test', 'app.url' => 'https://api.test']);

        $texto = (string) app(Markdown::class)->renderText('correos.password-temporal', [
            'nombre' => 'Ana',
            'password' => 'Abc123Def456',
            'minutos' => 10,
            'urlLogin' => 'https://tienda.test/login',
        ]);

        $this->assertStringContainsString('Abc123Def456', $texto);
        $this->assertStringContainsString('https://tienda.test', $texto);
        $this->assertStringContainsString('Todos los derechos reservados', $texto);
        $this->assertStringNotContainsString('All rights reserved', $texto);
        $this->assertStringNotContainsString('https://api.test', $texto);
    }

    public function test_la_plantilla_general_esta_en_espanol_y_enlaza_a_la_tienda(): void
    {
        config(['app.frontend_url' => 'https://tienda.test']);

        $html = (string) (new MailMessage)
            ->subject('Prueba')
            ->line('Tu pedido fue confirmado.')
            ->action('Ver pedido', 'https://tienda.test/pedidos/1')
            ->render();

        $this->assertStringContainsString('Saludos', $html);
        $this->assertStringContainsString('Todos los derechos reservados', $html);
        $this->assertStringContainsString('copia y pega este enlace', $html);
        $this->assertStringContainsString('href="https://tienda.test"', $html);
        $this->assertStringNotContainsString('Regards', $html);
        $this->assertStringNotContainsString('All rights reserved', $html);
    }
}
