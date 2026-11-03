<template>
  <section class="vendedores-reporte">
    <header class="encabezado">
      <div><p class="eyebrow">EXCERNAT · INFORMES</p><h1>{{ operativo ? "Informe operativo de ventas" : "Ventas por vendedor" }}</h1><p>Productos, cantidades, precios históricos y descuentos.</p></div>
      <router-link :to="operativo ? '/mi-panel-operativo' : '/reportes/contabilidad'" class="enlace">Volver</router-link>
    </header>
    <form class="filtros" @submit.prevent="consultar">
      <label>Desde<input type="date" v-model="f.desde"></label>
      <label>Hasta<input type="date" v-model="f.hasta"></label>
      <label>Evento<select v-model="f.id_evento" @change="f.id_ubicacion=''">
        <option value="">Todos los eventos</option><option v-for="e in eventos" :key="e.id_evento" :value="String(e.id_evento)">{{ e.nombre }}</option>
      </select></label>
      <label>Stand o sucursal<select v-model="f.id_ubicacion">
        <option value="">Todas las ubicaciones</option><option v-for="u in ubicacionesFiltradas" :key="u.id_ubicacion" :value="String(u.id_ubicacion)">{{ u.nombre }}</option>
      </select></label>
      <button type="submit" :disabled="cargando">{{ cargando?'Consultando...':'Consultar' }}</button>
      <button type="button" class="alterno" :disabled="cargando || !datos || exportando" @click="pdf">{{ exportando?'Generando...':'Descargar PDF' }}</button>
    </form>
    <p v-if="error" class="error" role="alert">{{ error }}</p>
    <template v-if="datos">
      <div class="tarjetas">
        <article><small>Ventas</small><strong>{{datos.totales.ventas}}</strong></article>
        <article><small>Unidades</small><strong>{{datos.totales.unidades}}</strong></article>
        <article><small>Total neto</small><strong>Bs {{dinero(datos.totales.neto)}}</strong></article>
        <article v-if="!operativo"><small>Comisión estimada</small><strong>Bs {{dinero(datos.totales.comision)}}</strong></article><article><small>Descuentos</small><strong>Bs {{dinero(datos.totales.descuento)}}</strong></article><article v-if="operativo"><small>Cobrado</small><strong>Bs {{dinero(datos.totales.cobrado)}}</strong></article>
      </div>
      <div class="advertencias" v-if="datos.advertencias.length"><p v-for="(a,i) in datos.advertencias" :key="i">{{a}}</p></div>
      <div class="tabla-scroll" role="region" aria-label="Resultados por vendedor" tabindex="0">
        <table>
          <thead><tr><th>Vendedor</th><th>Ventas</th><th>Unidades</th><th>Bruto</th><th>Descuento</th><th>Neto</th><th v-if="!operativo">Comisión</th><th>Detalle</th></tr></thead>
          <tbody>
            <template v-for="v in datos.vendedores" :key="v.id_asignacion_vendedor">
              <tr><td><strong>{{v.vendedor}}</strong><small>Asignación #{{v.id_asignacion_vendedor}} · {{v.tipo_remuneracion}}</small></td>
                <td>{{v.ventas}}</td><td>{{v.unidades}}</td><td>Bs {{dinero(v.bruto)}}</td><td>Bs {{dinero(v.descuento)}}</td>
                <td>Bs {{dinero(v.neto)}}</td><td v-if="!operativo">Bs {{dinero(v.comision)}}</td>
                <td><button class="detalle-btn" @click="alternar(v.id_asignacion_vendedor)" :aria-expanded="abierto===v.id_asignacion_vendedor">{{abierto===v.id_asignacion_vendedor?'Ocultar':'Ver productos'}}</button></td>
              </tr>
              <tr v-if="abierto===v.id_asignacion_vendedor"><td :colspan="operativo ? 7 : 8" class="celda-detalle">
                <p v-if="!operativo"><strong>Fijo referencial:</strong> Bs {{dinero(v.monto_fijo_referencial)}} · <strong>Remuneración referencial:</strong> Bs {{dinero(v.remuneracion_referencial)}}</p>
                <div class="tabla-scroll" role="region" tabindex="0" aria-label="Detalle de ventas del vendedor">
                  <table class="subtabla"><thead><tr><th>Fecha</th><th>Evento / Stand</th><th>Producto / presentación</th><th>Cant.</th><th>Precio</th><th>Bruto</th><th>Descuento</th><th>Neto</th><th v-if="!operativo">Comisión</th></tr></thead>
                  <tbody><tr v-for="(l,j) in v.detalle" :key="j"><td>{{l.fecha}}</td><td>{{l.evento}} / {{l.stand}}</td>
                    <td>{{l.producto}} · {{l.presentacion}}</td><td>{{l.cantidad}}</td><td>Bs {{dinero(l.precio_unitario)}}</td>
                    <td>Bs {{dinero(l.bruto)}}</td><td>Bs {{dinero(l.descuento)}}</td><td>Bs {{dinero(l.neto)}}</td><td v-if="!operativo">Bs {{dinero(l.comision)}}</td></tr></tbody></table>
                </div>
              </td></tr>
            </template>
            <tr v-if="!datos.vendedores.length"><td :colspan="operativo ? 7 : 8">No se encontraron ventas confirmadas de vendedores para los filtros seleccionados.</td></tr>
          </tbody>
        </table>
      </div>
    </template>
  </section>
</template>
<script setup>
import {ref,computed,onMounted} from 'vue';
import {useRoute} from 'vue-router';
import api from '../../services/api';
const route=useRoute();
const operativo=route.path==='/mi-informe-ventas';
const f=ref({desde:'',hasta:'',id_evento:String(route.query.id_evento||''),id_ubicacion:''});
const eventos=ref([]),ubicaciones=ref([]),datos=ref(null),error=ref(''),cargando=ref(false),exportando=ref(false),abierto=ref(null);
const ubicacionesFiltradas=computed(()=>ubicaciones.value.filter(u=>!f.value.id_evento || String(u.id_evento)===f.value.id_evento));
const dinero=n=>Number(n||0).toFixed(2);
const endpoint=operativo?'/api/mi-informe-ventas':'/api/reportes/vendedores';
const params=()=>Object.fromEntries(Object.entries(f.value).filter(([,v])=>v!==''));
const alternar=id=>{abierto.value=abierto.value===id?null:id;};
async function consultar(){cargando.value=true;error.value='';abierto.value=null;try{const {data}=await api.get(endpoint,{params:params()});datos.value=data;}
catch(e){datos.value=null;error.value=e.response?.data?.message||'No se pudo consultar las ventas por vendedor.';}
finally{cargando.value=false;}}
async function pdf(){exportando.value=true;error.value='';try{const {data}=await api.get(endpoint+'/pdf',{params:params(),responseType:'blob'});
  const url=URL.createObjectURL(new Blob([data],{type:'application/pdf'}));const a=document.createElement('a');a.href=url;a.download='EXCERNAT_Ventas_Vendedores.pdf';a.click();setTimeout(()=>URL.revokeObjectURL(url),1000);
}catch(e){error.value='No se pudo generar el reporte PDF.';}finally{exportando.value=false;}}
onMounted(async()=>{
if(operativo){
 try{
  const {data}=await api.get('/api/mi-panel-operativo');
  const seen=new Set();
  ubicaciones.value=(data.asignaciones||[]).map(a=>a.ubicacion).filter(u=>{
   if(!u || seen.has(u.id_ubicacion))return false;
   seen.add(u.id_ubicacion);return true;
  });
  const events=new Map();
  for(const u of ubicaciones.value){if(u.id_evento)events.set(String(u.id_evento),{id_evento:u.id_evento,nombre:u.evento?.nombre||'Evento #'+u.id_evento});}
  eventos.value=[...events.values()];
  if(f.value.id_ubicacion && !ubicaciones.value.some(u=>String(u.id_ubicacion)===f.value.id_ubicacion))f.value.id_ubicacion='';
  if(f.value.id_evento && !events.has(f.value.id_evento))f.value.id_evento='';
 }catch(e){error.value='No se pudieron cargar las ubicaciones asignadas.';return;}
 await consultar(); return;
}
const r=await Promise.allSettled([api.get('/api/eventos',{params:{limit:50}}),api.get('/api/ventas-stand/stands')]);
if(r[0].status==='fulfilled'){let x=r[0].value.data;eventos.value=Array.isArray(x)?x:(x.data||[]);}
if(r[1].status==='fulfilled'){let x=r[1].value.data;ubicaciones.value=Array.isArray(x)?x:[];}
await consultar();});
</script>
<style scoped>
.vendedores-reporte{color:#10372a;max-width:100%;min-width:0;padding:clamp(12px,2.4vw,26px)}.encabezado{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}.eyebrow{color:#e87315;font-weight:800;letter-spacing:1px;font-size:12px}.encabezado h1{margin:5px 0;font-size:clamp(22px,3vw,32px)}.encabezado p{margin:5px 0}.enlace{color:#14523e;font-weight:700}.filtros{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,170px),1fr));align-items:end;gap:12px;margin:20px 0;padding:16px;background:#f0f7f3;border:1px solid #d4e6dc;border-radius:12px}.filtros label{display:grid;gap:6px;font-weight:700;font-size:13px;min-width:0}.filtros input,.filtros select{width:100%;min-width:0;padding:10px;border:1px solid #b9cec3;border-radius:8px;background:white;color:#123829}.filtros button{min-height:44px}.filtros button,.detalle-btn{border:0;border-radius:8px;padding:10px;background:#ee781a;color:white;font-weight:700;cursor:pointer}.filtros button.alterno{background:#104230}.filtros button:disabled{opacity:.5;cursor:wait}.tarjetas{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,170px),1fr));gap:12px;margin-bottom:18px}.tarjetas article{border:1px solid #d5e3d8;background:white;border-radius:12px;padding:16px;display:grid;gap:8px}.tarjetas small{color:#5b7264}.tarjetas strong{font-size:clamp(18px,2.3vw,25px)}.tabla-scroll{width:100%;max-width:100%;overflow:auto;border:1px solid #dce8df;border-radius:10px;background:white}table{border-collapse:collapse;min-width:850px;width:100%}th,td{padding:12px;border-bottom:1px solid #e0e9e3;text-align:left;font-size:13px;vertical-align:top}th{background:#0c3325;color:white;white-space:nowrap}td small{display:block;margin-top:5px;color:#637267}.celda-detalle{background:#eff7f1;padding:16px}.subtabla{min-width:980px}.error{background:#fff0ed;padding:12px;border-radius:8px;color:#ac3022}.advertencias{border-left:3px solid #e67a1e;background:#fff5e8;padding:8px 14px;margin-bottom:16px;font-size:13px}.detalle-btn{background:#185740;white-space:nowrap}@media(max-width:640px){.encabezado{align-items:flex-start}.filtros{grid-template-columns:1fr}td,th{padding:9px;font-size:12px}.tarjetas{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>
