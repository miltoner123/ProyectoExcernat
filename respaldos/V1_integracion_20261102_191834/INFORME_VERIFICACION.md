# EXCERNAT V1.0 — Revisión técnica y entrega parcial verificable

**Base:** `ProyectoExcernat-main (24)(3).zip` proporcionado por el usuario.  
**Estado:** intervención incremental, **NO es cierre integral de V1.0**. La base de datos local y las sesiones de usuarios no están disponibles en este entorno.  

## Auditoría: hallazgos verificables

| Módulo | Función | Frontend | API | Backend | Base de datos | Permiso | Estado inicial | Acción aplicada |
|---|---|---|---|---|---|---|---|---|
| Comercial | Gestionar ubicaciones | `App.vue`, rutas | `ubicaciones` | `UbicacionController` limita ALMACEN | `ubicaciones` | `eventos.gestionar` | Parcial; GET exige permiso separado | GET permite gestión comercial; acceso de navegación coherente |
| Personal | Asignaciones | Vue, guardias | `asignaciones-personal` | `AsignacionPersonalController` | `asignaciones_personal` | `eventos.gestionar` | Guardias frontend inconsistentes | Ajuste de guardia de navegación comercial |
| Evento | Acceso temporal | Vue, rutas | `mi-contexto-eventos` | `AccesoTemporalEvento` | `personal` y asignaciones | Encargado vigente | Parcial | Respetado sin crear roles permanentes |
| Ventas | Registro del encargado | `VentasStandView.vue` | `ventas-stand` | `VentaController` sí acepta contexto | `ventas`, `detalle_ventas` | `ventas.registrar` o encargo | Botón oculto con encargo válido | Vue consulta contexto temporal antes de habilitar registro y vendedores |
| Caja | Apertura para cobrar | `CajasPuntoVentaView.vue` | `cajas-punto-venta` | `CajaPuntoVentaController::autorizar` restringe ubicación | `cajas_punto_venta` | `cobros.registrar` o encargo | Middleware permanente bloqueaba | Se admite entrada temporal; controlador valida stand y fecha |
| Inventario | Consultar stock del stand | `MiPanelOperativo.vue` | **Nuevo** `mi-inventario-operativo` | `PanelOperativoController::inventario` | `inventarios` | Encargo vigente por ubicación | Ausente del panel sin permiso global | Consulta filtrada al stand asignado |
| Inventario | Devolver sobrantes | `MiPanelOperativo.vue` | **Nuevo** `mi-stand/devolucion` | `MovimientoInventarioController::devolverStand` | movimientos, detalle e inventario | Encargo vigente | Sin botón operativo temporal | Devolución de stand al central, tránsito pendiente de recepción |
| Recepción | Diferencias 10 contra 9 | Componente de recepciones | Rutas existentes | `RecepcionTransferenciaController` | detalle de movimientos | Receptor | Implementado previamente; necesita prueba local | Sin modificación del circuito existente |
| Finanzas | Descuento de venta | `VentasStandView.vue` | Ventas existentes | `VentaController` | `ventas.descuento_total` | Registro autorizado | Implementado previamente; necesita prueba local | No reescrito |
| Dashboard / gastos / reportes | Indicadores y finanzas básicas | Parciales | Existentes parcialmente | Revisar en pruebas integrales | Según módulos | Por función | Pendiente de cierre | No se afirma terminado |

## Reglas de trazabilidad conservadas

1. Recepción de producción: registrar lote → confirmación de Almacén Central → aumentar stock.
2. Despacho: descuento en origen → tránsito → confirmar recepción destino → aumentar stock destino.
3. Recepción parcial: preservar cantidades despachadas, recibidas, diferencia y observación.
4. Venta: cajero/encargado y vendedor asignado, existencia comprobada, subtotal bruto y descuento, cobro integrado.
5. Devolución desde stand: encargada/o vigente → retirar stock del stand y crear `DEVOLUCION` en tránsito → confirmación por almacén central; nunca aumenta inmediatamente su stock.
6. Cierre de asignación temporal: quitar permisos de contexto al vencer o desactivar la asignación.

## Verificaciones efectuadas

- Archivo ZIP original: leído y extraído.
- Se inspeccionaron `routes/api.php`, middleware, `AccesoTemporalEvento`, ventas, cajas, movimiento, panel, `main.js`, `App.vue`, vistas y seeder.
- `php -l`: correcto en **todos los PHP del proyecto** (incluidas modificaciones).
- El intento de instalar dependencias npm quedó bloqueado por el entorno sin acceso a paquetes; **NO se verificó `npm run build`**. Es obligatorio ejecutarlo en el equipo Windows.
- Sin PostgreSQL local ni credenciales, **NO se probaron** los endpoints contra base real, escenarios concurrentes, roles efectivos ni recepción de devoluciones.
- No se ejecutaron migraciones, seeders ni cambios sobre PostgreSQL.

## Matriz de pruebas pendientes antes del cierre

| Prueba | Resultado aquí | Procedimiento Windows |
|---|---|---|
| Ruth crea stand y asigna encargado | Pendiente | Login Ruth → Eventos → Stand → Asignaciones, comprobar 201 y listado |
| Restricción comercial | Pendiente | Ruth intenta editar ALMACEN CENTRAL: debe recibir 403 |
| Encargo de Lucas vigente | Pendiente | Acceso al panel solo con asignación ENCARGADO a stand |
| Inventario del stand | Pendiente | Recibir 9/10 y comprobar que panel muestre 9, nunca 10 |
| Protección multi-stand | Pendiente | Lucas consulta ID de stand ajeno: API responde 403 |
| Apertura y venta con descuento | Pendiente | Abrir caja; 4×40−20=140; comprobar stock, venta y cobro únicos |
| Vendedor sin cuenta | Pendiente | Seleccionar asignación VENDEDOR activa; confirmar relación histórica |
| Devolución de sobrantes | Pendiente | Devolver un lote; stock stand disminuye y central aumenta solo al confirmar |
| Concurrencia | Pendiente | Confirmar dos veces recepción/devolución; solo una confirmación válida |
| Fin de asignación | Pendiente | Desactivar/vencer asignación; verificar rutas protegidas por API |
| Reportes, gastos y dashboard | Pendiente | Contrastar totales con base PostgreSQL y UI |

## Instalación

Utilizar `Instalar_EXCERNAT_V1_Integracion.ps1`. Crea copia de archivos afectados bajo `C:\proyectoLaravel\excernat\respaldos`, conserva `.env`, exige rutas existentes, aplica solo archivos del parche y valida sintaxis PHP, rutas Laravel y build Vue. **No** ejecuta seed ni migraciones destructivas. Cuando se modifiquen los permisos del usuario, cerrar sesión e ingresar nuevamente para refrescar el contexto.

## Alcance que NO se declara terminado

Falta verificar el circuito operativo de punta a punta, permisos efectivos de Ruth en su PostgreSQL local, ventas con caja y vendedor, diferencias de recepción, reportes y cierres. La entrega se califica **parcialmente integrada**, no V1.0 aprobada para pruebas formales todavía.
