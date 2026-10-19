<template>
    <div>
        <h1>Crear Lote</h1>
        <form @submit.prevent="crearLote">
            <div>
                <label>Presentación:</label>
                <select v-model="lote.id_presentacion" required>

                    <option value="" disabled>
                        Seleccione una presentación
                    </option>

                    <option v-for="presentacion in presentaciones" :key="presentacion.id_presentacion" :value="presentacion.id_presentacion">

                        {{ presentacion.producto?.nombre }}
                        {{ presentacion.nombre_presentacion }}                        -
                        {{ presentacion.codigo_sku }}

                    </option>
                </select>
            </div>
            <div>
                <label>Código de lote:</label>
                <input type="text" v-model="lote.codigo_lote" required>
            </div>
            <div>
                <label>Fecha de producción:</label>
                <input type="date" v-model="lote.fecha_produccion" required >
            </div>
            <div>
                <label>Vida útil del producto:</label>

                <select v-model="tipoDuracion" @change="actualizarDuracion">
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
                    readonly  >

                <small>
                    La fecha se calcula automáticamente según la producción
                    y la vida útil seleccionada.
                </small>
            </div>
            <div>
                <label>Cantidad producida:</label>
                <input type="number" min="1" v-model="lote.cantidad_producida" required>
            </div>
            <div>
                <label>Observación:</label>
                <textarea   model="lote.observacion" ></textarea>
            </div>
            <button type="submit">
                Crear Lote
            </button>

        </form>
    </div>
</template>
<script>

import api from '../../../services/api';
export default {
    data() {
        return {
            lote: {
                id_presentacion: '',
                codigo_lote: '',
                fecha_produccion: '',
                vida_util_meses: 12,    
                fecha_vencimiento: '',
                cantidad_producida: '',
                estado: true,
                tipoDuracion: '12',
                observacion: ''
            },
            presentaciones: []
        };

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

            const [anio, mes, dia] = this.lote.fecha_produccion.split('-').map(Number);

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

    mounted() {
        this.presentacionesGet();
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
            })
            .catch(error => {
                console.error(
                    'Error al cargar presentaciones:',
                    error
                );

            });

        },
        crearLote() {
            const { fecha_vencimiento, ...datosLote } = this.lote;
            const datos = {
                ...datosLote,
                vida_util_meses: Number(this.lote.vida_util_meses)
            };

            api.post('http://localhost:8000/api/lotes', datos)
                .then(response => {
                    alert('Lote registrado correctamente');
                    this.$router.push('/lotes');
                })
                .catch(error => {
                    console.error(error.response?.data || error);
                    alert('Error al registrar el lote');
                });
        },
        actualizarDuracion() {

            if (this.tipoDuracion !== 'otro') {
                this.lote.vida_util_meses = Number(this.tipoDuracion);
            } else {
                this.lote.vida_util_meses = null;
            }

        }
    }
};

</script>