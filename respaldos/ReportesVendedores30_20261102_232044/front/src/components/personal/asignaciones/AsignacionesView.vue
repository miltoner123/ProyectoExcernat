<template>
    <div class="asignaciones">

        <div class="encabezado">
            <div>
                <h1>Asignaciones de Personal</h1>
                <p>Gestión de asignaciones del personal a sucursales, almacenes, stands y eventos.</p>
            </div>

            <button @click="nuevaAsignacion">
                + Nueva Asignación
            </button>
        </div>

        <div class="filtros">

            <input
                type="text"
                v-model="buscar"
                @input="buscarAsignaciones"
                placeholder="Buscar trabajador..."
            >

            <select v-model="id_evento" @change="filtrar">
                <option value="">Todos los eventos</option>

                <option
                    v-for="evento in eventos"
                    :key="evento.id_evento"
                    :value="evento.id_evento"
                >
                    {{ evento.nombre }}
                </option>
            </select>

            <select v-model="tipo_remuneracion" @change="filtrar">
                <option value="">Toda remuneración</option>
                <option value="SALARIO">Salario</option>
                <option value="MONTO_FIJO">Monto fijo</option>
                <option value="COMISION">Comisión</option>
                <option value="MIXTO">Mixto</option>
            </select>

            <select v-model="estado" @change="filtrar">
                <option value="">Todos los estados</option>
                <option value="true">Activas</option>
                <option value="false">Inactivas</option>
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
                        <th>Personal</th>
                        <th>Cargo</th>
                        <th>Función</th>
                        <th>Ubicación</th>
                        <th>Evento</th>
                        <th>Remuneración</th>
                        <th>Periodo</th>
                        <th>Estado</th>
                        <th>Opciones</th>
                    </tr>
                </thead>

                <tbody>

                    <tr
                        v-for="asignacion in asignaciones"
                        :key="asignacion.id_asignacion"
                    >

                        <td>
                            <strong>
                                {{ nombrePersonal(asignacion) }}
                            </strong>

                            <small class="codigo">
                                {{ asignacion.personal?.codigo_empleado }}
                            </small>
                        </td>

                        <td>
                            {{ asignacion.personal?.cargo?.nombre || '—' }}
                        </td>

                        <td>
                            {{ asignacion.funcion_asignada }}
                        </td>

                        <td>
                            <span v-if="asignacion.ubicacion">
                                {{ asignacion.ubicacion.nombre }}
                                <small class="codigo">
                                    {{ asignacion.ubicacion.tipo }}
                                </small>
                            </span>

                            <span v-else>—</span>
                        </td>

                        <td>
                            {{ asignacion.evento?.nombre || '—' }}
                        </td>

                        <td>
                            {{ nombreRemuneracion(asignacion.tipo_remuneracion) }}
                        </td>

                        <td>
                            {{ fecha(asignacion.fecha_inicio) }}

                            <span v-if="asignacion.fecha_fin">
                                al {{ fecha(asignacion.fecha_fin) }}
                            </span>

                            <span v-else>
                                - Sin fecha fin
                            </span>
                        </td>

                        <td>
                            <span
                                :class="asignacion.estado
                                    ? 'estado-activo'
                                    : 'estado-inactivo'"
                            >
                                {{ asignacion.estado ? 'Activa' : 'Inactiva' }}
                            </span>
                        </td>

                        <td>
                            <div class="acciones">

                                <button
                                    @click="editarAsignacion(asignacion.id_asignacion)"
                                >
                                    Editar
                                </button>

                                <button
                                    class="btn-eliminar"
                                    @click="eliminarAsignacion(asignacion)"
                                >
                                    Eliminar
                                </button>

                            </div>
                        </td>

                    </tr>

                    <tr v-if="!cargando && asignaciones.length === 0">
                        <td colspan="9" class="sin-registros">
                            No se encontraron asignaciones.
                        </td>
                    </tr>

                    <tr v-if="cargando">
                        <td colspan="9" class="sin-registros">
                            Cargando asignaciones...
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
import api from '../../../services/api';

export default {

    data() {
        return {
            asignaciones: [],
            eventos: [],

            buscar: '',
            id_evento: '',
            tipo_remuneracion: '',
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
        this.eventosGet();
        this.asignacionesGet();
    },

    beforeUnmount() {
        clearTimeout(this.temporizadorBusqueda);
    },

    methods: {

        asignacionesGet() {

            this.cargando = true;

            api.get(
                'http://localhost:8000/api/asignaciones-personal',
                {
                    params: {
                        page: this.pagination.current_page,
                        limit: this.pagination.per_page,
                        buscar: this.buscar,
                        id_evento: this.id_evento,
                        tipo_remuneracion: this.tipo_remuneracion,
                        estado: this.estado
                    }
                }
            )
            .then(response => {

                this.asignaciones = response.data.data;

                this.pagination.current_page = response.data.current_page;
                this.pagination.last_page = response.data.last_page;
                this.pagination.total = response.data.total;

            })
            .catch(error => {

                console.error(
                    'Error al obtener asignaciones:',
                    error
                );

            })
            .finally(() => {

                this.cargando = false;

            });

        },

        eventosGet() {

            api.get(
                'http://localhost:8000/api/eventos',
                {
                    params: {
                        limit: 50
                    }
                }
            )
            .then(response => {

                this.eventos = response.data.data;

            });

        },

        buscarAsignaciones() {

            clearTimeout(this.temporizadorBusqueda);

            this.temporizadorBusqueda = setTimeout(() => {

                this.pagination.current_page = 1;
                this.asignacionesGet();

            }, 400);

        },

        filtrar() {

            this.pagination.current_page = 1;
            this.asignacionesGet();

        },

        cambiarLimite() {

            this.pagination.current_page = 1;
            this.asignacionesGet();

        },

        paginaAnterior() {

            if (this.pagination.current_page > 1) {
                this.pagination.current_page--;
                this.asignacionesGet();
            }

        },

        paginaSiguiente() {

            if (
                this.pagination.current_page <
                this.pagination.last_page
            ) {
                this.pagination.current_page++;
                this.asignacionesGet();
            }

        },

        nuevaAsignacion() {
            this.$router.push('/asignaciones/crear');
        },

        editarAsignacion(id) {
            this.$router.push(`/asignaciones/editar/${id}`);
        },

        eliminarAsignacion(asignacion) {

            if (!confirm(
                `¿Está seguro de eliminar la asignación de ${this.nombrePersonal(asignacion)}?`
            )) {
                return;
            }

            api.delete(
                `http://localhost:8000/api/asignaciones-personal/${asignacion.id_asignacion}`
            )
            .then(response => {

                alert(response.data.message);
                this.asignacionesGet();

            })
            .catch(error => {

                alert(
                    error.response?.data?.message ||
                    'No se pudo eliminar la asignación.'
                );

            });

        },

        nombrePersonal(asignacion) {

            const persona = asignacion.personal?.persona;

            if (!persona) {
                return '—';
            }

            return [
                persona.nombres_razon_social,
                persona.apellidos
            ]
            .filter(Boolean)
            .join(' ');

        },

        nombreRemuneracion(tipo) {

            const tipos = {
                SALARIO: 'Salario',
                MONTO_FIJO: 'Monto fijo',
                COMISION: 'Comisión',
                MIXTO: 'Mixto'
            };

            return tipos[tipo] || tipo;

        },

        fecha(valor) {

            if (!valor) {
                return '—';
            }

            return valor.substring(0, 10);

        }

    }

};
</script>

<style scoped>
.asignaciones { width: 100%; }

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
    background: #2563eb;
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
    flex: 1;
    min-width: 250px;
}

.tabla-contenedor {
    background: white;
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
    background: #f9fafb;
}

.codigo {
    display: block;
    margin-top: 3px;
    color: #6b7280;
}

.estado-activo {
    background: #dcfce7;
    color: #166534;
    padding: 5px 8px;
    border-radius: 5px;
}

.estado-inactivo {
    background: #fee2e2;
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
    background: #2563eb;
    color: white;
}

.acciones .btn-eliminar {
    background: #dc2626;
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
</style>