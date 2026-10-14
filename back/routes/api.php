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
Route::get('/productos-papelera', [\App\Http\Controllers\ProductoController::class, 'papelera']);
Route::post('/productos/{id}/restaurar', [\App\Http\Controllers\ProductoController::class, 'restaurar']);
Route::post('/productos/{id}/estado', [\App\Http\Controllers\ProductoController::class, 'cambiarEstado']);

Route::get('/categorias', [\App\Http\Controllers\CategoriaController::class, 'index']);
Route::post('/categorias', [\App\Http\Controllers\CategoriaController::class, 'store']);
Route::delete('/categorias/{id}', [\App\Http\Controllers\CategoriaController::class, 'destroy']);
Route::get('/categorias-papelera', [\App\Http\Controllers\CategoriaController::class, 'papelera']);
Route::post('/categorias/{id}/restaurar', [\App\Http\Controllers\CategoriaController::class, 'restaurar']);

Route::get('/presentaciones', [\App\Http\Controllers\PresentacionController::class, 'index']);
Route::post('/presentaciones', [\App\Http\Controllers\PresentacionController::class, 'store']);
Route::get('/presentaciones/{id}', [\App\Http\Controllers\PresentacionController::class, 'show']);
Route::put('/presentaciones/{id}', [\App\Http\Controllers\PresentacionController::class, 'update']);
Route::delete('/presentaciones/{id}', [\App\Http\Controllers\PresentacionController::class, 'destroy']);
Route::get('/presentaciones-papelera', [\App\Http\Controllers\PresentacionController::class, 'papelera']);
Route::post('/presentaciones/{id}/restaurar', [\App\Http\Controllers\PresentacionController::class, 'restaurar']);
Route::post('/presentaciones/{id}/estado', [\App\Http\Controllers\PresentacionController::class, 'cambiarEstado']);


route::get('/lotes', [\App\Http\Controllers\LoteController::class, 'index']);
Route::post('/lotes', [\App\Http\Controllers\LoteController::class, 'store']);
Route::get('/lotes/{id}', [\App\Http\Controllers\LoteController::class, 'show']);
Route::post('/lotes/{id}', [\App\Http\Controllers\LoteController::class, 'update']);
Route::delete('/lotes/{id}', [\App\Http\Controllers\LoteController::class, 'destroy']);
Route::get('/lotes-papelera', [\App\Http\Controllers\LoteController::class, 'papelera']);
Route::post('/lotes/{id}/restaurar', [\App\Http\Controllers\LoteController::class, 'restaurar']);
Route::post('/lotes/{id}/estado', [\App\Http\Controllers\LoteController::class, 'cambiarEstado']);

Route::get('/ubicaciones', [\App\Http\Controllers\UbicacionController::class,'index']);
Route::post('/ubicaciones', [\App\Http\Controllers\UbicacionController::class,'store']);
Route::get('/ubicaciones/{id}', [\App\Http\Controllers\UbicacionController::class,'show']);
Route::post('/ubicaciones/{id}', [\App\Http\Controllers\UbicacionController::class,'update']);
Route::delete('/ubicaciones/{id}', [\App\Http\Controllers\UbicacionController::class,'destroy']);


Route::get('/eventos', [\App\Http\Controllers\EventoController::class,'index']);
Route::post('/eventos', [\App\Http\Controllers\EventoController::class,'store']);
Route::get('/eventos/{id}', [\App\Http\Controllers\EventoController::class,'show']);
Route::post('/eventos/{id}', [\App\Http\Controllers\EventoController::class,'update']);
Route::delete('/eventos/{id}', [\App\Http\Controllers\EventoController::class,'destroy']);

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
Route::post('/pedidos/{id}', [\App\Http\Controllers\PedidoController::class,'update']);
Route::delete('/pedidos/{id}', [\App\Http\Controllers\PedidoController::class,'destroy']);

Route::get('/personas', [\App\Http\Controllers\PersonaController::class,'index']);
Route::post('/personas', [\App\Http\Controllers\PersonaController::class,'store']);
Route::get('/personas/{id}', [\App\Http\Controllers\PersonaController::class,'show']);
Route::post('/personas/{id}', [\App\Http\Controllers\PersonaController::class,'update']);
Route::delete('/personas/{id}', [\App\Http\Controllers\PersonaController::class,'destroy']);

Route::get('/cargos', [\App\Http\Controllers\CargoController::class,'index']);
Route::post('/cargos', [\App\Http\Controllers\CargoController::class,'store']);
Route::get('/cargos/{id}', [\App\Http\Controllers\CargoController::class,'show']);
Route::post('/cargos/{id}', [\App\Http\Controllers\CargoController::class,'update']);
Route::delete('/cargos/{id}', [\App\Http\Controllers\CargoController::class,'destroy']);

Route::get('/personal', [\App\Http\Controllers\PersonalController::class,'index']);
Route::post('/personal', [\App\Http\Controllers\PersonalController::class,'store']);
Route::get('/personal/{id}', [\App\Http\Controllers\PersonalController::class,'show']);
Route::post('/personal/{id}', [\App\Http\Controllers\PersonalController::class,'update']);
Route::delete('/personal/{id}', [\App\Http\Controllers\PersonalController::class,'destroy']);

Route::get('/asignaciones-personal', [\App\Http\Controllers\AsignacionPersonalController::class,'index']);
Route::post('/asignaciones-personal', [\App\Http\Controllers\AsignacionPersonalController::class,'store']);
Route::get('/asignaciones-personal/{id}', [\App\Http\Controllers\AsignacionPersonalController::class,'show']);
Route::post('/asignaciones-personal/{id}', [\App\Http\Controllers\AsignacionPersonalController::class,'update']);   
Route::delete('/asignaciones-personal/{id}', [\App\Http\Controllers\AsignacionPersonalController::class,'destroy']);

Route::get('/asistencias', [\App\Http\Controllers\AsistenciaController::class,'index']);
Route::post('/asistencias', [\App\Http\Controllers\AsistenciaController::class,'store']);
Route::get('/asistencias/{id}', [\App\Http\Controllers\AsistenciaController::class,'show']);
Route::post('/asistencias/{id}', [\App\Http\Controllers\AsistenciaController::class,'update']);   
Route::delete('/asistencias/{id}', [\App\Http\Controllers\AsistenciaController::class,'destroy']);