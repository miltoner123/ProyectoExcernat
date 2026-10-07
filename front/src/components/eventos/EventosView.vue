<template>
    <div class="eventos">

        <div class="encabezado">
            <div>
                <h1>Gestión de Eventos</h1>
                <p>Administración de ferias, exposiciones y eventos comerciales.</p>
            </div>

            <button @click="crearEvento">
                + Nuevo Evento
            </button>
        </div>

        <div class="filtros">

            <input
                type="text"
                v-model="buscar"
                @input="buscarEventos"
                placeholder="Buscar por nombre, tipo, departamento o lugar..."
            >

            <select v-model="tipo" @change="filtrarEventos">
                <option value="">Todos los tipos</option>
                <option value="FERIA">Feria</option>
                <option value="EXPOSICION">Exposición</option>
                <option value="PROMOCION">Promoción</option>
                <option value="OTRO">Otro</option>
            </select>

            <select v-model="departamento" @change="filtrarEventos">
                <option value="">Todos los departamentos</option>

                <option
                    v-for="dep in departamentos"
                    :key="dep"
                    :value="dep"
                >
                    {{ dep }}
                </option>
            </select>

            <select v-model="estado" @change="filtrarEventos">
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

        <div class="tabla-contenedor">

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Evento</th>
                        <th>Tipo</th>
                        <th>Departamento</th>
                        <th>Lugar</th>
                        <th>Fecha Inicio</th>
                        <th>Fecha Fin</th>
                        <th>Responsable</th>
                        <th>Estado</th>
                        <th>Opciones</th>
                    </tr>
                </thead>

                <tbody>

                    <tr
                        v-for="evento in eventos"
                        :key="evento.id_evento"
                    >
                        <td>{{ evento.id_evento }}</td>

                        <td>
                            <strong>{{ evento.nombre }}</strong>
                        </td>

                        <td>{{ evento.tipo }}</td>

                        <td>{{ evento.departamento }}</td>

                        <td>{{ evento.lugar }}</td>

                        <td>{{ formatearFecha(evento.fecha_inicio) }}</td>

                        <td>{{ formatearFecha(evento.fecha_fin) }}</td>

                        <td>
                            {{ nombreResponsable(evento) }}
                        </td>

                        <td>
                            <span
                                :class="evento.estado
                                    ? 'estado-activo'
                                    : 'estado-inactivo'"
                            >
                                {{ evento.estado ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>

                        <td>
                            <div class="acciones">
                                    <button class="btn-stands" @click="verStands(evento.id_evento)">
                                    Stands
                                </button>
                                <button
                                    @click="editarEvento(evento.id_evento)"
                                >
                                    Editar
                                </button>

                                <button
                                    class="btn-eliminar"
                                    @click="eliminarEvento(evento)"
                                >
                                    Eliminar
                                </button>

                            </div>
                        </td>
                    </tr>

                    <tr v-if="!cargando && eventos.length === 0">
                        <td colspan="10">
                            No se encontraron eventos.
                        </td>
                    </tr>

                    <tr v-if="cargando">
                        <td colspan="10">
                            Cargando eventos...
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

            eventos: [],

            buscar: '',
            tipo: '',
            departamento: '',
            estado: '',

            cargando: false,
            temporizadorBusqueda: null,
            numeroPeticion: 0,

            departamentos: [
                'La Paz',
                'Cochabamba',
                'Santa Cruz',
                'Oruro',
                'Potosí',
                'Chuquisaca',
                'Tarija',
                'Beni',
                'Pando'
            ],

            pagination: {
                current_page: 1,
                last_page: 1,
                per_page: 10,
                total: 0
            }

        };
    },

    mounted() {
        this.eventosGet();
    },

    beforeUnmount() {
        clearTimeout(this.temporizadorBusqueda);
    },

    methods: {
        verStands(id) {
        this.$router.push(`/eventos/${id}/stands`);
    },

        eventosGet() {

            const peticionActual = ++this.numeroPeticion;

            this.cargando = true;

            axios.get(
                'http://localhost:8000/api/eventos',
                {
                    params: {
                        page: this.pagination.current_page,
                        limit: this.pagination.per_page,
                        buscar: this.buscar,
                        tipo: this.tipo,
                        departamento: this.departamento,
                        estado: this.estado
                    }
                }
            )

            .then(response => {

                if (peticionActual !== this.numeroPeticion) {
                    return;
                }

                this.eventos = response.data.data;

                this.pagination.current_page =
                    response.data.current_page;

                this.pagination.last_page =
                    response.data.last_page;

                this.pagination.total =
                    response.data.total;

            })

            .catch(error => {

                console.error(
                    'Error al obtener eventos:',
                    error
                );

            })

            .finally(() => {

                if (peticionActual === this.numeroPeticion) {
                    this.cargando = false;
                }

            });

        },


        buscarEventos() {

            clearTimeout(this.temporizadorBusqueda);

            this.temporizadorBusqueda = setTimeout(() => {

                this.pagination.current_page = 1;

                this.eventosGet();

            }, 400);

        },


        filtrarEventos() {

            this.pagination.current_page = 1;

            this.eventosGet();

        },


        cambiarLimite() {

            this.pagination.current_page = 1;

            this.eventosGet();

        },


        paginaAnterior() {

            if (this.pagination.current_page > 1) {

                this.pagination.current_page--;

                this.eventosGet();

            }

        },


        paginaSiguiente() {

            if (
                this.pagination.current_page <
                this.pagination.last_page
            ) {

                this.pagination.current_page++;

                this.eventosGet();

            }

        },


        crearEvento() {

            this.$router.push('/eventos/crear');

        },


        editarEvento(id) {

            this.$router.push(`/eventos/editar/${id}`);

        },


        eliminarEvento(evento) {

            if (
                !confirm(
                    `¿Está seguro de eliminar el evento "${evento.nombre}"?`
                )
            ) {
                return;
            }

            axios.delete(
                `http://localhost:8000/api/eventos/${evento.id_evento}`
            )

            .then(response => {

                alert(response.data.message);

                this.eventosGet();

            })

            .catch(error => {

                alert(
                    error.response?.data?.message ||
                    'No se pudo eliminar el evento.'
                );

            });

        },


        nombreResponsable(evento) {

            if (!evento.responsable?.persona) {
                return 'Sin responsable';
            }

            const persona = evento.responsable.persona;

            return [
                persona.nombres_razon_social,
                persona.apellidos
            ]
            .filter(Boolean)
            .join(' ');

        },


        formatearFecha(fecha) {

            if (!fecha) {
                return '—';
            }

            return fecha.substring(0, 10);

        }

    }

};

</script>

<style scoped>

.eventos {
    width: 100%;
}

.encabezado {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.encabezado h1 {
    color: #1f2937;
    margin-bottom: 5px;
}

.encabezado p {
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
    margin-bottom: 20px;
    flex-wrap: wrap;
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

.paginacion {
    margin-top: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
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
    cursor: not-allowed;
    opacity: 0.5;
}

</style>