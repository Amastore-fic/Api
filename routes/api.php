<?php

use App\Http\Controllers\Api\V1\Admin\CategoriaController as AdminCategoriaController;
use App\Http\Controllers\Api\V1\Admin\PedidoController as AdminPedidoController;
use App\Http\Controllers\Api\V1\Admin\ProductoController as AdminProductoController;
use App\Http\Controllers\Api\V1\Admin\UsuarioController as AdminUsuarioController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CalificacionController;
use App\Http\Controllers\Api\V1\CarritoController;
use App\Http\Controllers\Api\V1\CatalogoController;
use App\Http\Controllers\Api\V1\DireccionController;
use App\Http\Controllers\Api\V1\PagoController;
use App\Http\Controllers\Api\V1\PedidoController;
use App\Http\Controllers\Api\V1\Vendedor\PedidoController as VendedorPedidoController;
use App\Http\Controllers\Api\V1\Vendedor\ProductoController as VendedorProductoController;
use Illuminate\Support\Facades\Route;

// Todas las rutas quedan bajo /api/v1 (ver bootstrap/app.php).

// ---- Autenticacion y cuentas ----
Route::prefix('auth')->group(function () {
    Route::post('registro', [AuthController::class, 'registro']);
    Route::post('registro-vendedor', [AuthController::class, 'registroVendedor']);
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('perfil', [AuthController::class, 'perfil']);
    });
});

// ---- Catalogo (publico) ----
Route::get('productos', [CatalogoController::class, 'productos']);
Route::get('productos/{id}', [CatalogoController::class, 'producto'])->whereNumber('id');
Route::get('categorias', [CatalogoController::class, 'categorias']);
Route::get('productos/{id}/calificaciones', [CalificacionController::class, 'index'])->whereNumber('id');

// ---- Notificacion asincrona de la pasarela (sin sesion de usuario) ----
Route::post('pagos/notificacion', [PagoController::class, 'notificacion']);

Route::middleware('auth:sanctum')->group(function () {

    // ---- Cliente ----
    Route::middleware('rol:cliente')->group(function () {
        Route::get('carrito', [CarritoController::class, 'show']);
        Route::post('carrito/items', [CarritoController::class, 'agregar']);
        Route::put('carrito/items/{id}', [CarritoController::class, 'actualizar'])->whereNumber('id');
        Route::delete('carrito/items/{id}', [CarritoController::class, 'eliminar'])->whereNumber('id');

        Route::get('direcciones', [DireccionController::class, 'index']);
        Route::post('direcciones', [DireccionController::class, 'store']);

        Route::post('pedidos', [PedidoController::class, 'store']);
        Route::get('pedidos', [PedidoController::class, 'index']);
        Route::post('pedidos/{id}/pago', [PagoController::class, 'pagar'])->whereNumber('id');

        Route::post('productos/{id}/calificaciones', [CalificacionController::class, 'store'])->whereNumber('id');
    });

    // Cliente, vendedor y administrador (la PedidoPolicy decide que pedido puede ver cada uno)
    Route::get('pedidos/{id}', [PedidoController::class, 'show'])
        ->middleware('rol:cliente,vendedor,administrador')
        ->whereNumber('id');

    // ---- Vendedor ----
    Route::middleware('rol:vendedor')->prefix('vendedor')->group(function () {
        Route::get('productos', [VendedorProductoController::class, 'index']);
        Route::post('productos', [VendedorProductoController::class, 'store']);
        Route::put('productos/{id}', [VendedorProductoController::class, 'update'])->whereNumber('id');
        Route::delete('productos/{id}', [VendedorProductoController::class, 'destroy'])->whereNumber('id');
        Route::get('pedidos', [VendedorPedidoController::class, 'index']);
    });

    // ---- Administrador ----
    Route::middleware('rol:administrador')->prefix('admin')->group(function () {
        Route::get('usuarios', [AdminUsuarioController::class, 'index']);
        Route::patch('usuarios/{id}/estado', [AdminUsuarioController::class, 'cambiarEstado'])->whereNumber('id');
        Route::get('pedidos', [AdminPedidoController::class, 'index']);
        Route::post('categorias', [AdminCategoriaController::class, 'store']);
        Route::delete('productos/{id}', [AdminProductoController::class, 'destroy'])->whereNumber('id');
    });
});
