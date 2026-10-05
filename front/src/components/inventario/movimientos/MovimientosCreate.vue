
<template>
    <div class="formulario">

        <h1>Registrar Movimiento de Inventario</h1>

        <p class="descripcion">
            Registra entradas, salidas, transferencias y ajustes de existencias.
        </p>

        <form @submit.prevent="guardarMovimiento">

            <!-- TIPO -->

            <div class="campo">
                <label>Tipo de movimiento:</label>

                <select
                    v-model="movimiento.tipo"
                    @change="cambiarTipo"
                    required
                >
                    <option value="" disabled>Seleccione una opción</option>
                    <option value="ENTRADA">Entrada</option>
                    <option value="SALIDA">Salida</option>
                    <option value="TRANSFERENCIA">Transferencia</option>
                    <option value="DEVOLUCION">Devolución</option>
                    <option value="AJUSTE">Ajuste</option>
                </select>
            </div>

            <!-- UBICACIÓN ORIGEN -->

            <div class="campo" v-if="mostrarOrigen">

                <label>Ubicación de origen:</label>

                <select
                    v-model="movimiento.id_ubicacion_origen"
                    required
                >
                    <option :value="null" disabled>
                        Seleccione la ubicación de origen
                    </option>

                    <option
                        v-for="ubicacion in ubicaciones"
                        :key="ubicacion.id_ubicacion"
                        :value="ubicacion.id_ubicacion"
                    >
                        {{ ubicacion.nombre }} - {{ ubicacion.tipo }}
                    </option>
                </select>
            </div>

            <!-- UBICACIÓN DESTINO -->

            <div class="campo" v-if="mostrarDestino">

                <label>Ubicación de destino:</label>

                <select
                    v-model="movimiento.id_ubicacion_destino"
                    required
                >
                    <option :value="null" disabled>
                        Seleccione la ubicación de destino
                    </option>

                    <option
                        v-for="ubicacion in ubicaciones"
                        :key="ubicacion.id_ubicacion"
                        :value="ubicacion.id_ubicacion"
                    >
                        {{ ubicacion.nombre }} - {{ ubicacion.tipo }}
                    </option>
                </select>
            </div>

            <!-- REFERENCIA -->

            <div class="campo">
                <label>Referencia:</label>

                <input
                    v-model="movimiento.referencia"
                    maxlength="100"
                    placeholder="Ej.: DESP-001"
                >
            </div>

            <!-- OBSERVACIÓN -->

            <div class="campo">
                <label>Observación:</label>

                <textarea
                    v-model="movimiento.observacion"
                    rows="3"
                    placeholder="Descripción del movimiento..."
                ></textarea>
            </div>

            <!-- DETALLES -->

            <div class="encabezado-detalles">

                <h3>Detalle de productos</h3>

                <button
                    type="button"
                    @click="agregarDetalle"
                >
                    + Agregar lote
                </button>

            </div>

            <div class="tabla-contenedor">

                <table>
                    <thead>
                        <tr>
                            <th>Lote / Producto</th>
                            <th>Cantidad</th>
                            <th>Opciones</th>
                        </tr>
                    </thead>

                    <tbody>

                        <tr
                            v-for="(detalle, index) in movimiento.detalles"
                            :key="index"
                        >

                            <td>
                                <select
                                    v-model="detalle.id_lote"
                                    required
                                >
                                    <option :value="null" disabled>
                                        Seleccione un lote
                                    </option>

                                    <option
                                        v-for="lote in lotes"
                                        :key="lote.id_lote"
                                        :value="lote.id_lote"
                                    >
                                        {{ lote.codigo_lote }}
                                        -
                                        {{ lote.presentacion?.producto?.nombre }}
                                        -
                                        {{ lote.presentacion?.nombre_presentacion }}
                                    </option>
                                </select>
                            </td>

                            <td>
                                <input
                                    type="number"
                                    v-model.number="detalle.cantidad"
                                    :min="movimiento.tipo === 'AJUSTE' ? null : 1"
                                    step="1"
                                    required
                                >
                            </td>

                            <td>
                                <button
                                    type="button"
                                    class="btn-eliminar"
                                    @click="eliminarDetalle(index)"
                                    :disabled="movimiento.detalles.length === 1"
                                >
                                    Quitar
                                </button>
                            </td>

                        </tr>

                    </tbody>
                </table>
            </div>

            <p
                v-if="movimiento.tipo === 'AJUSTE'"
                class="nota"
            >
                Para ajustes utiliza cantidades positivas cuando aumente
                el stock y negativas cuando disminuya.
            </p>

            <div class="botones">

                <button
                    type="submit"
                    :disabled="guardando || cargandoCatalogos"
                >
                    {{ guardando ? 'Guardando...' : 'Confirmar Movimiento' }}
                </button>

                <button
                    type="button"
                    class="btn-cancelar"
                    @click="$router.push('/movimientos')"
                >
                    Cancelar
                </button>

            </div>

        </form>

    </div>
</template>

<script>
import axios from 'axios';

export default {

    data() {
        return {

            movimiento: {
                tipo: '',
                id_ubicacion_origen: null,
                id_ubicacion_destino: null,
                referencia: '',
                observacion: '',
                detalles: [
                    {
                        id_lote: null,
                        cantidad: 1
                    }
                ]
            },

            ubicaciones: [],
            lotes: [],

            guardando: false,
            cargandoCatalogos: true
        };
    },

    computed: {

        mostrarOrigen() {
            return [
                'SALIDA',
                'TRANSFERENCIA',
                'DEVOLUCION',
                'AJUSTE'
            ].includes(this.movimiento.tipo);
        },

        mostrarDestino() {
            return [
                'ENTRADA',
                'TRANSFERENCIA',
                'DEVOLUCION'
            ].includes(this.movimiento.tipo);
        }
    },

    mounted() {
        this.cargarCatalogos();
    },

    methods: {

        async obtenerTodos(endpoint, parametros = {}) {

            let pagina = 1;
            let ultimaPagina = 1;
            let registros = [];

            do {

                const response = await axios.get(
                    `http://localhost:8000/api/${endpoint}`,
                    {
                        params: {
                            ...parametros,
                            page: pagina,
                            limit: 50
                        }
                    }
                );

                registros.push(...response.data.data);

                ultimaPagina = response.data.last_page;

                pagina++;

            } while (pagina <= ultimaPagina);

            return registros;
        },

        async cargarCatalogos() {

            this.cargandoCatalogos = true;

            try {

                const [ubicaciones, lotes] = await Promise.all([

                    this.obtenerTodos('ubicaciones'),

                    this.obtenerTodos('lotes', {
                        estado: 'true'
                    })

                ]);

                this.ubicaciones = ubicaciones.filter(
                    ubicacion => ubicacion.estado === true ||
                                 ubicacion.estado === 1
                );

                this.lotes = lotes.filter(
                    lote => lote.estado === true ||
                            lote.estado === 1
                );

            } catch (error) {

                console.error(error);

                alert('No se pudieron cargar las ubicaciones o los lotes.');

            } finally {

                this.cargandoCatalogos = false;
            }
        },

        cambiarTipo() {

            this.movimiento.id_ubicacion_origen = null;

            this.movimiento.id_ubicacion_destino = null;

            this.movimiento.detalles = [
                {
                    id_lote: null,
                    cantidad: 1
                }
            ];
        },

        agregarDetalle() {

            this.movimiento.detalles.push({
                id_lote: null,
                cantidad: 1
            });
        },

        eliminarDetalle(index) {

            if (this.movimiento.detalles.length > 1) {
                this.movimiento.detalles.splice(index, 1);
            }
        },

        guardarMovimiento() {

            if (!this.movimiento.tipo) {
                alert('Seleccione un tipo de movimiento.');
                return;
            }

            if (
                this.mostrarOrigen &&
                this.mostrarDestino &&
                Number(this.movimiento.id_ubicacion_origen) ===
                Number(this.movimiento.id_ubicacion_destino)
            ) {
                alert('El origen y el destino deben ser diferentes.');
                return;
            }

            const idsLotes = this.movimiento.detalles.map(
                detalle => detalle.id_lote
            );

            if (new Set(idsLotes).size !== idsLotes.length) {

                alert('No puedes repetir un mismo lote en el movimiento.');

                return;
            }

            const cantidadesValidas = this.movimiento.detalles.every(
                detalle => {

                    const cantidad = detalle.cantidad;

                    if (!Number.isInteger(cantidad) || cantidad === 0) {
                        return false;
                    }

                    if (this.movimiento.tipo !== 'AJUSTE' && cantidad < 0) {
                        return false;
                    }

                    return true;
                }
            );

            if (!cantidadesValidas) {

                alert('Revisa las cantidades de los detalles.');

                return;
            }

            const confirmar = window.confirm(
                '¿Deseas confirmar este movimiento? Una vez registrado no podrá editarse ni eliminarse.'
            );

            if (!confirmar) return;

            this.guardando = true;

            axios.post(
                'http://localhost:8000/api/movimientos-inventario',
                this.movimiento
            )

            .then(response => {

                alert(response.data.message);

                this.$router.push('/movimientos');

            })

            .catch(error => {

                if (error.response?.data?.errors) {

                    alert(
                        Object.values(error.response.data.errors)
                            .flat()
                            .join('\n')
                    );

                } else {

                    alert(
                        error.response?.data?.message ||
                        'No se pudo registrar el movimiento.'
                    );

                }

            })

            .finally(() => {
                this.guardando = false;
            });
        }
    }
};
</script>

<style scoped>
.formulario {
    max-width: 1000px;
    margin: 0 auto;
}

.descripcion {
    color: #777;
    margin-bottom: 20px;
}

.campo {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 16px;
}

.campo label {
    font-weight: 600;
}

.campo input,
.campo select,
.campo textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    box-sizing: border-box;
}

.encabezado-detalles {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 25px 0 12px;
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
}

td select,
td input {
    width: 100%;
    padding: 9px;
}

.nota {
    margin-top: 12px;
    color: #777;
    font-size: 13px;
}

.botones {
    display: flex;
    gap: 10px;
    margin-top: 25px;
}

.btn-eliminar {
    background: #b91c1c;
    color: white;
}

.btn-cancelar {
    background: #6c757d;
    color: white;
}

button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
