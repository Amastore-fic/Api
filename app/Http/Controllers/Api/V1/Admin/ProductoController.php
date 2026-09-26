<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ProductoController extends Controller
{
    public function destroy(int $id): JsonResponse
    {
        // TODO: retirar la publicacion del catalogo (estado = retirado), sin importar el vendedor
        return $this->pendiente('retirar publicacion (administrador)');
    }
}
