<template>
  <div class="formulario exc-modern">
    <h1>Registrar Movimiento de Inventario</h1>
    <p class="descripcion">Registra salidas, transferencias, devoluciones y ajustes. Las entradas de producción requieren confirmación del Almacén Central.</p>
    <form @submit.prevent="guardarMovimiento">
      <div class="aviso-recepcion"><strong>¿Tienes producción pendiente?</strong><span>Las entradas de producción se confirman exclusivamente desde Recepciones pendientes, con autorización del encargado de almacén.</span><button type="button" class="btn-ir-recepciones" @click="$router.push('/recepciones-produccion')">Ver recepciones pendientes</button></div>
      <div class="campo">
        <label>Tipo de movimiento:</label>
        <select v-model="movimiento.tipo" @change="cambiarTipo" required>
          <option value="" disabled>Seleccione una opción</option>
          <option v-for="t in tipos" :key="t" :value="t">{{ t }}</option>
        </select>
      </div>
      <div v-if="mostrarOrigen" class="campo">
        <label>Ubicación de origen:</label>
        <select v-model="movimiento.id_ubicacion_origen" @change="actualizarUbicacion" required>
          <option :value="null" disabled>Seleccione el origen</option>
          <option v-for="u in ubicaciones" :key="u.id_ubicacion" :value="u.id_ubicacion">{{ u.nombre }} - {{ u.tipo }}</option>
        </select>
      </div>
      <div v-if="mostrarDestino" class="panel-destino">
        <div class="campo">
          <label>Tipo de destino:</label>
          <select v-model="tipoDestino" @change="cambiarDestino" required>
            <option value="">Seleccione el tipo de destino</option>
            <option value="ALMACEN">Almacén</option>
            <option value="SUCURSAL">Sucursal</option>
            <option value="STAND">Stand de un evento</option>
          </select>
        </div>
        <div v-if="tipoDestino === 'STAND'" class="campo">
          <label>Evento o feria:</label>
          <select v-model="idEventoDestino" @change="movimiento.id_ubicacion_destino = null" required>
            <option :value="null" disabled>Seleccione primero el evento</option>
            <option v-for="e in eventosConStands" :key="e.id_evento" :value="e.id_evento">{{ e.nombre }}</option>
          </select>
          <small>Solo se muestran eventos con stands activos registrados.</small>
        </div>
        <div v-if="tipoDestino && (tipoDestino !== 'STAND' || idEventoDestino)" class="campo">
          <label>{{ tipoDestino === 'STAND' ? 'Stand del evento:' : 'Ubicación de destino:' }}</label>
          <select v-model="movimiento.id_ubicacion_destino" required>
            <option :value="null" disabled>Seleccione el destino</option>
            <option v-for="u in destinosFiltrados" :key="u.id_ubicacion" :value="u.id_ubicacion">{{ u.nombre }} - {{ u.tipo }}</option>
          </select>
          <small v-if="tipoDestino === 'STAND' && !destinosFiltrados.length">Este evento no tiene stands disponibles para la transferencia.</small>
        </div>
        <div v-if="destinoSeleccionado?.tipo === 'STAND'" class="panel-encargado">
          <div class="panel-encargado-titulo">Responsables del stand</div>
          <p v-if="cargandoResponsables" class="encargado-ayuda">Consultando asignaciones de personal...</p>
          <template v-else-if="errorResponsables">
            <p class="encargado-aviso">{{ errorResponsables }}</p>
          </template>
          <template v-else>
            <div v-if="encargadosStand.length" class="responsables-lista">
              <div v-for="a in encargadosStand" :key="a.id_asignacion" class="responsable-fila">
                <span class="rol-responsable">Encargado</span>
                <strong>{{ nombreResponsable(a) }}</strong>
                <small>Desde {{ a.fecha_inicio }}{{ a.fecha_fin ? ' hasta ' + a.fecha_fin : ' · sin fecha de finalización' }}</small>
              </div>
            </div>
            <p v-else class="encargado-aviso">No hay un encargado vigente asignado a este stand. Revisa las asignaciones antes de entregar productos.</p>
            <p v-if="vendedoresStand.length" class="encargado-ayuda">Vendedores vigentes: {{ vendedoresStand.map(nombreResponsable).join(', ') }}</p>
          </template>
          <button type="button" class="btn-consultar-asignaciones" @click="$router.push('/asignaciones')">Consultar asignaciones</button>
          <small class="encargado-ayuda">Información orientativa: la asignación de personal no sustituye la autorización de la transferencia.</small>
        </div>
        <div v-if="destinoSeleccionado?.tipo === 'STAND'" class="resumen-destino">
          <strong>Destino confirmado</strong>
          <span>{{ destinoSeleccionado.evento?.nombre || nombreEvento(destinoSeleccionado.id_evento) }} → {{ destinoSeleccionado.nombre }}</span>
          <small>El inventario se asignará únicamente a este stand, no a todos los stands del evento.</small>
        </div>
      </div>
      <div class="campo">
        <label>Observación:</label>
        <textarea v-model="movimiento.observacion" rows="3" placeholder="Describa el motivo del movimiento..."></textarea>
      </div>
      <div class="encabezado-detalles">
        <h3>Detalle de productos</h3>
        <button type="button" @click="agregarDetalle">+ Agregar lote</button>
      </div>
      <p v-if="cargandoCatalogos" class="nota">Cargando lotes y existencias...</p>
      <div class="tabla-contenedor">
        <table>
          <thead><tr><th>Lote / Producto / Presentación</th><th>Cantidad</th><th>Opciones</th></tr></thead>
          <tbody>
            <tr v-for="(d,i) in movimiento.detalles" :key="i">
              <td>
                <select v-model="d.id_lote" required>
                  <option :value="null" disabled>Seleccione un lote</option>
                  <option v-for="l in lotes" :key="l.id_lote" :value="l.id_lote">{{ nombreLote(l) }}</option>
                </select>
                <div v-if="d.id_lote" class="informacion">
                  <strong>{{ nombreLote(loteSeleccionado(d.id_lote)) }}</strong>
                  <template v-if="movimiento.tipo === 'ENTRADA'">
                    <p>Producidas: <b>{{ loteSeleccionado(d.id_lote)?.cantidad_producida ?? '—' }}</b> unidades</p>
                    <p>Pendientes de ingresar: <b>{{ pendienteProduccion(d.id_lote) }}</b> unidades</p>
                  </template>
                  <template v-else>
                    <p>Existencias en origen: <b>{{ stockOrigen(d.id_lote) }}</b> unidades</p>
                    <p>Stock estimado después del movimiento: <b :class="{ error: excedeStock(d) }">{{ stockEstimado(d) }}</b> unidades</p>
                  </template>
                  <p v-if="excedeStock(d)" class="error">La cantidad supera las existencias disponibles.</p>
                </div>
              </td>
              <td><input v-model.number="d.cantidad" type="number" step="1" :min="movimiento.tipo === 'AJUSTE' ? undefined : 1" required></td>
              <td><button type="button" class="btn-eliminar" :disabled="movimiento.detalles.length === 1" @click="movimiento.detalles.splice(i,1)">Quitar</button></td>
            </tr>
          </tbody>
        </table>
      </div>
      <p v-if="movimiento.tipo === 'AJUSTE'" class="nota">AJUSTE: corrige diferencias verificadas entre el stock físico y el registrado. Un valor positivo suma unidades y uno negativo resta unidades. Es obligatorio justificar el motivo; no debe usarse para registrar producción ni transferencias.</p>
      <p v-if="movimiento.tipo === 'TRANSFERENCIA'" class="nota">TRANSFERENCIA: mueve existencias entre ubicaciones (por ejemplo, Almacén Central → stand de un evento). No modifica el total general de unidades.</p>
      <p v-if="movimiento.tipo === 'SALIDA'" class="nota">SALIDA: descuenta unidades de una ubicación sin asignarlas a otro destino. Registra el motivo de la salida.</p>
      <p v-if="movimiento.tipo === 'DEVOLUCION'" class="nota">DEVOLUCIÓN: devuelve existencias desde una ubicación hacia otra. Verifica el origen y el destino antes de confirmar.</p>
      <p v-if="movimiento.tipo === 'ENTRADA'" class="nota">La producción se ingresa primero a un ALMACEN; después puede transferirse a otras ubicaciones.</p>
      <div class="botones">
        <button type="submit" :disabled="guardando || cargandoCatalogos">{{ guardando ? 'Guardando...' : 'Confirmar Movimiento' }}</button>
        <button type="button" class="btn-cancelar" @click="$router.push('/movimientos')">Cancelar</button>
      </div>
    </form>
  </div>
</template>

<script>
import api from '../../../services/api';
const API = 'http://localhost:8000/api';
export default {
  data() {
    return {
      tipos: ['SALIDA','TRANSFERENCIA','DEVOLUCION','AJUSTE'],
      movimiento: { tipo:'', id_ubicacion_origen:null, id_ubicacion_destino:null, observacion:'', detalles:[{id_lote:null,cantidad:1}] },
      ubicaciones: [], eventos: [], tipoDestino: '', idEventoDestino: null, lotes: [], inventarios: [], ingresos: {},
      guardando: false, cargandoCatalogos: true,
      asignacionesStand: [], cargandoResponsables: false, errorResponsables: '', solicitudResponsables: 0
    };
  },
  computed: {
    mostrarOrigen() { return ['SALIDA','TRANSFERENCIA','DEVOLUCION','AJUSTE'].includes(this.movimiento.tipo); },
    mostrarDestino() { return ['ENTRADA','TRANSFERENCIA','DEVOLUCION'].includes(this.movimiento.tipo); },
    destinos() {
      return this.ubicaciones.filter(u => Number(u.id_ubicacion) !== Number(this.movimiento.id_ubicacion_origen));
    },
    destinosFiltrados() {
      return this.destinos.filter(u => u.tipo === this.tipoDestino &&
        (this.tipoDestino !== 'STAND' || Number(u.id_evento) === Number(this.idEventoDestino)));
    },
    eventosConStands() {
      const ids = new Set(this.destinos.filter(u => u.tipo === 'STAND').map(u => Number(u.id_evento)));
      return this.eventos.filter(e => ids.has(Number(e.id_evento)));
    },
    destinoSeleccionado() {
      return this.ubicaciones.find(u => Number(u.id_ubicacion) === Number(this.movimiento.id_ubicacion_destino)) || null;
    },
    asignacionesVigentes() {
      const hoy = new Date().toISOString().slice(0, 10);
      return this.asignacionesStand.filter(a =>
        (a.estado === true || a.estado === 1 || a.estado === '1') &&
        (!a.fecha_inicio || String(a.fecha_inicio).slice(0, 10) <= hoy) &&
        (!a.fecha_fin || String(a.fecha_fin).slice(0, 10) >= hoy)
      );
    },
    encargadosStand() {
      return this.asignacionesVigentes.filter(a => String(a.funcion_asignada || '').trim().toUpperCase() === 'ENCARGADO');
    },
    vendedoresStand() {
      return this.asignacionesVigentes.filter(a => String(a.funcion_asignada || '').trim().toUpperCase() === 'VENDEDOR');
    }
  },
  watch: {
    'movimiento.id_ubicacion_destino'(id) {
      this.cargarResponsablesStand(id);
    }
  },
  mounted() { this.cargarCatalogos(); },
  methods: {
    nombreResponsable(asignacion) {
      const persona = asignacion.personal?.persona || {};
      const nombre = [persona.nombres_razon_social, persona.apellidos].filter(Boolean).join(' ').trim();
      return nombre || asignacion.personal?.codigo_empleado || 'Personal #' + asignacion.id_personal;
    },
    async cargarResponsablesStand(id) {
      const solicitud = ++this.solicitudResponsables;
      this.asignacionesStand = [];
      this.errorResponsables = '';
      const ubicacion = this.ubicaciones.find(u => Number(u.id_ubicacion) === Number(id));
      if (!id || ubicacion?.tipo !== 'STAND') {
        this.cargandoResponsables = false;
        return;
      }
      this.cargandoResponsables = true;
      try {
        const registros = await this.obtenerTodos('asignaciones-personal', { id_ubicacion: id });
        if (solicitud !== this.solicitudResponsables) return;
        this.asignacionesStand = registros.filter(a => Number(a.id_ubicacion) === Number(id));
      } catch (error) {
        if (solicitud !== this.solicitudResponsables) return;
        console.error('No se pudieron consultar responsables del stand:', error);
        this.errorResponsables = error.response?.status === 403
          ? 'Tu usuario no tiene permiso para consultar asignaciones. La transferencia sigue disponible según tus permisos.'
          : 'No fue posible consultar las asignaciones del stand. Comprueba la conexión.';
      } finally {
        if (solicitud === this.solicitudResponsables) this.cargandoResponsables = false;
      }
    },
    async obtenerTodos(endpoint, parametros = {}) {
      const registros = [];
      let pagina = 1, ultima = 1;
      do {
        const { data } = await api.get(`${API}/${endpoint}`, { params: {...parametros, page:pagina, limit:50} });
        registros.push(...(data.data || []));
        ultima = data.last_page || 1;
        pagina++;
      } while (pagina <= ultima);
      return registros;
    },
    async cargarCatalogos() {
      this.cargandoCatalogos = true;
      try {
        const [ubicaciones,eventos,lotes,inventarios,movimientos] = await Promise.all([
          this.obtenerTodos('ubicaciones'),
          this.obtenerTodos('eventos'),
          this.obtenerTodos('lotes',{estado:'true'}),
          this.obtenerTodos('inventarios'),
          this.obtenerTodos('movimientos-inventario',{tipo:'ENTRADA'})
        ]);
        this.ubicaciones = ubicaciones.filter(u => u.estado === true || u.estado === 1);
        this.eventos = eventos;
        this.lotes = lotes.filter(l => l.estado === true || l.estado === 1);
        this.inventarios = inventarios;
        const ingresos = {};
        for (const m of movimientos) for (const d of m.detalles || []) {
          ingresos[d.id_lote] = (ingresos[d.id_lote] || 0) + Number(d.cantidad);
        }
        this.ingresos = ingresos;
      } catch (error) {
        console.error('Error al cargar catálogos:', error);
        alert('No se pudieron cargar las ubicaciones, lotes, inventarios o ingresos.');
      } finally { this.cargandoCatalogos = false; }
    },
    cambiarTipo() {
      this.movimiento.id_ubicacion_origen = null;
      this.movimiento.id_ubicacion_destino = null;
      this.tipoDestino = '';
      this.idEventoDestino = null;
      this.movimiento.detalles = [{id_lote:null,cantidad:1}];
    },
    cambiarDestino() {
      this.idEventoDestino = null;
      this.movimiento.id_ubicacion_destino = null;
    },
    nombreEvento(id) {
      return this.eventos.find(e => Number(e.id_evento) === Number(id))?.nombre || 'Evento';
    },
    actualizarUbicacion() {
      if (this.movimiento.id_ubicacion_origen === this.movimiento.id_ubicacion_destino)
        this.movimiento.id_ubicacion_destino = null;
    },
    agregarDetalle() { this.movimiento.detalles.push({id_lote:null,cantidad:1}); },
    loteSeleccionado(id) { return this.lotes.find(l => Number(l.id_lote) === Number(id)); },
    nombreLote(l) {
      if (!l) return 'Lote no encontrado';
      return `${l.codigo_lote} - ${l.presentacion?.producto?.nombre || 'Sin producto'} - ${l.presentacion?.nombre_presentacion || 'Sin presentación'}`;
    },
    stockOrigen(idLote) {
      if (!this.movimiento.id_ubicacion_origen) return 0;
      return this.inventarios.filter(i => Number(i.id_lote) === Number(idLote) &&
        Number(i.id_ubicacion) === Number(this.movimiento.id_ubicacion_origen))
        .reduce((s,i) => s + Number(i.stock_actual), 0);
    },
    pendienteProduccion(idLote) {
      const l = this.loteSeleccionado(idLote);
      return l ? Math.max(0, Number(l.cantidad_producida) - (this.ingresos[idLote] || 0)) : 0;
    },
    stockEstimado(d) {
      const actual = this.stockOrigen(d.id_lote), cantidad = Number(d.cantidad) || 0;
      return this.movimiento.tipo === 'AJUSTE' ? actual + cantidad : actual - cantidad;
    },
    excedeStock(d) {
      if (!d.id_lote || !this.movimiento.tipo) return false;
      const cantidad = Number(d.cantidad);
      if (this.movimiento.tipo === 'ENTRADA') return cantidad > this.pendienteProduccion(d.id_lote);
      if (this.movimiento.tipo === 'AJUSTE') return this.stockOrigen(d.id_lote) + cantidad < 0;
      return cantidad > this.stockOrigen(d.id_lote);
    },
    async guardarMovimiento() {
      const m = this.movimiento;
      if (!m.tipo) return alert('Seleccione un tipo de movimiento.');
      if (this.mostrarOrigen && !m.id_ubicacion_origen) return alert('Seleccione el origen.');
      if (this.mostrarDestino && !m.id_ubicacion_destino) return alert('Seleccione el tipo de destino, evento si corresponde, y ubicación.');
      if (this.mostrarDestino && !this.destinosFiltrados.some(u => Number(u.id_ubicacion) === Number(m.id_ubicacion_destino)))
        return alert('El destino no corresponde al tipo y evento seleccionados.');
      if (this.mostrarOrigen && this.mostrarDestino && Number(m.id_ubicacion_origen) === Number(m.id_ubicacion_destino))
        return alert('El origen y destino deben ser diferentes.');
      if (m.tipo === 'AJUSTE' && !m.observacion.trim()) return alert('Justifique el ajuste en Observación.');
      const ids = m.detalles.map(d => d.id_lote);
      if (ids.some(id => !id)) return alert('Seleccione un lote en cada detalle.');
      if (new Set(ids).size !== ids.length) return alert('No puede repetir un mismo lote.');
      if (m.detalles.some(d => !Number.isInteger(d.cantidad) || d.cantidad === 0 || (m.tipo !== 'AJUSTE' && d.cantidad < 0)))
        return alert('Revise las cantidades: deben ser enteros y no pueden ser cero.');
      if (m.detalles.some(d => this.excedeStock(d))) return alert('Una cantidad supera las existencias o producción pendiente.');
      if (!confirm('¿Confirmar el movimiento? No podrá editarse ni eliminarse.')) return;
      this.guardando = true;
      try {
        const { data } = await api.post(`${API}/movimientos-inventario`, m);
        alert(data.message || 'Movimiento registrado.');
        this.$router.push('/movimientos');
      } catch (error) {
        console.error(error);
        const errores = error.response?.data?.errors;
        alert(errores ? Object.values(errores).flat().join('\n') : (error.response?.data?.message || 'No se pudo registrar el movimiento.'));
        // Releer las existencias si otro usuario registró movimientos.
        await this.cargarCatalogos();
      } finally { this.guardando = false; }
    }
  }
};
</script>

<style scoped>
.formulario{max-width:1050px;margin:auto}.descripcion,.nota{color:#777;margin-bottom:18px}
.campo{display:flex;flex-direction:column;gap:6px;margin-bottom:16px}.campo label{font-weight:600}
.campo select,.campo textarea{width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;box-sizing:border-box}
.encabezado-detalles,.botones{display:flex;justify-content:space-between;align-items:center;gap:10px;margin:22px 0 12px}
.tabla-contenedor{overflow-x:auto}table{width:100%;border-collapse:collapse}th,td{padding:12px;border-bottom:1px solid #ddd;text-align:left;vertical-align:top}
td select,td input{width:100%;padding:9px;box-sizing:border-box}.informacion{background:#f3f6fa;border-radius:6px;padding:12px;margin-top:9px;font-size:13px}
.informacion p{margin:6px 0}.informacion b{color:#176c3d}.informacion .error,.error{color:#b91c1c}
.btn-eliminar{background:#b91c1c;color:white}.btn-cancelar{background:#6c757d;color:white}button:disabled{opacity:.5;cursor:not-allowed}
.panel-destino{padding:18px;border:1px solid #d4e5da;border-radius:14px;background:#f6faf7;margin-bottom:16px}
.panel-destino .campo{margin-bottom:12px}.panel-destino small{font-size:12px;color:#61756b}
.resumen-destino{display:flex;flex-direction:column;gap:5px;border-left:4px solid #f47b20;padding:12px;background:white;border-radius:9px;color:#124734}
.panel-destino select{border-radius:9px;border:1px solid #c8dacd;background:#fff}
</style>



<style scoped>
.aviso-recepcion{display:flex;flex-wrap:wrap;align-items:center;gap:12px;background:#edf6f0;border:1px solid #cce4d6;border-left:4px solid #f47b20;border-radius:12px;padding:15px 18px;margin:12px 0 20px;color:#124734}.aviso-recepcion strong{font-size:14px}.aviso-recepcion span{font-size:13px;flex:1 1 280px;line-height:1.5}.btn-ir-recepciones{background:#124734!important;color:white!important;border:0!important;border-radius:9px!important;padding:10px 15px!important;cursor:pointer;font-weight:700}.btn-ir-recepciones:hover{background:#0c3025!important}
.panel-destino{padding:18px;border:1px solid #d4e5da;border-radius:14px;background:#f6faf7;margin-bottom:16px}
.panel-destino .campo{margin-bottom:12px}.panel-destino small{font-size:12px;color:#61756b}
.resumen-destino{display:flex;flex-direction:column;gap:5px;border-left:4px solid #f47b20;padding:12px;background:white;border-radius:9px;color:#124734}
.panel-destino select{border-radius:9px;border:1px solid #c8dacd;background:#fff}
</style>

<style scoped>
.panel-encargado{background:#fff;border:1px solid #d4e5da;border-radius:12px;padding:16px;margin:12px 0;color:#124734}.panel-encargado-titulo{font-weight:800;margin-bottom:10px}.responsables-lista{display:grid;gap:9px}.responsable-fila{display:flex;flex-wrap:wrap;align-items:center;gap:8px;padding:10px;border-radius:9px;background:#f1f7f3}.responsable-fila small{color:#657a6e}.rol-responsable{font-size:11px;font-weight:800;color:#124734;background:#e0f1e7;border-radius:20px;padding:4px 8px}.encargado-aviso{font-size:13px;color:#96520e;background:#fff7eb;padding:10px;border-radius:8px}.encargado-ayuda{display:block;color:#667d70;font-size:12px;margin:8px 0}.btn-consultar-asignaciones{background:#124734;color:white;border:0;border-radius:9px;padding:9px 13px;font-weight:700;cursor:pointer}.btn-consultar-asignaciones:hover{background:#0c3025}
</style>


