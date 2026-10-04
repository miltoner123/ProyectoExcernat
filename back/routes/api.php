<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
Route::get('/productos', [\App\Http\Controllers\ProductoController::class, 'index']);
Route::post('/productos', [\App\Http\Controllers\ProductoController::class, 'store']);
Route::delete('/productos/{id}', [\App\Http\Controllers\ProductoController::class, 'destroy']);
Route::get('/productos/{id}', [\App\Http\Controllers\ProductoController::class, 'show']); 
Route::post('/productos/{id}', [\App\Http\Controllers\ProductoController::class, 'update']);

Route::get('/categorias', [\App\Http\Controllers\CategoriaController::class, 'index']);
Route::post('/categorias', [\App\Http\Controllers\CategoriaController::class, 'store']);

Route::get('/presentaciones', [\App\Http\Controllers\PresentacionController::class, 'index']);
Route::post('/presentaciones', [\App\Http\Controllers\PresentacionController::class, 'store']);
Route::get('/presentaciones/{id}', [\App\Http\Controllers\PresentacionController::class, 'show']);
Route::post('/presentaciones/{id}', [\App\Http\Controllers\PresentacionController::class, 'update']);
Route::delete('/presentaciones/{id}', [\App\Http\Controllers\PresentacionController::class, 'destroy']);

route::get('/lotes', [\App\Http\Controllers\LoteController::class, 'index']);
Route::post('/lotes', [\App\Http\Controllers\LoteController::class, 'store']);
Route::get('/lotes/{id}', [\App\Http\Controllers\LoteController::class, 'show']);
Route::post('/lotes/{id}', [\App\Http\Controllers\LoteController::class, 'update']);
Route::delete('/lotes/{id}', [\App\Http\Controllers\LoteController::class, 'destroy']);

Route::get('/ubicaciones', [\App\Http\Controllers\UbicacionController::class,'index']);
Route::post('/ubicaciones', [\App\Http\Controllers\UbicacionController::class,'store']);
Route::get('/ubicaciones/{id}', [\App\Http\Controllers\UbicacionController::class,'show']);
Route::post('/ubicaciones/{id}', [\App\Http\Controllers\UbicacionController::class,'update']);
Route::delete('/ubicaciones/{id}', [\App\Http\Controllers\UbicacionController::class,'destroy']);


Route::get('/eventos', [\App\Http\Controllers\EventoController::class,'index']);

Route::get('/inventarios', [\App\Http\Controllers\InventarioController::class,'index']);
Route::post('/inventarios', [\App\Http\Controllers\InventarioController::class,'store']);
Route::get('/inventarios/{id}', [\App\Http\Controllers\InventarioController::class,'show']);
Route::post('/inventarios/{id}', [\App\Http\Controllers\InventarioController::class,'update']);
Route::delete('/inventarios/{id}', [\App\Http\Controllers\InventarioController::class,'destroy']);

Route::get('/movimientos-inventario', [\App\Http\Controllers\MovimientoInventarioController::class,'index']);
Route::post('/movimientos-inventario', [\App\Http\Controllers\MovimientoInventarioController::class,'store']);
Route::get('/movimientos-inventario/{id}', [\App\Http\Controllers\MovimientoInventarioController::class,'show']);

Route::get('/pedidos', [\App\Http\Controllers\PedidoController::class,'index']);
Route::post('/pedidos', [\App\Http\Controllers\PedidoController::class,'store']);
Route::get('/pedidos/{id}', [\App\Http\Controllers\PedidoController::class,'show']);
Route::put('/pedidos/{id}/estado', [\App\Http\Controllers\PedidoController::class,'updateEstado']);
Route::delete('/pedidos/{id}', [\App\Http\Controllers\PedidoController::class,'destroy']);