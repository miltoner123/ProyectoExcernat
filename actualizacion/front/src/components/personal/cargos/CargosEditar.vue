<template>
  <div class="formulario">
    <div class="encabezado">
      <h1>Editar Cargo</h1>
      <p>Actualice la información del cargo de EXCERNAT.</p>
    </div>

    <p v-if="cargando">Cargando cargo...</p>
    <div v-if="error" class="mensaje-error">{{ error }}</div>

    <form v-if="!cargando && encontrado" @submit.prevent="actualizarCargo">
      <div class="campo">
        <label for="nombre">Nombre del cargo:</label>
        <input id="nombre" v-model.trim="cargo.nombre" type="text" maxlength="255" placeholder="Ej. Vendedor" required />
        <small v-if="errores.nombre" class="campo-error">{{ errores.nombre[0] }}</small>
      </div>

      <div class="campo">
        <label for="descripcion">Descripción:</label>
        <textarea id="descripcion" v-model="cargo.descripcion" rows="4" placeholder="Descripción de las funciones generales del cargo..."></textarea>
        <small v-if="errores.descripcion" class="campo-error">{{ errores.descripcion[0] }}</small>
      </div>

      <div class="campo">
        <label for="estado">Estado:</label>
        <select id="estado" v-model="cargo.estado">
          <option :value="true">Activo</option>
          <option :value="false">Inactivo</option>
        </select>
        <small v-if="errores.estado" class="campo-error">{{ errores.estado[0] }}</small>
      </div>

      <div class="botones">
        <button type="submit" :disabled="guardando">{{ guardando ? 'Guardando...' : 'Actualizar Cargo' }}</button>
        <button type="button" class="btn-cancelar" :disabled="guardando" @click="$router.push('/cargos')">Cancelar</button>
      </div>
    </form>
  </div>
</template>

<script>
import api from '../../../services/api';

export default {
  name: 'CargosEditar',
  data() {
    return {
      cargo: { nombre: '', descripcion: '', estado: true },
      cargando: true,
      encontrado: false,
      guardando: false,
      error: '',
      errores: {}
    };
  },
  mounted() { this.cargarCargo(); },
  methods: {
    async cargarCargo() {
      this.cargando = true;
      this.error = '';
      try {
        const { data } = await api.get(`/api/cargos/${this.$route.params.id}`);
        this.cargo = {
          nombre: data.nombre || '',
          descripcion: data.descripcion || '',
          estado: Boolean(data.estado)
        };
        this.encontrado = true;
      } catch (e) {
        this.error = e.response?.data?.message || 'No se pudo cargar el cargo.';
      } finally {
        this.cargando = false;
      }
    },
    async actualizarCargo() {
      if (this.guardando) return;
      this.guardando = true;
      this.error = '';
      this.errores = {};
      try {
        const { data } = await api.put(`/api/cargos/${this.$route.params.id}`, {
          nombre: this.cargo.nombre,
          descripcion: this.cargo.descripcion || null,
          estado: this.cargo.estado
        });
        alert(data.message || 'Cargo actualizado correctamente.');
        this.$router.push('/cargos');
      } catch (e) {
        this.errores = e.response?.data?.errors || {};
        this.error = e.response?.data?.message || 'No se pudo actualizar el cargo.';
      } finally {
        this.guardando = false;
      }
    }
  }
};
</script>

<style scoped>
.formulario { max-width: 850px; margin: 0 auto; padding: 20px; }
.encabezado { margin-bottom: 24px; }
.encabezado h1 { margin: 0 0 6px; color: #1f2937; }
.encabezado p { color: #6b7280; margin: 0; }
.campo { display: flex; flex-direction: column; gap: 7px; margin-bottom: 20px; }
.campo label { font-weight: 600; color: #374151; }
.campo input, .campo textarea, .campo select { padding: 11px; border: 1px solid #d1d5db; border-radius: 6px; font: inherit; background: #fff; }
.botones { display: flex; gap: 12px; flex-wrap: wrap; }
.botones button { padding: 11px 18px; border: 0; border-radius: 6px; background: #2563eb; color: #fff; cursor: pointer; }
.botones .btn-cancelar { background: #6b7280; }
.botones button:disabled { opacity: .6; cursor: not-allowed; }
.mensaje-error { white-space: pre-line; padding: 12px; background: #fee2e2; color: #991b1b; border-radius: 6px; margin-bottom: 18px; }
.campo-error { color: #b91c1c; }
</style>