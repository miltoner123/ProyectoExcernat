<template>

    <div class="formulario">

        <h1>Editar Ubicación</h1>

        <form
            v-if="cargado"
            @submit.prevent="actualizarUbicacion"
        >

            <div class="campo">
                <label>Nombre:</label>

                <input
                    type="text"
                    v-model="ubicacion.nombre"
                    required
                >
            </div>


            <div class="campo">
                <label>Tipo:</label>

                <select
                    v-model="ubicacion.tipo"
                    @change="cambiarTipo"
                    required
                >
                    <option value="ALMACEN">Almacén</option>
                    <option value="SUCURSAL">Sucursal</option>
                    <option value="STAND">Stand</option>
                </select>
            </div>


            <div class="campo">
                <label>Propiedad:</label>

                <select
                    v-model="ubicacion.propiedad"
                    :disabled="ubicacion.tipo === 'STAND'"
                    required
                >
                    <option value="PROPIA">Propia</option>
                    <option value="EXTERNA">Externa</option>
                    <option value="TEMPORAL">Temporal</option>
                </select>
            </div>


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
                    </option>

                </select>
            </div>


            <div class="campo">
                <label>Departamento:</label>

                <select
                    v-model="ubicacion.departamento"
                    required
                >
                    <option
                        v-for="departamento in departamentos"
                        :key="departamento"
                        :value="departamento"
                    >
                        {{ departamento }}
                    </option>
                </select>
            </div>


            <div class="campo">
                <label>Dirección:</label>

                <input
                    type="text"
                    v-model="ubicacion.direccion"
                >
            </div>


            <div class="campo">
                <label>Estado:</label>

                <select v-model="ubicacion.estado">
                    <option :value="true">Activo</option>
                    <option :value="false">Inactivo</option>
                </select>
            </div>


            <div class="botones">

                <button
                    type="submit"
                    :disabled="guardando"
                >
                    {{ guardando ? 'Actualizando...' : 'Actualizar Ubicación' }}
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

        <p v-else>
            Cargando ubicación...
        </p>

    </div>

</template>


<script>

import axios from 'axios';

export default {

    data() {

        return {

            ubicacion: {},

            eventos: [],

            cargado: false,

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

            return this.eventos.filter(evento => {

                return (
                    ['PLANIFICADO', 'ACTIVO'].includes(evento.estado)
                    ||
                    evento.id_evento === this.ubicacion.id_evento
                );

            });

        }

    },


    mounted() {

        this.eventosGet();

        this.ubicacionGet();

    },


    methods: {

        eventosGet() {

            axios.get(
                'http://localhost:8000/api/eventos'
            )

            .then(response => {

                this.eventos = response.data;

            })

            .catch(error => {

                console.error(
                    'Error al obtener eventos:',
                    error
                );

            });

        },


        ubicacionGet() {

            const id = this.$route.params.id;

            axios.get(
                `http://localhost:8000/api/ubicaciones/${id}`
            )

            .then(response => {

                this.ubicacion = response.data;

                this.cargado = true;

            })

            .catch(error => {

                console.error(error);

                alert('No se pudo cargar la ubicación.');

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


        actualizarUbicacion() {

            const id = this.$route.params.id;

            this.guardando = true;

            axios.post(
                `http://localhost:8000/api/ubicaciones/${id}`,
                this.ubicacion
            )

            .then(() => {

                alert('Ubicación actualizada correctamente.');

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
                        'No se pudo actualizar la ubicación.'
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
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    box-sizing: border-box;
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