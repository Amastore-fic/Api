<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_el_endpoint_de_salud_responde(): void
    {
        $this->get('/up')->assertStatus(200);
    }
}
