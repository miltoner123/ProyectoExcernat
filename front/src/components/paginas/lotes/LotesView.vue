<template>

    <div>

        <div class="encabezado">

            <div>
                <h1>Lotes</h1>
                <span>Gestión de lotes de producción</span>
            </div>

            <button @click="crearLote">
                Crear Lote
            </button>

        </div>


        <!-- FILTROS -->

        <div class="filtros">

            <input
                type="text"
                v-model="buscar"
                @input="buscarLotes"
                placeholder="Buscar lote, producto, SKU o presentación..."
            >


            <select
                v-model="estado"
                @change="filtrarLotes"
            >

                <option value="">
                    Todos los estados
                </option>

                <option value="true">
                    Activos
                </option>

                <option value="false">
                    Inactivos
                </option>

            </select>


            <select
                v-model="pagination.per_page"
                @change="cambiarLimite"
            >

                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>

            </select>

        </div>


        <!-- TABLA -->

        <table>

            <thead>

                <tr>
                    <th>ID</th>
                    <th>Lote</th>
                    <th>Producto</th>
                    <th>Presentación</th>
                    <th>Producción</th>
                    <th>Vencimiento</th>
                    <th>Cantidad</th>
                    <th>Estado</th>
                    <th>Opciones</th>
                </tr>

            </thead>


            <tbody>

                <tr
                    v-for="lote in lotes"
                    :key="lote.id_lote"
                >

                    <td>
                        {{ lote.id_lote }}
                    </td>

                    <td>
                        {{ lote.codigo_lote }}
                    </td>

                    <td>
                        {{ lote.presentacion?.producto?.nombre }}
                    </td>

                    <td>
                        {{ lote.presentacion?.nombre_presentacion }}
                    </td>

                    <td>
                        {{ lote.fecha_produccion }}
                    </td>

                    <td>
                        {{ lote.fecha_vencimiento }}
                    </td>

                    <td>
                        {{ lote.cantidad_producida }}
                    </td>

                    <td>
                        {{ lote.estado ? 'Activo' : 'Inactivo' }}
                    </td>

                    <td>

                        <button
                            @click="editarLote(lote.id_lote)"
                        >
                            Editar
                        </button>

                        <button
                            @click="eliminarLote(lote.id_lote)"
                        >
                            Eliminar
                        </button>

                    </td>

                </tr>


                <tr v-if="lotes.length === 0">

                    <td colspan="9">
                        No se encontraron lotes.
                    </td>

                </tr>

            </tbody>

        </table>


        <!-- PAGINACIÓN -->

        <div class="paginacion">

            <button
                @click="paginaAnterior"
                :disabled="pagination.current_page <= 1"
            >
                Anterior
            </button>


            <span>

                Página
                {{ pagination.current_page }}
                de
                {{ pagination.last_page }}

            </span>


            <button
                @click="paginaSiguiente"
                :disabled="
                    pagination.current_page >=
                    pagination.last_page
                "
            >
                Siguiente
            </button>

        </div>


        <p>
            Total de registros:
            {{ pagination.total }}
        </p>

    </div>

</template>


<script>

import axios from 'axios';

export default {

    data() {

        return {

            lotes: [],

            buscar: '',

            estado: '',

            temporizadorBusqueda: null,

            pagination: {

                current_page: 1,

                last_page: 1,

                per_page: 10,

                total: 0

            }

        };

    },


    mounted() {

        this.lotesGet();

    },


    methods: {

        lotesGet() {

            axios.get(
                'http://localhost:8000/api/lotes',
                {
                    params: {

                        page:
                            this.pagination.current_page,

                        limit:
                            this.pagination.per_page,

                        buscar:
                            this.buscar,

                        estado:
                            this.estado

                    }
                }
            )

            .then(response => {

                this.lotes =
                    response.data.data;

                this.pagination.current_page =
                    response.data.current_page;

                this.pagination.last_page =
                    response.data.last_page;

                this.pagination.total =
                    response.data.total;

            })

            .catch(error => {

                console.error(
                    'Error al obtener lotes:',
                    error
                );

            });

        },


        buscarLotes() {

            clearTimeout(
                this.temporizadorBusqueda
            );


            this.temporizadorBusqueda =
                setTimeout(() => {

                    this.pagination.current_page = 1;

                    this.lotesGet();

                }, 400);

        },


        filtrarLotes() {

            this.pagination.current_page = 1;

            this.lotesGet();

        },


        cambiarLimite() {

            this.pagination.current_page = 1;

            this.lotesGet();

        },


        paginaAnterior() {

            if (
                this.pagination.current_page > 1
            ) {

                this.pagination.current_page--;

                this.lotesGet();

            }

        },


        paginaSiguiente() {

            if (
                this.pagination.current_page <
                this.pagination.last_page
            ) {

                this.pagination.current_page++;

                this.lotesGet();

            }

        },


        crearLote() {

            this.$router.push(
                '/lotes/crear'
            );

        },


        editarLote(id) {

            this.$router.push(
                `/lotes/editar/${id}`
            );

        },


        eliminarLote(id) {

            if (
                confirm(
                    '¿Está seguro de eliminar este lote?'
                )
            ) {

                axios.delete(
                    `http://localhost:8000/api/lotes/${id}`
                )

                .then(() => {

                    this.lotesGet();

                })

                .catch(error => {

                    alert(
                        error.response?.data?.message
                        ||
                        'Error al eliminar el lote'
                    );

                });

            }

        }

    }

};

</script>