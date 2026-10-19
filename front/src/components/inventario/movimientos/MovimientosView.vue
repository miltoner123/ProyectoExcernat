<template>
  <div class="movimientos">
    <div class="encabezado">
      <div><h1>Movimientos de Inventario</h1><p>Historial de entradas, salidas y transferencias.</p></div>
      <button @click="$router.push('/movimientos/crear')">+ Nuevo Movimiento</button>
    </div>
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
      total: 0, cargando: false, expandido: null, temporizador: null, numeroPeticion: 0
    };
  },
  mounted() { this.obtenerMovimientos(); },
  beforeUnmount() { clearTimeout(this.temporizador); this.numeroPeticion++; },
  methods: {
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
