
<template>
    <div class="formulario">

        <h1>Registrar Inventario</h1>

        <form @submit.prevent="crearInventario">

            <div class="campo">
                <label>Ubicación:</label>

                <select
                    v-model="inventario.id_ubicacion"
                    required
                >
                    <option :value="null" disabled>
                        Seleccione una ubicación
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

            <div class="campo">
                <label>Lote:</label>

                <select
                    v-model="inventario.id_lote"
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
            </div>

            <div class="campo">
                <label>Stock mínimo:</label>

                <input
                    type="number"
                    v-model.number="inventario.stock_minimo"
                    min="0"
                    required
                >
            </div>

            <div class="campo">
                <label>Stock inicial:</label>

                <input
                    type="number"
                    :value="0"
                    disabled
                >

                <small>
                    El stock se incrementará mediante los movimientos de entrada.
                </small>
            </div>

            <div class="botones">

                <button
                    type="submit"
                    :disabled="guardando"
                >
                    {{ guardando ? 'Guardando...' : 'Guardar Inventario' }}
                </button>

                <button
                    type="button"
                    class="btn-cancelar"
                    @click="$router.push('/inventarios')"
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
            inventario: {
                id_ubicacion: null,
                id_lote: null,
                stock_minimo: 0
            },

            ubicaciones: [],
            lotes: [],

            guardando: false
        };
    },

    mounted() {
        this.ubicacionesGet();
        this.lotesGet();
    },

    methods: {

        ubicacionesGet() {

            axios.get(
                'http://localhost:8000/api/ubicaciones',
                {
                    params: {
                        limit: 50,
                        estado: 'true'
                    }
                }
            )

            .then(response => {
                this.ubicaciones = response.data.data;
            })

            .catch(error => {
                console.error('Error al obtener ubicaciones:', error);
            });
        },


        lotesGet() {

            axios.get(
                'http://localhost:8000/api/lotes',
                {
                    params: {
                        limit: 50,
                        estado: 'true'
                    }
                }
            )

            .then(response => {
                this.lotes = response.data.data;
            })

            .catch(error => {
                console.error('Error al obtener lotes:', error);
            });
        },


        crearInventario() {

            this.guardando = true;

            axios.post(
                'http://localhost:8000/api/inventarios',
                this.inventario
            )

            .then(() => {

                alert('Inventario registrado correctamente.');

                this.$router.push('/inventarios');

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
                        'No se pudo registrar el inventario.'
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
    max-width: 700px;
    margin: 0 auto;
}

.formulario h1 {
    margin-bottom: 20px;
}

.campo {
    display: flex;
    flex-direction: column;
    margin-bottom: 16px;
    gap: 6px;
}

.campo label {
    font-weight: 600;
}

.campo input,
.campo select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    box-sizing: border-box;
}

.campo small {
    color: #777;
}

.botones {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.btn-cancelar {
    background: #6c757d;
    color: white;
}

button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>

