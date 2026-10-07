<template>
    <div class="personal">

        <div class="encabezado">
            <div>
                <h1>Gestión de Personal</h1>
                <p>Administración del personal de EXCERNAT.</p>
            </div>

            <button @click="crearPersonal">
                + Nuevo Personal
            </button>
        </div>

        <div class="filtros">

            <input
                type="text"
                v-model="buscar"
                @input="buscarPersonal"
                placeholder="Buscar por nombre, apellido, documento o código..."
            >

            <select v-model="id_cargo" @change="filtrar">
                <option value="">Todos los cargos</option>

                <option
                    v-for="cargo in cargos"
                    :key="cargo.id_cargo"
                    :value="cargo.id_cargo"
                >
                    {{ cargo.nombre }}
                </option>
            </select>

            <select v-model="tipo_contrato" @change="filtrar">
                <option value="">Todos los contratos</option>
                <option value="INDEFINIDO">Indefinido</option>
                <option value="PLAZO_FIJO">Plazo fijo</option>
                <option value="EVENTUAL">Eventual</option>
                <option value="SERVICIOS">Servicios</option>
            </select>

            <select v-model="estado" @change="filtrar">
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
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Documento</th>
                        <th>Cargo</th>
                        <th>Ingreso</th>
                        <th>Contrato</th>
                        <th>Salario Base</th>
                        <th>Estado</th>
                        <th>Opciones</th>
                    </tr>
                </thead>

                <tbody>

                    <tr
                        v-for="trabajador in personal"
                        :key="trabajador.id_personal"
                    >
                        <td>
                            <strong>
                                {{ trabajador.codigo_empleado }}
                            </strong>
                        </td>

                        <td>
                            {{ nombrePersona(trabajador) }}
                        </td>

                        <td>
                            {{ documentoPersona(trabajador) }}
                        </td>

                        <td>
                            {{ trabajador.cargo?.nombre || '—' }}
                        </td>

                        <td>
                            {{ formatearFecha(trabajador.fecha_ingreso) }}
                        </td>

                        <td>
                            {{ formatearContrato(trabajador.tipo_contrato) }}
                        </td>

                        <td>
                            Bs {{ formatearMonto(trabajador.salario_base) }}
                        </td>

                        <td>
                            <span
                                :class="trabajador.estado
                                    ? 'estado-activo'
                                    : 'estado-inactivo'"
                            >
                                {{ trabajador.estado ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>

                        <td>
                            <div class="acciones">

                                <button
                                    @click="editarPersonal(trabajador.id_personal)"
                                >
                                    Editar
                                </button>

                                <button
                                    class="btn-eliminar"
                                    @click="eliminarPersonal(trabajador)"
                                >
                                    Eliminar
                                </button>

                            </div>
                        </td>
                    </tr>

                    <tr v-if="!cargando && personal.length === 0">
                        <td colspan="9" class="sin-registros">
                            No se encontró personal registrado.
                        </td>
                    </tr>

                    <tr v-if="cargando">
                        <td colspan="9" class="sin-registros">
                            Cargando personal...
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

            personal: [],
            cargos: [],

            buscar: '',
            id_cargo: '',
            tipo_contrato: '',
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
        this.personalGet();
    },

    beforeUnmount() {
        clearTimeout(this.temporizadorBusqueda);
    },

    methods: {

        personalGet() {

            this.cargando = true;

            axios.get(
                'http://localhost:8000/api/personal',
                {
                    params: {
                        page: this.pagination.current_page,
                        limit: this.pagination.per_page,
                        buscar: this.buscar,
                        id_cargo: this.id_cargo,
                        tipo_contrato: this.tipo_contrato,
                        estado: this.estado
                    }
                }
            )
            .then(response => {

                this.personal = response.data.data;

                this.pagination.current_page =
                    response.data.current_page;

                this.pagination.last_page =
                    response.data.last_page;

                this.pagination.total =
                    response.data.total;

            })
            .catch(error => {

                console.error(
                    'Error al obtener personal:',
                    error
                );

            })
            .finally(() => {

                this.cargando = false;

            });

        },


        cargosGet() {

            axios.get(
                'http://localhost:8000/api/cargos',
                {
                    params: {
                        limit: 50,
                        estado: true
                    }
                }
            )
            .then(response => {

                this.cargos = response.data.data;

            })
            .catch(error => {

                console.error(
                    'Error al obtener cargos:',
                    error
                );

            });

        },


        buscarPersonal() {

            clearTimeout(this.temporizadorBusqueda);

            this.temporizadorBusqueda = setTimeout(() => {

                this.pagination.current_page = 1;

                this.personalGet();

            }, 400);

        },


        filtrar() {

            this.pagination.current_page = 1;

            this.personalGet();

        },


        cambiarLimite() {

            this.pagination.current_page = 1;

            this.personalGet();

        },


        paginaAnterior() {

            if (this.pagination.current_page > 1) {

                this.pagination.current_page--;

                this.personalGet();

            }

        },


        paginaSiguiente() {

            if (
                this.pagination.current_page <
                this.pagination.last_page
            ) {

                this.pagination.current_page++;

                this.personalGet();

            }

        },


        crearPersonal() {

            this.$router.push('/personal/crear');

        },


        editarPersonal(id) {

            this.$router.push(`/personal/editar/${id}`);

        },


        eliminarPersonal(trabajador) {

            if (
                !confirm(
                    `¿Está seguro de eliminar al trabajador "${this.nombrePersona(trabajador)}"?`
                )
            ) {
                return;
            }

            axios.delete(
                `http://localhost:8000/api/personal/${trabajador.id_personal}`
            )
            .then(response => {

                alert(response.data.message);

                this.personalGet();

            })
            .catch(error => {

                alert(
                    error.response?.data?.message ||
                    'No se pudo eliminar el personal.'
                );

            });

        },


        nombrePersona(trabajador) {

            if (!trabajador.persona) {
                return '—';
            }

            return [
                trabajador.persona.nombres_razon_social,
                trabajador.persona.apellidos
            ]
            .filter(Boolean)
            .join(' ');

        },


        documentoPersona(trabajador) {

            if (!trabajador.persona?.numero_documento) {
                return 'Sin documento';
            }

            return `${trabajador.persona.tipo_documento ?? ''} ${trabajador.persona.numero_documento}`.trim();

        },


        formatearFecha(fecha) {

            if (!fecha) {
                return '—';
            }

            return fecha.substring(0, 10);

        },


        formatearMonto(monto) {

            return Number(monto || 0).toFixed(2);

        },


        formatearContrato(tipo) {

            const contratos = {
                INDEFINIDO: 'Indefinido',
                PLAZO_FIJO: 'Plazo fijo',
                EVENTUAL: 'Eventual',
                SERVICIOS: 'Servicios'
            };

            return contratos[tipo] || tipo || '—';

        }

    }

};
</script>

<style scoped>

.personal {
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
    min-width: 280px;
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
}

.controles button:disabled {
    opacity: 0.5;
}

</style>