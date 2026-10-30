<template>

    <div class="exc-modern">

        <header class="lote-head"><div><h1>Editar lote</h1><p>Actualiza los datos del lote y revisa su fecha de vencimiento.</p></div><router-link to="/lotes" class="volver-lotes">Volver a lotes</router-link></header>

        <form class="lote-form" @submit.prevent="actualizarLote">


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


            <div class="lote-actions"><router-link to="/lotes" class="volver-lotes">Cancelar</router-link><button type="submit" class="guardar-lote">Actualizar lote</button></div>

        </form>

    </div>

</template>


<script>

import api from '../../../services/api';

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

            api.get(
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


            api.get(
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

            api.post(
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
<style scoped>
.exc-modern{max-width:1080px;margin:22px auto;padding:26px;background:#fff;border:1px solid #dbe8df;border-radius:17px;box-shadow:0 10px 26px rgba(12,48,37,.06);color:#123d2d}
.lote-head{display:flex;justify-content:space-between;align-items:center;gap:15px;flex-wrap:wrap;padding:0 0 20px;margin-bottom:22px;border-bottom:1px solid #dce8df}
.lote-head h1{font-size:26px;color:#0c3025;margin:0 0 5px}.lote-head p{margin:0;color:#62796c;font-size:14px}
.lote-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
.lote-form>div:not(.lote-actions){display:flex;flex-direction:column;gap:7px;min-width:0}
.lote-form label{font-weight:700;color:#124734;font-size:13px}
.lote-form input,.lote-form select,.lote-form textarea{width:100%;min-height:44px;box-sizing:border-box;padding:10px 12px;border:1px solid #cbded2;border-radius:9px;background:#fff;color:#183d2e;font:inherit}
.lote-form textarea{min-height:95px;resize:vertical}
.lote-form input:focus,.lote-form select:focus,.lote-form textarea:focus{outline:none;border-color:#f47b20;box-shadow:0 0 0 3px rgba(244,123,32,.14)}
.lote-form input[readonly]{background:#edf4ef;color:#496e59}
.lote-actions{grid-column:1/-1;display:flex;justify-content:flex-end;gap:12px;padding-top:18px;border-top:1px solid #dce8df}
.volver-lotes,.guardar-lote{display:inline-flex;align-items:center;justify-content:center;text-decoration:none;border-radius:9px;padding:11px 18px;font-weight:700;font-size:13px;cursor:pointer}
.volver-lotes{background:#edf4ef;border:1px solid #cbded2;color:#124734}.guardar-lote{background:#f47b20;color:#fff;border:1px solid #f47b20}
@media(max-width:700px){.exc-modern{padding:15px;margin:10px}.lote-form{grid-template-columns:1fr}.lote-head h1{font-size:22px}}
</style>
