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

                <label>Fecha de vencimiento:</label>

                <input
                    type="date"
                    v-model="lote.fecha_vencimiento"
                    required
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

            presentaciones: []

        };

    },


    mounted() {

        this.presentacionesGet();

        this.loteGet();

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

                this.lote =
                    response.data;

            })

            .catch(error => {

                console.error(error);

            });

        },


        actualizarLote() {

            const id =
                this.$route.params.id;


            axios.post(
                `http://localhost:8000/api/lotes/${id}`,
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

                    alert(
                        Object.values(
                            error.response.data.errors
                        )
                        .flat()
                        .join('\n')
                    );

                }

            });

        }

    }

};

</script>