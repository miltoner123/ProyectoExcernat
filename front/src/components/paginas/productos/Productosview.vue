
<template>
    <div class="contenedor-productos">

        <!-- ENCABEZADO -->
        <div class="encabezado">
            <div>
                <h1>Productos</h1>
                <p>Gestión y administración de productos</p>
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

        <!-- TABLA -->
        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Imagen</th>
                        <th>Categoría</th>
                        <th>Estado</th>
                        <th>Opciones</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-if="cargando">
                        <td colspan="7" class="mensaje-tabla">
                            Cargando productos...
                        </td>
                    </tr>

                    <template v-else>
                        <tr
                            v-for="producto in productos"
                            :key="producto.id"
                        >
                            <td>{{ producto.id }}</td>

                            <td>
                                <strong>{{ producto.nombre }}</strong>
                            </td>

                            <td>{{ producto.descripcion || '—' }}</td>

                            <td>
                                <img
                                    v-if="producto.imagen"
                                    :src="'http://localhost:8000/imagenes/' + producto.imagen"
                                    alt="Imagen del producto"
                                    class="imagen-producto"
                                >

                                <span v-else class="sin-imagen">
                                    Sin imagen
                                </span>
                            </td>

                            <td>
                                {{ producto.categoria?.nombre || 'Sin categoría' }}
                            </td>

                            <td>
                                <span
                                    class="etiqueta-estado"
                                    :class="producto.estado ? 'estado-activo' : 'estado-inactivo'"
                                >
                                    {{ producto.estado ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>

                            <td>
                                <div class="acciones">

                                    <button
                                        class="btn-editar"
                                        @click="editarProducto(producto.id)"
                                    >
                                        Editar
                                    </button>

                                    <button
                                        :class="producto.estado ? 'btn-inactivar' : 'btn-activar'"
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

                        <tr v-if="productos.length === 0">
                            <td colspan="7" class="mensaje-tabla">
                                No se encontraron productos.
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

                this.pagination.current_page = response.data.current_page;
                this.pagination.last_page = response.data.last_page;
                this.pagination.total = response.data.total;

            })
            .catch(error => {

                console.error('Error al obtener productos:', error);
                alert('No se pudieron cargar los productos.');

            })
            .finally(() => {

                this.cargando = false;

            });
        },

        crearProducto() {
            this.$router.push('/productos/crear');
        },

        editarProducto(id) {
            this.$router.push(`/productos/editar/${id}`);
        },

        cambiarEstadoProducto(producto) {

            const nuevoEstado = !Boolean(producto.estado);
            const accion = nuevoEstado ? 'activar' : 'inactivar';

            if (!confirm(`¿Está seguro de ${accion} el producto ${producto.nombre}?`)) {
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

                producto.estado = Boolean(response.data.producto.estado);

            })
            .catch(error => {

                alert(
                    error.response?.data?.message ||
                    'Error al cambiar el estado del producto.'
                );

            });
        },

        eliminarProducto(id) {

            if (!confirm('¿Está seguro de enviar este producto a la papelera?')) {
                return;
            }

            axios.delete(`http://localhost:8000/api/productos/${id}`)

            .then(response => {

                alert(response.data.message);

                if (this.productos.length === 1 && this.pagination.current_page > 1) {
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

        paginaAnterior() {

            if (this.pagination.current_page > 1) {
                this.pagination.current_page--;
                this.productosGet();
            }

        },

        paginaSiguiente() {

            if (this.pagination.current_page < this.pagination.last_page) {
                this.pagination.current_page++;
                this.productosGet();
            }

        }

    }
};
</script>

<style scoped>
.contenedor-productos {
    width: 100%;
}

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

.barra-superior {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    color: #4b5563;
    font-size: 14px;
}

.tabla-contenedor {
    width: 100%;
    overflow-x: auto;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th,
td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
    font-size: 13px;
}

th {
    background: #f3f4f6;
    color: #374151;
    font-weight: 600;
}

tbody tr:hover {
    background: #f9fafb;
}

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

.etiqueta-estado {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}

.estado-activo {
    background: #dcfce7;
    color: #166534;
}

.estado-inactivo {
    background: #fee2e2;
    color: #991b1b;
}

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

.mensaje-tabla {
    text-align: center;
    padding: 25px;
    color: #6b7280;
}

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

.paginacion button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

@media (max-width: 700px) {
    .encabezado,
    .barra-superior {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>
