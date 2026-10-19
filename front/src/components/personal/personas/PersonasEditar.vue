
<template>
  <div class="formulario">
    <div class="encabezado">
      <h1>Editar Persona</h1>
      <p>Actualice la información de la persona registrada.</p>
    </div>

    <p v-if="error" class="mensaje-error">{{ error }}</p>
    <p v-if="cargando" class="mensaje-info">Cargando datos de la persona...</p>

    <form v-else @submit.prevent="actualizarPersona">
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
            v-model="persona.numero_documento"
            type="text"
            maxlength="30"
            placeholder="Ej. 12345678"
            :disabled="persona.tipo_documento === 'SIN_DOCUMENTO'"
          >
        </div>
      </div>

      <div class="fila">
        <div class="campo">
          <label>Nombre / Razón Social:</label>
          <input
            v-model="persona.nombres_razon_social"
            type="text"
            maxlength="255"
            required
          >
        </div>

        <div class="campo">
          <label>Apellidos:</label>
          <input
            v-model="persona.apellidos"
            type="text"
            maxlength="255"
          >
        </div>
      </div>

      <div class="fila">
        <div class="campo">
          <label>Correo electrónico:</label>
          <input
            v-model="persona.correo"
            type="email"
            maxlength="255"
          >
        </div>

        <div class="campo">
          <label>Teléfono:</label>
          <input
            v-model="persona.telefono"
            type="text"
            maxlength="30"
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
          <select v-model="persona.tipo_persona" required>
            <option value="" disabled>Seleccione</option>
            <option value="PERSONAL">Personal</option>
            <option value="CLIENTE">Cliente</option>
            <option value="OTRO">Otro</option>
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
        <button type="submit" :disabled="guardando">
          {{ guardando ? 'Guardando cambios...' : 'Actualizar Persona' }}
        </button>

        <button
          type="button"
          class="btn-cancelar"
          :disabled="guardando"
          @click="$router.push('/personas')"
        >
          Cancelar
        </button>
      </div>
    </form>
  </div>
</template>

<script>
import api from '../../../services/api';

export default {
  name: 'PersonasEditar',

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
      cargando: true,
      guardando: false,
      error: ''
    };
  },

  computed: {
    idPersona() {
      return this.$route.params.id;
    }
  },

  watch: {
    'persona.tipo_documento'(valor) {
      if (valor === 'SIN_DOCUMENTO') {
        this.persona.numero_documento = '';
      }
    },

    idPersona() {
      this.obtenerPersona();
    }
  },

  mounted() {
    this.obtenerPersona();
  },

  methods: {
    async obtenerPersona() {
      this.cargando = true;
      this.error = '';

      try {
        const { data } = await api.get(`/api/personas/${this.idPersona}`);

        this.persona = {
          tipo_documento: data.tipo_documento ?? '',
          numero_documento: data.numero_documento ?? '',
          nombres_razon_social: data.nombres_razon_social ?? '',
          apellidos: data.apellidos ?? '',
          correo: data.correo ?? '',
          telefono: data.telefono ?? '',
          sexo: data.sexo ?? '',
          tipo_persona: data.tipo_persona ?? '',
          estado: Boolean(data.estado)
        };
      } catch (error) {
        console.error('Error al obtener persona:', error);
        this.error = error.response?.data?.message ||
          'No se pudieron cargar los datos de la persona.';
      } finally {
        this.cargando = false;
      }
    },

    async actualizarPersona() {
      if (this.guardando) return;

      this.guardando = true;
      this.error = '';

      const datos = {
        ...this.persona,
        tipo_documento: this.persona.tipo_documento || null,
        numero_documento: this.persona.numero_documento?.trim() || null,
        nombres_razon_social: this.persona.nombres_razon_social.trim(),
        apellidos: this.persona.apellidos?.trim() || null,
        correo: this.persona.correo?.trim() || null,
        telefono: this.persona.telefono?.trim() || null,
        sexo: this.persona.sexo || null
      };

      try {
        const { data } = await api.put(
          `/api/personas/${this.idPersona}`,
          datos
        );

        alert(data.message || 'Persona actualizada correctamente.');
        this.$router.push('/personas');
      } catch (error) {
        console.error('Error al actualizar persona:', error);

        const errores = error.response?.data?.errors;
        this.error = errores
          ? Object.values(errores).flat().join('\n')
          : error.response?.data?.message ||
            'No se pudo actualizar la persona.';
      } finally {
        this.guardando = false;
      }
    }
  }
};
</script>

<style scoped>
.formulario {
  max-width: 950px;
  margin: 0 auto;
  padding: 25px;
  background: white;
  border-radius: 10px;
}

.encabezado { margin-bottom: 25px; }
.encabezado h1 { margin: 0 0 6px; color: #1f2937; }
.encabezado p { margin: 0; color: #6b7280; }

.fila {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 20px;
  margin-bottom: 18px;
}

.campo {
  display: flex;
  flex-direction: column;
  gap: 7px;
  margin-bottom: 18px;
}

.fila .campo { margin-bottom: 0; }

.campo label {
  font-weight: 600;
  font-size: 14px;
  color: #374151;
}

.campo input, .campo select {
  width: 100%;
  box-sizing: border-box;
  padding: 11px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  background: white;
  font-size: 14px;
}

.campo input:focus, .campo select:focus {
  outline: none;
  border-color: #2563eb;
}

.campo input:disabled { background: #f3f4f6; }

.botones {
  display: flex;
  gap: 12px;
  margin-top: 25px;
}

.botones button {
  border: none;
  padding: 11px 18px;
  border-radius: 6px;
  cursor: pointer;
  background: #2563eb;
  color: white;
}

.botones .btn-cancelar {
  background: #6b7280;
}

.botones button:disabled {
  opacity: .6;
  cursor: not-allowed;
}

.mensaje-error {
  padding: 12px;
  margin-bottom: 18px;
  border-radius: 6px;
  background: #fef2f2;
  color: #b91c1c;
  border: 1px solid #fecaca;
  white-space: pre-line;
}

.mensaje-info { color: #6b7280; }

@media (max-width: 650px) {
  .formulario { padding: 15px; }
  .fila { grid-template-columns: 1fr; }
  .botones { flex-wrap: wrap; }
}
</style>
