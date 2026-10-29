<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UsuarioController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    // AUTENTICACIÓN
    Route::get('/usuario', [AuthController::class, 'usuario']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // USUARIOS
    Route::get('/usuarios', [UsuarioController::class, 'index'])->middleware('permission:usuarios.ver');
    Route::get('/usuarios/roles', [UsuarioController::class, 'roles'])->middleware('permission:usuarios.roles');
    Route::get('/usuarios/personas-disponibles', [UsuarioController::class, 'personasDisponibles'])->middleware('permission:usuarios.crear');
    Route::post('/usuarios', [UsuarioController::class, 'store'])->middleware('permission:usuarios.crear');
    Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])->middleware('permission:usuarios.editar');

    // PRODUCTOS
    Route::get('/productos', [\App\Http\Controllers\ProductoController::class, 'index'])->middleware('permission:productos.ver');
    Route::post('/productos', [\App\Http\Controllers\ProductoController::class, 'store'])->middleware('permission:productos.gestionar');
    Route::delete('/productos/{id}', [\App\Http\Controllers\ProductoController::class, 'destroy'])->middleware('permission:productos.gestionar');
    Route::get('/productos/{id}', [\App\Http\Controllers\ProductoController::class, 'show'])->middleware('permission:productos.ver');
    Route::post('/productos/{id}', [\App\Http\Controllers\ProductoController::class, 'update'])->middleware('permission:productos.gestionar');
    Route::get('/productos-papelera', [\App\Http\Controllers\ProductoController::class, 'papelera'])->middleware('permission:productos.ver');
    Route::post('/productos/{id}/restaurar', [\App\Http\Controllers\ProductoController::class, 'restaurar'])->middleware('permission:productos.gestionar');
    Route::post('/productos/{id}/estado', [\App\Http\Controllers\ProductoController::class, 'cambiarEstado'])->middleware('permission:productos.gestionar');

    // CATEGORÍAS
    Route::get('/categorias', [\App\Http\Controllers\CategoriaController::class, 'index'])->middleware('permission:categorias.ver');
    Route::post('/categorias', [\App\Http\Controllers\CategoriaController::class, 'store'])->middleware('permission:categorias.gestionar');
    Route::delete('/categorias/{id}', [\App\Http\Controllers\CategoriaController::class, 'destroy'])->middleware('permission:categorias.gestionar');
    Route::get('/categorias-papelera', [\App\Http\Controllers\CategoriaController::class, 'papelera'])->middleware('permission:categorias.ver');
    Route::post('/categorias/{id}/restaurar', [\App\Http\Controllers\CategoriaController::class, 'restaurar'])->middleware('permission:categorias.gestionar');

    // PRESENTACIONES
    Route::get('/presentaciones', [\App\Http\Controllers\PresentacionController::class, 'index'])->middleware('permission:presentaciones.ver');
    Route::post('/presentaciones', [\App\Http\Controllers\PresentacionController::class, 'store'])->middleware('permission:presentaciones.gestionar');
    Route::get('/presentaciones/{id}', [\App\Http\Controllers\PresentacionController::class, 'show'])->middleware('permission:presentaciones.ver');
    Route::put('/presentaciones/{id}', [\App\Http\Controllers\PresentacionController::class, 'update'])->middleware('permission:presentaciones.gestionar');
    Route::delete('/presentaciones/{id}', [\App\Http\Controllers\PresentacionController::class, 'destroy'])->middleware('permission:presentaciones.gestionar');
    Route::get('/presentaciones-papelera', [\App\Http\Controllers\PresentacionController::class, 'papelera'])->middleware('permission:presentaciones.ver');
    Route::post('/presentaciones/{id}/restaurar', [\App\Http\Controllers\PresentacionController::class, 'restaurar'])->middleware('permission:presentaciones.gestionar');
    Route::post('/presentaciones/{id}/estado', [\App\Http\Controllers\PresentacionController::class, 'cambiarEstado'])->middleware('permission:presentaciones.gestionar');

    // LOTES
    Route::get('/lotes', [\App\Http\Controllers\LoteController::class, 'index'])->middleware('permission:lotes.ver');
    Route::post('/lotes', [\App\Http\Controllers\LoteController::class, 'store'])->middleware('permission:lotes.gestionar');
    Route::get('/lotes/{id}', [\App\Http\Controllers\LoteController::class, 'show'])->middleware('permission:lotes.ver');
    Route::post('/lotes/{id}', [\App\Http\Controllers\LoteController::class, 'update'])->middleware('permission:lotes.gestionar');
    Route::delete('/lotes/{id}', [\App\Http\Controllers\LoteController::class, 'destroy'])->middleware('permission:lotes.gestionar');
    Route::get('/lotes-papelera', [\App\Http\Controllers\LoteController::class, 'papelera'])->middleware('permission:lotes.ver');
    Route::post('/lotes/{id}/restaurar', [\App\Http\Controllers\LoteController::class, 'restaurar'])->middleware('permission:lotes.gestionar');
    Route::post('/lotes/{id}/estado', [\App\Http\Controllers\LoteController::class, 'cambiarEstado'])->middleware('permission:lotes.gestionar');

    // UBICACIONES
    Route::get('/ubicaciones', [\App\Http\Controllers\UbicacionController::class, 'index'])->middleware('permission:ubicaciones.ver');
    Route::post('/ubicaciones', [\App\Http\Controllers\UbicacionController::class, 'store'])->middleware('permission:ubicaciones.gestionar');
    Route::get('/ubicaciones/{id}', [\App\Http\Controllers\UbicacionController::class, 'show'])->middleware('permission:ubicaciones.ver');
    Route::post('/ubicaciones/{id}', [\App\Http\Controllers\UbicacionController::class, 'update'])->middleware('permission:ubicaciones.gestionar');
    Route::delete('/ubicaciones/{id}', [\App\Http\Controllers\UbicacionController::class, 'destroy'])->middleware('permission:ubicaciones.gestionar');

    // EVENTOS
    Route::get('/eventos', [\App\Http\Controllers\EventoController::class, 'index'])->middleware('permission:eventos.ver');
    Route::post('/eventos', [\App\Http\Controllers\EventoController::class, 'store'])->middleware('permission:eventos.gestionar');
    Route::get('/eventos/{id}', [\App\Http\Controllers\EventoController::class, 'show'])->middleware('permission:eventos.ver');
    Route::post('/eventos/{id}', [\App\Http\Controllers\EventoController::class, 'update'])->middleware('permission:eventos.gestionar');
    Route::delete('/eventos/{id}', [\App\Http\Controllers\EventoController::class, 'destroy'])->middleware('permission:eventos.gestionar');

    // INVENTARIOS
    Route::get('/inventarios', [\App\Http\Controllers\InventarioController::class, 'index'])->middleware('permission:inventarios.ver');
    Route::post('/inventarios', [\App\Http\Controllers\InventarioController::class, 'store'])->middleware('permission:inventarios.gestionar');
    Route::get('/inventarios/consulta', [\App\Http\Controllers\InventarioController::class, 'consulta'])->middleware('permission:inventarios.ver');
    Route::get('/inventarios/{id}', [\App\Http\Controllers\InventarioController::class, 'show'])->middleware('permission:inventarios.ver');
    Route::post('/inventarios/{id}', [\App\Http\Controllers\InventarioController::class, 'update'])->middleware('permission:inventarios.gestionar');
    Route::delete('/inventarios/{id}', [\App\Http\Controllers\InventarioController::class, 'destroy'])->middleware('permission:inventarios.gestionar');

    // RECEPCIÓN DE PRODUCCIÓN (ALMACÉN CENTRAL)
    Route::get('/recepciones-produccion', [\App\Http\Controllers\RecepcionProduccionController::class, 'index'])->middleware('permission:recepciones.ver');
    Route::post('/recepciones-produccion/{id}/confirmar', [\App\Http\Controllers\RecepcionProduccionController::class, 'confirmar'])->middleware('permission:recepciones.confirmar');

    // MOVIMIENTOS DE INVENTARIO
    Route::get('/recepciones-transferencias', [\App\Http\Controllers\RecepcionTransferenciaController::class, 'index'])->middleware('permission:transferencias.recibir');
    Route::post('/recepciones-transferencias/{id}/confirmar', [\App\Http\Controllers\RecepcionTransferenciaController::class, 'confirmar'])->middleware('permission:transferencias.recibir');

    Route::get('/movimientos-inventario', [\App\Http\Controllers\MovimientoInventarioController::class, 'index'])->middleware('permission:movimientos.ver');
    Route::post('/movimientos-inventario', [\App\Http\Controllers\MovimientoInventarioController::class, 'store'])->middleware('permission:movimientos.registrar');
    Route::get('/movimientos-inventario/{id}', [\App\Http\Controllers\MovimientoInventarioController::class, 'show'])->middleware('permission:movimientos.ver');

    // VENTAS POR STAND
    Route::get('/ventas-stand/stands', [\App\Http\Controllers\VentaController::class, 'stands'])->middleware('permission:ventas.ver');
    Route::get('/ventas-stand/existencias', [\App\Http\Controllers\VentaController::class, 'existencias'])->middleware('permission:ventas.ver');
    Route::get('/ventas-stand', [\App\Http\Controllers\VentaController::class, 'index'])->middleware('permission:ventas.ver');
    Route::post('/ventas-stand', [\App\Http\Controllers\VentaController::class, 'store'])->middleware('permission:ventas.registrar');

    // PEDIDOS (PERMISOS TEMPORALES)
    Route::get('/pedidos', [\App\Http\Controllers\PedidoController::class, 'index'])->middleware('permission:pedidos.ver');
    Route::post('/pedidos', [\App\Http\Controllers\PedidoController::class, 'store'])->middleware('permission:pedidos.gestionar');
    Route::get('/pedidos/{id}', [\App\Http\Controllers\PedidoController::class, 'show'])->middleware('permission:pedidos.ver');
    Route::post('/pedidos/{id}', [\App\Http\Controllers\PedidoController::class, 'update'])->middleware('permission:pedidos.gestionar');
    Route::delete('/pedidos/{id}', [\App\Http\Controllers\PedidoController::class, 'destroy'])->middleware('permission:pedidos.gestionar');

    // PERSONAS
    Route::get('/personas', [\App\Http\Controllers\PersonaController::class, 'index'])->middleware('permission:personas.ver');
    Route::post('/personas', [\App\Http\Controllers\PersonaController::class, 'store'])->middleware('permission:personas.gestionar');
    Route::get('/personas/{id}', [\App\Http\Controllers\PersonaController::class, 'show'])->middleware('permission:personas.ver');
    Route::put('/personas/{id}', [\App\Http\Controllers\PersonaController::class, 'update'])->middleware('permission:personas.gestionar');
    Route::delete('/personas/{id}', [\App\Http\Controllers\PersonaController::class, 'destroy'])->middleware('permission:personas.gestionar');

    // CARGOS
    Route::get('/cargos', [\App\Http\Controllers\CargoController::class, 'index'])->middleware('permission:personal.ver');
    Route::post('/cargos', [\App\Http\Controllers\CargoController::class, 'store'])->middleware('permission:personal.gestionar');
    Route::get('/cargos/{id}', [\App\Http\Controllers\CargoController::class, 'show'])->middleware('permission:personal.ver');
    Route::put('/cargos/{id}', [\App\Http\Controllers\CargoController::class, 'update'])->middleware('permission:personal.gestionar');
    Route::delete('/cargos/{id}', [\App\Http\Controllers\CargoController::class, 'destroy'])->middleware('permission:personal.gestionar');

    // PERSONAL
    Route::get('/personal', [\App\Http\Controllers\PersonalController::class, 'index'])->middleware('permission:personal.ver');
    Route::post('/personal', [\App\Http\Controllers\PersonalController::class, 'store'])->middleware('permission:personal.gestionar');
    Route::get('/personal/{id}', [\App\Http\Controllers\PersonalController::class, 'show'])->middleware('permission:personal.ver');
    Route::put('/personal/{id}', [\App\Http\Controllers\PersonalController::class, 'update'])->middleware('permission:personal.gestionar');
    Route::delete('/personal/{id}', [\App\Http\Controllers\PersonalController::class, 'destroy'])->middleware('permission:personal.gestionar');

    // ASIGNACIONES DE PERSONAL
    Route::get('/asignaciones-personal', [\App\Http\Controllers\AsignacionPersonalController::class, 'index'])->middleware('permission:eventos.ver');
    Route::post('/asignaciones-personal', [\App\Http\Controllers\AsignacionPersonalController::class, 'store'])->middleware('permission:eventos.asignar_personal');
    Route::get('/asignaciones-personal/{id}', [\App\Http\Controllers\AsignacionPersonalController::class, 'show'])->middleware('permission:eventos.ver');
    Route::post('/asignaciones-personal/{id}', [\App\Http\Controllers\AsignacionPersonalController::class, 'update'])->middleware('permission:eventos.asignar_personal');
    Route::delete('/asignaciones-personal/{id}', [\App\Http\Controllers\AsignacionPersonalController::class, 'destroy'])->middleware('permission:eventos.asignar_personal');

    // ASISTENCIAS
    Route::get('/asistencias', [\App\Http\Controllers\AsistenciaController::class, 'index'])->middleware('permission:asistencias.ver');
    Route::post('/asistencias', [\App\Http\Controllers\AsistenciaController::class, 'store'])->middleware('permission:asistencias.registrar');
    Route::get('/asistencias/{id}', [\App\Http\Controllers\AsistenciaController::class, 'show'])->middleware('permission:asistencias.ver');
    Route::post('/asistencias/{id}', [\App\Http\Controllers\AsistenciaController::class, 'update'])->middleware('permission:asistencias.gestionar');
    Route::delete('/asistencias/{id}', [\App\Http\Controllers\AsistenciaController::class, 'destroy'])->middleware('permission:asistencias.gestionar');

});
