<template>
  <main class="rt-wrap">
    <header class="rt-header"><div><h1>Recepción de transferencias</h1><p>Despachos pendientes de confirmación por el encargado del destino.</p></div><button @click="cargar" :disabled="cargando">Actualizar</button></header>
    <p v-if="error" class="rt-error">{{ error }}</p>
    <p v-if="cargando">Cargando transferencias...</p>
    <div v-else-if="!movimientos.length" class="rt-empty">No tienes transferencias pendientes de recepción.</div>
    <article v-for="m in movimientos" :key="m.id_movimiento" class="rt-card">
      <div class="rt-title"><strong>Transferencia #{{ m.id_movimiento }}</strong><span>PENDIENTE</span></div>
      <p><b>Origen:</b> {{ m.origen?.nombre || '—' }} · <b>Destino:</b> {{ m.destino?.nombre || '—' }}</p>
      <p v-if="m.destino?.evento"><b>Evento:</b> {{ m.destino.evento.nombre }}</p>
      <p><b>Despacho:</b> {{ fecha(m.fecha_movimiento) }}</p>
      <table><thead><tr><th>Lote</th><th>Producto</th><th>Cantidad</th></tr></thead><tbody><tr v-for="d in m.detalles" :key="d.id_lote"><td>{{ d.lote?.codigo_lote }}</td><td>{{ d.lote?.presentacion?.producto?.nombre || '—' }}</td><td>{{ d.cantidad }}</td></tr></tbody></table>
      <div class="rt-actions"><button :disabled="procesando === m.id_movimiento" @click="confirmar(m)">{{ procesando === m.id_movimiento ? 'Confirmando...' : 'Confirmar recepción completa' }}</button></div>
    </article>
  </main>
</template>
<script>
import api from '../../../services/api';
export default {
  name: 'RecepcionesTransferenciasView',
  data() { return { movimientos: [], cargando: false, procesando: null, error: '' }; },
  mounted() { this.cargar(); },
  methods: {
    fecha(v) { return v ? new Date(v).toLocaleString('es-BO') : '—'; },
    async cargar() { this.cargando = true; this.error = ''; try { const { data } = await api.get('/api/recepciones-transferencias'); this.movimientos = data.data || []; } catch(e) { this.error = e.response?.data?.message || 'No se pudieron cargar las transferencias.'; } finally { this.cargando = false; } },
    async confirmar(m) {
      if (!window.confirm(`¿Confirmar recepción COMPLETA de la transferencia #${m.id_movimiento}? Verifica físicamente todos los productos.`)) return;
      this.procesando = m.id_movimiento; this.error = '';
      try { await api.post(`/api/recepciones-transferencias/${m.id_movimiento}/confirmar`); await this.cargar(); }
      catch(e) { this.error = Object.values(e.response?.data?.errors || {}).flat().join(' ') || e.response?.data?.message || 'No se pudo confirmar.'; }
      finally { this.procesando = null; }
    }
  }
};
</script>
<style scoped>
.rt-wrap{padding:24px;max-width:1200px;margin:auto;color:#17372d}.rt-header{display:flex;justify-content:space-between;gap:20px;align-items:center;margin-bottom:22px}.rt-header h1{font-size:26px;font-weight:800;margin:0;color:#0c3025}.rt-header p{color:#61746b}.rt-header button,.rt-actions button{background:#124734;color:white;border:0;border-radius:10px;padding:11px 18px;cursor:pointer;font-weight:700}.rt-card{background:white;border:1px solid #e2e8e3;border-left:4px solid #f47b20;border-radius:14px;padding:20px;margin-bottom:16px;box-shadow:0 3px 14px #0c30250b}.rt-title{display:flex;justify-content:space-between;align-items:center}.rt-title span{color:#a65a10;background:#fff0df;border-radius:20px;padding:5px 12px;font-size:12px;font-weight:800}.rt-card p{margin:10px 0}.rt-card table{width:100%;border-collapse:collapse;margin-top:14px}.rt-card th,.rt-card td{text-align:left;border-bottom:1px solid #edf0ed;padding:10px}.rt-card th{background:#f4f7f4}.rt-actions{text-align:right;margin-top:16px}.rt-actions button{background:#f47b20}.rt-actions button:disabled{opacity:.6}.rt-empty,.rt-error{padding:24px;background:white;border-radius:12px}.rt-error{color:#9b2525;background:#fff1f1}
</style>
