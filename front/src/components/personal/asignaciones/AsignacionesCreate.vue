<template>
    <div class="formulario">

        <div class="encabezado">
            <h1>Nueva Asignación</h1>
            <p>Asigne un trabajador a una ubicación o evento.</p>
        </div>

        <form @submit.prevent="guardar">

            <div class="seccion">

                <h3>Personal</h3>

                <div class="fila">

                    <div class="campo">
                        <label>Trabajador:</label>

                        <select v-model="asignacion.id_personal" required>
                            <option value="" disabled>Seleccione</option>

                            <option
                                v-for="trabajador in personal"
                                :key="trabajador.id_personal"
                                :value="trabajador.id_personal"
                            >
                                {{ nombrePersonal(trabajador) }}
                                - {{ trabajador.cargo?.nombre }}
                            </option>
                        </select>
                    </div>

                    <div class="campo">
                        <label>Función asignada:</label>

                        <input
                            type="text"
                            v-model="asignacion.funcion_asignada"
                            placeholder="Ej. Vendedor del Stand 1"
                            required
                        >
                    </div>

                </div>

            </div>

            <div class="seccion">

                <h3>Destino de la asignación</h3>

                <div class="fila">

                    <div class="campo">
                        <label>Ubicación:</label>

                        <select
                            v-model="asignacion.id_ubicacion"
                            @change="ubicacionSeleccionada"
                        >
                            <option :value="null">
                                Sin ubicación específica
                            </option>

                            <option
                                v-for="ubicacion in ubicaciones"
                                :key="ubicacion.id_ubicacion"
                                :value="ubicacion.id_ubicacion"
                            >
                                {{ ubicacion.nombre }} - {{ ubicacion.tipo }}
                            </option>
                        </select>
                    </div>

                    <div class="campo">
                        <label>Evento:</label>

                        <select
                            v-model="asignacion.id_evento"
                            :disabled="standSeleccionado"
                        >
                            <option :value="null">
                                Sin evento
                            </option>

                            <option
                                v-for="evento in eventos"
                                :key="evento.id_evento"
                                :value="evento.id_evento"
                            >
                                {{ evento.nombre }}
                            </option>
                        </select>

                        <small v-if="standSeleccionado">
                            El evento se obtiene automáticamente del stand.
                        </small>
                    </div>

                </div>

            </div>

            <div class="seccion">

                <h3>Remuneración</h3>

                <div class="campo">

                    <label>Tipo de remuneración:</label>

                    <select
                        v-model="asignacion.tipo_remuneracion"
                        required
                    >
                        <option value="" disabled>Seleccione</option>
                        <option value="SALARIO">Salario</option>
                        <option value="MONTO_FIJO">Monto fijo</option>
                        <option value="COMISION">Comisión</option>
                        <option value="MIXTO">Mixto</option>
                    </select>

                </div>

                <div
                    v-if="mostrarMontoFijo"
                    class="campo"
                >
                    <label>Monto fijo (Bs):</label>

                    <input
                        type="number"
                        v-model="asignacion.monto_fijo"
                        min="0"
                        step="0.01"
                    >
                </div>

                <div
                    v-if="mostrarComisiones"
                    class="fila"
                >

                    <div class="campo">
                        <label>Comisión productos en polvo (Bs/unidad):</label>

                        <input
                            type="number"
                            v-model="asignacion.comision_polvo"
                            min="0"
                            step="0.01"
                        >
                    </div>

                    <div class="campo">
                        <label>Comisión granola (Bs/unidad):</label>

                        <input
                            type="number"
                            v-model="asignacion.comision_granola"
                            min="0"
                            step="0.01"
                        >
                    </div>

                </div>

            </div>

            <div class="seccion">

                <h3>Periodo</h3>

                <div class="fila">

                    <div class="campo">
                        <label>Fecha inicio:</label>

                        <input
                            type="date"
                            v-model="asignacion.fecha_inicio"
                            required
                        >
                    </div>

                    <div class="campo">
                        <label>Fecha fin:</label>

                        <input
                            type="date"
                            v-model="asignacion.fecha_fin"
                            :min="asignacion.fecha_inicio"
                        >
                    </div>

                </div>

                <div class="campo">
                    <label>Estado:</label>

                    <select v-model="asignacion.estado">
                        <option :value="true">Activa</option>
                        <option :value="false">Inactiva</option>
                    </select>
                </div>

            </div>

            <div class="botones">

                <button
                    type="submit"
                    :disabled="guardando"
                >
                    {{ guardando ? 'Guardando...' : 'Guardar Asignación' }}
                </button>

                <button
                    type="button"
                    class="btn-cancelar"
                    @click="$router.push('/asignaciones')"
                >
                    Cancelar
                </button>

            </div>

        </form>

    </div>
</template>

<script>
import api from '../../../services/api';

export default {

    data() {
        return {

            asignacion: {
                id_personal: '',
                id_ubicacion: null,
                id_evento: null,
                funcion_asignada: '',
                tipo_remuneracion: '',
                monto_fijo: null,
                comision_polvo: null,
                comision_granola: null,
                fecha_inicio: '',
                fecha_fin: '',
                estado: true
            },

            personal: [],
            ubicaciones: [],
            eventos: [],

            guardando: false
        };
    },

    computed: {

        ubicacionActual() {

            return this.ubicaciones.find(
                ubicacion =>
                    Number(ubicacion.id_ubicacion) ===
                    Number(this.asignacion.id_ubicacion)
            );

        },

        standSeleccionado() {
            return this.ubicacionActual?.tipo === 'STAND';
        },

        mostrarMontoFijo() {

            return [
                'MONTO_FIJO',
                'MIXTO'
            ].includes(this.asignacion.tipo_remuneracion);

        },

        mostrarComisiones() {

            return [
                'COMISION',
                'MIXTO'
            ].includes(this.asignacion.tipo_remuneracion);

        }

    },

    mounted() {
        this.cargarDatos();
    },

    methods: {

        async cargarDatos() {

            try {

                await Promise.all([
                    this.personalGet(),
                    this.ubicacionesGet(),
                    this.eventosGet()
                ]);

            } catch (error) {

                console.error(
                    'Error al cargar datos:',
                    error
                );

            }

        },

        personalGet() {

            return api.get(
                'http://localhost:8000/api/personal',
                {
                    params: {
                        limit: 50,
                        estado: true
                    }
                }
            )
            .then(response => {

                this.personal = response.data.data;

            });

        },

        ubicacionesGet() {

            return api.get(
                'http://localhost:8000/api/ubicaciones',
                {
                    params: {
                        limit: 50,
                        estado: true
                    }
                }
            )
            .then(response => {

                this.ubicaciones = response.data.data;

            });

        },

        eventosGet() {

            return api.get(
                'http://localhost:8000/api/eventos',
                {
                    params: {
                        limit: 50,
                        estado: true
                    }
                }
            )
            .then(response => {

                this.eventos = response.data.data;

            });

        },

        ubicacionSeleccionada() {

            const ubicacion = this.ubicacionActual;

            if (!ubicacion) {
                return;
            }

            if (ubicacion.tipo === 'STAND') {

                this.asignacion.id_evento =
                    ubicacion.id_evento;

            } else {

                this.asignacion.id_evento = null;

            }

        },

        nombrePersonal(trabajador) {

            if (!trabajador.persona) {
                return trabajador.codigo_empleado;
            }

            return [
                trabajador.persona.nombres_razon_social,
                trabajador.persona.apellidos
            ]
            .filter(Boolean)
            .join(' ');

        },

        guardar() {

            if (
                !this.asignacion.id_ubicacion &&
                !this.asignacion.id_evento
            ) {

                alert(
                    'Debe seleccionar una ubicación o un evento.'
                );

                return;
            }

            if (
                this.asignacion.fecha_fin &&
                this.asignacion.fecha_fin <
                this.asignacion.fecha_inicio
            ) {

                alert(
                    'La fecha final no puede ser anterior a la fecha inicial.'
                );

                return;
            }

            this.guardando = true;

            const datos = {
                ...this.asignacion,

                fecha_fin:
                    this.asignacion.fecha_fin || null,

                monto_fijo:
                    this.mostrarMontoFijo
                        ? this.asignacion.monto_fijo
                        : null,

                comision_polvo:
                    this.mostrarComisiones
                        ? this.asignacion.comision_polvo
                        : null,

                comision_granola:
                    this.mostrarComisiones
                        ? this.asignacion.comision_granola
                        : null
            };

            api.post(
                'http://localhost:8000/api/asignaciones-personal',
                datos
            )
            .then(response => {

                alert(response.data.message);

                this.$router.push('/asignaciones');

            })
            .catch(error => {

                console.error(error);

                if (
                    error.response?.status === 422 &&
                    error.response?.data?.errors
                ) {

                    alert(
                        Object.values(
                            error.response.data.errors
                        )
                        .flat()
                        .join('\n')
                    );

                    return;
                }

                alert(
                    error.response?.data?.message ||
                    'No se pudo registrar la asignación.'
                );

            })
            .finally(() => {

                this.guardando = false;

            });

        }

    }

};
</script>

<style scoped>
.formulario {
    max-width: 950px;
    margin: 0 auto;
}

.encabezado {
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

.seccion {
    background: white;
    padding: 25px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.seccion h3 {
    margin-top: 0;
    color: #1f2937;
}

.fila {
    display: flex;
    gap: 20px;
}

.campo {
    display: flex;
    flex-direction: column;
    flex: 1;
    margin-bottom: 18px;
}

.campo label {
    margin-bottom: 7px;
    font-weight: 600;
}

.campo input,
.campo select {
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
}

.campo small {
    margin-top: 5px;
    color: #6b7280;
}

.botones {
    display: flex;
    gap: 10px;
}

.botones button {
    padding: 11px 18px;
    border: none;
    border-radius: 6px;
    background: #2563eb;
    color: white;
    cursor: pointer;
}

.botones .btn-cancelar {
    background: #6b7280;
}

@media (max-width: 700px) {
    .fila {
        flex-direction: column;
        gap: 0;
    }
}
</style>