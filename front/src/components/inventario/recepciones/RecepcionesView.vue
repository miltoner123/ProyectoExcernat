<template>
  <main class="recepcion-page">
    <header class="head"><div><span class="eyebrow">PRODUCCIÓN / ALMACÉN CENTRAL</span><h1>Recepciones pendientes</h1><p>Confirma la recepción antes de incorporar existencias al inventario.</p></div><button class="refresh" @click="cargar" :disabled="cargando">Actualizar</button></header>
    <div v-if="error" class="error">{{ error }}</div>
    <section class="kpi"><div><small>Lotes pendientes</small><strong>{{ lotes.length }}</strong></div><div><small>Unidades por recibir</small><strong>{{ totalUnidades }}</strong></div><div><small>Destino obligatorio</small><strong class="destino">{{ almacen || 'Almacén Central' }}</strong></div></section>
    <div class="tabla-wrap"><table><thead><tr><th>Lote</th><th>Producto / presentación</th><th>Producidas</th><th>Recibidas</th><th>Pendientes</th><th>Acción</th></tr></thead><tbody><tr v-for="l in lotes" :key="l.id_lote"><td><b>{{ l.codigo_lote }}</b></td><td>{{ l.producto }}<br><small>{{ l.presentacion }}</small></td><td>{{ l.cantidad_producida }}</td><td>{{ l.cantidad_recibida }}</td><td><b>{{ l.cantidad_pendiente }}</b></td><td><button class="confirm" :disabled="procesando === l.id_lote" @click="confirmar(l)">{{ procesando === l.id_lote ? 'Procesando...' : 'Confirmar recepción' }}</button></td></tr><tr v-if="!cargando && !lotes.length"><td colspan="6" class="vacio">No hay recepciones pendientes.</td></tr><tr v-if="cargando"><td colspan="6" class="vacio">Cargando lotes...</td></tr></tbody></table></div>
  </main>
</template>
<script>
import api from '../../../services/api';
export default {
  name: 'RecepcionesView',
  data: () => ({ lotes: [], almacen: '', cargando: false, procesando: null, error: '' }),
  computed: { totalUnidades() { return this.lotes.reduce((n,l) => n + Number(l.cantidad_pendiente || 0), 0); } },
  mounted() { this.cargar(); },
  methods: {
    async cargar() { this.cargando = true; this.error = ''; try { const { data } = await api.get('/api/recepciones-produccion'); this.lotes = data.data || []; this.almacen = data.almacen || ''; } catch(e) { this.error = e.response?.data?.message || 'No se pudieron cargar las recepciones. Comprueba los permisos y el Almacén Central.'; } finally { this.cargando = false; } },
    async confirmar(l) { if (!window.confirm(`¿Confirmar recepción de ${l.cantidad_pendiente} unidades del lote ${l.codigo_lote} en Almacén Central?`)) return; this.procesando = l.id_lote; this.error = ''; try { await api.post(`/api/recepciones-produccion/${l.id_lote}/confirmar`, {}); await this.cargar(); } catch(e) { this.error = e.response?.data?.message || Object.values(e.response?.data?.errors || {}).flat().join(' ') || 'No se pudo confirmar la recepción.'; } finally { this.procesando = null; } }
  }
};
</script>
<style scoped>
.recepcion-page{padding:24px;color:#103a2d}.head{display:flex;justify-content:space-between;gap:16px;align-items:center;margin-bottom:22px}.eyebrow{color:#ed7619;font-weight:800;font-size:11px;letter-spacing:1.4px}h1{font-size:29px;margin:7px 0}p{color:#627b70;margin:0}.kpi{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:15px;margin-bottom:22px}.kpi>div{background:white;border:1px solid #dbe7df;border-radius:15px;padding:20px;display:flex;flex-direction:column;gap:10px}.kpi small{color:#64796d}.kpi strong{font-size:28px}.kpi .destino{font-size:17px}.tabla-wrap{overflow:auto;background:#fff;border:1px solid #dbe7df;border-radius:14px}table{width:100%;border-collapse:collapse;text-align:left}th{background:#0c3025;color:white;padding:15px;font-size:13px}td{padding:14px;border-bottom:1px solid #e0ebe3;font-size:14px}td small{color:#667c70}.confirm{background:#f47b20;color:white;border:0;border-radius:9px;padding:11px 14px;font-weight:700;cursor:pointer}.refresh{background:white;border:1px solid #cddfd4;border-radius:9px;padding:10px 15px;cursor:pointer}.confirm:disabled,.refresh:disabled{opacity:.6;cursor:wait}.vacio{text-align:center;padding:28px}.error{background:#fff0ef;color:#a32e24;padding:12px 16px;border-radius:10px;margin-bottom:16px}@media(max-width:700px){.recepcion-page{padding:12px}.kpi{grid-template-columns:1fr}.head{align-items:flex-start;flex-direction:column}}
</style>
