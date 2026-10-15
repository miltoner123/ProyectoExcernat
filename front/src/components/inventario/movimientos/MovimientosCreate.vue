<template>
  <div class="formulario">
    <h1>Registrar Movimiento de Inventario</h1>
    <p class="descripcion">Registra entradas, salidas, transferencias, devoluciones y ajustes.</p>
    <form @submit.prevent="guardarMovimiento">
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
      <div v-if="mostrarDestino" class="campo">
        <label>Ubicación de destino:</label>
        <select v-model="movimiento.id_ubicacion_destino" required>
          <option :value="null" disabled>Seleccione el destino</option>
          <option v-for="u in destinos" :key="u.id_ubicacion" :value="u.id_ubicacion">{{ u.nombre }} - {{ u.tipo }}</option>
        </select>
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
      <p v-if="movimiento.tipo === 'AJUSTE'" class="nota">Ajuste positivo: aumenta stock. Ajuste negativo: disminuye stock. Debe justificarlo en Observación.</p>
      <p v-if="movimiento.tipo === 'ENTRADA'" class="nota">La producción se ingresa primero a un ALMACEN; después puede transferirse a otras ubicaciones.</p>
      <div class="botones">
        <button type="submit" :disabled="guardando || cargandoCatalogos">{{ guardando ? 'Guardando...' : 'Confirmar Movimiento' }}</button>
        <button type="button" class="btn-cancelar" @click="$router.push('/movimientos')">Cancelar</button>
      </div>
    </form>
  </div>
</template>

<script>
import axios from 'axios';
const API = 'http://localhost:8000/api';
export default {
  data() {
    return {
      tipos: ['ENTRADA','SALIDA','TRANSFERENCIA','DEVOLUCION','AJUSTE'],
      movimiento: { tipo:'', id_ubicacion_origen:null, id_ubicacion_destino:null, observacion:'', detalles:[{id_lote:null,cantidad:1}] },
      ubicaciones: [], lotes: [], inventarios: [], ingresos: {},
      guardando: false, cargandoCatalogos: true
    };
  },
  computed: {
    mostrarOrigen() { return ['SALIDA','TRANSFERENCIA','DEVOLUCION','AJUSTE'].includes(this.movimiento.tipo); },
    mostrarDestino() { return ['ENTRADA','TRANSFERENCIA','DEVOLUCION'].includes(this.movimiento.tipo); },
    destinos() {
      return this.ubicaciones.filter(u =>
        (this.movimiento.tipo !== 'ENTRADA' || u.tipo === 'ALMACEN') &&
        u.id_ubicacion !== this.movimiento.id_ubicacion_origen
      );
    }
  },
  mounted() { this.cargarCatalogos(); },
  methods: {
    async obtenerTodos(endpoint, parametros = {}) {
      const registros = [];
      let pagina = 1, ultima = 1;
      do {
        const { data } = await axios.get(`${API}/${endpoint}`, { params: {...parametros, page:pagina, limit:50} });
        registros.push(...(data.data || []));
        ultima = data.last_page || 1;
        pagina++;
      } while (pagina <= ultima);
      return registros;
    },
    async cargarCatalogos() {
      this.cargandoCatalogos = true;
      try {
        const [ubicaciones,lotes,inventarios,movimientos] = await Promise.all([
          this.obtenerTodos('ubicaciones'),
          this.obtenerTodos('lotes',{estado:'true'}),
          this.obtenerTodos('inventarios'),
          this.obtenerTodos('movimientos-inventario',{tipo:'ENTRADA'})
        ]);
        this.ubicaciones = ubicaciones.filter(u => u.estado === true || u.estado === 1);
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
      this.movimiento.detalles = [{id_lote:null,cantidad:1}];
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
      if (this.mostrarDestino && !m.id_ubicacion_destino) return alert('Seleccione el destino.');
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
        const { data } = await axios.post(`${API}/movimientos-inventario`, m);
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
</style>
