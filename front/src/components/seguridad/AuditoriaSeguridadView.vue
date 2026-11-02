<template>
  <main class="auditoria"><header><h1>Auditoría de seguridad</h1><p>Historial de usuarios, niveles y cargos.</p></header>
  <p v-if="error" class="error">{{ error }}</p>
  <div class="filtros"><label>Desde <input type="date" v-model="desde" /></label><label>Hasta <input type="date" v-model="hasta" /></label><button @click="pagina=1; cargar()">Filtrar</button></div>
  <p v-if="cargando">Cargando registros...</p>
  <div class="tabla"><table><thead><tr><th>Fecha</th><th>Responsable</th><th>Usuario afectado</th><th>Acción</th><th>Anterior</th><th>Nuevo</th></tr></thead>
    <tbody><tr v-for="r in registros" :key="r.id"><td>{{ r.created_at }}</td><td>{{ r.actor_nombre || 'Sistema' }}</td><td>{{ r.afectado_nombre || '-' }}</td><td>{{ nombreAccion(r.accion) }}</td><td><pre>{{ formatoLegible(r.anterior) }}</pre></td><td><pre>{{ formatoLegible(r.nuevo) }}</pre></td></tr><tr v-if="!registros.length"><td colspan="6">Sin registros en el período.</td></tr></tbody>
  </table></div><nav><button :disabled="pagina<=1" @click="pagina--;cargar()">Anterior</button> Página {{ pagina }} de {{ ultima }} <button :disabled="pagina>=ultima" @click="pagina++;cargar()">Siguiente</button></nav>
  </main>
</template>
<script setup>
import { onMounted, ref } from 'vue';
import api from '../../services/api';
const registros=ref([]), pagina=ref(1), ultima=ref(1), error=ref(''), cargando=ref(false), desde=ref(''), hasta=ref('');
// Las etiquetas cambian únicamente la presentación: la auditoría almacenada permanece intacta.
const acciones = {
  USUARIO_CREADO: 'Usuario creado',
  USUARIO_ACTUALIZADO: 'Usuario actualizado',
  PERSONAL_ACCESO_CAMBIADO: 'Cargo o estado del personal modificado',
};
const nombreAccion = (accion) => acciones[accion] || (accion || '-').replaceAll('_', ' ');
const etiquetas = { estado: 'Estado', cargo_id: 'Cargo', rol: 'Nivel', roles: 'Roles', nivel: 'Nivel', email: 'Correo' };
function formatoLegible(valor) {
  if (valor == null) return '-';
  let datos = valor;
  if (typeof valor === 'string') {
    try { datos = JSON.parse(valor); } catch { return valor; }
  }
  if (Array.isArray(datos)) return datos.join(', ') || '-';
  if (typeof datos !== 'object') return String(datos);
  return Object.entries(datos).map(([clave, dato]) => {
    const etiqueta = etiquetas[clave] || clave.replaceAll('_', ' ');
    let texto = dato === true ? 'Activo' : dato === false ? 'Inactivo' : dato == null ? '-' : Array.isArray(dato) ? dato.join(', ') : String(dato);
    if (clave === 'cargo_id' && dato != null) texto = `ID ${dato}`;
    return `${etiqueta}: ${texto}`;
  }).join('\n') || '-';
}
async function cargar(){cargando.value=true;error.value='';try{const {data}=await api.get('/api/auditoria-seguridad',{params:{page:pagina.value,limit:20, ...(desde.value?{desde:desde.value}:{}),...(hasta.value?{hasta:hasta.value}:{})}});registros.value=data.data||[];ultima.value=data.last_page||1;}catch(e){error.value=e.response?.data?.message||'No fue posible cargar el historial.';}finally{cargando.value=false;}}
onMounted(cargar);
</script>
<style scoped>
.auditoria{padding:24px;color:var(--color-text,#183729)}header{margin-bottom:20px}.filtros{display:flex;gap:12px;align-items:end;flex-wrap:wrap;margin-bottom:16px}.filtros label{display:grid;gap:4px}.tabla{overflow:auto;background:white;border-radius:12px}table{border-collapse:collapse;width:100%;font-size:13px}th,td{border-bottom:1px solid #ddd;text-align:left;padding:10px;vertical-align:top}th{background:#174b37;color:#fff}pre{font-size:11px;max-width:240px;white-space:pre-wrap;word-break:break-word}button{padding:8px 14px;cursor:pointer}nav{margin-top:16px;display:flex;align-items:center;gap:12px}.error{color:#ad2727}
</style>
