
<template>
    <div class="contenedor">

        <div class="encabezado">
            <div>
                <h1>Presentaciones</h1>
                <p>Gestión de presentaciones comerciales de productos</p>
            </div>

            <button class="btn-crear" @click="crearPresentacion">
                + Crear Presentación
            </button>
        </div>

        <div class="resumen">
            <span>Total de presentaciones: {{ pagination.total }}</span>
            <span>Página {{ pagination.current_page }} de {{ pagination.last_page }}</span>
        </div>

        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Producto</th>
                        <th>SKU</th>
                        <th>Presentación</th>
                        <th>Imagen</th>
                        <th>Peso</th>
                        <th>Envase</th>
                        <th>U./Paquete</th>
                        <th>Precio Unitario</th>
                        <th>Precio Paquete</th>
                        <th>Estado</th>
                        <th>Opciones</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-if="cargando">
                        <td colspan="12" class="mensaje">
                            Cargando presentaciones...
                        </td>
                    </tr>

                    <template v-else>
                        <tr
                            v-for="presentacion in presentaciones"
                            :key="presentacion.id_presentacion"
                        >
                            <td>{{ presentacion.id_presentacion }}</td>

                            <td>
                                {{ presentacion.producto?.nombre || 'Sin producto' }}
                            </td>

                            <td>{{ presentacion.codigo_sku }}</td>

                            <td>{{ presentacion.nombre_presentacion }}</td>
                            <td>
                                <img
                                    v-if="presentacion.imagen"
                                    :src="'http://localhost:8000/storage/' + presentacion.imagen"
                                    alt="Fotografía de la presentación"
                                    class="imagen-presentacion"
                                >

                                <span v-else class="sin-imagen">
                                    Sin imagen
                                </span>
                            </td>
                            <td>
                                {{ presentacion.peso_neto }}
                                {{ presentacion.unidad_medida }}
                            </td>

                            <td>{{ presentacion.tipo_envase }}</td>

                            <td>{{ presentacion.unidades_por_paquete }}</td>

                            <td>Bs {{ presentacion.precio_unitario }}</td>

                            <td>
                                {{ presentacion.precio_paquete !== null
                                    ? 'Bs ' + presentacion.precio_paquete
                                    : '—' }}
                            </td>

                            <td>
                                <span
                                    class="etiqueta-estado"
                                    :class="presentacion.estado ? 'activo' : 'inactivo'"
                                >
                                    {{ presentacion.estado ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>

                            <td>
                                <div class="acciones">
                                    <button
                                        class="btn-editar"
                                        @click="editarPresentacion(presentacion.id_presentacion)"
                                    >
                                        Editar
                                    </button>

                                    <button
                                        :class="presentacion.estado ? 'btn-inactivar' : 'btn-activar'"
                                        @click="cambiarEstadoPresentacion(presentacion)"
                                    >
                                        {{ presentacion.estado ? 'Inactivar' : 'Activar' }}
                                    </button>

                                    <button
                                        class="btn-eliminar"
                                        @click="eliminarPresentacion(presentacion.id_presentacion)"
                                    >
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="presentaciones.length === 0">
                            <td colspan="12" class="mensaje">
                                No se encontraron presentaciones.
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

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
            presentaciones: [],
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
        this.presentacionesGet();
    },

    methods: {

        presentacionesGet() {

            this.cargando = true;

            axios.get('http://localhost:8000/api/presentaciones', {
                params: {
                    limit: this.pagination.per_page,
                    page: this.pagination.current_page
                }
            })
            .then(response => {

                this.presentaciones = response.data.data;
                this.pagination.current_page = response.data.current_page;
                this.pagination.last_page = response.data.last_page;
                this.pagination.total = response.data.total;

            })
            .catch(error => {

                console.error('Error al obtener presentaciones:', error);
                alert('No se pudieron cargar las presentaciones.');

            })
            .finally(() => {

                this.cargando = false;

            });
        },

        crearPresentacion() {
            this.$router.push('/presentaciones/crear');
        },

        editarPresentacion(id) {
            this.$router.push(`/presentaciones/editar/${id}`);
        },

        cambiarEstadoPresentacion(presentacion) {

            const nuevoEstado = !Boolean(presentacion.estado);
            const accion = nuevoEstado ? 'activar' : 'inactivar';

            if (!confirm(`¿Está seguro de ${accion} la presentación ${presentacion.nombre_presentacion}?`)) {
                return;
            }

            axios.post(
                `http://localhost:8000/api/presentaciones/${presentacion.id_presentacion}/estado`,
                { estado: nuevoEstado }
            )
            .then(response => {

                alert(response.data.message);
                presentacion.estado = Boolean(response.data.presentacion.estado);

            })
            .catch(error => {

                alert(
                    error.response?.data?.message ||
                    'Error al cambiar el estado de la presentación.'
                );

            });
        },

        eliminarPresentacion(id) {

            if (!confirm('¿Está seguro de enviar esta presentación a la papelera?')) {
                return;
            }

            axios.delete(`http://localhost:8000/api/presentaciones/${id}`)
            .then(response => {

                alert(response.data.message);

                if (this.presentaciones.length === 1 && this.pagination.current_page > 1) {
                    this.pagination.current_page--;
                }

                this.presentacionesGet();

            })
            .catch(error => {

                alert(
                    error.response?.data?.message ||
                    'Error al eliminar la presentación.'
                );

            });
        },

        paginaAnterior() {

            if (this.pagination.current_page > 1) {
                this.pagination.current_page--;
                this.presentacionesGet();
            }

        },

        paginaSiguiente() {

            if (this.pagination.current_page < this.pagination.last_page) {
                this.pagination.current_page++;
                this.presentacionesGet();
            }

        }

    }
};
</script>

<style scoped>
.contenedor {
    width: 100%;
}
.imagen-presentacion {
    width: 65px;
    height: 65px;
    object-fit: contain;
    border-radius: 6px;
}
.encabezado {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}

.encabezado h1 {
    margin: 0 0 5px;
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
    border-radius: 6px;
    padding: 10px 15px;
    cursor: pointer;
}

.resumen {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
    color: #4b5563;
    font-size: 14px;
}

.tabla-contenedor {
    width: 100%;
    overflow-x: auto;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th,
td {
    padding: 11px;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
    font-size: 13px;
}

th {
    background: #f3f4f6;
    color: #374151;
}

.etiqueta-estado {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 6px;
    font-weight: 600;
}

.activo {
    background: #dcfce7;
    color: #166534;
}

.inactivo {
    background: #fee2e2;
    color: #991b1b;
}

.acciones {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.acciones button {
    border: none;
    border-radius: 5px;
    padding: 7px 10px;
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

.mensaje {
    text-align: center;
    padding: 20px;
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
</style>
