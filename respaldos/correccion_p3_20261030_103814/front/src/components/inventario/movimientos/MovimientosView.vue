<template>
  <div class="movimientos">
    <div class="encabezado">
      <div><h1>Movimientos de Inventario</h1><p>Historial de entradas, salidas y transferencias.</p></div>
      <button @click="$router.push('/movimientos/crear?pedido=' + p.id_pedido)">+ Nuevo Movimiento</button>
    </div>
    <section class="recepciones-panel" aria-label="Producción pendiente de recepción">
      <div class="recepciones-head">
        <div><span class="recepciones-eyebrow">CONTROL DE PRODUCCIÓN</span><h2>Recepciones pendientes en Almacén Central</h2><p>Lotes producidos que todavía requieren confirmación del encargado de almacén.</p></div>
        <button type="button" class="btn-revisar" @click="$router.push('/recepciones-produccion')">Revisar recepciones</button>
      </div>
      <p v-if="cargandoPendientes" class="recepciones-aviso">Consultando producción pendiente...</p>
      <p v-else-if="errorPendientes" class="recepciones-aviso">{{ errorPendientes }}</p>
      <template v-else>
        <div class="recepciones-resumen"><span><strong>{{ pendientes.length }}</strong> lotes pendientes</span><span><strong>{{ unidadesPendientes }}</strong> unidades por recibir</span></div>
        <div v-if="pendientes.length" class="recepciones-lista">
          <div v-for="l in pendientes.slice(0, 5)" :key="l.id_lote" class="recepcion-item">
            <div><strong>{{ l.codigo_lote }} · {{ l.producto || 'Producto' }}</strong><small>{{ l.presentacion || 'Sin presentación' }}</small></div>
            <div class="recepcion-cantidades"><span>Producidas: {{ l.cantidad_producida }}</span><b>Pendientes: {{ l.cantidad_pendiente }}</b></div>
          </div>
          <p v-if="pendientes.length > 5" class="recepciones-aviso">Y {{ pendientes.length - 5 }} lotes adicionales. Consulta la bandeja para verlos todos.</p>
        </div>
        <p v-else class="recepciones-aviso">No hay producción pendiente de recepción.</p>
      </template>
    </section>
    <section class="pedidos-panel" aria-label="Pedidos pendientes">
      <div class="recepciones-head">
        <div><span class="recepciones-eyebrow">SOLICITUDES DE ABASTECIMIENTO</span><h2>Pedidos pendientes de despacho</h2><p>Pedidos registrados que requieren preparación por el almacén.</p></div>
        <button type="button" class="btn-revisar" @click="$router.push('/pedidos')">Ver pedidos</button>
      </div>
      <p v-if="cargandoPedidos" class="recepciones-aviso">Consultando pedidos pendientes...</p>
      <p v-else-if="errorPedidos" class="recepciones-aviso">{{ errorPedidos }}</p>
      <template v-else>
        <div class="recepciones-resumen"><span><strong>{{ pedidosPendientes.length }}</strong> pedidos pendientes</span></div>
        <div v-if="pedidosPendientes.length" class="recepciones-lista">
          <div v-for="p in pedidosPendientes.slice(0, 6)" :key="p.id_pedido" class="recepcion-item">
            <div><strong>Pedido #{{ p.id_pedido }} · {{ p.ubicacion_solicitante?.nombre || 'Ubicación solicitante' }}</strong>
              <small>{{ p.detalles?.length || 0 }} presentaciones · {{ p.unidades_pendientes }} unidades pendientes · {{ fecha(p.fecha_pedido) }}</small>
              <small v-for="d in (p.detalles || []).slice(0,4)" :key="d.id_detalle_pedido">
                {{ d.presentacion?.producto?.nombre || 'Producto' }} / {{ d.presentacion?.nombre_presentacion || 'Presentación' }}:
                {{ d.pendiente || 0 }} unidades pendientes
              </small>
            </div>
            <div class="recepcion-cantidades">
              <b>PENDIENTE</b>
              <button type="button" class="btn-revisar" @click="$router.push('/movimientos/crear?pedido=' + p.id_pedido)">Preparar movimiento</button>
            </div>
          </div>
          <p v-if="pedidosPendientes.length > 6" class="recepciones-aviso">Hay más pedidos. Consulta la sección Pedidos.</p>
        </div>
        <p v-else class="recepciones-aviso">No hay pedidos pendientes.</p>
      </template>
      <p class="recepciones-aviso"><strong>Nota:</strong> Los despachos se vinculan al pedido. El inventario del destino aumenta únicamente cuando se confirma la recepción.</p>
    </section>
    <div class="filtros">
      <input v-model="buscar" @input="buscarMovimientos" placeholder="Buscar lote, ubicación u observación...">
      <select v-model="tipo" @change="filtrar">
        <option value="">Todos los movimientos</option>
        <option v-for="t in tipos" :key="t" :value="t">{{ t }}</option>
      </select>
      <select v-model.number="limite" @change="filtrar">
        <option v-for="n in [10,25,50]" :key="n" :value="n">{{ n }} registros</option>
      </select>
    </div>
    <div class="tabla-contenedor">
      <table>
        <thead><tr><th>ID</th><th>Fecha</th><th>Tipo</th><th>Origen</th><th>Destino</th><th>Observación</th><th>Lotes</th><th>Opciones</th></tr></thead>
        <tbody>
          <tr v-if="cargando"><td colspan="8" class="mensaje">Cargando movimientos...</td></tr>
          <template v-else v-for="m in movimientos" :key="m.id_movimiento">
            <tr>
              <td>{{ m.id_movimiento }}</td>
              <td>{{ fecha(m.fecha_movimiento) }}</td>
              <td><span class="etiqueta">{{ m.tipo }}</span></td>
              <td>{{ m.origen?.nombre || '—' }}</td>
              <td>{{ m.destino?.nombre || '—' }}</td>
              <td>{{ m.observacion || '—' }}</td>
              <td>{{ m.detalles?.length || 0 }}</td>
              <td><button @click="expandido = expandido === m.id_movimiento ? null : m.id_movimiento">{{ expandido === m.id_movimiento ? 'Ocultar detalle ▲' : 'Ver detalle ▼' }}</button></td>
            </tr>
            <tr v-if="expandido === m.id_movimiento" class="detalle-fila">
              <td colspan="8">
                <strong>Detalle del movimiento #{{ m.id_movimiento }}</strong>
                <p><strong>Observación:</strong> {{ m.observacion || 'Sin observación' }}</p>
                <table class="tabla-detalle">
                  <thead><tr><th>Código de lote</th><th>Producto</th><th>Presentación</th><th>Cantidad</th></tr></thead>
                  <tbody>
                    <tr v-for="d in m.detalles || []" :key="d.id_detalle_movimiento || d.id_lote">
                      <td>{{ d.lote?.codigo_lote || '—' }}</td>
                      <td>{{ d.lote?.presentacion?.producto?.nombre || '—' }}</td>
                      <td>{{ d.lote?.presentacion?.nombre_presentacion || '—' }}</td>
                      <td>{{ d.cantidad }} unidades</td>
                    </tr>
                    <tr v-if="!m.detalles?.length"><td colspan="4">Sin detalles registrados.</td></tr>
                  </tbody>
                </table>
              </td>
            </tr>
          </template>
          <tr v-if="!cargando && !movimientos.length"><td colspan="8" class="mensaje">No se encontraron movimientos.</td></tr>
        </tbody>
      </table>
    </div>
    <div class="paginacion">
      <span>Total: {{ total }} registros</span>
      <div class="controles">
        <button :disabled="cargando || pagina <= 1" @click="cambiarPagina(pagina-1)">Anterior</button>
        <span>Página {{ pagina }} de {{ ultimaPagina }}</span>
        <button :disabled="cargando || pagina >= ultimaPagina" @click="cambiarPagina(pagina+1)">Siguiente</button>
      </div>
    </div>
  </div>
</template>

<script>
import api from '../../../services/api';
const API = 'http://localhost:8000/api/movimientos-inventario';
export default {
  data() {
    return {
      movimientos: [], tipos: ['ENTRADA','SALIDA','TRANSFERENCIA','DEVOLUCION','AJUSTE'],
      buscar: '', tipo: '', limite: 10, pagina: 1, ultimaPagina: 1,
      total: 0, cargando: false, expandido: null, temporizador: null, numeroPeticion: 0, pendientes: [], cargandoPendientes: false, errorPendientes: '', pedidosPendientes: [], cargandoPedidos: false, errorPedidos: ''
    };
  },
  computed: { unidadesPendientes() { return this.pendientes.reduce((n, l) => n + Number(l.cantidad_pendiente || 0), 0); } },
  mounted() { this.obtenerMovimientos(); this.cargarPendientes(); this.cargarPedidosPendientes(); },
  beforeUnmount() { clearTimeout(this.temporizador); this.numeroPeticion++; },
  methods: {
    async cargarPedidosPendientes() {
      this.cargandoPedidos = true;
      this.errorPedidos = '';
      try {
        const registros = [];
        let pagina = 1, ultima = 1;
        do {
          const { data } = await api.get('/api/pedidos-pendientes-despacho');
          registros.push(...(data.data || []));
          ultima = 1;
          pagina++;
        } while (pagina <= ultima);
        this.pedidosPendientes = registros.filter(p => p.unidades_pendientes > 0);
      } catch (error) {
        this.pedidosPendientes = [];
        this.errorPedidos = error.response?.status === 403
          ? 'Tu usuario no tiene permiso para consultar pedidos pendientes.'
          : 'No se pudieron consultar los pedidos. Verifica la conexión.';
      } finally {
        this.cargandoPedidos = false;
      }
    },

    async cargarPendientes() {
      this.cargandoPendientes = true;
      this.errorPendientes = '';
      try {
        const { data } = await api.get('/api/recepciones-produccion');
        this.pendientes = Array.isArray(data.data) ? data.data : [];
      } catch (error) {
        this.pendientes = [];
        this.errorPendientes = error.response?.status === 403
          ? 'No tienes permiso para consultar las recepciones pendientes.'
          : 'No se pudieron consultar las recepciones pendientes. Verifica la conexión y el Almacén Central.';
      } finally { this.cargandoPendientes = false; }
    },
    async obtenerMovimientos() {
      const peticion = ++this.numeroPeticion;
      this.cargando = true;
      const params = { page: this.pagina, limit: this.limite };
      if (this.buscar.trim()) params.buscar = this.buscar.trim();
      if (this.tipo) params.tipo = this.tipo;
      try {
        const { data } = await api.get(API, { params });
        if (peticion !== this.numeroPeticion) return;
        this.movimientos = data.data || [];
        this.pagina = data.current_page || 1;
        this.ultimaPagina = data.last_page || 1;
        this.total = data.total || 0;
        this.expandido = null;
      } catch (error) {
        if (peticion !== this.numeroPeticion) return;
        console.error(error);
        alert(error.response?.data?.message || 'No se pudieron cargar los movimientos.');
      } finally {
        if (peticion === this.numeroPeticion) this.cargando = false;
      }
    },
    buscarMovimientos() {
      clearTimeout(this.temporizador);
      this.temporizador = setTimeout(() => this.filtrar(), 400);
    },
    filtrar() { this.pagina = 1; this.obtenerMovimientos(); },
    cambiarPagina(n) { if (n >= 1 && n <= this.ultimaPagina) { this.pagina = n; this.obtenerMovimientos(); } },
    fecha(valor) { return valor ? new Date(valor).toLocaleString('es-BO') : '—'; }
  }
};
</script>

<style scoped>
.recepciones-panel{background:#fff;border:1px solid #d9e7de;border-left:5px solid #f47b20;border-radius:15px;padding:20px;margin:0 0 22px;box-shadow:0 6px 20px rgba(12,48,37,.05)}
.recepciones-head{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap}.recepciones-head h2{font-size:19px;color:#103a2d;margin:5px 0}.recepciones-head p{color:#607b6c;margin:0}.recepciones-eyebrow{color:#df6e0c;font-size:11px;font-weight:800;letter-spacing:1.2px}.btn-revisar{border:0;border-radius:9px;background:#f47b20;color:white;padding:11px 15px;font-weight:700;cursor:pointer}.btn-revisar:hover{background:#d9600b}.recepciones-resumen{display:flex;gap:20px;flex-wrap:wrap;margin:15px 0;color:#355a47}.recepciones-resumen strong{color:#0c3025;font-size:19px}.recepciones-lista{display:grid;gap:8px}.recepcion-item{border:1px solid #dce9df;background:#f7fbf8;border-radius:10px;padding:12px 14px;display:flex;justify-content:space-between;align-items:center;gap:15px;flex-wrap:wrap}.recepcion-item>div:first-child{display:flex;flex-direction:column;gap:4px}.recepcion-item strong{color:#123e2e}.recepcion-item small{color:#688073}.recepcion-cantidades{display:flex;gap:14px;align-items:center;flex-wrap:wrap;color:#5b7264;font-size:13px}.recepcion-cantidades b{color:#b45a0c}.recepciones-aviso{color:#607b6c;font-size:13px;margin:10px 0 0}

.encabezado,.paginacion,.controles{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.encabezado{margin-bottom:20px}.encabezado p{color:#777}
.filtros{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px}
.filtros input,.filtros select{padding:10px;border:1px solid #ddd;border-radius:6px;flex:1;min-width:140px}
.filtros input{flex:2;min-width:220px}.tabla-contenedor{overflow-x:auto}
table{width:100%;border-collapse:collapse}th,td{padding:12px;border-bottom:1px solid #ddd;text-align:left}
.etiqueta{font-size:12px;font-weight:600;padding:5px 8px;background:#e8edf7;border-radius:5px}
.detalle-fila{background:#f4f6fa}.detalle-fila p{margin:10px 0}.tabla-detalle{margin-top:10px;background:white}
.tabla-detalle th,.tabla-detalle td{padding:9px}.mensaje{text-align:center;color:#777}
.paginacion{margin-top:18px}button:disabled{opacity:.5;cursor:not-allowed}
</style>

<style scoped>
.pedidos-panel{background:#fff;border:1px solid #d9e7de;border-left:5px solid #124734;border-radius:15px;padding:20px;margin:0 0 22px;box-shadow:0 6px 20px rgba(12,48,37,.05)}
.pedidos-panel .btn-revisar{background:#124734;color:#fff}
.pedidos-panel .btn-revisar:hover{background:#0c3025}
</style>
