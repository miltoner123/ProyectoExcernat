<template>

    <div>

        <h1>Editar Lote</h1>

        <form @submit.prevent="actualizarLote">


            <div>

                <label>Presentación:</label>

                <select
                    v-model="lote.id_presentacion"
                    required
                >

                    <option
                        v-for="presentacion in presentaciones"
                        :key="presentacion.id_presentacion"
                        :value="presentacion.id_presentacion"
                    >

                        {{ presentacion.producto?.nombre }}
                        -
                        {{ presentacion.nombre_presentacion }}

                    </option>

                </select>

            </div>


            <div>

                <label>Código de lote:</label>

                <input
                    type="text"
                    v-model="lote.codigo_lote"
                    required
                >

            </div>


            <div>

                <label>Fecha de producción:</label>

                <input
                    type="date"
                    v-model="lote.fecha_produccion"
                    required
                >

            </div>

            <div>
                <label>Vida útil del producto:</label>

                <select v-model="tipoDuracion" @change="actualizarDuracion" required>
                    <option value="8">8 meses</option>
                    <option value="12">1 año</option>
                    <option value="18">1 año y medio</option>
                    <option value="otro">Otra duración</option>
                </select>
            </div>

            <div v-if="tipoDuracion === 'otro'">
                <label>Duración personalizada (meses):</label>

                <input
                    type="number"
                    v-model.number="lote.vida_util_meses"
                    min="1"
                    max="120"
                    required
                >
            </div>

            <div>
                <label>Fecha de vencimiento calculada:</label>

                <input
                    type="date"
                    :value="fechaVencimientoCalculada"
                    readonly
                >
            </div>


            <div>

                <label>Cantidad producida:</label>

                <input
                    type="number"
                    min="1"
                    v-model="lote.cantidad_producida"
                    required
                >

            </div>


            <div>

                <label>Estado:</label>

                <select v-model="lote.estado">

                    <option :value="true">
                        Activo
                    </option>

                    <option :value="false">
                        Inactivo
                    </option>

                </select>

            </div>


            <div>

                <label>Observación:</label>

                <textarea
                    v-model="lote.observacion"
                ></textarea>

            </div>


            <button type="submit">
                Actualizar Lote
            </button>

        </form>

    </div>

</template>


<script>

import axios from 'axios';

export default {

    data() {
            return {
                lote: {},
                presentaciones: [],
                tipoDuracion: '12'
            };
        },


    mounted() {

        this.presentacionesGet();

        this.loteGet();

    },
    computed: {

            fechaVencimientoCalculada() {

                if (!this.lote.fecha_produccion || !this.lote.vida_util_meses) {
                    return '';
                }

                const meses = Number(this.lote.vida_util_meses);

                if (!Number.isInteger(meses) || meses < 1 || meses > 120) {
                    return '';
                }

                const [anio, mes, dia] = this.lote.fecha_produccion.substring(0, 10).split('-').map(Number);

                const fecha = new Date(Date.UTC(anio, mes - 1 + meses, 1));

                const ultimoDia = new Date(Date.UTC(
                    fecha.getUTCFullYear(),
                    fecha.getUTCMonth() + 1,
                    0
                )).getUTCDate();

                fecha.setUTCDate(Math.min(dia, ultimoDia));

                return fecha.toISOString().substring(0, 10);
            }

        },

    methods: {

        presentacionesGet() {

            axios.get(
                'http://localhost:8000/api/presentaciones',
                {
                    params: {
                        limit: 100
                    }
                }
            )

            .then(response => {

                this.presentaciones =
                    response.data.data;

            });

        },


        loteGet() {

            const id =
                this.$route.params.id;


            axios.get(
                `http://localhost:8000/api/lotes/${id}`
            )

            .then(response => {

                this.lote = response.data.lote ?? response.data;

                const meses = Number(this.lote.vida_util_meses);

                this.tipoDuracion = [8, 12, 18].includes(meses)
                    ? String(meses)
                    : 'otro';

            })

            .catch(error => {

                console.error(error);

            });

        },

        actualizarDuracion() {
            if (this.tipoDuracion !== 'otro') {
                this.lote.vida_util_meses = Number(this.tipoDuracion);
            } else {
                this.lote.vida_util_meses = null;
            }

        },
        actualizarLote() {

            const id = this.$route.params.id;

            const { fecha_vencimiento, ...datosLote } = this.lote;

            const datos = {
                ...datosLote,
                vida_util_meses: Number(this.lote.vida_util_meses)
            };

            if (!Number.isInteger(datos.vida_util_meses) || datos.vida_util_meses < 1 || datos.vida_util_meses > 120) {
                alert('La vida útil debe estar entre 1 y 120 meses.');
                return;
            }

            axios.post(
                `http://localhost:8000/api/lotes/${id}`,
                datos
            )

            .then(() => {

                alert('Lote actualizado correctamente');

                this.$router.push('/lotes');

            })

            .catch(error => {

                console.error(error);

                if (error.response?.data?.errors) {

                    alert(
                        Object.values(error.response.data.errors)
                            .flat()
                            .join('\n')
                    );

                } else {

                    alert(
                        error.response?.data?.message ||
                        'Error al actualizar el lote'
                    );

                }

            });

        }

    }

};

</script>