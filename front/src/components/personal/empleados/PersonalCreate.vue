<template>

    <div class="formulario exc-modern">

        <div class="encabezado">
            <h1>Registrar Personal</h1>
            <p>Complete la información laboral del trabajador.</p>
        </div>

        <form @submit.prevent="crearPersonal">

            <div class="seccion">
                <h3>Trabajador</h3>

                <div class="campo">
                    <label for="buscar-persona">Buscar persona por nombre, apellido o CI:</label>
                    <input id="buscar-persona" v-model.trim="buscarPersona" type="search"
                        placeholder="Escriba un nombre o número de CI..." autocomplete="off"
                        @input="buscarPersonas" />
                    <small>Solo personas activas registradas como PERSONAL y sin asignación laboral.</small>
                    <small v-if="buscandoPersonas">Buscando personas...</small>
                    <p v-if="errorBusqueda" class="error">{{ errorBusqueda }}</p>
                    <div v-if="buscarPersona && !buscandoPersonas && personasDisponibles.length" class="resultados">
                        <button v-for="persona in personasDisponibles" :key="persona.id_persona" type="button"
                            class="resultado" @click="seleccionarPersona(persona)">
                            <strong>{{ nombrePersona(persona) }}</strong>
                            <span>CI/Documento: {{ persona.numero_documento || 'Sin documento' }}</span>
                        </button>
                    </div>
                    <small v-else-if="buscarPersona && !buscandoPersonas && !errorBusqueda && !personaSeleccionada">
                        No hay personas disponibles con esa búsqueda. Si ya están registradas como trabajadores, no se pueden asignar otra vez.
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
import api from '../../../services/api';

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
            buscarPersona: '',
            buscandoPersonas: false,
            temporizadorPersona: null,
            error: '',
            errorBusqueda: '',
            personaElegida: null,
            cargos: [],

            guardando: false

        };
    },

    computed: {

        personasDisponibles() {
            const registrados = new Set(this.personalRegistrado.map(p => Number(p.id_persona)));
            return this.personas.filter(p =>
                p.tipo_persona === 'PERSONAL' &&
                (p.estado === true || p.estado === 1 || p.estado === '1') &&
                !registrados.has(Number(p.id_persona))
            );
        },

        personaSeleccionada() {
            return this.personaElegida;
        }

    },

    mounted() {
        this.cargarDatos();
    },

    beforeUnmount() {
        clearTimeout(this.temporizadorPersona);
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

                console.error('Error al cargar datos:', error);
                alert(error.response?.data?.message || 'No se pudieron cargar los datos.');

            }

        },


        buscarPersonas() {
            clearTimeout(this.temporizadorPersona);
            this.trabajador.id_persona = '';
            this.personaElegida = null;
            this.temporizadorPersona = setTimeout(() => this.personasGet(), 350);
        },

        async obtenerPaginas(url, params = {}) {
            let pagina = 1;
            let ultimaPagina = 1;
            const registros = [];
            do {
                const response = await api.get(url, { params: { ...params, page: pagina, limit: 50 } });
                registros.push(...(response.data.data || []));
                ultimaPagina = response.data.last_page || 1;
                pagina++;
            } while (pagina <= ultimaPagina);
            return registros;
        },

        async personasGet() {
            this.buscandoPersonas = true;
            this.errorBusqueda = '';
            const busquedaActual = this.buscarPersona;
            try {
                const resultados = await this.obtenerPaginas('/api/personas', {
                    buscar: busquedaActual,
                    tipo_persona: 'PERSONAL',
                    estado: 'true'
                });
                if (busquedaActual === this.buscarPersona) this.personas = resultados;
            } catch (error) {
                this.errorBusqueda = error.response?.data?.message || 'No se pudieron buscar las personas.';
                this.personas = [];
            } finally {
                if (busquedaActual === this.buscarPersona) this.buscandoPersonas = false;
            }
        },

        async cargosGet() {
            this.cargos = await this.obtenerPaginas('/api/cargos', { estado: 'true' });
        },

        async personalGet() {
            this.personalRegistrado = await this.obtenerPaginas('/api/personal');
        },


        seleccionarPersona(persona) {
            this.trabajador.id_persona = persona.id_persona;
            this.personaElegida = persona;
            this.buscarPersona = '';
            this.personas = [];
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

            if (!this.trabajador.id_persona) {
                alert('Busque y seleccione una persona antes de guardar.');
                return;
            }
            this.guardando = true;

            const datos = {
                ...this.trabajador,

                tipo_contrato:
                    this.trabajador.tipo_contrato || null
            };

            api.post(
                '/api/personal',
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


.resultados { border: 1px solid #d1d5db; border-radius: 6px; max-height: 240px; overflow-y: auto; }
.resultado { display: flex; flex-direction: column; width: 100%; padding: 12px; text-align: left; background: white; border: 0; border-bottom: 1px solid #e5e7eb; cursor: pointer; color: #1f2937; }
.resultado:hover { background: #eff6ff; }
.resultado span { margin-top: 4px; font-size: 13px; color: #6b7280; }
.error { color: #b91c1c; font-size: 13px; }
</style>
<style src="../../../styles/excernat-formularios.css"></style>
