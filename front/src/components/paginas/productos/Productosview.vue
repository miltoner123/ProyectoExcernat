<template>
    <div class="contenedor-productos">

        <!-- ENCABEZADO -->
        <div class="encabezado">
            <div>
                <h1>Productos</h1>
                <p>Gestión y administración de productos y sus presentaciones</p>
            </div>

            <button class="btn-crear" @click="crearProducto">
                + Crear Producto
            </button>
        </div>

        <!-- INFORMACIÓN DE REGISTROS -->
        <div class="barra-superior">
            <span>
                Total de productos:
                <strong>{{ pagination.total }}</strong>
            </span>

            <span>
                Página {{ pagination.current_page }}
                de {{ pagination.last_page }}
            </span>
        </div>

        <!-- TABLA DE PRODUCTOS -->
        <div class="tabla-contenedor">
            <table class="tabla-productos">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Categoría</th>
                        <th>Presentaciones</th>
                        <th>Estado</th>
                        <th>Opciones</th>
                    </tr>
                </thead>

                <tbody>
                    <!-- CARGANDO -->
                    <tr v-if="cargando">
                        <td colspan="8" class="mensaje-tabla">
                            Cargando productos...
                        </td>
                    </tr>

                    <template v-else>

                        <!-- PRODUCTO Y SUS PRESENTACIONES -->
                        <template
                            v-for="producto in productos"
                            :key="producto.id"
                        >
                            <!-- FILA PRINCIPAL -->
                            <tr>
                                <td>{{ producto.id }}</td>

                                <td>
                                    <strong>{{ producto.nombre }}</strong>
                                </td>

                                <td>
                                    {{ producto.descripcion || '—' }}
                                </td>

                                <td>
                                    {{ producto.categoria?.nombre || 'Sin categoría' }}
                                </td>

                                <!-- BOTÓN DESPLEGABLE -->
                                <td>
                                    <button
                                        class="btn-presentaciones"
                                        @click="alternarPresentaciones(producto.id)"
                                    >
                                        {{ producto.presentaciones?.length || 0 }}
                                        presentaciones

                                        <span class="flecha">
                                            {{ productoExpandido === producto.id ? '▲' : '▼' }}
                                        </span>
                                    </button>
                                </td>

                                <!-- ESTADO -->
                                <td>
                                    <span
                                        class="etiqueta-estado"
                                        :class="producto.estado
                                            ? 'estado-activo'
                                            : 'estado-inactivo'"
                                    >
                                        {{ producto.estado ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>

                                <!-- OPCIONES -->
                                <td>
                                    <div class="acciones">

                                        <button
                                            class="btn-editar"
                                            @click="editarProducto(producto.id)"
                                        >
                                            Editar
                                        </button>

                                        <button
                                            :class="producto.estado
                                                ? 'btn-inactivar'
                                                : 'btn-activar'"
                                            @click="cambiarEstadoProducto(producto)"
                                        >
                                            {{ producto.estado ? 'Inactivar' : 'Activar' }}
                                        </button>

                                        <button
                                            class="btn-eliminar"
                                            @click="eliminarProducto(producto.id)"
                                        >
                                            Eliminar
                                        </button>

                                    </div>
                                </td>
                            </tr>

                            <!-- PRESENTACIONES DESPLEGABLES -->
                            <tr v-if="productoExpandido === producto.id">
                                <td colspan="8" class="detalle-presentaciones">

                                    <div class="encabezado-presentaciones">
                                        <h4>
                                            Presentaciones de {{ producto.nombre }}
                                        </h4>

                                        <span class="contador-presentaciones">
                                            {{ producto.presentaciones?.length || 0 }}
                                            registradas
                                        </span>
                                    </div>

                                    <div class="tabla-presentaciones-contenedor">
                                        <table class="tabla-presentaciones">
                                            <thead>
                                                <tr>
                                                    <th>SKU</th>
                                                    <th>Presentación</th>
                                                    <th>Imagen</th>
                                                    <th>Peso</th>
                                                    <th>Envase</th>
                                                    <th>Unidades/paquete</th>
                                                    <th>Precio unitario</th>
                                                    <th>Precio paquete</th>
                                                    <th>Estado</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                <tr
                                                    v-for="presentacion in producto.presentaciones || []"
                                                    :key="presentacion.id_presentacion"
                                                >
                                                    <td>
                                                        {{ presentacion.codigo_sku }}
                                                    </td>

                                                    <td>
                                                        <strong>
                                                            {{ presentacion.nombre_presentacion }}
                                                        </strong>
                                                    </td>
                                                    <td>
                                                        <div v-if="presentacion.imagen">
                                                            <img
                                                                :src="`http://localhost:8000/storage/${presentacion.imagen}`"
                                                                alt="Imagen de la presentación"
                                                                class="imagen-producto"
                                                            />
                                                        </div>
                                                        <div v-else class="sin-imagen">
                                                            Sin imagen
                                                        </div>
                                                    </td> 
                                                    <td>
                                                        {{ presentacion.peso_neto }}
                                                        {{ presentacion.unidad_medida }}
                                                    </td>

                                                    <td>
                                                        {{ presentacion.tipo_envase }}
                                                    </td>

                                                    <td>
                                                        {{ presentacion.unidades_por_paquete }}
                                                    </td>

                                                    <td>
                                                        {{
                                                            presentacion.precio_unitario != null
                                                                ? 'Bs ' + presentacion.precio_unitario
                                                                : '—'
                                                        }}
                                                    </td>

                                                    <td>
                                                        {{
                                                            presentacion.precio_paquete != null
                                                                ? 'Bs ' + presentacion.precio_paquete
                                                                : '—'
                                                        }}
                                                    </td>

                                                    <td>
                                                        <span
                                                            class="etiqueta-estado"
                                                            :class="presentacion.estado
                                                                ? 'estado-activo'
                                                                : 'estado-inactivo'"
                                                        >
                                                            {{
                                                                presentacion.estado
                                                                    ? 'Activo'
                                                                    : 'Inactivo'
                                                            }}
                                                        </span>
                                                    </td>
                                                </tr>

                                                <!-- SIN PRESENTACIONES -->
                                                <tr v-if="!producto.presentaciones?.length">
                                                    <td
                                                        colspan="8"
                                                        class="mensaje-tabla"
                                                    >
                                                        Este producto todavía no tiene
                                                        presentaciones registradas.
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                </td>
                            </tr>

                        </template>

                        <!-- SIN PRODUCTOS -->
                        <tr v-if="productos.length === 0">
                            <td colspan="8" class="mensaje-tabla">
                                No se encontraron productos registrados.
                            </td>
                        </tr>

                    </template>
                </tbody>
            </table>
        </div>

        <!-- PAGINACIÓN -->
        <div class="paginacion">

            <button
                @click="paginaAnterior"
                :disabled="pagination.current_page <= 1 || cargando"
            >
                Anterior
            </button>

            <span>
                Página {{ pagination.current_page }}
                de {{ pagination.last_page }}
            </span>

            <button
                @click="paginaSiguiente"
                :disabled="pagination.current_page >= pagination.last_page || cargando"
            >
                Siguiente
            </button>

        </div>

    </div>
</template>

<script>
import axios from 'axios';

export default {

    data() {
        return {
            productos: [],
            productoExpandido: null,
            cargando: false,

            pagination: {
                current_page: 1,
                last_page: 1,
                per_page: 10,
                total: 0
            }
        };
    },

    mounted() {
        this.productosGet();
    },

    methods: {

        // MOSTRAR U OCULTAR PRESENTACIONES
        alternarPresentaciones(id) {
            this.productoExpandido =
                this.productoExpandido === id ? null : id;
        },

        // OBTENER PRODUCTOS
        productosGet() {

            this.cargando = true;

            axios.get('http://localhost:8000/api/productos', {
                params: {
                    limit: this.pagination.per_page,
                    page: this.pagination.current_page
                }
            })
            .then(response => {

                this.productos = response.data.data;

                this.pagination.current_page =
                    response.data.current_page;

                this.pagination.last_page =
                    response.data.last_page;

                this.pagination.total =
                    response.data.total;

                // Cerrar el detalle al cambiar de página
                this.productoExpandido = null;

            })
            .catch(error => {

                console.error('Error al obtener productos:', error);

                alert('No se pudieron cargar los productos.');

            })
            .finally(() => {

                this.cargando = false;

            });
        },

        // CREAR PRODUCTO
        crearProducto() {
            this.$router.push('/productos/crear');
        },

        // EDITAR PRODUCTO
        editarProducto(id) {
            this.$router.push(`/productos/editar/${id}`);
        },

        // ACTIVAR O INACTIVAR PRODUCTO
        cambiarEstadoProducto(producto) {

            const nuevoEstado = !Boolean(producto.estado);

            const accion = nuevoEstado
                ? 'activar'
                : 'inactivar';

            if (!confirm(
                `¿Está seguro de ${accion} el producto ${producto.nombre}?`
            )) {
                return;
            }

            axios.post(
                `http://localhost:8000/api/productos/${producto.id}/estado`,
                {
                    estado: nuevoEstado
                }
            )
            .then(response => {

                alert(response.data.message);

                producto.estado =
                    Boolean(response.data.producto.estado);

            })
            .catch(error => {

                alert(
                    error.response?.data?.message ||
                    'Error al cambiar el estado del producto.'
                );

            });
        },

        // ELIMINAR PRODUCTO (SOFT DELETE)
        eliminarProducto(id) {

            if (!confirm(
                '¿Está seguro de enviar este producto a la papelera?'
            )) {
                return;
            }

            axios.delete(
                `http://localhost:8000/api/productos/${id}`
            )
            .then(response => {

                alert(response.data.message);

                if (
                    this.productos.length === 1 &&
                    this.pagination.current_page > 1
                ) {
                    this.pagination.current_page--;
                }

                this.productosGet();

            })
            .catch(error => {

                if (error.response?.status === 422) {

                    alert(
                        error.response.data.message ||
                        'No se puede eliminar este producto porque tiene registros relacionados.'
                    );

                } else {

                    alert(
                        error.response?.data?.message ||
                        'Ocurrió un error al eliminar el producto.'
                    );

                }

            });
        },

        // PÁGINA ANTERIOR
        paginaAnterior() {

            if (this.pagination.current_page > 1) {

                this.pagination.current_page--;

                this.productosGet();

            }
        },

        // PÁGINA SIGUIENTE
        paginaSiguiente() {

            if (
                this.pagination.current_page <
                this.pagination.last_page
            ) {

                this.pagination.current_page++;

                this.productosGet();

            }
        }

    }
};
</script>

<style scoped>
/* CONTENEDOR PRINCIPAL */
.contenedor-productos {
    width: 100%;
}

/* ENCABEZADO */
.encabezado {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 25px;
}

.encabezado h1 {
    margin: 0 0 5px;
    color: #1f2937;
}

.encabezado p {
    margin: 0;
    color: #6b7280;
    font-size: 14px;
}

.btn-crear {
    background: #2563eb;
    color: white;
    border: none;
    padding: 10px 18px;
    border-radius: 6px;
    cursor: pointer;
}

.btn-crear:hover {
    background: #1d4ed8;
}

/* BARRA SUPERIOR */
.barra-superior {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    color: #4b5563;
    font-size: 14px;
}

/* TABLA */
.tabla-contenedor {
    width: 100%;
    overflow-x: auto;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
}

.tabla-productos,
.tabla-presentaciones {
    width: 100%;
    border-collapse: collapse;
}

.tabla-productos th,
.tabla-productos td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
    font-size: 13px;
}

.tabla-productos > thead > tr > th {
    background: #f3f4f6;
    color: #374151;
    font-weight: 600;
}

.tabla-productos > tbody > tr:hover {
    background: #f9fafb;
}

/* IMÁGENES */
.imagen-producto {
    max-width: 55px;
    max-height: 65px;
    object-fit: contain;
    border-radius: 5px;
}

.sin-imagen {
    color: #9ca3af;
    font-size: 12px;
}

/* ESTADOS */
.etiqueta-estado {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
}

.estado-activo {
    background: #dcfce7;
    color: #166534;
}

.estado-inactivo {
    background: #fee2e2;
    color: #991b1b;
}

/* BOTONES DE ACCIÓN */
.acciones {
    display: flex;
    gap: 7px;
    flex-wrap: wrap;
}

.acciones button {
    border: none;
    padding: 7px 11px;
    border-radius: 5px;
    color: white;
    cursor: pointer;
}

.btn-editar {
    background: #2563eb;
}

.btn-activar {
    background: #16a34a;
}

.btn-inactivar {
    background: #d97706;
}

.btn-eliminar {
    background: #dc2626;
}

/* BOTÓN PRESENTACIONES */
.btn-presentaciones {
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
    border-radius: 6px;
    padding: 8px 11px;
    cursor: pointer;
    font-weight: 600;
    white-space: nowrap;
}

.btn-presentaciones:hover {
    background: #dbeafe;
}

.flecha {
    margin-left: 6px;
}

/* DETALLE DE PRESENTACIONES */
.tabla-productos > tbody > tr > td.detalle-presentaciones {
    background: #f8fafc;
    padding: 20px;
}

.encabezado-presentaciones {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
}

.encabezado-presentaciones h4 {
    margin: 0;
    color: #334155;
}

.contador-presentaciones {
    background: #e2e8f0;
    color: #475569;
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 12px;
}

.tabla-presentaciones-contenedor {
    overflow-x: auto;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
}

.tabla-presentaciones {
    background: white;
}

.tabla-presentaciones th,
.tabla-presentaciones td {
    padding: 11px;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
    font-size: 13px;
}

.tabla-presentaciones th {
    background: #e2e8f0;
    color: #334155;
}

.tabla-presentaciones tbody tr:hover {
    background: #f8fafc;
}

/* MENSAJES */
.mensaje-tabla {
    text-align: center;
    padding: 25px;
    color: #6b7280;
}

/* PAGINACIÓN */
.paginacion {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 15px;
    margin-top: 20px;
}

.paginacion button {
    padding: 8px 14px;
    border: 1px solid #d1d5db;
    border-radius: 5px;
    background: white;
    cursor: pointer;
}

.paginacion button:hover:not(:disabled) {
    background: #f3f4f6;
}

.paginacion button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* RESPONSIVE */
@media (max-width: 700px) {
    .encabezado,
    .barra-superior,
    .encabezado-presentaciones {
        flex-direction: column;
        align-items: flex-start;
    }

    .paginacion {
        gap: 8px;
    }
}
</style>
