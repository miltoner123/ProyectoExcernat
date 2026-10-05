<template>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">

        <h1>Presentaciones</h1>

        <button @click="crearPresentacion">
            Crear Presentación
        </button>

    </div>


    <table>

        <thead>

            <tr>

                <th>ID</th>
                <th>Producto</th>
                <th>SKU</th>
                <th>Presentación</th>
                <th>Peso</th>
                <th>Envase</th>
                <th>U./Paquete</th>
                <th>Precio Unitario</th>
                <th>Precio Paquete</th>
                <th>Estado</th>
                <th>Opciones</th>

            </tr>

        </thead>


        <tbody>

            <tr
                v-for="presentacion in presentaciones"
                :key="presentacion.id_presentacion"
            >

                <td>
                    {{ presentacion.id_presentacion }}
                </td>

                <td>
                    {{ presentacion.producto?.nombre }}
                </td>

                <td>
                    {{ presentacion.codigo_sku }}
                </td>

                <td>
                    {{ presentacion.nombre_presentacion }}
                </td>

                <td>
                    {{ presentacion.peso_neto }}
                    {{ presentacion.unidad_medida }}
                </td>

                <td>
                    {{ presentacion.tipo_envase }}
                </td>

                <td>
                    {{ presentacion.unidades_por_paquete }}
                </td>

                <td>
                    Bs {{ presentacion.precio_unitario }}
                </td>

                <td>
                    Bs {{ presentacion.precio_paquete }}
                </td>

                <td>
                    {{ presentacion.estado ? 'Activo' : 'Inactivo' }}
                </td>

                <td>

                    <button
                        @click="editarPresentacion(
                            presentacion.id_presentacion
                        )"
                    >
                        Editar
                    </button>

                    <button
                        @click="eliminarPresentacion(
                            presentacion.id_presentacion
                        )"
                    >
                        Eliminar
                    </button>

                </td>

            </tr>

        </tbody>

    </table>

</template>


<script>

import axios from 'axios';

export default {

    data() {

        return {

            presentaciones: [],

            pagination: {
                current_page: 1,
                last_page: 1,
                per_page: 10,
                total: 0
            }

        };

    },


    mounted() {

        this.presentacionesGet();

    },


    methods: {

        presentacionesGet() {

            axios.get(
                'http://localhost:8000/api/presentaciones?limit=10&page='
                + this.pagination.current_page
            )

            .then(response => {

                this.presentaciones = response.data.data;

                this.pagination.last_page =
                    response.data.last_page;

                this.pagination.total =
                    response.data.total;

            })

            .catch(error => {

                console.error(
                    'Error al obtener presentaciones:',
                    error
                );

            });

        },


        crearPresentacion() {

            this.$router.push(
                '/presentaciones/crear'
            );

        },


        editarPresentacion(id) {

            this.$router.push(
                `/presentaciones/editar/${id}`
            );

        },


        eliminarPresentacion(id) {

            if (
                confirm(
                    '¿Estás seguro de eliminar esta presentación?'
                )
            ) {

                axios.delete(
                    `http://localhost:8000/api/presentaciones/${id}`
                )

                .then(() => {

                    this.presentacionesGet();

                })

                .catch(error => {

                    console.error(
                        'Error al eliminar:',
                        error
                    );

                });

            }

        }

    }

};

</script>