<template>
  <section class="reporte">
    <h1>Resumen contable</h1>
    <p>Consulta de ventas confirmadas y cobros. No permite modificar operaciones.</p>
    <div class="filtros">
      <label>Desde <input type="date" v-model="desde"></label>
      <label>Hasta <input type="date" v-model="hasta"></label>
      <label>Evento <select v-model="idEvento"><option value="">Todos</option><option v-for="e in eventos" :key="e.id_evento" :value="e.id_evento">{{e.nombre}}</option></select></label>
      <label>Ubicación <select v-model="idUbicacion"><option value="">Todas</option><option v-for="u in ubicaciones" :key="u.id_ubicacion" :value="u.id_ubicacion">{{u.nombre}} · {{u.tipo}}</option></select></label>
      <button @click="cargar" :disabled="cargando">Consultar</button>
    </div>
    <p v-if="error" role="alert" class="error">{{error}}</p>
    <div v-if="datos" class="tarjetas">
      <article><small>Ventas confirmadas</small><strong>{{datos.ventas_confirmadas}}</strong></article>
      <article><small>Total ventas (Bs)</small><strong>{{moneda(datos.total_ventas)}}</strong></article>
      <article><small>Total cobrado (Bs)</small><strong>{{moneda(datos.total_cobrado)}}</strong></article>
    </div>
    <h2 v-if="datos">Ventas por ubicación (solo consulta)</h2>
    <table v-if="datos"><thead><tr><th>Ubicación</th><th>Tipo</th><th>Operaciones</th><th>Total (Bs)</th></tr></thead>
    <tbody><tr v-for="r in datos.por_ubicacion" :key="r.id_ubicacion"><td>{{r.nombre}}</td><td>{{r.tipo}}</td><td>{{r.operaciones}}</td><td>{{moneda(r.total)}}</td></tr></tbody></table>
    <h2 v-if="datos">Cobros por método de pago</h2>
    <table v-if="datos"><thead><tr><th>Método</th><th>Operaciones</th><th>Total (Bs)</th></tr></thead>
      <tbody><tr v-for="r in datos.cobros_por_metodo" :key="r.metodo_pago"><td>{{r.metodo_pago}}</td><td>{{r.operaciones}}</td><td>{{moneda(r.total)}}</td></tr></tbody>
    </table>
    <p class="nota">Reporte preliminar: no representa utilidad neta ni conciliación contable aprobada.</p>
  </section>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import api from '../../services/api';
const desde=ref(''),hasta=ref(''),idEvento=ref(''),idUbicacion=ref(''),eventos=ref([]),ubicaciones=ref([]),datos=ref(null),error=ref(''),cargando=ref(false);
const moneda=v=>Number(v||0).toFixed(2);
async function cargar(){
  error.value='';cargando.value=true;
  try{const {data}=await api.get('/api/reportes/contabilidad/resumen',{params:{...(desde.value?{desde:desde.value}:{}),...(hasta.value?{hasta:hasta.value}:{}),...(idEvento.value?{id_evento:idEvento.value}:{}),...(idUbicacion.value?{id_ubicacion:idUbicacion.value}:{})}});datos.value=data;}
  catch(e){datos.value=null;error.value=e.response?.data?.message||'No se pudo cargar el resumen.';}
  finally{cargando.value=false;}
}
onMounted(async()=>{
  try { const {data}=await api.get('/api/ventas-stand/stands'); ubicaciones.value=Array.isArray(data)?data:[]; }
  catch(e){error.value='No se pudieron cargar las ubicaciones.';}
  try { const {data}=await api.get('/api/eventos'); eventos.value=Array.isArray(data)?data:(data.data||[]); }
  catch(e){ /* El filtro de eventos es opcional; el resumen permanece disponible. */ }
  await cargar();
});
</script>
<style scoped>
.reporte{padding:25px;color:#0b3326}.filtros,.tarjetas{display:flex;gap:15px;flex-wrap:wrap;margin:18px 0}.filtros label{display:flex;flex-direction:column;gap:5px}.tarjetas article{background:white;border:1px solid #d9e6df;border-radius:12px;padding:20px;min-width:190px;display:flex;flex-direction:column;gap:10px}.tarjetas strong{font-size:26px}button{background:#f47b20;border:0;border-radius:8px;padding:10px 18px;color:white;font-weight:700}table{border-collapse:collapse;width:100%;background:white}th,td{padding:12px;border-bottom:1px solid #ddd;text-align:left}th{background:#0b3326;color:white}.error{color:#aa2020}.nota{color:#607568;font-size:13px}
</style>
