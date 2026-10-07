<template>

    <div class="formulario">

        <div class="encabezado">
            <h1>Registrar Persona</h1>
            <p>Ingrese los datos de la persona.</p>
        </div>

        <form @submit.prevent="crearPersona">

            <div class="fila">

                <div class="campo">

                    <label>Tipo de documento:</label>

                    <select v-model="persona.tipo_documento">
                        <option value="">Seleccione</option>
                        <option value="CI">Cédula de Identidad</option>
                        <option value="NIT">NIT</option>
                        <option value="OTRO">Otro</option>
                        <option value="SIN_DOCUMENTO">Sin documento</option>
                    </select>

                </div>

                <div class="campo">

                    <label>Número de documento:</label>

                    <input
                        type="text"
                        v-model="persona.numero_documento"
                        :disabled="persona.tipo_documento === 'SIN_DOCUMENTO'"
                        placeholder="Ej. 12345678"
                    >

                </div>

            </div>

            <div class="fila">

                <div class="campo">

                    <label>Nombre / Razón Social:</label>

                    <input
                        type="text"
                        v-model="persona.nombres_razon_social"
                        required
                    >

                </div>

                <div class="campo">

                    <label>Apellidos:</label>

                    <input
                        type="text"
                        v-model="persona.apellidos"
                    >

                </div>

            </div>

            <div class="fila">

                <div class="campo">

                    <label>Correo electrónico:</label>

                    <input
                        type="email"
                        v-model="persona.correo"
                    >

                </div>

                <div class="campo">

                    <label>Teléfono:</label>

                    <input
                        type="text"
                        v-model="persona.telefono"
                    >

                </div>

            </div>

            <div class="fila">

                <div class="campo">

                    <label>Sexo:</label>

                    <select v-model="persona.sexo">
                        <option value="">No especificado</option>
                        <option value="MASCULINO">Masculino</option>
                        <option value="FEMENINO">Femenino</option>
                    </select>

                </div>

                <div class="campo">

                    <label>Tipo de persona:</label>

                    <select
                        v-model="persona.tipo_persona"
                        required
                    >
                        <option value="" disabled>
                            Seleccione
                        </option>

                        <option value="PERSONAL">
                            Personal
                        </option>

                        <option value="CLIENTE">
                            Cliente
                        </option>

                        <option value="OTRO">
                            Otro
                        </option>
                    </select>

                </div>

            </div>

            <div class="campo">

                <label>Estado:</label>

                <select v-model="persona.estado">
                    <option :value="true">Activo</option>
                    <option :value="false">Inactivo</option>
                </select>

            </div>

            <div class="botones">

                <button
                    type="submit"
                    :disabled="guardando"
                >
                    {{ guardando ? 'Guardando...' : 'Guardar Persona' }}
                </button>

                <button
                    type="button"
                    class="btn-cancelar"
                    @click="$router.push('/personas')"
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

            persona: {
                tipo_documento: '',
                numero_documento: '',
                nombres_razon_social: '',
                apellidos: '',
                correo: '',
                telefono: '',
                sexo: '',
                tipo_persona: '',
                estado: true
            },

            guardando: false

        };
    },

    watch: {

        'persona.tipo_documento'(valor) {

            if (valor === 'SIN_DOCUMENTO') {
                this.persona.numero_documento = '';
            }

        }

    },

    methods: {

        crearPersona() {

            this.guardando = true;

            const datos = {
                ...this.persona,

                tipo_documento:
                    this.persona.tipo_documento || null,

                numero_documento:
                    this.persona.numero_documento || null,

                apellidos:
                    this.persona.apellidos || null,

                correo:
                    this.persona.correo || null,

                telefono:
                    this.persona.telefono || null,

                sexo:
                    this.persona.sexo || null
            };

            axios.post(
                'http://localhost:8000/api/personas',
                datos
            )
            .then(response => {

                alert(response.data.message);

                this.$router.push('/personas');

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
                    'No se pudo registrar la persona.'
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
    max-width: 850px;
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

.fila {
    display: flex;
    gap: 20px;
}

.campo {
    display: flex;
    flex-direction: column;
    margin-bottom: 18px;
    flex: 1;
}

.campo label {
    margin-bottom: 7px;
    font-weight: 600;
    color: #374151;
}

.campo input,
.campo select {
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
}

.campo input:disabled {
    background-color: #f3f4f6;
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

@media (max-width: 700px) {

    .fila {
        flex-direction: column;
        gap: 0;
    }

}

</style>