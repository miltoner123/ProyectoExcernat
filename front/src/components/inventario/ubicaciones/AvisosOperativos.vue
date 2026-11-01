<template>
  <section class="avisos-excernat" v-if="avisos.length">
    <h3>🔔 Pendientes de habilitación logística ({{ avisos.length }})</h3>
    <p v-for="aviso in avisos" :key="aviso.id">{{ aviso.detalle }} — {{ aviso.titulo }}</p>
    <RouterLink to="/ubicaciones">Revisar ubicaciones</RouterLink>
  </section>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import api from '../../../services/api';
const avisos=ref([]);
onMounted(async()=>{try {const {data}=await api.get('http://localhost:8000/api/avisos-operativos');avisos.value=data.avisos||[];}catch(e){avisos.value=[];}});
</script>
<style scoped>
.avisos-excernat{background:#edf5ee;border-left:5px solid #f47b20;border-radius:10px;padding:14px;margin:12px 0;color:#113b2a}
.avisos-excernat h3{margin-bottom:8px}.avisos-excernat p{margin:5px 0}
</style>
