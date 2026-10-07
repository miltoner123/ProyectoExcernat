<template>

    <div class="formulario">

        <div class="encabezado">
            <h1>Registrar Personal</h1>
            <p>Complete la información laboral del trabajador.</p>
        </div>

        <form @submit.prevent="crearPersonal">

            <div class="seccion">
                <h3>Trabajador</h3>

                <div class="campo">

                    <label>Persona:</label>

                    <select
                        v-model="trabajador.id_persona"
                        required
                    >
                        <option value="" disabled>
                            Seleccione una persona
                        </option>

                        <option
                            v-for="persona in personasDisponibles"
                            :key="persona.id_persona"
                            :value="persona.id_persona"
                        >
                            {{ nombrePersona(persona) }}
                        </option>

                    </select>

                    <small>
                        Solo se muestran personas registradas como PERSONAL.
                    </small>

                </div>

                <div
                    v-if="personaSeleccionada"
                    class="persona-info"
                >
                    <strong>
                        {{ nombrePersona(personaSeleccionada) }}
                    </strong>

                    <span>
                        Documento:
                        {{ personaSeleccionada.tipo_documento || '—' }}
                        {{ personaSeleccionada.numero_documento || 'Sin documento' }}
                    </span>

                    <span>
                        Teléfono:
                        {{ personaSeleccionada.telefono || '—' }}
                    </span>
                </div>

            </div>

            <div class="seccion">

                <h3>Información laboral</h3>

                <div class="fila">

                    <div class="campo">

                        <label>Código de empleado:</label>

                        <input
                            type="text"
                            v-model="trabajador.codigo_empleado"
                            placeholder="Ej. EMP-001"
                            required
                        >

                    </div>

                    <div class="campo">

                        <label>Cargo:</label>

                        <select
                            v-model="trabajador.id_cargo"
                            required
                        >
                            <option value="" disabled>
                                Seleccione un cargo
                            </option>

                            <option
                                v-for="cargo in cargos"
                                :key="cargo.id_cargo"
                                :value="cargo.id_cargo"
                            >
                                {{ cargo.nombre }}
                            </option>

                        </select>

                    </div>

                </div>

                <div class="fila">

                    <div class="campo">

                        <label>Fecha de ingreso:</label>

                        <input
                            type="date"
                            v-model="trabajador.fecha_ingreso"
                            required
                        >

                    </div>

                    <div class="campo">

                        <label>Tipo de contrato:</label>

                        <select v-model="trabajador.tipo_contrato">

                            <option value="">
                                Sin especificar
                            </option>

                            <option value="INDEFINIDO">
                                Indefinido
                            </option>

                            <option value="PLAZO_FIJO">
                                Plazo fijo
                            </option>

                            <option value="EVENTUAL">
                                Eventual
                            </option>

                            <option value="SERVICIOS">
                                Servicios
                            </option>

                        </select>

                    </div>

                </div>

                <div class="fila">

                    <div class="campo">

                        <label>Salario base (Bs):</label>

                        <input
                            type="number"
                            v-model="trabajador.salario_base"
                            min="0"
                            step="0.01"
                            required
                        >

                    </div>

                    <div class="campo">

                        <label>Estado:</label>

                        <select v-model="trabajador.estado">
                            <option :value="true">
                                Activo
                            </option>

                            <option :value="false">
                                Inactivo
                            </option>
                        </select>

                    </div>

                </div>

            </div>

            <div class="botones">

                <button
                    type="submit"
                    :disabled="guardando"
                >
                    {{ guardando ? 'Guardando...' : 'Guardar Personal' }}
                </button>

                <button
                    type="button"
                    class="btn-cancelar"
                    @click="$router.push('/personal')"
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

            trabajador: {
                id_persona: '',
                id_cargo: '',
                codigo_empleado: '',
                fecha_ingreso: '',
                tipo_contrato: '',
                salario_base: 0,
                estado: true
            },

            personas: [],
            personalRegistrado: [],
            cargos: [],

            guardando: false

        };
    },

    computed: {

        personasDisponibles() {

            const registrados = this.personalRegistrado.map(
                trabajador => Number(trabajador.id_persona)
            );

            return this.personas.filter(persona =>
                persona.tipo_persona === 'PERSONAL' &&
                persona.estado === true &&
                !registrados.includes(Number(persona.id_persona))
            );

        },


        personaSeleccionada() {

            return this.personas.find(
                persona =>
                    Number(persona.id_persona) ===
                    Number(this.trabajador.id_persona)
            );

        }

    },

    mounted() {
        this.cargarDatos();
    },

    methods: {

        async cargarDatos() {

            try {

                await Promise.all([
                    this.personasGet(),
                    this.cargosGet(),
                    this.personalGet()
                ]);

            } catch (error) {

                console.error(
                    'Error al cargar datos:',
                    error
                );

            }

        },


        personasGet() {

            return axios.get(
                'http://localhost:8000/api/personas',
                {
                    params: {
                        limit: 50,
                        tipo_persona: 'PERSONAL',
                        estado: true
                    }
                }
            )
            .then(response => {

                this.personas = response.data.data;

            });

        },


        cargosGet() {

            return axios.get(
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

            });

        },


        personalGet() {

            return axios.get(
                'http://localhost:8000/api/personal',
                {
                    params: {
                        limit: 50
                    }
                }
            )
            .then(response => {

                this.personalRegistrado =
                    response.data.data;

            });

        },


        nombrePersona(persona) {

            return [
                persona.nombres_razon_social,
                persona.apellidos
            ]
            .filter(Boolean)
            .join(' ');

        },


        crearPersonal() {

            this.guardando = true;

            const datos = {
                ...this.trabajador,

                tipo_contrato:
                    this.trabajador.tipo_contrato || null
            };

            axios.post(
                'http://localhost:8000/api/personal',
                datos
            )
            .then(response => {

                alert(response.data.message);

                this.$router.push('/personal');

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
                    'No se pudo registrar el personal.'
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
    max-width: 900px;
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
    background-color: white;
    padding: 25px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.seccion h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #1f2937;
}

.fila {
    display: flex;
    gap: 20px;
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
.campo select {
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
}

.campo small {
    color: #6b7280;
    margin-top: 5px;
}

.persona-info {
    display: flex;
    flex-direction: column;
    gap: 5px;
    background-color: #f9fafb;
    border: 1px solid #e5e7eb;
    padding: 15px;
    border-radius: 6px;
}

.botones {
    display: flex;
    gap: 10px;
}

.botones button {
    padding: 11px 18px;
    border: none;
    border-radius: 6px;
    background-color: #2563eb;
    color: white;
    cursor: pointer;
}

.botones .btn-cancelar {
    background-color: #6b7280;
}

.botones button:disabled {
    opacity: 0.6;
}

@media (max-width: 700px) {

    .fila {
        flex-direction: column;
        gap: 0;
    }

}

</style>