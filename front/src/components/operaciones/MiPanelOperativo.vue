<template>
  <main class="panel"><header><div><h1>Mi panel operativo</h1><p>Ubicaciones y funciones vigentes asignadas a tu usuario.</p></div><button @click="cargar" :disabled="cargando">Actualizar</button></header>
    <p v-if="error" class="error" role="alert">{{ error }}</p>
    <p v-if="cargando">Consultando asignaciones...</p>
    <p v-else-if="!ubicaciones.length" class="vacio">No tienes ubicaciones vigentes asignadas. Solicita a Administración que revise tu asignación y el vínculo de tu usuario con Personal.</p>
    <nav class="pestanas" aria-label="Secciones operativas">
      <button type="button" :class="{seleccionada:pestana==='operaciones'}" @click="pestana='operaciones'">Operaciones</button>
      <button type="button" :class="{seleccionada:pestana==='ventas'}" @click="pestana='ventas'">Ventas y caja</button>
      <button type="button" :class="{seleccionada:pestana==='evento'}" @click="pestana='evento'">Evento</button>
    </nav>
    <section v-for="u in ubicaciones" :key="u.id" class="tarjeta">
      <div class="fila"><div><h2>{{ u.nombre }}</h2><p>{{ u.evento || u.tipo }} · {{ u.funciones.join(', ') }}</p></div><span v-if="u.pendientes" class="pendientes">{{ u.pendientes }} recepción(es) pendiente(s)</span></div>
      <div v-if="pestana==='evento'" class="acciones acciones-evento">
        <RouterLink v-if="u.encargado && u.evento" to="/eventos">Ver evento y stands</RouterLink>
        <button v-if="u.encargado && u.id_evento" class="accion-peligrosa" :disabled="finalizando" @click="finalizarEvento(u)">Finalizar evento</button>
      </div>
      <div v-if="pestana==='operaciones'" class="acciones">
        <RouterLink v-if="u.encargado" to="/recepciones-transferencias">Recibir productos</RouterLink>
        <RouterLink v-if="permisos.inventarios" :to="'/mi-panel-operativo?id_ubicacion=' + u.id" @click.prevent="cargarInventario(u.id)">Ver inventario</RouterLink>
        <RouterLink v-if="permisos.movimientos" to="/movimientos">Movimientos</RouterLink>
      </div>
      <div v-if="pestana==='ventas'" class="acciones">
        <RouterLink v-if="u.encargado && permisos.vender" :to="'/ventas-stand?id_ubicacion=' + u.id">Registrar venta</RouterLink>
        <RouterLink v-if="u.encargado && permisos.ventas_ver" :to="'/ventas-stand?id_ubicacion=' + u.id">Historial de ventas</RouterLink>
        <RouterLink v-if="u.encargado" :to="'/cajas-punto-venta?id_ubicacion=' + u.id">Mi caja</RouterLink>
      </div>
      <section v-if="pestana==='operaciones' && inventarioAbierto===u.id" class="stock-panel">
        <h3>Existencias disponibles · {{u.nombre}}</h3>
        <p v-if="cargandoStock">Consultando stock...</p>
        <p v-else-if="!existencias.length">No hay unidades disponibles; una recepción pendiente aún no suma stock.</p>
        <div v-else class="stock-tabla"><table><thead><tr><th>Producto / lote</th><th>Disponible</th><th>Devolver</th></tr></thead><tbody>
          <tr v-for="item in existencias" :key="item.id_lote"><td>{{item.lote?.presentacion?.producto?.nombre}} · {{item.lote?.codigo_lote}}</td><td>{{item.stock_actual}}</td><td><input v-if="u.tipo==='STAND'" type="number" min="0" :max="item.stock_actual" v-model.number="cantidades[item.id_lote]" /></td></tr>
        </tbody></table></div>
        <div v-if="u.tipo==='STAND' && existencias.length" class="retorno"><label>Observación de devolución (obligatoria) <input v-model="observacion" maxlength="1000" placeholder="Motivo y estado de los productos" /></label><button :disabled="devolviendo" @click="devolver(u.id)">{{devolviendo?'Registrando...':'Devolver sobrantes al Almacén Central'}}</button><p>La devolución queda en tránsito hasta que el almacén confirme físicamente la recepción.</p></div>
      </section>
      <small v-if="!u.encargado">Las recepciones requieren función ENCARGADO vigente en esta ubicación.</small>
    </section>
  </main>
</template>
<script>
import api from '../../services/api';
export default {
  name:'MiPanelOperativo',
  data(){return {pestana:'operaciones',asignaciones:[],pendientes:{},permisos:{},cargando:false,error:'',inventarioAbierto:null,existencias:[],cantidades:{},observacion:'',cargandoStock:false,devolviendo:false,finalizando:false}},
  computed:{
    ubicaciones(){
      const mapa=new Map();
      for(const a of this.asignaciones){
        const u=a.ubicacion;if(!u)continue;
        const id=Number(u.id_ubicacion);
        if(!mapa.has(id))mapa.set(id,{id,nombre:u.nombre,tipo:u.tipo,evento:u.evento?.nombre,id_evento:Number(u.id_evento||u.evento?.id_evento||0),funciones:[],encargado:false,pendientes:Number(this.pendientes[id]||0)});
        const item=mapa.get(id),funcion=String(a.funcion_asignada||'').toUpperCase();
        if(!item.funciones.includes(funcion))item.funciones.push(funcion);
        if(funcion.includes('ENCARGAD'))item.encargado=true;
      }
      return [...mapa.values()];
    }
  },
  mounted(){this.cargar()},
  methods:{
    async finalizarEvento(u){
      if(!u.id_evento)return;
      this.error='';this.finalizando=true;
      try{
        const {data}=await api.get(`/api/eventos/${u.id_evento}/pendientes`);
        const p=data.pendientes||{};
        if(Object.values(p).some(v=>Number(v)>0)){
          this.error=`Para finalizar ${u.evento} debes devolver y conciliar: stock ${p.stock_sobrante||0}, transferencias ${p.transferencias_pendientes||0}, diferencias ${p.recepciones_observadas||0}, cajas abiertas ${p.cajas_abiertas||0}.`;
          return;
        }
        if(!window.confirm(`¿Finalizar manualmente ${u.evento}? Esta acción bloquea nuevas ventas y requiere que todos los stands estén conciliados.`))return;
        await api.post(`/api/eventos/${u.id_evento}/finalizar`);
        alert('Evento finalizado manualmente. El cierre administrativo corresponde al responsable comercial.');
        await this.cargar();
      }catch(e){this.error=Object.values(e.response?.data?.errors||{}).flat().join(' ')||e.response?.data?.message||'No se pudo finalizar el evento.'}
      finally{this.finalizando=false}
    },
    async cargarInventario(id){this.inventarioAbierto=id;this.cargandoStock=true;this.error='';this.cantidades={};try{const {data}=await api.get('/api/mi-inventario-operativo',{params:{id_ubicacion:id}});this.existencias=data}catch(e){this.error=e.response?.data?.message||'No se pudo consultar inventario';this.existencias=[]}finally{this.cargandoStock=false}},
    async devolver(id){const filas=this.existencias.filter(x=>Number(this.cantidades[x.id_lote])>0);if(!filas.length){this.error='Indique una cantidad a devolver.';return}if(!this.observacion.trim()||this.observacion.trim().length<5){this.error='Justifique la devolución (mínimo cinco caracteres).';return}if(filas.some(x=>!Number.isInteger(Number(this.cantidades[x.id_lote]))||this.cantidades[x.id_lote]>x.stock_actual)){this.error='Cantidad inválida o superior a las existencias.';return}if(!confirm('¿Confirmar devolución al Almacén Central? Quedará pendiente de recepción.'))return;this.devolviendo=true;this.error='';try{for(const fila of filas){await api.post('/api/mi-stand/devolucion',{id_ubicacion_origen:id,id_lote:fila.id_lote,cantidad:Number(this.cantidades[fila.id_lote]),observacion:this.observacion.trim()})}this.observacion='';await this.cargarInventario(id);alert('Devolución registrada en tránsito; el almacén debe confirmar la recepción.')}catch(e){this.error=e.response?.data?.message||'No se pudo registrar toda la devolución. Revise movimientos antes de repetir.';await this.cargarInventario(id)}finally{this.devolviendo=false}},
    async cargar(){this.cargando=true;this.error='';try{const {data}=await api.get('/api/mi-panel-operativo');this.asignaciones=data.asignaciones||[];this.pendientes=data.recepciones_pendientes||{};this.permisos=data.permisos||{}}catch(e){this.error=e.response?.data?.message||'No fue posible consultar tus asignaciones.'}finally{this.cargando=false}}}
}
</script>
<style scoped>
.panel{max-width:1100px;margin:0 auto;padding:25px;color:#0c3025}.panel header,.fila{display:flex;justify-content:space-between;align-items:center;gap:15px;flex-wrap:wrap}.panel h1{font-size:27px;margin:0}.panel header p,.tarjeta p{color:#63756b;margin:5px 0 15px}.panel button{border:1px solid #bcd4c5;border-radius:8px;padding:10px 16px;background:white;color:#124734;cursor:pointer}.tarjeta,.vacio{background:white;border:1px solid #d9e6de;border-radius:14px;padding:20px;margin-top:16px;box-shadow:0 4px 14px #0c30250c}.tarjeta{border-left:5px solid #f47b20}.tarjeta h2{margin:0;font-size:20px}.acciones{display:flex;flex-wrap:wrap;gap:10px;margin-top:14px}.acciones a{background:#124734;color:white;text-decoration:none;border-radius:8px;padding:10px 14px;font-weight:650;font-size:13px}.acciones a:hover{background:#0c3025}.pendientes{background:#fff1e1;color:#995112;padding:7px 11px;border-radius:20px;font-weight:700}.error{padding:15px;background:#fff0f0;color:#9c2525}.tarjeta small{display:block;margin-top:12px;color:#687a6e}
.pestanas{display:flex;gap:8px;flex-wrap:wrap;margin-top:18px;border-bottom:1px solid #dbe6de;padding-bottom:12px}
.pestanas button.seleccionada{background:#103d2e;color:white;border-color:#103d2e}
.acciones-evento{background:#fff9f2;border-radius:10px;padding:14px}
.acciones-evento .accion-peligrosa{border-color:#bf4b35;color:#9d301c}
.stock-panel{margin-top:18px;padding:16px;background:#f4f8f5;border-radius:10px}.stock-tabla{overflow:auto}.stock-tabla table{width:100%;border-collapse:collapse}.stock-tabla td,.stock-tabla th{padding:9px;border-bottom:1px solid #d8e5dc;text-align:left}.stock-tabla input{width:85px;padding:8px}.retorno{margin-top:16px}.retorno label{display:block;margin-bottom:12px}.retorno input{padding:9px;border:1px solid #aebfb6;border-radius:8px;min-width:220px}.retorno p{font-size:12px;margin-top:10px}
</style>
