<template>

    <div>

        <h1>Crear Presentación</h1>

        <p>
            Registrar una nueva presentación de producto
        </p>


        <form @submit.prevent="crearPresentacion">


            <div>

                <label>
                    Producto:
                </label>

                <select
                    v-model="presentacion.id_producto"
                    required
                >

                    <option value="" disabled>
                        Seleccione un producto
                    </option>

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

                <label>
                    Peso neto:
                </label>

                <input
                    type="number"
                    step="0.01"
                    v-model="presentacion.peso_neto"
                    required
                >

            </div>


            <div>

                <label>
                    Unidad de medida:
                </label>

                <select
                    v-model="presentacion.unidad_medida"
                    required
                >

                    <option value="">
                        Seleccione
                    </option>

                    <option value="g">
                        Gramos (g)
                    </option>

                    <option value="kg">
                        Kilogramos (kg)
                    </option>

                </select>

            </div>


            <div>

                <label>
                    Tipo de envase:
                </label>

                <select
                    v-model="presentacion.tipo_envase"
                    required
                >

                    <option value="">
                        Seleccione
                    </option>

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
                    required
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
                    required
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


            <button type="submit">

                Crear Presentación

            </button>

        </form>

    </div>

</template>


<script>

import axios from 'axios';

export default {

    data() {

        return {

            presentacion: {

                id_producto: '',

                codigo_sku: '',

                nombre_presentacion: '',

                peso_neto: '',

                unidad_medida: '',

                tipo_envase: '',

                unidades_por_paquete: '',

                precio_unitario: '',

                precio_paquete: '',

                estado: true

            },

            productos: []

        };

    },


    mounted() {

        this.productosGet();

    },


    methods: {

        productosGet() {

            axios.get(
                'http://localhost:8000/api/productos?limit=100'
            )

            .then(response => {

                this.productos =
                    response.data.data;

            })

            .catch(error => {

                console.error(
                    'Error al obtener productos:',
                    error
                );

            });

        },


        crearPresentacion() {

            axios.post(
                'http://localhost:8000/api/presentaciones',
                this.presentacion
            )

            .then(response => {

                console.log(
                    'Presentación creada:',
                    response.data
                );

                this.$router.push(
                    '/presentaciones'
                );

            })

            .catch(error => {

                console.error(
                    'Error al crear presentación:',
                    error
                );

            });

        }

    }

};

</script>


<style scoped>

form {
    display: flex;
    flex-direction: column;
    max-width: 500px;
}

div {
    margin-bottom: 10px;
}

label {
    font-weight: bold;
}

input,
select {
    width: 100%;
    padding: 8px;
    box-sizing: border-box;
}

button {
    padding: 10px;

    background-color: #007BFF;

    color: white;

    border: none;

    cursor: pointer;
}

button:hover {
    background-color: #0056b3;
}

</style>