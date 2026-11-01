<template>

    <div class="formulario">

        <h1>Editar Ubicación</h1>
        <p class="subtitulo">Actualice la información de la ubicación registrada.</p>

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
                    class="btn-guardar"
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

import api from '../../../services/api';

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

            return (Array.isArray(this.eventos) ? this.eventos : []).filter(evento => {

                return (
                    ['PLANIFICADO', 'ACTIVO'].includes(String(evento.estado || '').toUpperCase())
                    ||
                    String(evento.id_evento) === String(this.ubicacion.id_evento)
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

            api.get(
                'http://localhost:8000/api/eventos'
            )

            .then(response => {

                const payload = response.data;
                this.eventos = Array.isArray(payload)
                    ? payload
                    : Array.isArray(payload?.data)
                        ? payload.data
                        : Array.isArray(payload?.data?.data)
                            ? payload.data.data
                            : [];

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

            api.get(
                `http://localhost:8000/api/ubicaciones/${id}`
            )

            .then(response => {

                this.ubicacion = response.data?.data ?? response.data;

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

            api.post(
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
.formulario{width:min(100%,900px);max-width:900px;margin:20px auto;background:#fff;border:1px solid #d8e7dd;border-radius:16px;padding:30px;box-shadow:0 10px 28px #0d352217;box-sizing:border-box;color:#0b3025}
h1{font-size:27px;margin:0 0 6px;font-weight:800;color:#0b3025}.subtitulo{margin:0 0 25px;color:#668073;font-size:14px}
.campo{display:flex;flex-direction:column;gap:8px;margin-bottom:19px}.campo label{font-weight:700;font-size:14px;color:#123d2f}
.campo input,.campo select{display:block;width:100%;min-height:44px;padding:10px 13px;border:1px solid #cbded1;border-radius:10px;background:#fff;color:#163d30;font:inherit;box-sizing:border-box}
.campo input:focus,.campo select:focus{outline:2px solid #f47b2066;border-color:#f47b20}
.campo select:disabled{background:#eef4f0;color:#63796b}
.botones{display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap;margin-top:25px;padding-top:18px;border-top:1px solid #e4ede6}
.botones button{padding:11px 19px;border-radius:10px;font-weight:700;font-size:14px;border:1px solid transparent;cursor:pointer}
.btn-guardar{background:#f47b20;color:#fff}.btn-guardar:hover{background:#df6810}
.btn-cancelar{background:#fff;color:#124734;border-color:#cbded1!important}.btn-cancelar:hover{background:#eff5f0}
button:disabled{opacity:.6;cursor:not-allowed}
@media(max-width:650px){.formulario{padding:18px;margin:8px auto}.botones button{flex:1}}
</style>
