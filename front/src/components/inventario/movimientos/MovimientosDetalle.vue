
<template>
    <div class="detalle">

        <div class="encabezado">
            <h1>Detalle del Movimiento</h1>

            <button @click="$router.push('/movimientos')">
                Volver
            </button>
        </div>

        <p v-if="cargando">Cargando movimiento...</p>

        <div v-else-if="movimiento">

            <div class="informacion">

                <p>
                    <strong>N.º Movimiento:</strong>
                    {{ movimiento.id_movimiento }}
                </p>

                <p>
                    <strong>Tipo:</strong>
                    {{ movimiento.tipo }}
                </p>

                <p>
                    <strong>Fecha:</strong>
                    {{ formatearFecha(movimiento.fecha_movimiento) }}
                </p>

                <p>
                    <strong>Origen:</strong>
                    {{ movimiento.origen?.nombre || '—' }}
                </p>

                <p>
                    <strong>Destino:</strong>
                    {{ movimiento.destino?.nombre || '—' }}
                </p>

                <p>
                    <strong>Referencia:</strong>
                    {{ movimiento.referencia || '—' }}
                </p>

                <p>
                    <strong>Observación:</strong>
                    {{ movimiento.observacion || '—' }}
                </p>

            </div>

            <h3>Productos del movimiento</h3>

            <div class="tabla-contenedor">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Producto</th>
                            <th>Presentación</th>
                            <th>Lote</th>
                            <th>Cantidad</th>
                        </tr>
                    </thead>

                    <tbody>

                        <tr
                            v-for="detalle in movimiento.detalles"
                            :key="detalle.id_detalle_movimiento"
                        >

                            <td>
                                {{ detalle.id_detalle_movimiento }}
                            </td>

                            <td>
                                {{ detalle.lote?.presentacion?.producto?.nombre || '—' }}
                            </td>

                            <td>
                                {{ detalle.lote?.presentacion?.nombre_presentacion || '—' }}
                            </td>

                            <td>
                                {{ detalle.lote?.codigo_lote || '—' }}
                            </td>

                            <td>
                                {{ detalle.cantidad }}
                            </td>

                        </tr>

                    </tbody>
                </table>
            </div>

        </div>

        <p v-else>No se encontró el movimiento.</p>

    </div>
</template>

<script>
import axios from 'axios';

export default {

    data() {
        return {
            movimiento: null,
            cargando: true
        };
    },

    mounted() {
        this.obtenerMovimiento();
    },

    methods: {

        obtenerMovimiento() {

            const id = this.$route.params.id;

            axios.get(
                `http://localhost:8000/api/movimientos-inventario/${id}`
            )

            .then(response => {
                this.movimiento = response.data;
            })

            .catch(error => {
                console.error(error);
                alert('No se pudo consultar el movimiento.');
            })

            .finally(() => {
                this.cargando = false;
            });
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
    margin-bottom: 20px;
}

.informacion {
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin-bottom: 25px;
}

.informacion p {
    margin-bottom: 12px;
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
    text-align: left;
    border-bottom: 1px solid #ddd;
}
</style>
