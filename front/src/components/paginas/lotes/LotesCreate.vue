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
                <label>Fecha de vencimiento:</label>
                <input type="date" v-model="lote.fecha_vencimiento" required>
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

import axios from 'axios';
export default {
    data() {
        return {
            lote: {
                id_presentacion: '',
                codigo_lote: '',
                fecha_produccion: '',
                fecha_vencimiento: '',
                cantidad_producida: '',
                estado: true,
                observacion: ''
            },
            presentaciones: []
        };

    },


    mounted() {
        this.presentacionesGet();
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
            })
            .catch(error => {
                console.error(
                    'Error al cargar presentaciones:',
                    error
                );

            });

        },
        crearLote() {
            axios.post(
                'http://localhost:8000/api/lotes',
                this.lote
            )
            .then(() => {

                this.$router.push(
                    '/lotes'
                );
            })
            .catch(error => {
                console.error(error);
                if (error.response?.data?.errors) {
                    const errores =
                        error.response.data.errors;
                    alert(
                        Object.values(errores)
                            .flat()
                            .join('\n')
                    );
                } else {
                    alert(
                        'No se pudo registrar el lote'
                    );
                }
            });
        }
    }
};

</script>