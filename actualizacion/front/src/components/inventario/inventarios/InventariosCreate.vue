<template>
  <div class="formulario">
    <h1>Registrar Inventario</h1>
    <p>Configura el stock mínimo. Las existencias se actualizan con los movimientos.</p>

    <form @submit.prevent="guardar">
      <div class="campo">
        <label>Ubicación:</label>
        <select v-model="form.id_ubicacion" required @change="consultarStock">
          <option :value="null" disabled>Seleccione una ubicación</option>
          <option v-for="u in ubicaciones" :key="u.id_ubicacion" :value="u.id_ubicacion">
            {{ u.nombre }} - {{ u.tipo }}
          </option>
        </select>
      </div>

      <div class="campo">
        <label>Lote / Producto / Presentación:</label>
        <input v-model="buscarLote" placeholder="Filtrar lotes por código, producto o presentación...">
        <select v-model="form.id_lote" required @change="consultarStock">
          <option :value="null" disabled>Seleccione un lote</option>
          <option v-for="l in lotesFiltrados" :key="l.id_lote" :value="l.id_lote">
            {{ nombreLote(l) }}
          </option>
        </select>
        <small v-if="cargando">Cargando información...</small>
      </div>

      <div v-if="loteSeleccionado" class="resumen">
        <strong>{{ nombreLote(loteSeleccionado) }}</strong>
        <div>Producción registrada: {{ loteSeleccionado.cantidad_producida ?? '—' }} unidades</div>
        <div>Vencimiento: {{ loteSeleccionado.fecha_vencimiento || '—' }}</div>
        <div v-if="form.id_ubicacion">
          Existencias actuales en la ubicación:
          <strong>{{ stockActual }} unidades</strong>
        </div>
        <p v-if="inventarioExistente" class="aviso">
          Este lote ya tiene inventario aquí (ID {{ inventarioExistente.id_inventario }}).
          Puedes actualizar su stock mínimo desde esta pantalla.
        </p>
      </div>

      <div class="campo">
        <label>Stock mínimo:</label>
        <input v-model.number="form.stock_minimo" type="number" min="0" required>
      </div>

      <div class="campo">
        <label>Stock actual:</label>
        <input :value="stockActual" type="number" disabled>
        <small>El stock solo cambia mediante entradas, salidas, transferencias, devoluciones y ajustes.</small>
      </div>

      <div class="botones">
        <button type="submit" :disabled="guardando || cargando || !form.id_lote || !form.id_ubicacion">
          {{ guardando ? 'Guardando...' : inventarioExistente ? 'Actualizar stock mínimo' : 'Guardar Inventario' }}
        </button>
        <button type="button" @click="$router.push('/inventarios')">Cancelar</button>
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
      form: { id_ubicacion: null, id_lote: null, stock_minimo: 0 },
      ubicaciones: [], lotes: [], buscarLote: '',
      inventarioExistente: null, stockActual: 0,
      guardando: false, cargando: false, consulta: 0
    };
  },
  computed: {
    loteSeleccionado() {
      return this.lotes.find(l => l.id_lote === this.form.id_lote);
    },
    lotesFiltrados() {
      const q = this.buscarLote.toLowerCase().trim();
      return q ? this.lotes.filter(l => this.nombreLote(l).toLowerCase().includes(q)) : this.lotes;
    }
  },
  mounted() { this.cargarCatalogos(); },
  methods: {
    nombreLote(l) {
      return `${l.codigo_lote} - ${l.presentacion?.producto?.nombre || 'Sin producto'} - ${l.presentacion?.nombre_presentacion || 'Sin presentación'}`;
    },
    async todasLasPaginas(ruta, params = {}) {
      let pagina = 1, resultado = [], ultima = 1;
      do {
        const { data } = await api.get(`${API}/${ruta}`, { params: { ...params, page: pagina, limit: 100 } });
        resultado.push(...(Array.isArray(data) ? data : data.data || []));
        ultima = Array.isArray(data) ? 1 : Number(data.last_page || 1);
        pagina++;
      } while (pagina <= ultima);
      return resultado;
    },
    async cargarCatalogos() {
      this.cargando = true;
      try {
        const [ubicaciones, lotes] = await Promise.all([
          this.todasLasPaginas('ubicaciones', { estado: 'true' }),
          this.todasLasPaginas('lotes', { estado: 'true' })
        ]);
        this.ubicaciones = ubicaciones;
        this.lotes = lotes;
      } catch (e) {
        console.error(e);
        alert('No se pudieron cargar los lotes o las ubicaciones.');
      } finally { this.cargando = false; }
    },
    async consultarStock() {
      const numero = ++this.consulta;
      this.inventarioExistente = null;
      this.stockActual = 0;
      if (!this.form.id_ubicacion || !this.form.id_lote) return;
      this.cargando = true;
      try {
        const { data } = await api.get(`${API}/inventarios/consulta`, {
          params: { id_ubicacion: this.form.id_ubicacion, id_lote: this.form.id_lote }
        });
        if (numero !== this.consulta) return;
        this.inventarioExistente = data.inventario;
        this.stockActual = Number(data.stock_actual || 0);
        this.form.stock_minimo = Number(data.inventario?.stock_minimo || 0);
      } catch (e) {
        if (numero === this.consulta) {
          console.error(e);
          alert('No se pudieron consultar las existencias.');
        }
      } finally { if (numero === this.consulta) this.cargando = false; }
    },
    async guardar() {
      this.guardando = true;
      try {
        const existente = this.inventarioExistente;
        const url = existente ? `${API}/inventarios/${existente.id_inventario}` : `${API}/inventarios`;
        const datos = existente ? { stock_minimo: this.form.stock_minimo } : this.form;
        const { data } = await api.post(url, datos);
        alert(data.message || 'Inventario guardado correctamente.');
        this.$router.push('/inventarios');
      } catch (e) {
        alert(e.response?.data?.message || Object.values(e.response?.data?.errors || {}).flat().join('\n') || 'No se pudo guardar el inventario.');
      } finally { this.guardando = false; }
    }
  }
};
</script>

<style scoped>
.formulario { max-width: 750px; margin: 0 auto; }
h1 { margin-bottom: 6px; }
p { color: #64748b; margin-bottom: 20px; }
.campo { display: grid; gap: 7px; margin-bottom: 15px; }
label { font-weight: 600; }
input, select { padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; width: 100%; box-sizing: border-box; }
input:disabled { background: #f3f4f6; }
small { color: #64748b; }
.resumen { padding: 14px; background: #f1f5f9; border-radius: 8px; margin: 16px 0; line-height: 1.9; }
.aviso { color: #92400e; margin: 8px 0 0; }
.botones { display: flex; gap: 10px; }
button { padding: 9px 14px; cursor: pointer; }
button:disabled { opacity: .6; cursor: not-allowed; }
</style>
