<template>

    <div class="formulario">

        <h1>Registrar Ubicación</h1>

        <form @submit.prevent="crearUbicacion">

            <!-- NOMBRE -->

            <div class="campo">
                <label>Nombre:</label>

                <input
                    type="text"
                    v-model="ubicacion.nombre"
                    placeholder="Ej. Almacén Central"
                    required
                >
            </div>


            <!-- TIPO -->

            <div class="campo">
                <label>Tipo de ubicación:</label>

                <select
                    v-model="ubicacion.tipo"
                    @change="cambiarTipo"
                    required
                >
                    <option value="" disabled>
                        Seleccione un tipo
                    </option>

                    <option value="ALMACEN">
                        Almacén
                    </option>

                    <option value="SUCURSAL">
                        Sucursal
                    </option>

                    <option value="STAND">
                        Stand
                    </option>
                </select>
            </div>


            <!-- PROPIEDAD -->

            <div class="campo">
                <label>Propiedad:</label>

                <select
                    v-model="ubicacion.propiedad"
                    :disabled="ubicacion.tipo === 'STAND'"
                    required
                >
                    <option value="" disabled>
                        Seleccione una opción
                    </option>

                    <option value="PROPIA">
                        Propia
                    </option>

                    <option value="EXTERNA">
                        Externa
                    </option>

                    <option value="TEMPORAL">
                        Temporal
                    </option>
                </select>
            </div>


            <!-- EVENTO: SOLO PARA STAND -->

            <div
                class="campo"
                v-if="ubicacion.tipo === 'STAND'"
            >
                <label>Evento:</label>

                <select
                    v-model="ubicacion.id_evento"
                    required
                >
                    <option :value="null" disabled>
                        Seleccione un evento
                    </option>

                    <option
                        v-for="evento in eventosDisponibles"
                        :key="evento.id_evento"
                        :value="evento.id_evento"
                    >
                        {{ evento.nombre }}
                        - {{ evento.departamento }}
                    </option>
                </select>

                <small>
                    El stand quedará asociado al evento seleccionado.
                </small>
            </div>


            <!-- DEPARTAMENTO -->

            <div class="campo">
                <label>Departamento:</label>

                <select
                    v-model="ubicacion.departamento"
                    required
                >
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


            <!-- DIRECCIÓN -->

            <div class="campo">
                <label>Dirección:</label>

                <input
                    type="text"
                    v-model="ubicacion.direccion"
                    placeholder="Ingrese la dirección"
                >
            </div>


            <!-- ESTADO -->

            <div class="campo">
                <label>Estado:</label>

                <select v-model="ubicacion.estado">
                    <option :value="true">
                        Activo
                    </option>

                    <option :value="false">
                        Inactivo
                    </option>
                </select>
            </div>


            <!-- BOTONES -->

            <div class="botones">

                <button
                    type="submit"
                    :disabled="guardando"
                >
                    {{ guardando ? 'Guardando...' : 'Guardar Ubicación' }}
                </button>

                <button
                    type="button"
                    class="btn-cancelar"
                    @click="$router.push('/ubicaciones')"
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

            ubicacion: {

                id_evento: null,
                nombre: '',
                tipo: '',
                propiedad: '',
                departamento: '',
                direccion: '',
                estado: true

            },

            eventos: [],

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


    computed: {

        eventosDisponibles() {
            return this.eventos.filter(evento =>
                evento.estado === true
            );
        }

    },


    mounted() {

        this.eventosGet();

        if (this.$route.query.tipo) {
            this.ubicacion.tipo = this.$route.query.tipo;
        }

        if (this.$route.query.id_evento) {
            this.ubicacion.id_evento = Number(
                this.$route.query.id_evento
            );
        }

    },

    methods: {

        eventosGet() {

            axios.get(
                'http://localhost:8000/api/eventos'
            )

                    .then(response => {
                this.eventos = response.data.data;
            })
            .catch(error => {

                console.error(
                    'Error al obtener eventos:',
                    error
                );

            });

        },


        cambiarTipo() {

            if (this.ubicacion.tipo === 'STAND') {

                this.ubicacion.propiedad = 'TEMPORAL';

            } else {

                this.ubicacion.id_evento = null;

                if (this.ubicacion.propiedad === 'TEMPORAL') {
                    this.ubicacion.propiedad = '';
                }

            }

        },


        crearUbicacion() {

            this.guardando = true;

            axios.post(
                'http://localhost:8000/api/ubicaciones',
                this.ubicacion
            )

            .then(() => {

                alert('Ubicación registrada correctamente.');

                this.$router.push('/ubicaciones');

            })

            .catch(error => {

                if (error.response?.data?.errors) {

                    alert(
                        Object.values(error.response.data.errors)
                            .flat()
                            .join('\n')
                    );

                } else {

                    alert(
                        error.response?.data?.message ||
                        'No se pudo registrar la ubicación.'
                    );

                }

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
    max-width: 700px;
    margin: 0 auto;
}

.formulario h1 {
    margin-bottom: 20px;
}

.campo {
    display: flex;
    flex-direction: column;
    margin-bottom: 16px;
    gap: 6px;
}

.campo label {
    font-weight: 600;
}

.campo input,
.campo select {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    width: 100%;
    box-sizing: border-box;
}

.campo small {
    color: #777;
}

.botones {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.btn-cancelar {
    background: #6c757d;
    color: white;
}

button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

</style>