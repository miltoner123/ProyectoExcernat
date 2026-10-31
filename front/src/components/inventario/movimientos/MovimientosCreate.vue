<template>
  <div class="formulario exc-modern">
    <h1>Registrar Movimiento de Inventario</h1>
    <p class="descripcion">Registra salidas, transferencias, devoluciones y ajustes. Las entradas de producción requieren confirmación del Almacén Central.</p>
    <form @submit.prevent="guardarMovimiento">
      <div v-if="movimiento.tipo === 'TRANSFERENCIA'" class="panel-pedido">
        <label><strong>¿Tienes un pedido pendiente por despachar? (opcional)</strong></label>
        <select v-model="idPedido" @change="seleccionarPedido">
          <option :value="null">Movimiento independiente (sin pedido)</option>
          <option v-for="p in pedidosPendientes" :key="p.id_pedido" :value="p.id_pedido">Pedido #{{ p.id_pedido }} · {{ p.ubicacion_solicitante?.nombre }} · {{ p.unidades_pendientes }} unidades pendientes</option>
        </select>
        <p v-if="idPedido" class="nota">El destino se establece según el pedido. El despacho se vinculará al pedido y quedará pendiente de recepción.</p>
        <p v-if="errorPedidos" class="error">{{ errorPedidos }}</p>
        <div v-if="pedidoSeleccionado" class="pedido-detalles">
          <div v-for="d in pedidoSeleccionado.detalles.filter(x => x.pendiente > 0)" :key="d.id_presentacion">
            {{ d.presentacion?.producto?.nombre }} · {{ d.presentacion?.nombre_presentacion }}: <b>{{ d.pendiente }} unidades pendientes</b>
          </div>
        </div>
      </div>
      <div class="campo">
        <label>Tipo de movimiento:</label>
        <select v-model="movimiento.tipo" @change="cambiarTipo" :disabled="!!idPedido" required>
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
            <p v-else class="encargado-aviso">No hay un encargado operativo vigente para este stand. El responsable general del evento no sustituye una asignación al stand.</p>
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
        <h3>Detalle de productos <small class="contador-productos">({{ movimiento.detalles.length }} lotes)</small></h3>
        <button type="button" @click="agregarDetalle">+ Agregar producto / lote</button>
      </div>
      <p class="nota">{{ pedidoSeleccionado ? 'Productos y cantidades cargados desde el pedido. Puedes modificar las cantidades o elegir otro lote con stock para realizar un despacho parcial.' : 'Puedes incluir Multicereal, Granola y Tricereal en un mismo movimiento. Cada fila corresponde a un lote y cantidad; todo se guarda como una sola operación.' }}</p>
      <p v-if="cargandoCatalogos" class="nota">Cargando lotes y existencias...</p>
      <div class="tabla-contenedor">
        <table>
          <thead><tr><th>Presentación y lote disponible</th><th>Cantidad</th><th>Opciones</th></tr></thead>
          <tbody>
            <tr v-for="(d,i) in movimiento.detalles" :key="i">
              <td>
                <div v-if="pedidoSeleccionado && d.id_presentacion && !lotesConStockPresentacion(d.id_presentacion).length" class="aviso-sin-stock" role="alert">
                  <strong>Producto solicitado sin existencias disponibles</strong>
                  <span>Se solicitó {{ nombrePresentacionPedido(d.id_presentacion) }} ({{ pendientePresentacion(d.id_presentacion) }} unidades pendientes), pero no hay lotes con stock en el almacén de origen.</span>
                  <small>Este producto no se puede despachar hasta que se repongan las existencias. Puedes retirarlo del despacho parcial y conservarlo pendiente en el pedido.</small>
                </div>
                <label class="etiqueta-detalle">1. Producto / presentación</label>
                <select v-model="d.id_presentacion" @change="d.id_lote = null" required>
                  <option :value="null" disabled>Seleccione la presentación</option>
                  <option v-for="p in opcionesPresentacion(d.id_presentacion)" :key="p.id_presentacion" :value="p.id_presentacion">{{ p.producto?.nombre || p.nombre_producto || 'Producto' }} · {{ p.nombre_presentacion }}</option>
                </select>
                <label class="etiqueta-detalle">2. Lote con existencias</label>
                <select v-model="d.id_lote" :disabled="!d.id_presentacion" required>
                  <option :value="null" disabled>Seleccione un lote con stock</option>
                  <option v-for="l in lotesDisponibles(d.id_lote).filter(l => Number(l.id_presentacion) === Number(d.id_presentacion))" :key="l.id_lote" :value="l.id_lote">{{ l.codigo_lote }} · {{ stockOrigen(l.id_lote) }} unidades</option>
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
      movimiento: { tipo:'', id_ubicacion_origen:null, id_ubicacion_destino:null, observacion:'', detalles:[{id_presentacion:null,id_lote:null,cantidad:1}] },
      ubicaciones: [], eventos: [], tipoDestino: '', idEventoDestino: null, lotes: [], inventarios: [], ingresos: {},
      idPedido: null, pedidosPendientes: [], errorPedidos: '',
      guardando: false, cargandoCatalogos: true,
      asignacionesStand: [], cargandoResponsables: false, errorResponsables: '', solicitudResponsables: 0
    };
  },
  computed: {
    pedidoSeleccionado() { return this.pedidosPendientes.find(p => Number(p.id_pedido) === Number(this.idPedido)) || null; },
    presentacionesDisponibles() {
      const ids = new Set(this.lotes.filter(l => this.stockOrigen(l.id_lote) > 0 &&
        (!this.pedidoSeleccionado || this.pedidoSeleccionado.detalles.some(d => Number(d.id_presentacion) === Number(l.id_presentacion) && d.pendiente > 0)))
        .map(l => Number(l.id_presentacion)));
      const vistos = new Set();
      return this.lotes.filter(l => ids.has(Number(l.id_presentacion)) && !vistos.has(Number(l.id_presentacion)) && vistos.add(Number(l.id_presentacion)))
        .map(l => ({...l.presentacion, id_presentacion: l.id_presentacion}));
    },

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
    async cargarPedidosPendientes() {
      try {
        const {data} = await api.get(`${API}/pedidos-pendientes-despacho`);
        this.pedidosPendientes = data.data || [];
      } catch(e) {
        this.errorPedidos = 'No fue posible consultar los pedidos pendientes.';
      }
    },
    seleccionarPedido() {
      const p = this.pedidoSeleccionado;
      if (!p) {
        this.movimiento.detalles = [{id_presentacion:null,id_lote:null,cantidad:1}];
        return;
      }
      // El pedido seleccionado siempre se despacha como TRANSFERENCIA.
      this.movimiento.tipo = 'TRANSFERENCIA';
      // Los productos y cantidades se cargan desde el pedido; solo falta elegir lote.
      // Si no hay existencias suficientes, la cantidad puede ajustarse para despacho parcial.
      this.movimiento.detalles = p.detalles
        .filter(d => Number(d.pendiente) > 0)
        .map(d => ({
          id_presentacion: Number(d.id_presentacion),
          id_lote: null,
          cantidad: Number(d.pendiente)
        }));
      if (!this.movimiento.detalles.length) {
        this.movimiento.detalles = [{id_presentacion:null,id_lote:null,cantidad:1}];
      }
      const destino = this.ubicaciones.find(u => Number(u.id_ubicacion) === Number(p.ubicacion_solicitante?.id_ubicacion));
      if (destino) {
        this.tipoDestino = destino.tipo;
        this.idEventoDestino = destino.tipo === 'STAND' ? destino.id_evento : null;
        this.movimiento.id_ubicacion_destino = destino.id_ubicacion;
      }
      const central = this.ubicaciones.find(u => u.tipo === 'ALMACEN' && ['ALMACÉN CENTRAL','ALMACEN CENTRAL'].includes(String(u.nombre).trim().toUpperCase()));
      if (central) this.movimiento.id_ubicacion_origen = central.id_ubicacion;
      // Seleccionar automáticamente un lote solo cuando el origen tiene stock.
      // La selección sigue siendo editable por el encargado.
      for (const d of this.movimiento.detalles) {
        const disponibles = this.lotes.filter(l =>
          Number(l.id_presentacion) === Number(d.id_presentacion) &&
          this.stockOrigen(l.id_lote) > 0
        ).sort((a,b) => Number(a.id_lote) - Number(b.id_lote));
        const lote = disponibles.find(l => !this.movimiento.detalles.some(x => Number(x.id_lote) === Number(l.id_lote)));
        if (lote) {
          d.id_lote = lote.id_lote;
          d.cantidad = Math.min(Number(d.cantidad), this.stockOrigen(lote.id_lote));
        }
      }
    },

    lotesConStockPresentacion(id) {
      return this.lotes.filter(l => Number(l.id_presentacion) === Number(id) && this.stockOrigen(l.id_lote) > 0);
    },
    lineaPedido(id) {
      return this.pedidoSeleccionado?.detalles?.find(d => Number(d.id_presentacion) === Number(id));
    },
    pendientePresentacion(id) {
      return Number(this.lineaPedido(id)?.pendiente || 0);
    },
    nombrePresentacionPedido(id) {
      const p = this.lineaPedido(id)?.presentacion;
      return [p?.producto?.nombre, p?.nombre_presentacion].filter(Boolean).join(' · ') || `presentación #${id}`;
    },
    opcionesPresentacion(idActual) {
      const opciones = [...this.presentacionesDisponibles];
      const linea = this.lineaPedido(idActual);
      if (linea?.presentacion && !opciones.some(p => Number(p.id_presentacion) === Number(idActual))) {
        opciones.push({ ...linea.presentacion, id_presentacion: Number(idActual) });
      }
      return opciones;
    },
    nombrePersonal(personal) {
      const persona = personal?.persona || {};
      return [persona.nombres_razon_social, persona.apellidos].filter(Boolean).join(' ').trim()
        || personal?.codigo_empleado || 'Personal #' + (personal?.id_personal || 'sin nombre');
    },
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
        await this.cargarPedidosPendientes();
        const pedidoURL = Number(this.$route.query.pedido);
        if (pedidoURL && this.pedidosPendientes.some(p => Number(p.id_pedido) === pedidoURL)) {
          this.movimiento.tipo = 'TRANSFERENCIA';
          this.idPedido = pedidoURL;
          this.seleccionarPedido();
        }
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
      this.movimiento.detalles = [{id_presentacion:null,id_lote:null,cantidad:1}];
      this.idPedido = null;
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
    agregarDetalle() { this.movimiento.detalles.push({id_presentacion:null,id_lote:null,cantidad:1}); },
    lotesDisponibles(actual) {
      const usados = new Set(this.movimiento.detalles.map(d => Number(d.id_lote)).filter(Boolean));
      return this.lotes.filter(l => (Number(l.id_lote) === Number(actual) || !usados.has(Number(l.id_lote))) && this.stockOrigen(l.id_lote) > 0);
    },
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
      if (this.pedidoSeleccionado && m.detalles.some(d => d.id_presentacion && !this.lotesConStockPresentacion(d.id_presentacion).length)) {
        return alert('El pedido contiene productos sin existencias. Quita del movimiento las filas sin stock para realizar un despacho parcial; seguirán pendientes en el pedido.');
      }
      const ids = m.detalles.map(d => d.id_lote);
      if (ids.some(id => !id)) return alert('Seleccione un lote en cada detalle.');
      if (new Set(ids).size !== ids.length) return alert('No puede repetir un mismo lote.');
      if (m.detalles.some(d => !Number.isInteger(d.cantidad) || d.cantidad === 0 || (m.tipo !== 'AJUSTE' && d.cantidad < 0)))
        return alert('Revise las cantidades: deben ser enteros y no pueden ser cero.');
      if (m.detalles.some(d => this.excedeStock(d))) return alert('Una cantidad supera las existencias o producción pendiente.');
      if (this.pedidoSeleccionado) {
        if (m.tipo !== 'TRANSFERENCIA') return alert('El pedido solo puede despacharse mediante transferencia.');
        const sumas = {};
        for (const d of m.detalles) sumas[d.id_presentacion] = (sumas[d.id_presentacion] || 0) + d.cantidad;
        for (const [id, cantidad] of Object.entries(sumas)) {
          const linea = this.pedidoSeleccionado.detalles.find(d => Number(d.id_presentacion) === Number(id));
          if (!linea || cantidad > linea.pendiente) return alert('La cantidad excede lo solicitado o la presentación no corresponde al pedido.');
        }
      }
      if (!confirm('¿Confirmar el movimiento? No podrá editarse ni eliminarse.')) return;
      this.guardando = true;
      try {
        const ruta = this.idPedido ? `${API}/pedidos/${this.idPedido}/despachar` : `${API}/movimientos-inventario`;
        const { data } = await api.post(ruta, m);
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



<style scoped>
.contador-productos{font-size:13px;color:#658071;font-weight:500}
.encabezado-detalles button{background:#f47b20;color:#fff;border:0;border-radius:9px;padding:10px 14px;font-weight:700}
.tabla-contenedor{border:1px solid #dce8df;border-radius:12px}
.tabla-contenedor thead{background:#0c3025;color:#fff}
.tabla-contenedor tbody tr:nth-child(even){background:#f7faf8}
.tabla-contenedor select,.tabla-contenedor input{border:1px solid #cbded2;border-radius:8px}
</style>

<style scoped>
.etiqueta-detalle{display:block;font-size:12px;color:#124734;font-weight:700;margin:8px 0 5px}
.panel-pedido{border:1px solid #d2e5d7;border-left:4px solid #f47b20;background:#f7fbf8;padding:16px;border-radius:12px;margin:16px 0}
.panel-pedido select{width:100%;padding:11px;border:1px solid #c9dacf;border-radius:9px;margin:9px 0}
.pedido-detalles{display:grid;gap:7px;background:white;padding:12px;border-radius:9px;font-size:13px;color:#124734}
</style>

<style scoped>
.aviso-sin-stock{display:flex;flex-direction:column;gap:5px;padding:12px;margin:0 0 12px;border:1px solid #f2ca9a;border-left:4px solid #f47b20;border-radius:9px;background:#fff8ee;color:#824411;font-size:13px}.aviso-sin-stock strong{color:#92400e}.aviso-sin-stock small{color:#855b38;line-height:1.45}
</style>
