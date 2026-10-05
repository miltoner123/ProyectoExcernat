
<template>
    <div class="inventarios">

        <div class="encabezado">
            <div>
                <h1>Gestión de Inventarios</h1>
                <p>Control de existencias por ubicación y lote.</p>
            </div>

            <button @click="crearInventario">
                + Nuevo Inventario
            </button>
        </div>

        <!-- FILTROS -->

        <div class="filtros">

            <input
                type="text"
                v-model="buscar"
                @input="buscarInventarios"
                placeholder="Buscar producto, lote o ubicación..."
            >

            <select
                v-model="id_ubicacion"
                @change="filtrarInventarios"
            >
                <option value="">Todas las ubicaciones</option>

                <option
                    v-for="ubicacion in ubicaciones"
                    :key="ubicacion.id_ubicacion"
                    :value="ubicacion.id_ubicacion"
                >
                    {{ ubicacion.nombre }}
                </option>
            </select>

            <select
                v-model="stock_bajo"
                @change="filtrarInventarios"
            >
                <option value="">Todos los inventarios</option>
                <option value="true">Stock bajo</option>
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
                        <th>Producto</th>
                        <th>Presentación</th>
                        <th>Lote</th>
                        <th>Ubicación</th>
                        <th>Stock actual</th>
                        <th>Stock mínimo</th>
                        <th>Vencimiento</th>
                        <th>Estado</th>
                        <th>Opciones</th>
                    </tr>
                </thead>

                <tbody>

                    <tr
                        v-for="inventario in inventarios"
                        :key="inventario.id_inventario"
                    >

                        <td>{{ inventario.id_inventario }}</td>

                        <td>
                            {{ inventario.lote?.presentacion?.producto?.nombre || '—' }}
                        </td>

                        <td>
                            {{ inventario.lote?.presentacion?.nombre_presentacion || '—' }}
                        </td>

                        <td>
                            {{ inventario.lote?.codigo_lote || '—' }}
                        </td>

                        <td>
                            {{ inventario.ubicacion?.nombre || '—' }}
                        </td>

                        <td>
                            {{ inventario.stock_actual }}
                        </td>

                        <td>
                            {{ inventario.stock_minimo }}
                        </td>

                        <td>
                            {{ inventario.lote?.fecha_vencimiento || '—' }}
                        </td>

                        <td>
                            <span
                                :class="inventario.stock_actual <= inventario.stock_minimo
                                    ? 'stock-bajo'
                                    : 'stock-normal'"
                            >
                                {{
                                    inventario.stock_actual <= inventario.stock_minimo
                                        ? 'Stock bajo'
                                        : 'Normal'
                                }}
                            </span>
                        </td>

                        <td>
                            <button
                                @click="editarInventario(inventario.id_inventario)"
                            >
                                Editar mínimo
                            </button>
                        </td>

                    </tr>

                    <tr v-if="!cargando && inventarios.length === 0">
                        <td colspan="10">
                            No se encontraron registros de inventario.
                        </td>
                    </tr>

                    <tr v-if="cargando">
                        <td colspan="10">
                            Cargando inventarios...
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
                    :disabled="pagination.current_page >= pagination.last_page"
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
            inventarios: [],
            ubicaciones: [],

            buscar: '',
            id_ubicacion: '',
            stock_bajo: '',

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
        this.inventariosGet();
        this.ubicacionesGet();
    },

    beforeUnmount() {
        clearTimeout(this.temporizadorBusqueda);
    },

    methods: {

        inventariosGet() {

            const peticionActual = ++this.numeroPeticion;

            this.cargando = true;

            axios.get(
                'http://localhost:8000/api/inventarios',
                {
                    params: {
                        page: this.pagination.current_page,
                        limit: this.pagination.per_page,
                        buscar: this.buscar,
                        id_ubicacion: this.id_ubicacion,
                        stock_bajo: this.stock_bajo
                    }
                }
            )

            .then(response => {

                if (peticionActual !== this.numeroPeticion) {
                    return;
                }

                this.inventarios = response.data.data;

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
                    'Error al obtener inventarios:',
                    error
                );

                alert('No se pudieron cargar los inventarios.');

            })

            .finally(() => {

                if (peticionActual === this.numeroPeticion) {
                    this.cargando = false;
                }

            });
        },


        ubicacionesGet() {

            axios.get(
                'http://localhost:8000/api/ubicaciones',
                {
                    params: {
                        limit: 50
                    }
                }
            )

            .then(response => {

                this.ubicaciones = response.data.data;

            })

            .catch(error => {

                console.error(
                    'Error al obtener ubicaciones:',
                    error
                );

            });
        },


        buscarInventarios() {

            clearTimeout(this.temporizadorBusqueda);

            this.temporizadorBusqueda = setTimeout(() => {

                this.pagination.current_page = 1;

                this.inventariosGet();

            }, 400);
        },


        filtrarInventarios() {

            this.pagination.current_page = 1;

            this.inventariosGet();
        },


        cambiarLimite() {

            this.pagination.current_page = 1;

            this.inventariosGet();
        },


        paginaAnterior() {

            if (this.pagination.current_page > 1) {

                this.pagination.current_page--;

                this.inventariosGet();
            }
        },


        paginaSiguiente() {

            if (
                this.pagination.current_page <
                this.pagination.last_page
            ) {

                this.pagination.current_page++;

                this.inventariosGet();
            }
        },


        crearInventario() {

            this.$router.push('/inventarios/crear');
        },


        editarInventario(id) {

            this.$router.push(`/inventarios/editar/${id}`);
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

.stock-bajo {
    color: #b91c1c;
    font-weight: 600;
}

.stock-normal {
    color: #15803d;
    font-weight: 600;
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
