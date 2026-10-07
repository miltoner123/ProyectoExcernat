<template>
    <div class="personas">

        <div class="encabezado">
            <div>
                <h1>Gestión de Personas</h1>
                <p>Administración de personas registradas en el sistema.</p>
            </div>

            <button @click="crearPersona">
                + Nueva Persona
            </button>
        </div>

        <div class="filtros">

            <input
                type="text"
                v-model="buscar"
                @input="buscarPersonas"
                placeholder="Buscar por nombre, apellido o documento..."
            >

            <select v-model="tipo_persona" @change="filtrar">
                <option value="">Todos los tipos</option>
                <option value="PERSONAL">Personal</option>
                <option value="CLIENTE">Cliente</option>
                <option value="OTRO">Otro</option>
            </select>

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
                        <th>Documento</th>
                        <th>Nombre / Razón Social</th>
                        <th>Apellidos</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Opciones</th>
                    </tr>
                </thead>

                <tbody>

                    <tr
                        v-for="persona in personas"
                        :key="persona.id_persona"
                    >
                        <td>{{ persona.id_persona }}</td>

                        <td>
                            <span v-if="persona.numero_documento">
                                {{ persona.tipo_documento }}
                                {{ persona.numero_documento }}
                            </span>

                            <span v-else>
                                Sin documento
                            </span>
                        </td>

                        <td>
                            <strong>
                                {{ persona.nombres_razon_social }}
                            </strong>
                        </td>

                        <td>
                            {{ persona.apellidos || '—' }}
                        </td>

                        <td>
                            {{ persona.telefono || '—' }}
                        </td>

                        <td>
                            {{ persona.correo || '—' }}
                        </td>

                        <td>
                            {{ persona.tipo_persona }}
                        </td>

                        <td>
                            <span
                                :class="persona.estado
                                    ? 'estado-activo'
                                    : 'estado-inactivo'"
                            >
                                {{ persona.estado ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>

                        <td>
                            <div class="acciones">

                                <button
                                    @click="editarPersona(persona.id_persona)"
                                >
                                    Editar
                                </button>

                                <button
                                    class="btn-eliminar"
                                    @click="eliminarPersona(persona)"
                                >
                                    Eliminar
                                </button>

                            </div>
                        </td>
                    </tr>

                    <tr v-if="!cargando && personas.length === 0">
                        <td colspan="9" class="sin-registros">
                            No se encontraron personas.
                        </td>
                    </tr>

                    <tr v-if="cargando">
                        <td colspan="9" class="sin-registros">
                            Cargando personas...
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
            personas: [],
            buscar: '',
            tipo_persona: '',
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
        this.personasGet();
    },

    beforeUnmount() {
        clearTimeout(this.temporizadorBusqueda);
    },

    methods: {

        personasGet() {

            this.cargando = true;

            axios.get(
                'http://localhost:8000/api/personas',
                {
                    params: {
                        page: this.pagination.current_page,
                        limit: this.pagination.per_page,
                        buscar: this.buscar,
                        tipo_persona: this.tipo_persona,
                        estado: this.estado
                    }
                }
            )
            .then(response => {

                this.personas = response.data.data;

                this.pagination.current_page =
                    response.data.current_page;

                this.pagination.last_page =
                    response.data.last_page;

                this.pagination.total =
                    response.data.total;

            })
            .catch(error => {

                console.error(
                    'Error al obtener personas:',
                    error
                );

            })
            .finally(() => {

                this.cargando = false;

            });

        },


        buscarPersonas() {

            clearTimeout(this.temporizadorBusqueda);

            this.temporizadorBusqueda = setTimeout(() => {

                this.pagination.current_page = 1;

                this.personasGet();

            }, 400);

        },


        filtrar() {

            this.pagination.current_page = 1;

            this.personasGet();

        },


        cambiarLimite() {

            this.pagination.current_page = 1;

            this.personasGet();

        },


        paginaAnterior() {

            if (this.pagination.current_page > 1) {

                this.pagination.current_page--;

                this.personasGet();

            }

        },


        paginaSiguiente() {

            if (
                this.pagination.current_page <
                this.pagination.last_page
            ) {

                this.pagination.current_page++;

                this.personasGet();

            }

        },


        crearPersona() {

            this.$router.push('/personas/crear');

        },


        editarPersona(id) {

            this.$router.push(`/personas/editar/${id}`);

        },


        eliminarPersona(persona) {

            if (
                !confirm(
                    `¿Está seguro de eliminar a "${persona.nombres_razon_social}"?`
                )
            ) {
                return;
            }

            axios.delete(
                `http://localhost:8000/api/personas/${persona.id_persona}`
            )
            .then(response => {

                alert(response.data.message);

                this.personasGet();

            })
            .catch(error => {

                alert(
                    error.response?.data?.message ||
                    'No se pudo eliminar la persona.'
                );

            });

        }

    }

};
</script>

<style scoped>

.personas {
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
    color: #6b7280;
    padding: 25px;
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