<template>
  <div class="formulario">
    <div class="encabezado"><h1>Editar Asignación</h1><p>Actualice la asignación laboral del trabajador.</p></div>
    <p v-if="error" class="mensaje-error">{{ error }}</p>
    <p v-if="cargando">Cargando asignación...</p>
    <form v-else-if="encontrado" @submit.prevent="guardar">
      <div class="seccion">
        <h3>Personal</h3>
        <div class="fila">
          <div class="campo"><label>Trabajador:</label><select v-model="asignacion.id_personal" required>
            <option value="" disabled>Seleccione</option>
            <option v-for="p in personal" :key="p.id_personal" :value="p.id_personal">{{ nombrePersonal(p) }} - {{ p.cargo?.nombre || 'Sin cargo' }}</option>
          </select></div>
          <div class="campo"><label>Función asignada:</label><input v-model.trim="asignacion.funcion_asignada" maxlength="100" required placeholder="Ej. Vendedor del Stand 1" /></div>
        </div>
      </div>
      <div class="seccion">
        <h3>Destino de la asignación</h3>
        <div class="fila">
          <div class="campo"><label>Ubicación:</label><select v-model="asignacion.id_ubicacion" @change="ubicacionSeleccionada">
            <option :value="null">Sin ubicación específica</option>
            <option v-for="u in ubicaciones" :key="u.id_ubicacion" :value="u.id_ubicacion">{{ u.nombre }} - {{ u.tipo }}</option>
          </select></div>
          <div class="campo"><label>Evento:</label><select v-model="asignacion.id_evento" :disabled="standSeleccionado">
            <option :value="null">Sin evento</option>
            <option v-for="e in eventos" :key="e.id_evento" :value="e.id_evento">{{ e.nombre }}</option>
          </select><small v-if="standSeleccionado">El evento corresponde al stand seleccionado.</small></div>
        </div>
      </div>
      <div class="seccion">
        <h3>Remuneración</h3>
        <div class="campo"><label>Tipo de remuneración:</label><select v-model="asignacion.tipo_remuneracion" required>
          <option value="" disabled>Seleccione</option><option value="SALARIO">Salario</option><option value="MONTO_FIJO">Monto fijo</option><option value="COMISION">Comisión</option><option value="MIXTO">Mixto</option>
        </select></div>
        <div v-if="mostrarMontoFijo" class="campo"><label>Monto fijo (Bs):</label><input v-model.number="asignacion.monto_fijo" type="number" min="0" step="0.01" /></div>
        <div v-if="mostrarComisiones" class="fila">
          <div class="campo"><label>Comisión productos en polvo (Bs/unidad):</label><input v-model.number="asignacion.comision_polvo" type="number" min="0" step="0.01" /></div>
          <div class="campo"><label>Comisión granola (Bs/unidad):</label><input v-model.number="asignacion.comision_granola" type="number" min="0" step="0.01" /></div>
        </div>
      </div>
      <div class="seccion">
        <h3>Periodo</h3>
        <div class="fila">
          <div class="campo"><label>Fecha inicio:</label><input v-model="asignacion.fecha_inicio" type="date" required /></div>
          <div class="campo"><label>Fecha fin:</label><input v-model="asignacion.fecha_fin" type="date" :min="asignacion.fecha_inicio" /></div>
        </div>
        <div class="campo"><label>Estado:</label><select v-model="asignacion.estado"><option :value="true">Activa</option><option :value="false">Inactiva</option></select></div>
      </div>
      <div class="botones"><button type="submit" :disabled="guardando">{{ guardando ? 'Guardando...' : 'Actualizar Asignación' }}</button><button type="button" class="btn-cancelar" @click="$router.push('/asignaciones')">Cancelar</button></div>
    </form>
  </div>
</template>

<script>
import api from '../../../services/api';

export default {
  name: 'AsignacionesEditar',
  data() {
    return {
      asignacion: { id_personal: '', id_ubicacion: null, id_evento: null, funcion_asignada: '', tipo_remuneracion: '', monto_fijo: null, comision_polvo: null, comision_granola: null, fecha_inicio: '', fecha_fin: '', estado: true },
      personal: [], ubicaciones: [], eventos: [], cargando: true, encontrado: false, guardando: false, error: ''
    };
  },
  computed: {
    ubicacionActual() { return this.ubicaciones.find(u => Number(u.id_ubicacion) === Number(this.asignacion.id_ubicacion)); },
    standSeleccionado() { return this.ubicacionActual?.tipo === 'STAND'; },
    mostrarMontoFijo() { return ['MONTO_FIJO', 'MIXTO'].includes(this.asignacion.tipo_remuneracion); },
    mostrarComisiones() { return ['COMISION', 'MIXTO'].includes(this.asignacion.tipo_remuneracion); }
  },
  mounted() { this.cargarDatos(); },
  methods: {
    async obtenerTodos(url) {
      let pagina = 1, ultima = 1; const registros = [];
      do {
        const { data } = await api.get(url, { params: { page: pagina, limit: 50 } });
        registros.push(...(Array.isArray(data) ? data : data.data || []));
        ultima = Array.isArray(data) ? 1 : Number(data.last_page || 1);
        pagina++;
      } while (pagina <= ultima);
      return registros;
    },
    async cargarDatos() {
      this.cargando = true; this.error = '';
      try {
        const { data } = await api.get(`/api/asignaciones-personal/${this.$route.params.id}`);
        this.asignacion = {
          id_personal: data.id_personal, id_ubicacion: data.id_ubicacion, id_evento: data.id_evento,
          funcion_asignada: data.funcion_asignada || '', tipo_remuneracion: data.tipo_remuneracion || '',
          monto_fijo: data.monto_fijo, comision_polvo: data.comision_polvo, comision_granola: data.comision_granola,
          fecha_inicio: String(data.fecha_inicio || '').slice(0, 10), fecha_fin: String(data.fecha_fin || '').slice(0, 10), estado: Boolean(data.estado)
        };
        const [personal, ubicaciones, eventos] = await Promise.all([
          this.obtenerTodos('/api/personal'), this.obtenerTodos('/api/ubicaciones'), this.obtenerTodos('/api/eventos')
        ]);
        this.personal = personal.filter(p => p.estado || Number(p.id_personal) === Number(data.id_personal));
        this.ubicaciones = ubicaciones.filter(u => u.estado || Number(u.id_ubicacion) === Number(data.id_ubicacion));
        this.eventos = eventos.filter(e => e.estado || Number(e.id_evento) === Number(data.id_evento));
        this.encontrado = true;
      } catch (e) { this.error = e.response?.data?.message || 'No se pudo cargar la asignación.'; }
      finally { this.cargando = false; }
    },
    nombrePersonal(p) { return [p.persona?.nombres_razon_social, p.persona?.apellidos].filter(Boolean).join(' ') || p.codigo_empleado || 'Sin nombre'; },
    ubicacionSeleccionada() {
      const u = this.ubicacionActual;
      if (u?.tipo === 'STAND') this.asignacion.id_evento = u.id_evento;
      else this.asignacion.id_evento = null;
    },
    async guardar() {
      if (this.guardando) return;
      this.error = '';
      if (!this.asignacion.id_ubicacion && !this.asignacion.id_evento) { this.error = 'Seleccione una ubicación o un evento.'; return; }
      if (this.asignacion.fecha_fin && this.asignacion.fecha_fin < this.asignacion.fecha_inicio) { this.error = 'La fecha final no puede ser anterior a la inicial.'; return; }
      this.guardando = true;
      try {
        const datos = {
          ...this.asignacion, fecha_fin: this.asignacion.fecha_fin || null,
          monto_fijo: this.mostrarMontoFijo ? this.asignacion.monto_fijo : null,
          comision_polvo: this.mostrarComisiones ? this.asignacion.comision_polvo : null,
          comision_granola: this.mostrarComisiones ? this.asignacion.comision_granola : null
        };
        const { data } = await api.post(`/api/asignaciones-personal/${this.$route.params.id}`, datos);
        alert(data.message || 'Asignación actualizada correctamente.');
        this.$router.push('/asignaciones');
      } catch (e) {
        this.error = e.response?.status === 422 && e.response.data.errors
          ? Object.values(e.response.data.errors).flat().join('\n')
          : e.response?.data?.message || 'No se pudo actualizar la asignación.';
      } finally { this.guardando = false; }
    }
  }
};
</script>

<style scoped>
.mensaje-error { padding: 12px; margin-bottom: 20px; background: #fee2e2; color: #991b1b; border-radius: 6px; white-space: pre-line; }
.formulario {
    max-width: 950px;
    margin: 0 auto;
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

.seccion {
    background: white;
    padding: 25px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.seccion h3 {
    margin-top: 0;
    color: #1f2937;
}

.fila {
    display: flex;
    gap: 20px;
}

.campo {
    display: flex;
    flex-direction: column;
    flex: 1;
    margin-bottom: 18px;
}

.campo label {
    margin-bottom: 7px;
    font-weight: 600;
}

.campo input,
.campo select {
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
}

.campo small {
    margin-top: 5px;
    color: #6b7280;
}

.botones {
    display: flex;
    gap: 10px;
}

.botones button {
    padding: 11px 18px;
    border: none;
    border-radius: 6px;
    background: #2563eb;
    color: white;
    cursor: pointer;
}

.botones .btn-cancelar {
    background: #6b7280;
}

@media (max-width: 700px) {
    .fila {
        flex-direction: column;
        gap: 0;
    }
}
</style>
