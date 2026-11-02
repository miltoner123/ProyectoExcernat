<template>
  <main class="panel"><header><div><h1>Mi panel operativo</h1><p>Ubicaciones y funciones vigentes asignadas a tu usuario.</p></div><button @click="cargar" :disabled="cargando">Actualizar</button></header>
    <p v-if="error" class="error" role="alert">{{ error }}</p>
    <p v-if="cargando">Consultando asignaciones...</p>
    <p v-else-if="!ubicaciones.length" class="vacio">No tienes ubicaciones vigentes asignadas. Solicita a Administración que revise tu asignación y el vínculo de tu usuario con Personal.</p>
    <section v-for="u in ubicaciones" :key="u.id" class="tarjeta">
      <div class="fila"><div><h2>{{ u.nombre }}</h2><p>{{ u.evento || u.tipo }} · {{ u.funciones.join(', ') }}</p></div><span v-if="u.pendientes" class="pendientes">{{ u.pendientes }} recepción(es) pendiente(s)</span></div>
      <div class="acciones">
        <RouterLink v-if="permisos.recibir && u.encargado" to="/recepciones-transferencias">Recibir productos</RouterLink>
        <RouterLink v-if="permisos.vender" :to="'/ventas-stand?id_ubicacion=' + u.id">Registrar venta</RouterLink>
        <RouterLink v-if="permisos.ventas_ver" :to="'/ventas-stand?id_ubicacion=' + u.id">Historial de ventas</RouterLink>
        <RouterLink v-if="permisos.cajas" :to="'/cajas-punto-venta?id_ubicacion=' + u.id">Mi caja</RouterLink>
        <RouterLink v-if="permisos.inventarios" to="/inventarios">Inventario</RouterLink>
        <RouterLink v-if="permisos.movimientos" to="/movimientos">Movimientos</RouterLink>
      </div>
      <small v-if="permisos.recibir && !u.encargado">Las recepciones requieren función ENCARGADO vigente en esta ubicación.</small>
    </section>
  </main>
</template>
<script>
import api from '../../services/api';
export default {
  name:'MiPanelOperativo',
  data(){return {asignaciones:[],pendientes:{},permisos:{},cargando:false,error:''}},
  computed:{
    ubicaciones(){
      const mapa=new Map();
      for(const a of this.asignaciones){
        const u=a.ubicacion;if(!u)continue;
        const id=Number(u.id_ubicacion);
        if(!mapa.has(id))mapa.set(id,{id,nombre:u.nombre,tipo:u.tipo,evento:u.evento?.nombre,funciones:[],encargado:false,pendientes:Number(this.pendientes[id]||0)});
        const item=mapa.get(id),funcion=String(a.funcion_asignada||'').toUpperCase();
        if(!item.funciones.includes(funcion))item.funciones.push(funcion);
        if(funcion.includes('ENCARGAD'))item.encargado=true;
      }
      return [...mapa.values()];
    }
  },
  mounted(){this.cargar()},
  methods:{async cargar(){this.cargando=true;this.error='';try{const {data}=await api.get('/api/mi-panel-operativo');this.asignaciones=data.asignaciones||[];this.pendientes=data.recepciones_pendientes||{};this.permisos=data.permisos||{}}catch(e){this.error=e.response?.data?.message||'No fue posible consultar tus asignaciones.'}finally{this.cargando=false}}}
}
</script>
<style scoped>
.panel{max-width:1100px;margin:0 auto;padding:25px;color:#0c3025}.panel header,.fila{display:flex;justify-content:space-between;align-items:center;gap:15px;flex-wrap:wrap}.panel h1{font-size:27px;margin:0}.panel header p,.tarjeta p{color:#63756b;margin:5px 0 15px}.panel button{border:1px solid #bcd4c5;border-radius:8px;padding:10px 16px;background:white;color:#124734;cursor:pointer}.tarjeta,.vacio{background:white;border:1px solid #d9e6de;border-radius:14px;padding:20px;margin-top:16px;box-shadow:0 4px 14px #0c30250c}.tarjeta{border-left:5px solid #f47b20}.tarjeta h2{margin:0;font-size:20px}.acciones{display:flex;flex-wrap:wrap;gap:10px;margin-top:14px}.acciones a{background:#124734;color:white;text-decoration:none;border-radius:8px;padding:10px 14px;font-weight:650;font-size:13px}.acciones a:hover{background:#0c3025}.pendientes{background:#fff1e1;color:#995112;padding:7px 11px;border-radius:20px;font-weight:700}.error{padding:15px;background:#fff0f0;color:#9c2525}.tarjeta small{display:block;margin-top:12px;color:#687a6e}
</style>
