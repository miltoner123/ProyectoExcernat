<template>

    <div class="formulario">

        <div class="encabezado">
            <h1>Registrar Cargo</h1>
            <p>Registre un nuevo cargo para el personal de EXCERNAT.</p>
        </div>

        <form @submit.prevent="crearCargo">

            <div class="campo">

                <label>Nombre del cargo:</label>

                <input
                    type="text"
                    v-model="cargo.nombre"
                    placeholder="Ej. Vendedor"
                    required
                >

            </div>

            <div class="campo">

                <label>Descripción:</label>

                <textarea
                    v-model="cargo.descripcion"
                    rows="4"
                    placeholder="Descripción de las funciones generales del cargo..."
                ></textarea>

            </div>

            <div class="campo">

                <label>Estado:</label>

                <select v-model="cargo.estado">
                    <option :value="true">Activo</option>
                    <option :value="false">Inactivo</option>
                </select>

            </div>

            <div class="botones">

                <button
                    type="submit"
                    :disabled="guardando"
                >
                    {{ guardando ? 'Guardando...' : 'Guardar Cargo' }}
                </button>

                <button
                    type="button"
                    class="btn-cancelar"
                    @click="$router.push('/cargos')"
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

            cargo: {
                nombre: '',
                descripcion: '',
                estado: true
            },

            guardando: false

        };
    },

    methods: {

        crearCargo() {

            this.guardando = true;

            const datos = {
                ...this.cargo,
                descripcion: this.cargo.descripcion || null
            };

            axios.post(
                'http://localhost:8000/api/cargos',
                datos
            )
            .then(response => {

                alert(response.data.message);

                this.$router.push('/cargos');

            })
            .catch(error => {

                console.error(error);

                if (
                    error.response?.status === 422 &&
                    error.response?.data?.errors
                ) {

                    alert(
                        Object.values(
                            error.response.data.errors
                        )
                        .flat()
                        .join('\n')
                    );

                    return;

                }

                alert(
                    error.response?.data?.message ||
                    'No se pudo registrar el cargo.'
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
    max-width: 750px;
    margin: 0 auto;
    background-color: white;
    padding: 25px;
    border-radius: 8px;
}

.encabezado {
    margin-bottom: 25px;
}

.encabezado h1 {
    margin: 0 0 5px;
    color: #1f2937;
}

.encabezado p {
    margin: 0;
    color: #6b7280;
}

.campo {
    display: flex;
    flex-direction: column;
    margin-bottom: 18px;
}

.campo label {
    margin-bottom: 7px;
    font-weight: 600;
    color: #374151;
}

.campo input,
.campo select,
.campo textarea {
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
}

.botones {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.botones button {
    padding: 11px 18px;
    border: none;
    border-radius: 6px;
    background-color: #2563eb;
    color: white;
    cursor: pointer;
}

.botones .btn-cancelar {
    background-color: #6b7280;
}

.botones button:disabled {
    opacity: 0.6;
}

</style>