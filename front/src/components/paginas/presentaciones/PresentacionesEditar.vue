<template>

    <div>

        <h1>Editar Presentación</h1>


        <form @submit.prevent="actualizarPresentacion">


            <div>

                <label>
                    Producto:
                </label>

                <select
                    v-model="presentacion.id_producto"
                    required
                >

                    <option
                        v-for="producto in productos"
                        :key="producto.id"
                        :value="producto.id"
                    >

                        {{ producto.nombre }}

                    </option>

                </select>

            </div>


            <div>

                <label>
                    Código SKU:
                </label>

                <input
                    type="text"
                    v-model="presentacion.codigo_sku"
                    required
                >

            </div>


            <div>

                <label>
                    Nombre presentación:
                </label>

                <input
                    type="text"
                    v-model="presentacion.nombre_presentacion"
                    required
                >

            </div>


            <div>

                <label>Peso neto:</label>

                <input
                    type="number"
                    step="0.01"
                    v-model="presentacion.peso_neto"
                    required
                >

            </div>


            <div>

                <label>Unidad de medida:</label>

                <select
                    v-model="presentacion.unidad_medida"
                >

                    <option value="g">
                        Gramos (g)
                    </option>

                    <option value="kg">
                        Kilogramos (kg)
                    </option>

                </select>

            </div>


            <div>

                <label>Tipo envase:</label>

                <select
                    v-model="presentacion.tipo_envase"
                >

                    <option value="Caja">
                        Caja
                    </option>

                    <option value="Bolsa">
                        Bolsa
                    </option>

                </select>

            </div>


            <div>

                <label>
                    Unidades por paquete:
                </label>

                <input
                    type="number"
                    v-model="presentacion.unidades_por_paquete"
                >

            </div>


            <div>

                <label>
                    Precio unitario:
                </label>

                <input
                    type="number"
                    step="0.01"
                    v-model="presentacion.precio_unitario"
                >

            </div>


            <div>

                <label>
                    Precio paquete:
                </label>

                <input
                    type="number"
                    step="0.01"
                    v-model="presentacion.precio_paquete"
                >

            </div>


            <div>

                <label>
                    Estado:
                </label>

                <select
                    v-model="presentacion.estado"
                >

                    <option :value="true">
                        Activo
                    </option>

                    <option :value="false">
                        Inactivo
                    </option>

                </select>

            </div>


            <button type="submit">

                Actualizar Presentación

            </button>

        </form>

    </div>

</template>


<script>

import axios from 'axios';

export default {

    data() {

        return {

            presentacion: {},

            productos: []

        };

    },


    mounted() {

        this.productosGet();

        this.presentacionGet();

    },


    methods: {

        productosGet() {

            axios.get(
                'http://localhost:8000/api/productos?limit=100'
            )

            .then(response => {

                this.productos =
                    response.data.data;

            });

        },


        presentacionGet() {

            const id =
                this.$route.params.id;

            axios.get(
                `http://localhost:8000/api/presentaciones/${id}`
            )

            .then(response => {

                this.presentacion =
                    response.data;

            })

            .catch(error => {

                console.error(error);

            });

        },


        actualizarPresentacion() {

            const id =
                this.$route.params.id;

            axios.post(
                `http://localhost:8000/api/presentaciones/${id}`,
                this.presentacion
            )

            .then(response => {

                console.log(response.data);

                this.$router.push(
                    '/presentaciones'
                );

            })

            .catch(error => {

                console.error(
                    'Error al actualizar:',
                    error
                );

            });

        }

    }

};

</script>