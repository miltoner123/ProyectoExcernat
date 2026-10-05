
<template>
    <div class="movimientos">

        <div class="encabezado">
            <div>
                <h1>Movimientos de Inventario</h1>
                <p>Historial de entradas, salidas y transferencias.</p>
            </div>

            <button @click="$router.push('/movimientos/crear')">
                + Nuevo Movimiento
            </button>
        </div>

        <!-- FILTROS -->

        <div class="filtros">

            <input
                v-model="buscar"
                @input="buscarMovimientos"
                placeholder="Buscar referencia, lote o ubicación..."
            >

            <select v-model="tipo" @change="filtrar">
                <option value="">Todos los movimientos</option>
                <option value="ENTRADA">Entrada</option>
                <option value="SALIDA">Salida</option>
                <option value="TRANSFERENCIA">Transferencia</option>
                <option value="DEVOLUCION">Devolución</option>
                <option value="AJUSTE">Ajuste</option>
            </select>

            <select v-model.number="limite" @change="filtrar">
                <option :value="10">10 registros</option>
                <option :value="25">25 registros</option>
                <option :value="50">50 registros</option>
            </select>

        </div>

        <!-- TABLA -->

        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Origen</th>
                        <th>Destino</th>
                        <th>Referencia</th>
                        <th>Lotes</th>
                        <th>Opciones</th>
                    </tr>
                </thead>

                <tbody>

                    <tr
                        v-for="movimiento in movimientos"
                        :key="movimiento.id_movimiento"
                    >
                        <td>{{ movimiento.id_movimiento }}</td>

                        <td>
                            {{ formatearFecha(movimiento.fecha_movimiento) }}
                        </td>

                        <td>
                            <span class="etiqueta">
                                {{ movimiento.tipo }}
                            </span>
                        </td>

                        <td>
                            {{ movimiento.origen?.nombre || '—' }}
                        </td>

                        <td>
                            {{ movimiento.destino?.nombre || '—' }}
                        </td>

                        <td>
                            {{ movimiento.referencia || '—' }}
                        </td>

                        <td>
                            {{ movimiento.detalles?.length || 0 }}
                        </td>

                        <td>
                            <button
                                @click="verDetalle(movimiento.id_movimiento)"
                            >
                                Ver detalle
                            </button>
                        </td>
                    </tr>

                    <tr v-if="!cargando && movimientos.length === 0">
                        <td colspan="8">
                            No se encontraron movimientos.
                        </td>
                    </tr>

                    <tr v-if="cargando">
                        <td colspan="8">Cargando movimientos...</td>
                    </tr>

                </tbody>
            </table>
        </div>

        <!-- PAGINACIÓN -->

        <div class="paginacion">

            <span>Total: {{ total }} registros</span>

            <div class="controles">

                <button
                    :disabled="pagina <= 1"
                    @click="cambiarPagina(pagina - 1)"
                >
                    Anterior
                </button>

                <span>Página {{ pagina }} de {{ ultimaPagina }}</span>

                <button
                    :disabled="pagina >= ultimaPagina"
                    @click="cambiarPagina(pagina + 1)"
                >
                    Siguiente
                </button>

            </div>

        </div>

    </div>
</template>

<script>
import axios from 'axios';

export default {

    data() {
        return {
            movimientos: [],
            buscar: '',
            tipo: '',
            limite: 10,
            pagina: 1,
            ultimaPagina: 1,
            total: 0,
            cargando: false,
            temporizador: null,
            numeroPeticion: 0
        };
    },

    mounted() {
        this.obtenerMovimientos();
    },

    beforeUnmount() {
        clearTimeout(this.temporizador);
    },

    methods: {

        obtenerMovimientos() {

            const peticionActual = ++this.numeroPeticion;

            this.cargando = true;

            axios.get(
                'http://localhost:8000/api/movimientos-inventario',
                {
                    params: {
                        page: this.pagina,
                        limit: this.limite,
                        buscar: this.buscar,
                        tipo: this.tipo
                    }
                }
            )

            .then(response => {

                if (peticionActual !== this.numeroPeticion) return;

                this.movimientos = response.data.data;
                this.pagina = response.data.current_page;
                this.ultimaPagina = response.data.last_page;
                this.total = response.data.total;

            })

            .catch(error => {

                if (peticionActual !== this.numeroPeticion) return;

                console.error(error);
                alert('No se pudieron cargar los movimientos.');

            })

            .finally(() => {

                if (peticionActual === this.numeroPeticion) {
                    this.cargando = false;
                }

            });
        },

        buscarMovimientos() {

            clearTimeout(this.temporizador);

            this.temporizador = setTimeout(() => {
                this.pagina = 1;
                this.obtenerMovimientos();
            }, 400);
        },

        filtrar() {
            this.pagina = 1;
            this.obtenerMovimientos();
        },

        cambiarPagina(numero) {
            this.pagina = numero;
            this.obtenerMovimientos();
        },

        verDetalle(id) {
            this.$router.push(`/movimientos/detalle/${id}`);
        },

        formatearFecha(fecha) {

            if (!fecha) return '—';

            return new Date(fecha).toLocaleString('es-BO');
        }
    }
};
</script>

<style scoped>
.encabezado {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}

.encabezado p {
    color: #777;
}

.filtros {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 20px;
}

.filtros input,
.filtros select {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
}

.filtros input {
    flex: 2;
    min-width: 220px;
}

.filtros select {
    flex: 1;
    min-width: 140px;
}

.tabla-contenedor {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th,
td {
    padding: 12px;
    border-bottom: 1px solid #ddd;
    text-align: left;
}

.etiqueta {
    font-size: 12px;
    font-weight: 600;
    padding: 5px 8px;
    background: #e8edf7;
    border-radius: 5px;
}

.paginacion,
.controles {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-top: 15px;
}

button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
