<template>
  <div class="inicio-excernat">
    <div class="inicio-banner"><img class="exc-home-logo" src="/excernat-logo.webp" alt="Logotipo de Excelentes Cereales Naturales" /><div class="eyebrow">PANEL DE CONTROL</div><h1>Bienvenido a EXCERNAT</h1><p>Acceso centralizado a los módulos de gestión de la empresa.</p></div>
    <h2>Accesos rápidos</h2>
    <div class="inicio-grid">
      <RouterLink v-for="item in accesos" :key="item.to" :to="item.to" class="inicio-tarjeta"><span class="inicio-icono">{{ item.icono }}</span><strong>{{ item.titulo }}</strong><small>{{ item.descripcion }}</small><span class="inicio-enlace">Abrir módulo →</span></RouterLink>
    </div>
    <p class="inicio-nota">Los indicadores estadísticos se incorporarán cuando estén definidos sus cálculos y permisos en la API.</p>
  </div>
</template>
<script setup>
const accesos = [
  {to:'/productos',icono:'📦',titulo:'Productos',descripcion:'Catálogo y presentaciones'},
  {to:'/inventarios',icono:'▤',titulo:'Inventarios',descripcion:'Existencias por ubicación'},
  {to:'/movimientos',icono:'↔',titulo:'Movimientos',descripcion:'Trazabilidad del inventario'},
  {to:'/eventos',icono:'▦',titulo:'Eventos',descripcion:'Ferias y stands'},
  {to:'/personal',icono:'♙',titulo:'Personal',descripcion:'Gestión de trabajadores'},
  {to:'/asignaciones',icono:'✓',titulo:'Asignaciones',descripcion:'Destinos y funciones del personal'},
];
</script>
<style scoped>
.inicio-excernat{max-width:1450px}.inicio-banner{padding:35px;border-radius:15px;background:linear-gradient(120deg,#0b3025,#21634b);color:white;margin-bottom:27px}.inicio-banner h1{color:white!important;font-size:30px;margin:9px 0}.inicio-banner p{color:#d7ece0}.eyebrow{font-size:11px;letter-spacing:2px;color:#ffa358;font-weight:800}.inicio-excernat h2{margin:0 0 16px}.inicio-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:17px}.inicio-tarjeta{display:flex;flex-direction:column;gap:9px;padding:23px;background:white;border:1px solid #dde8e0;border-radius:13px;text-decoration:none;color:#124734;box-shadow:0 5px 20px #0c302512}.inicio-tarjeta:hover{border-color:#f47b20}.inicio-icono{display:grid;place-items:center;background:#e8f3ed;border-radius:11px;width:45px;height:45px;font-size:23px}.inicio-tarjeta strong{font-size:17px}.inicio-tarjeta small{color:#6c7e73}.inicio-enlace{margin-top:9px;color:#dc6510;font-weight:750;font-size:12px}.inicio-nota{margin-top:25px;color:#6c7e73;font-size:12px}@media(max-width:900px){.inicio-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:550px){.inicio-grid{grid-template-columns:1fr}.inicio-banner{padding:23px}}
</style>
