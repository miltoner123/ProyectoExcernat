<template>
  <div class="exc-modern lote-page">
    <div class="lote-heading">
      <div>
        <span class="lote-eyebrow">PRODUCCIÓN / LOTES</span>
        <h1>Crear Lote</h1>
        <p>Registra la producción, fechas y trazabilidad del lote.</p>
      </div>
      <button type="button" class="lote-btn lote-btn-outline" @click="$router.push('/lotes')">← Volver al listado</button>
    </div>

    <form class="lote-card" @submit.prevent="crearLote">
      <h2>Información del lote</h2>
      <p class="lote-hint">El código se genera automáticamente a partir del SKU de la presentación.</p>

      <div v-if="errorCarga" class="lote-alert lote-alert-error" role="alert">
        {{ errorCarga }}
        <button type="button" class="lote-link" @click="presentacionesGet">Reintentar</button>
      </div>

      <div class="lote-grid">
        <div class="lote-field lote-full">
          <label for="lote-presentacion">Presentación <span>*</span></label>
          <select id="lote-presentacion" v-model="lote.id_presentacion" required :disabled="cargandoPresentaciones || guardando">
            <option value="" disabled>{{ cargandoPresentaciones ? 'Cargando presentaciones...' : 'Seleccione una presentación' }}</option>
            <option v-for="presentacion in presentaciones" :key="presentacion.id_presentacion" :value="presentacion.id_presentacion">
              {{ presentacion.producto?.nombre || 'Producto' }} — {{ presentacion.nombre_presentacion }} — {{ presentacion.codigo_sku }}
            </option>
          </select>
          <small v-if="!cargandoPresentaciones && !errorCarga && presentaciones.length === 0">No se encontraron presentaciones disponibles.</small>
        </div>

        <div class="lote-field lote-full">
          <label for="lote-codigo">Código de lote</label>
          <input id="lote-codigo" type="text" :value="skuSeleccionado ? `${skuSeleccionado}-L...` : 'Seleccione una presentación'" readonly />
          <small>El código definitivo se asignará al guardar. Ejemplo: T-EC-01-L001.</small>
        </div>

        <div class="lote-field">
          <label for="lote-fecha">Fecha de producción <span>*</span></label>
          <input id="lote-fecha" type="date" v-model="lote.fecha_produccion" required :disabled="guardando" />
        </div>
        <div class="lote-field">
          <label for="lote-duracion">Vida útil del producto <span>*</span></label>
          <select id="lote-duracion" v-model="lote.tipoDuracion" @change="actualizarDuracion" :disabled="guardando">
            <option value="8">8 meses</option>
            <option value="12">1 año</option>
            <option value="18">1 año y medio</option>
            <option value="otro">Otra duración</option>
          </select>
        </div>
        <div v-if="lote.tipoDuracion === 'otro'" class="lote-field">
          <label for="lote-meses">Duración personalizada (meses) <span>*</span></label>
          <input id="lote-meses" type="number" v-model.number="lote.vida_util_meses" min="1" max="120" required :disabled="guardando" />
        </div>
        <div class="lote-field">
          <label for="lote-vencimiento">Fecha de vencimiento calculada</label>
          <input id="lote-vencimiento" type="date" :value="fechaVencimientoCalculada" readonly />
          <small>Se calcula automáticamente según la fecha de producción y la vida útil.</small>
        </div>
        <div class="lote-field">
          <label for="lote-cantidad">Cantidad producida <span>*</span></label>
          <input id="lote-cantidad" type="number" min="1" step="1" v-model.number="lote.cantidad_producida" required :disabled="guardando" />
        </div>
        <div class="lote-field lote-full">
          <label for="lote-observacion">Observación</label>
          <textarea id="lote-observacion" v-model="lote.observacion" rows="3" placeholder="Información adicional sobre la producción..." :disabled="guardando"></textarea>
        </div>
      </div>

      <div v-if="errorGuardado" class="lote-alert lote-alert-error" role="alert">{{ errorGuardado }}</div>
      <div class="lote-actions">
        <button type="button" class="lote-btn lote-btn-outline" :disabled="guardando" @click="$router.push('/lotes')">Cancelar</button>
        <button type="submit" class="lote-btn lote-btn-primary" :disabled="guardando || cargandoPresentaciones || !presentaciones.length">
          {{ guardando ? 'Guardando...' : 'Crear lote' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script>
import api from '../../../services/api';

// Respeta la configuración y los interceptores del servicio Axios existente.
// Acepta tanto baseURL http://localhost:8000 como http://localhost:8000/api.
const rutaApi = (recurso) => {
  const base = String(api.defaults.baseURL || '').replace(/\/+$/, '');
  return /\/api$/i.test(base) ? `/${recurso}` : `/api/${recurso}`;
};

export default {
  data() {
    return {
      lote: {
        id_presentacion: '',
        codigo_lote: '',
        fecha_produccion: '',
        vida_util_meses: 12,
        fecha_vencimiento: '',
        cantidad_producida: '',
        estado: true,
        tipoDuracion: '12',
        observacion: ''
      },
      presentaciones: [],
      cargandoPresentaciones: false,
      guardando: false,
      errorCarga: '',
      errorGuardado: ''
    };
  },
  computed: {
    skuSeleccionado() {
      const p = this.presentaciones.find(x => String(x.id_presentacion) === String(this.lote.id_presentacion));
      return p?.codigo_sku || '';
    },
    fechaVencimientoCalculada() {
      if (!this.lote.fecha_produccion || !this.lote.vida_util_meses) return '';
      const meses = Number(this.lote.vida_util_meses);
      if (!Number.isInteger(meses) || meses < 1 || meses > 120) return '';
      const [anio, mes, dia] = this.lote.fecha_produccion.split('-').map(Number);
      const fecha = new Date(Date.UTC(anio, mes - 1 + meses, 1));
      const ultimoDia = new Date(Date.UTC(fecha.getUTCFullYear(), fecha.getUTCMonth() + 1, 0)).getUTCDate();
      fecha.setUTCDate(Math.min(dia, ultimoDia));
      return fecha.toISOString().substring(0, 10);
    }
  },
  mounted() {
    this.presentacionesGet();
  },
  methods: {
    async presentacionesGet() {
      this.cargandoPresentaciones = true;
      this.errorCarga = '';
      this.presentaciones = [];
      try {
        // Recorre las páginas para no ocultar presentaciones después de la primera.
        let pagina = 1;
        let ultima = 1;
        const registros = [];
        do {
          const response = await api.get(rutaApi('presentaciones'), { params: { limit: 100, page: pagina } });
          const payload = response.data;
          const paginado = Array.isArray(payload?.data) ? payload : payload?.data;
          const items = Array.isArray(payload) ? payload : Array.isArray(paginado?.data) ? paginado.data : Array.isArray(paginado) ? paginado : [];
          registros.push(...items);
          ultima = Number(paginado?.last_page || 1);
          pagina++;
        } while (pagina <= ultima && pagina <= 100);
        this.presentaciones = registros;
      } catch (error) {
        console.error('Error al cargar presentaciones:', error);
        this.errorCarga = error.response?.status === 401
          ? 'La sesión ha expirado. Inicia sesión nuevamente.'
          : error.response?.status === 403
            ? 'No tienes permiso para consultar las presentaciones.'
            : 'No se pudieron cargar las presentaciones. Comprueba que el backend esté activo y la ruta API sea correcta.';
      } finally {
        this.cargandoPresentaciones = false;
      }
    },
    async crearLote() {
      if (this.guardando) return;
      this.errorGuardado = '';
      if (!this.lote.id_presentacion || !this.skuSeleccionado || !this.fechaVencimientoCalculada) {
        this.errorGuardado = 'Selecciona una presentación válida y completa la fecha y vida útil.';
        return;
      }
      const { fecha_vencimiento, tipoDuracion, codigo_lote, ...datosLote } = this.lote;
      const datos = { ...datosLote, vida_util_meses: Number(this.lote.vida_util_meses), cantidad_producida: Number(this.lote.cantidad_producida) };
      this.guardando = true;
      try {
        await api.post(rutaApi('lotes'), datos);
        alert('Lote registrado correctamente');
        this.$router.push('/lotes');
      } catch (error) {
        console.error('Error al registrar el lote:', error.response?.data || error);
        const detalles = error.response?.data?.errors;
        this.errorGuardado = detalles && typeof detalles === 'object'
          ? Object.values(detalles).flat().join(' ')
          : error.response?.data?.message || 'No se pudo registrar el lote.';
      } finally {
        this.guardando = false;
      }
    },
    actualizarDuracion() {
      this.lote.vida_util_meses = this.lote.tipoDuracion === 'otro' ? null : Number(this.lote.tipoDuracion);
    }
  }
};
</script>

<style scoped>
.lote-page{max-width:1120px;margin:0 auto;padding:24px;color:#103d30}
.lote-heading{display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:22px}
.lote-eyebrow{font-size:11px;letter-spacing:2px;font-weight:800;color:#ed7316}
.lote-heading h1{font-size:30px;margin:7px 0 5px;color:#0b382b}
.lote-heading p,.lote-hint{color:#657a73;margin:0}
.lote-card{background:#fff;border:1px solid #dce9e1;border-radius:18px;padding:28px;box-shadow:0 8px 24px rgba(10,49,36,.05)}
.lote-card h2{font-size:19px;margin:0 0 6px}
.lote-hint{font-size:13px;margin-bottom:22px}
.lote-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:19px 22px}
.lote-full{grid-column:1/-1}
.lote-field{display:flex;flex-direction:column;gap:7px;min-width:0}
.lote-field label{font-size:13px;font-weight:750;color:#123e30}
.lote-field label span{color:#ed7316}
.lote-field input,.lote-field select,.lote-field textarea{box-sizing:border-box;width:100%;min-height:44px;border:1px solid #cbded2;border-radius:10px;padding:10px 13px;background:#fff;color:#173e31;font:inherit;font-size:14px;outline:none}
.lote-field textarea{resize:vertical}
.lote-field input:focus,.lote-field select:focus,.lote-field textarea:focus{border-color:#ee842d;box-shadow:0 0 0 3px rgba(244,123,32,.13)}
.lote-field input[readonly]{background:#f1f7f3;color:#426558}
.lote-field small{font-size:12px;color:#71857a}
.lote-actions{display:flex;justify-content:flex-end;gap:12px;margin-top:25px;padding-top:20px;border-top:1px solid #e4ede7}
.lote-btn{border-radius:10px;border:1px solid transparent;min-height:42px;padding:10px 18px;font-weight:750;cursor:pointer}
.lote-btn-outline{background:#fff;border-color:#cddfd3;color:#134634}
.lote-btn-primary{background:#f47b20;color:#fff}
.lote-btn-primary:hover:not(:disabled){background:#dc6710}
.lote-btn:disabled{opacity:.55;cursor:not-allowed}
.lote-alert{padding:12px 14px;border-radius:10px;margin:15px 0;font-size:13px}
.lote-alert-error{background:#fff2ef;border:1px solid #f3c9bf;color:#9b3020}
.lote-link{margin-left:10px;border:0;background:transparent;text-decoration:underline;color:inherit;cursor:pointer;font-weight:700}
@media(max-width:700px){.lote-page{padding:14px}.lote-heading{align-items:flex-start;flex-direction:column}.lote-card{padding:18px}.lote-grid{grid-template-columns:1fr}.lote-heading h1{font-size:25px}}
</style>


