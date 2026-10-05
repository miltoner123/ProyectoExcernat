
<template>
    <div class="formulario">

        <h1>Editar Inventario</h1>

        <form
            v-if="cargado"
            @submit.prevent="actualizarInventario"
        >

            <div class="campo">
                <label>Ubicación:</label>

                <input
                    type="text"
                    :value="inventario.ubicacion?.nombre"
                    disabled
                >
            </div>

            <div class="campo">
                <label>Producto:</label>

                <input
                    type="text"
                    :value="inventario.lote?.presentacion?.producto?.nombre"
                    disabled
                >
            </div>

            <div class="campo">
                <label>Presentación:</label>

                <input
                    type="text"
                    :value="inventario.lote?.presentacion?.nombre_presentacion"
                    disabled
                >
            </div>

            <div class="campo">
                <label>Lote:</label>

                <input
                    type="text"
                    :value="inventario.lote?.codigo_lote"
                    disabled
                >
            </div>

            <div class="campo">
                <label>Stock actual:</label>

                <input
                    type="number"
                    :value="inventario.stock_actual"
                    disabled
                >
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

            <div class="botones">

                <button
                    type="submit"
                    :disabled="guardando"
                >
                    {{ guardando ? 'Actualizando...' : 'Actualizar Inventario' }}
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

        <p v-else>
            Cargando inventario...
        </p>

    </div>
</template>


<script>
import axios from 'axios';

export default {

    data() {
        return {
            inventario: {},

            cargado: false,
            guardando: false
        };
    },

    mounted() {
        this.inventarioGet();
    },

    methods: {

        inventarioGet() {

            const id = this.$route.params.id;

            axios.get(
                `http://localhost:8000/api/inventarios/${id}`
            )

            .then(response => {

                this.inventario = response.data;

                this.cargado = true;

            })

            .catch(error => {

                console.error(error);

                alert('No se pudo cargar el inventario.');

            });
        },


        actualizarInventario() {

            const id = this.$route.params.id;

            this.guardando = true;

            axios.post(
                `http://localhost:8000/api/inventarios/${id}`,
                {
                    stock_minimo: this.inventario.stock_minimo
                }
            )

            .then(() => {

                alert('Stock mínimo actualizado correctamente.');

                this.$router.push('/inventarios');

            })

            .catch(error => {

                alert(
                    error.response?.data?.message ||
                    'No se pudo actualizar el inventario.'
                );

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

.campo input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    box-sizing: border-box;
}

.campo input:disabled {
    background: #f3f4f6;
    color: #555;
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
