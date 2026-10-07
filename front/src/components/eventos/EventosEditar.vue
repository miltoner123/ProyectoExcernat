<template>
    <div class="formulario">

        <div class="encabezado">
            <div>
                <h1>Editar Evento</h1>
                <p>Modifique la información del evento seleccionado.</p>
            </div>
        </div>

        <div v-if="cargando" class="mensaje">
            Cargando información del evento...
        </div>

        <form v-else @submit.prevent="actualizarEvento">

            <div class="campo">
                <label>Nombre del evento:</label>
                <input
                    type="text"
                    v-model="evento.nombre"
                    placeholder="Ej. FIPAZ 2026"
                    required
                >
            </div>

            <div class="fila">

                <div class="campo">
                    <label>Tipo de evento:</label>

                    <select v-model="evento.tipo" required>
                        <option value="" disabled>Seleccione un tipo</option>
                        <option value="FERIA">Feria</option>
                        <option value="EXPOSICION">Exposición</option>
                        <option value="PROMOCION">Promoción</option>
                        <option value="OTRO">Otro</option>
                    </select>
                </div>

                <div class="campo">
                    <label>Departamento:</label>

                    <select v-model="evento.departamento" required>
                        <option value="" disabled>
                            Seleccione un departamento
                        </option>

                        <option
                            v-for="departamento in departamentos"
                            :key="departamento"
                            :value="departamento"
                        >
                            {{ departamento }}
                        </option>
                    </select>
                </div>

            </div>

            <div class="campo">
                <label>Lugar:</label>

                <input
                    type="text"
                    v-model="evento.lugar"
                    placeholder="Ej. Campo Ferial Chuquiago Marka"
                    required
                >
            </div>

            <div class="fila">

                <div class="campo">
                    <label>Fecha de inicio:</label>

                    <input
                        type="date"
                        v-model="evento.fecha_inicio"
                        required
                    >
                </div>

                <div class="campo">
                    <label>Fecha de finalización:</label>

                    <input
                        type="date"
                        v-model="evento.fecha_fin"
                        :min="evento.fecha_inicio"
                        required
                    >
                </div>

            </div>

            <div class="campo">
                <label>Responsable:</label>

                <select v-model="evento.id_responsable">

                    <option :value="null">
                        Sin responsable asignado
                    </option>

                    <option
                        v-for="trabajador in personal"
                        :key="trabajador.id_personal"
                        :value="trabajador.id_personal"
                    >
                        {{ nombrePersonal(trabajador) }}
                    </option>

                </select>
            </div>

            <div class="campo">
                <label>Presupuesto estimado (Bs):</label>

                <input
                    type="number"
                    v-model="evento.presupuesto_estimado"
                    min="0"
                    step="0.01"
                    placeholder="0.00"
                >
            </div>

            <div class="campo">
                <label>Observación:</label>

                <textarea
                    v-model="evento.observacion"
                    rows="4"
                    placeholder="Información adicional del evento..."
                ></textarea>
            </div>

            <div class="campo">
                <label>Estado:</label>

                <select v-model="evento.estado">
                    <option :value="true">Activo</option>
                    <option :value="false">Inactivo</option>
                </select>
            </div>

            <div class="botones">

                <button
                    type="submit"
                    :disabled="guardando"
                >
                    {{ guardando ? 'Actualizando...' : 'Actualizar Evento' }}
                </button>

                <button
                    type="button"
                    class="btn-cancelar"
                    @click="volver"
                >
                    Cancelar
                </button>

            </div>

        </form>

    </div>
</template>

<script>
import axios from 'axios';

export default {

    data() {
        return {

            evento: {
                nombre: '',
                tipo: '',
                departamento: '',
                lugar: '',
                fecha_inicio: '',
                fecha_fin: '',
                id_responsable: null,
                presupuesto_estimado: null,
                estado: true,
                observacion: ''
            },

            personal: [],

            cargando: true,
            guardando: false,

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
            ]

        };
    },

    mounted() {
        this.cargarDatos();
    },

    methods: {

        async cargarDatos() {

            try {

                await Promise.all([
                    this.eventoGet(),
                    this.personalGet()
                ]);

            } catch (error) {

                console.error(
                    'Error al cargar datos:',
                    error
                );

            } finally {

                this.cargando = false;

            }

        },


        eventoGet() {

            const id = this.$route.params.id;

            return axios.get(
                `http://localhost:8000/api/eventos/${id}`
            )
            .then(response => {

                const datos = response.data;

                this.evento = {
                    nombre: datos.nombre ?? '',
                    tipo: datos.tipo ?? '',
                    departamento: datos.departamento ?? '',
                    lugar: datos.lugar ?? '',
                    fecha_inicio: this.fechaInput(datos.fecha_inicio),
                    fecha_fin: this.fechaInput(datos.fecha_fin),
                    id_responsable: datos.id_responsable ?? null,
                    presupuesto_estimado: datos.presupuesto_estimado ?? null,
                    estado: datos.estado,
                    observacion: datos.observacion ?? ''
                };

            })
            .catch(error => {

                console.error(
                    'Error al obtener evento:',
                    error
                );

                alert('No se pudo obtener el evento.');

                this.$router.push('/eventos');

                throw error;

            });

        },


        personalGet() {

            return axios.get(
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


        actualizarEvento() {

            if (
                this.evento.fecha_fin <
                this.evento.fecha_inicio
            ) {

                alert(
                    'La fecha de finalización no puede ser anterior a la fecha de inicio.'
                );

                return;

            }

            this.guardando = true;

            const id = this.$route.params.id;

            axios.post(
                `http://localhost:8000/api/eventos/${id}`,
                this.evento
            )
            .then(response => {

                alert(response.data.message);

                this.$router.push('/eventos');

            })
            .catch(error => {

                console.error(error);

                if (
                    error.response?.status === 422 &&
                    error.response?.data?.errors
                ) {

                    const errores =
                        error.response.data.errors;

                    alert(
                        Object.values(errores)
                            .flat()
                            .join('\n')
                    );

                    return;

                }

                alert(
                    error.response?.data?.message ||
                    'No se pudo actualizar el evento.'
                );

            })
            .finally(() => {

                this.guardando = false;

            });

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


        fechaInput(fecha) {

            if (!fecha) {
                return '';
            }

            return fecha.substring(0, 10);

        },


        volver() {
            this.$router.push('/eventos');
        }

    }

};
</script>

<style scoped>

.formulario {
    max-width: 850px;
    margin: 0 auto;
    background-color: white;
    padding: 25px;
    border-radius: 8px;
}

.encabezado {
    margin-bottom: 25px;
}

.encabezado h1 {
    margin: 0 0 5px 0;
    color: #1f2937;
}

.encabezado p {
    margin: 0;
    color: #6b7280;
}

.campo {
    display: flex;
    flex-direction: column;
    margin-bottom: 18px;
    flex: 1;
}

.campo label {
    margin-bottom: 7px;
    font-weight: 600;
    color: #374151;
}

.campo input,
.campo select,
.campo textarea {
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
}

.fila {
    display: flex;
    gap: 20px;
}

.botones {
    display: flex;
    gap: 10px;
    margin-top: 25px;
}

.botones button {
    padding: 11px 18px;
    border: none;
    border-radius: 6px;
    background-color: #2563eb;
    color: white;
    cursor: pointer;
}

.botones button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.botones .btn-cancelar {
    background-color: #6b7280;
}

.mensaje {
    padding: 25px;
    text-align: center;
    color: #6b7280;
}

@media (max-width: 700px) {

    .fila {
        flex-direction: column;
        gap: 0;
    }

}

</style>