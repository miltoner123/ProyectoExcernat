<template>
    <div class="ubicaciones">

        <!-- ENCABEZADO -->

        <div class="encabezado">
            <div>
                <h1>Gestión de Ubicaciones</h1>
                <p>
                    Administración de almacenes, sucursales y stands.
                </p>
            </div>

            <button @click="crearUbicacion">
                + Nueva Ubicación
            </button>
        </div>


        <!-- BUSCADOR Y FILTROS -->

        <div class="filtros">

            <input
                type="text"
                v-model="buscar"
                @input="buscarUbicaciones"
                placeholder="Buscar por nombre, departamento o dirección..."
            >

            <select
                v-model="tipo"
                @change="filtrarUbicaciones"
            >
                <option value="">Todos los tipos</option>
                <option value="ALMACEN">Almacenes</option>
                <option value="SUCURSAL">Sucursales</option>
                <option value="STAND">Stands</option>
            </select>

            <select
                v-model="estado"
                @change="filtrarUbicaciones"
            >
                <option value="">Todos los estados</option>
                <option value="true">Activos</option>
                <option value="false">Inactivos</option>
            </select>

            <select
                v-model.number="pagination.per_page"
                @change="cambiarLimite"
            >
                <option :value="10">10 registros</option>
                <option :value="25">25 registros</option>
                <option :value="50">50 registros</option>
            </select>

        </div>


        <!-- TABLA -->

        <div class="tabla-contenedor">

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Propiedad</th>
                        <th>Departamento</th>
                        <th>Dirección</th>
                        <th>Evento</th>
                        <th>Estado</th>
                        <th>Opciones</th>
                    </tr>
                </thead>

                <tbody>

                    <tr
                        v-for="ubicacion in ubicaciones"
                        :key="ubicacion.id_ubicacion"
                    >

                        <td>
                            {{ ubicacion.id_ubicacion }}
                        </td>

                        <td>
                            {{ ubicacion.nombre }}
                        </td>

                        <td>
                            {{ ubicacion.tipo }}
                        </td>

                        <td>
                            {{ ubicacion.propiedad }}
                        </td>

                        <td>
                            {{ ubicacion.departamento }}
                        </td>

                        <td>
                            {{ ubicacion.direccion || '—' }}
                        </td>

                        <td>
                            {{ ubicacion.evento?.nombre || '—' }}
                        </td>

                        <td>
                            <span
                                :class="ubicacion.estado
                                    ? 'estado-activo'
                                    : 'estado-inactivo'"
                            >
                                {{ ubicacion.estado ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>

                        <td>
                            <div class="acciones">

                                <button
                                    @click="editarUbicacion(ubicacion.id_ubicacion)"
                                >
                                    Editar
                                </button>

                                <button
                                    class="btn-eliminar"
                                    @click="eliminarUbicacion(ubicacion.id_ubicacion)"
                                >
                                    Eliminar
                                </button>

                            </div>
                        </td>

                    </tr>

                    <tr v-if="!cargando && ubicaciones.length === 0">
                        <td colspan="9">
                            No se encontraron ubicaciones.
                        </td>
                    </tr>

                    <tr v-if="cargando">
                        <td colspan="9">
                            Cargando ubicaciones...
                        </td>
                    </tr>

                </tbody>
            </table>

        </div>


        <!-- PAGINACIÓN -->

        <div class="paginacion">

            <span>
                Total: {{ pagination.total }} registros
            </span>

            <div class="controles">

                <button
                    @click="paginaAnterior"
                    :disabled="pagination.current_page <= 1"
                >
                    Anterior
                </button>

                <span>
                    Página {{ pagination.current_page }}
                    de {{ pagination.last_page }}
                </span>

                <button
                    @click="paginaSiguiente"
                    :disabled="
                        pagination.current_page >= pagination.last_page
                    "
                >
                    Siguiente
                </button>

            </div>

        </div>

    </div>
</template>


<script>
import axios from 'axios';

export default {

    data() {
        return {
            ubicaciones: [],

            buscar: '',
            tipo: '',
            estado: '',

            cargando: false,
            temporizadorBusqueda: null,
            numeroPeticion: 0,

            pagination: {
                current_page: 1,
                last_page: 1,
                per_page: 10,
                total: 0
            }
        };
    },

    mounted() {
        this.ubicacionesGet();
    },

    beforeUnmount() {
        clearTimeout(this.temporizadorBusqueda);
    },

    methods: {

        ubicacionesGet() {

            const peticionActual = ++this.numeroPeticion;

            this.cargando = true;

            axios.get(
                'http://localhost:8000/api/ubicaciones',
                {
                    params: {
                        page: this.pagination.current_page,
                        limit: this.pagination.per_page,
                        buscar: this.buscar,
                        tipo: this.tipo,
                        estado: this.estado
                    }
                }
            )

            .then(response => {

                if (peticionActual !== this.numeroPeticion) {
                    return;
                }

                this.ubicaciones = response.data.data;

                this.pagination.current_page =
                    response.data.current_page;

                this.pagination.last_page =
                    response.data.last_page;

                this.pagination.total =
                    response.data.total;

            })

            .catch(error => {

                if (peticionActual !== this.numeroPeticion) {
                    return;
                }

                console.error(
                    'Error al obtener ubicaciones:',
                    error
                );

                alert('No se pudieron cargar las ubicaciones.');

            })

            .finally(() => {

                if (peticionActual === this.numeroPeticion) {
                    this.cargando = false;
                }

            });
        },


        buscarUbicaciones() {

            clearTimeout(this.temporizadorBusqueda);

            this.temporizadorBusqueda = setTimeout(() => {

                this.pagination.current_page = 1;

                this.ubicacionesGet();

            }, 400);
        },


        filtrarUbicaciones() {

            this.pagination.current_page = 1;

            this.ubicacionesGet();
        },


        cambiarLimite() {

            this.pagination.current_page = 1;

            this.ubicacionesGet();
        },


        paginaAnterior() {

            if (this.pagination.current_page > 1) {

                this.pagination.current_page--;

                this.ubicacionesGet();
            }
        },


        paginaSiguiente() {

            if (
                this.pagination.current_page <
                this.pagination.last_page
            ) {

                this.pagination.current_page++;

                this.ubicacionesGet();
            }
        },


        crearUbicacion() {

            this.$router.push('/ubicaciones/crear');
        },


        editarUbicacion(id) {

            this.$router.push(`/ubicaciones/editar/${id}`);
        },


        eliminarUbicacion(id) {

            if (!confirm('¿Está seguro de eliminar esta ubicación?')) {
                return;
            }

            axios.delete(
                `http://localhost:8000/api/ubicaciones/${id}`
            )

            .then(() => {

                alert('Ubicación eliminada correctamente.');

                this.ubicacionesGet();

            })

            .catch(error => {

                alert(
                    error.response?.data?.message ||
                    'No se pudo eliminar la ubicación.'
                );

            });
        }

    }
};
</script>


<style scoped>

.encabezado {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    gap: 15px;
}

.encabezado p {
    color: #777;
    margin-top: 5px;
}

.filtros {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 20px;
}

.filtros input {
    flex: 2;
    min-width: 220px;
}

.filtros select {
    flex: 1;
    min-width: 140px;
}

.filtros input,
.filtros select {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
}

.tabla-contenedor {
    width: 100%;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th,
td {
    padding: 12px;
    border-bottom: 1px solid #e5e5e5;
    text-align: left;
}

.acciones {
    display: flex;
    gap: 8px;
}

.estado-activo {
    color: #15803d;
    font-weight: 600;
}

.estado-inactivo {
    color: #b91c1c;
    font-weight: 600;
}

.btn-eliminar {
    background: #dc3545;
    color: white;
}

.paginacion {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    margin-top: 20px;
    gap: 12px;
}

.controles {
    display: flex;
    align-items: center;
    gap: 12px;
}

button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

</style>