<template>
    <div class="cargos">

        <div class="encabezado">
            <div>
                <h1>Gestión de Cargos</h1>
                <p>Administración de los cargos del personal de EXCERNAT.</p>
            </div>

            <button @click="crearCargo">
                + Nuevo Cargo
            </button>
        </div>

        <div class="filtros">

            <input
                type="text"
                v-model="buscar"
                @input="buscarCargos"
                placeholder="Buscar por nombre o descripción..."
            >

            <select v-model="estado" @change="filtrar">
                <option value="">Todos los estados</option>
                <option value="true">Activos</option>
                <option value="false">Inactivos</option>
            </select>

            <select v-model.number="pagination.per_page" @change="cambiarLimite">
                <option :value="10">10 registros</option>
                <option :value="25">25 registros</option>
                <option :value="50">50 registros</option>
            </select>

        </div>

        <div class="tabla-contenedor">

            <table>

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cargo</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Opciones</th>
                    </tr>
                </thead>

                <tbody>

                    <tr
                        v-for="cargo in cargos"
                        :key="cargo.id_cargo"
                    >
                        <td>{{ cargo.id_cargo }}</td>

                        <td>
                            <strong>{{ cargo.nombre }}</strong>
                        </td>

                        <td>
                            {{ cargo.descripcion || '—' }}
                        </td>

                        <td>
                            <span
                                :class="cargo.estado
                                    ? 'estado-activo'
                                    : 'estado-inactivo'"
                            >
                                {{ cargo.estado ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>

                        <td>
                            <div class="acciones">

                                <button @click="editarCargo(cargo.id_cargo)">
                                    Editar
                                </button>

                                <button
                                    class="btn-eliminar"
                                    @click="eliminarCargo(cargo)"
                                >
                                    Eliminar
                                </button>

                            </div>
                        </td>
                    </tr>

                    <tr v-if="!cargando && cargos.length === 0">
                        <td colspan="5" class="sin-registros">
                            No se encontraron cargos.
                        </td>
                    </tr>

                    <tr v-if="cargando">
                        <td colspan="5" class="sin-registros">
                            Cargando cargos...
                        </td>
                    </tr>

                </tbody>

            </table>

        </div>

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
            cargos: [],
            buscar: '',
            estado: '',
            cargando: false,
            temporizadorBusqueda: null,

            pagination: {
                current_page: 1,
                last_page: 1,
                per_page: 10,
                total: 0
            }
        };
    },

    mounted() {
        this.cargosGet();
    },

    beforeUnmount() {
        clearTimeout(this.temporizadorBusqueda);
    },

    methods: {

        cargosGet() {

            this.cargando = true;

            axios.get(
                'http://localhost:8000/api/cargos',
                {
                    params: {
                        page: this.pagination.current_page,
                        limit: this.pagination.per_page,
                        buscar: this.buscar,
                        estado: this.estado
                    }
                }
            )
            .then(response => {

                this.cargos = response.data.data;

                this.pagination.current_page =
                    response.data.current_page;

                this.pagination.last_page =
                    response.data.last_page;

                this.pagination.total =
                    response.data.total;

            })
            .catch(error => {

                console.error(
                    'Error al obtener cargos:',
                    error
                );

            })
            .finally(() => {

                this.cargando = false;

            });

        },


        buscarCargos() {

            clearTimeout(this.temporizadorBusqueda);

            this.temporizadorBusqueda = setTimeout(() => {

                this.pagination.current_page = 1;

                this.cargosGet();

            }, 400);

        },


        filtrar() {

            this.pagination.current_page = 1;

            this.cargosGet();

        },


        cambiarLimite() {

            this.pagination.current_page = 1;

            this.cargosGet();

        },


        paginaAnterior() {

            if (this.pagination.current_page > 1) {
                this.pagination.current_page--;
                this.cargosGet();
            }

        },


        paginaSiguiente() {

            if (
                this.pagination.current_page <
                this.pagination.last_page
            ) {
                this.pagination.current_page++;
                this.cargosGet();
            }

        },


        crearCargo() {

            this.$router.push('/cargos/crear');

        },


        editarCargo(id) {

            this.$router.push(`/cargos/editar/${id}`);

        },


        eliminarCargo(cargo) {

            if (
                !confirm(
                    `¿Está seguro de eliminar el cargo "${cargo.nombre}"?`
                )
            ) {
                return;
            }

            axios.delete(
                `http://localhost:8000/api/cargos/${cargo.id_cargo}`
            )
            .then(response => {

                alert(response.data.message);

                this.cargosGet();

            })
            .catch(error => {

                alert(
                    error.response?.data?.message ||
                    'No se pudo eliminar el cargo.'
                );

            });

        }

    }

};
</script>

<style scoped>

.cargos {
    width: 100%;
}

.encabezado {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.encabezado h1 {
    margin: 0 0 5px;
    color: #1f2937;
}

.encabezado p {
    margin: 0;
    color: #6b7280;
}

.encabezado button {
    background-color: #2563eb;
    color: white;
    border: none;
    padding: 11px 18px;
    border-radius: 6px;
    cursor: pointer;
}

.filtros {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.filtros input,
.filtros select {
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
}

.filtros input {
    min-width: 300px;
    flex: 1;
}

.tabla-contenedor {
    background-color: white;
    border-radius: 8px;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th,
td {
    padding: 12px;
    border-bottom: 1px solid #e5e7eb;
    text-align: left;
    font-size: 14px;
}

th {
    background-color: #f9fafb;
    color: #374151;
}

.estado-activo {
    background-color: #dcfce7;
    color: #166534;
    padding: 5px 8px;
    border-radius: 5px;
}

.estado-inactivo {
    background-color: #fee2e2;
    color: #991b1b;
    padding: 5px 8px;
    border-radius: 5px;
}

.acciones {
    display: flex;
    gap: 5px;
}

.acciones button {
    border: none;
    padding: 7px 10px;
    border-radius: 5px;
    cursor: pointer;
    background-color: #2563eb;
    color: white;
}

.acciones .btn-eliminar {
    background-color: #dc2626;
}

.sin-registros {
    text-align: center;
    padding: 25px;
    color: #6b7280;
}

.paginacion {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 20px;
}

.controles {
    display: flex;
    align-items: center;
    gap: 10px;
}

.controles button {
    padding: 8px 12px;
    cursor: pointer;
}

.controles button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

</style>