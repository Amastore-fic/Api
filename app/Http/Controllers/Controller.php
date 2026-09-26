<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class Controller
{
    // Respuesta temporal para endpoints definidos en el MVP que aun no se implementan.
    protected function pendiente(string $funcionalidad): JsonResponse
    {
        return response()->json([
            'message' => "Pendiente de implementar: {$funcionalidad}",
        ], 501);
    }
}
