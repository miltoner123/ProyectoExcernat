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
          <div class="campo"><label>Función asignada:</label><select v-if="standSeleccionado" v-model="asignacion.funcion_asignada" required><option value="" disabled>Seleccione</option><option value="ENCARGADO">Encargado</option><option value="VENDEDOR">Vendedor</option></select><input v-else v-model.trim="asignacion.funcion_asignada" maxlength="100" required placeholder="Ej. Responsable de almacén" /></div>
        </div>
      </div>
<div class="seccion">
        <h3>Destino de la asignación</h3>
        <div class="campo">
          <label>Tipo de destino:</label>
          <select v-model="tipoDestino" @change="cambiarDestino">
            <option value="FERIA">Feria / Evento</option>
            <option value="OTRO">Sucursal / Almacén</option>
          </select>
        </div>
        <div class="fila" v-if="tipoDestino === 'FERIA'">
          <div class="campo">
            <label>Evento:</label>
            <select v-model="asignacion.id_evento" @change="eventoSeleccionado" required>
              <option :value="null" disabled>Seleccione un evento</option>
              <option v-for="e in eventos" :key="e.id_evento" :value="e.id_evento">{{ e.nombre }}</option>
            </select>
          </div>
          <div class="campo">
            <label>Stand del evento:</label>
            <select v-model="asignacion.id_ubicacion" @change="ubicacionSeleccionada" required :disabled="!asignacion.id_evento">
              <option :value="null" disabled>Seleccione un stand</option>
              <option v-for="u in ubicacionesFiltradas" :key="u.id_ubicacion" :value="u.id_ubicacion">{{ u.nombre }}</option>
            </select>
            <small>Solo se muestran stands del evento seleccionado. Las fechas se sugieren automáticamente y pueden editarse.</small>
          </div>
        </div>
        <div class="campo" v-else>
          <label>Ubicación:</label>
          <select v-model="asignacion.id_ubicacion" @change="ubicacionSeleccionada" required>
            <option :value="null" disabled>Seleccione sucursal o almacén</option>
            <option v-for="u in ubicacionesFiltradas" :key="u.id_ubicacion" :value="u.id_ubicacion">{{ u.nombre }} - {{ u.tipo }}</option>
          </select>
          <small>Las sucursales y almacenes no se vinculan a eventos.</small>
        </div>
      </div>
      <div class="seccion">
        <h3>Remuneración</h3>
        <div class="campo"><label>Tipo de remuneración:</label><select v-model="asignacion.tipo_remuneracion" required>
          <option value="" disabled>Seleccione</option><option value="SALARIO">Salario</option><option value="MONTO_FIJO">Monto fijo</option><option value="COMISION">Comisión</option><option value="MIXTO">Mixto</option>
        </select></div>
        <div v-if="mostrarMontoFijo" class="campo"><label>Monto fijo (Bs):</label><input v-model.number="asignacion.monto_fijo" type="number" min="0" step="0.01" /></div>
        <div v-if="esEncargadoStand" class="campo"><label><input type="checkbox" v-model="comisionOpcional" /> Asignar comisión adicional al encargado</label><small>La comisión es opcional.</small></div>
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
      comisionOpcional: false,
      asignacion: { id_personal: '', id_ubicacion: null, id_evento: null, funcion_asignada: '', tipo_remuneracion: '', monto_fijo: null, comision_polvo: null, comision_granola: null, fecha_inicio: '', fecha_fin: '', estado: true },
      personal: [], ubicaciones: [], eventos: [], tipoDestino: 'OTRO', cargando: true, encontrado: false, guardando: false, error: ''
    };
  },
  computed: {
    ubicacionActual() { return this.ubicaciones.find(u => Number(u.id_ubicacion) === Number(this.asignacion.id_ubicacion)); },
    standSeleccionado() { return this.tipoDestino === 'FERIA'; },
    ubicacionesFiltradas() {
      return this.ubicaciones.filter(u => this.tipoDestino === 'FERIA'
        ? u.tipo === 'STAND' && Number(u.id_evento) === Number(this.asignacion.id_evento)
        : ['SUCURSAL', 'ALMACEN'].includes(u.tipo));
    },
    mostrarMontoFijo() { return ['MONTO_FIJO', 'MIXTO'].includes(this.asignacion.tipo_remuneracion); },
    esEncargadoStand() { return this.tipoDestino === 'FERIA' && String(this.asignacion.funcion_asignada).toUpperCase() === 'ENCARGADO'; },
    mostrarComisiones() { return (this.esEncargadoStand && this.comisionOpcional) || ['COMISION', 'MIXTO'].includes(this.asignacion.tipo_remuneracion); }
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
        this.comisionOpcional = data.comision_polvo !== null || data.comision_granola !== null;
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
        this.tipoDestino = this.ubicaciones.find(u => Number(u.id_ubicacion) === Number(data.id_ubicacion))?.tipo === 'STAND' ? 'FERIA' : 'OTRO';
        this.encontrado = true;
      } catch (e) { this.error = e.response?.data?.message || 'No se pudo cargar la asignación.'; }
      finally { this.cargando = false; }
    },
    cambiarDestino() {
      this.asignacion.id_ubicacion = null;
      this.asignacion.id_evento = null;
      this.asignacion.funcion_asignada = '';
      this.asignacion.fecha_inicio = '';
      this.asignacion.fecha_fin = '';
    },
    eventoSeleccionado() {
      this.asignacion.id_ubicacion = null;
      this.asignacion.funcion_asignada = '';
      const evento = this.eventos.find(e => Number(e.id_evento) === Number(this.asignacion.id_evento));
      this.asignacion.fecha_inicio = String(evento?.fecha_inicio || '').slice(0, 10);
      this.asignacion.fecha_fin = String(evento?.fecha_fin || '').slice(0, 10);
    },
    nombrePersonal(p) { return [p.persona?.nombres_razon_social, p.persona?.apellidos].filter(Boolean).join(' ') || p.codigo_empleado || 'Sin nombre'; },
    ubicacionSeleccionada() {
      if (this.tipoDestino === 'OTRO') this.asignacion.id_evento = null;
      this.asignacion.funcion_asignada = '';
    },
    async guardar() {
      if (this.guardando) return;
      this.error = '';
      if (!this.asignacion.id_ubicacion || (this.tipoDestino === 'FERIA' && !this.asignacion.id_evento)) { this.error = 'Seleccione el destino y su ubicación.'; return; }
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
.formulario{max-width:1050px;margin:24px auto;padding:26px;background:#fff;border:1px solid #d9e7de;border-radius:18px;box-shadow:0 10px 32px rgba(12,48,37,.07);color:#143b2c}
.encabezado{border-left:5px solid #f47b20;padding:4px 0 14px 16px;margin-bottom:24px;border-bottom:1px solid #e4ece6}
.encabezado h1{font-size:26px;color:#0c3025;margin:0 0 6px}.encabezado p{color:#627b6c;margin:0;font-size:14px}
.seccion{background:#f8fbf9;border:1px solid #dce8df;border-radius:14px;padding:20px;margin-bottom:20px}
.seccion h3{color:#124734;font-size:17px;margin:0 0 18px;padding-bottom:10px;border-bottom:1px solid #dce8df}
.fila{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.campo{display:flex;flex-direction:column;gap:7px;margin-bottom:14px;min-width:0;flex:1}
.campo label{font-weight:700;color:#173d2d;font-size:13px}.campo input:not([type=checkbox]),.campo select{width:100%;box-sizing:border-box;min-height:43px;padding:10px 12px;border:1px solid #cbded2;border-radius:9px;background:#fff;color:#173d2d;font:inherit}
.campo input:focus,.campo select:focus{outline:none;border-color:#f47b20;box-shadow:0 0 0 3px rgba(244,123,32,.13)}
.campo label:has(input[type=checkbox]){display:flex;align-items:center;gap:9px;flex-direction:row;cursor:pointer}.campo input[type=checkbox]{accent-color:#124734;width:17px;height:17px}
.campo small{color:#62796c;font-size:12px}.mensaje-error{padding:13px 16px;margin-bottom:18px;background:#fff0ee;border-left:4px solid #b53c30;color:#912b22;border-radius:8px;white-space:pre-line}
.botones{display:flex;gap:12px;flex-wrap:wrap;justify-content:flex-end;margin-top:22px}.botones button{padding:12px 20px;border:0;border-radius:9px;background:#f47b20;color:#fff;font-weight:800;cursor:pointer}.botones button:disabled{opacity:.55;cursor:not-allowed}.botones .btn-cancelar{background:#eaf2ed;color:#124734}
@media(max-width:700px){.formulario{padding:14px;margin:10px}.fila{grid-template-columns:1fr}.seccion{padding:14px}.encabezado h1{font-size:22px}}
</style>
