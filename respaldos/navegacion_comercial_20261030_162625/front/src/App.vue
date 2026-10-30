<script setup>
import { ref } from 'vue';
import AppIcon from './components/AppIcon.vue';
const menuVisible=ref(true);
const abiertos=ref({productos:true,inventario:true,eventos:true,personal:true});
import { useRouter } from 'vue-router';
import api from './services/api';
import { sesion, establecerUsuario } from './services/sesion';
const router = useRouter();
const saliendo = ref(false);
async function cerrarSesion() {
  saliendo.value = true;
  try {
    await api.post('/api/logout');
    establecerUsuario(null);
    await router.replace('/login');
  } catch (error) {
    alert('No se pudo cerrar la sesión. Inténtalo nuevamente.');
  } finally { saliendo.value = false; }
}
</script>

<template>
  <div id="app">
    <template v-if="sesion.usuario">
      <header class="topbar">
        <div class="logo"><button class="exc-toggle" type="button" @click="menuVisible=!menuVisible" aria-label="Mostrar u ocultar menú"><AppIcon name="menu"/></button><img src="/logo-excernat.jpg" alt="Logotipo de la empresa" class="exc-logo-img"/> EXCERNAT</div>
        <div class="topbar-user">
          {{ sesion.usuario.name }}
          <button class="btn-salir" :disabled="saliendo" @click="cerrarSesion">Cerrar sesión</button>
        </div>
      </header>
      <div class="app-container" :class="{'exc-menu-hidden':!menuVisible}">
        <aside v-if="menuVisible" class="sidebar">
          <div class="sidebar-title">MENÚ PRINCIPAL</div>
          <nav class="menu">
            <RouterLink to="/" class="menu-item"><AppIcon name="dashboard"/> Dashboard principal</RouterLink>
            <button class="menu-section exc-group" @click="abiertos.productos=!abiertos.productos">PRODUCTOS <span>{{abiertos.productos?'⌃':'⌄'}}</span></button><div v-show="abiertos.productos" class="exc-group-links">
            <RouterLink to="/productos" class="menu-item"><AppIcon name="package"/> Productos</RouterLink>
            <RouterLink to="/presentaciones" class="menu-item"><AppIcon name="layers"/> Presentaciones</RouterLink>
            <RouterLink to="/lotes" class="menu-item"><AppIcon name="tag"/> Lotes</RouterLink>
            </div><button class="menu-section exc-group" @click="abiertos.inventario=!abiertos.inventario">INVENTARIO <span>{{abiertos.inventario?'⌃':'⌄'}}</span></button><div v-show="abiertos.inventario" class="exc-group-links">
            <RouterLink to="/ubicaciones" class="menu-item"><AppIcon name="pin"/> Ubicaciones</RouterLink>
            <RouterLink to="/inventarios" class="menu-item"><AppIcon name="package"/> Inventarios</RouterLink>
            <RouterLink to="/movimientos" class="menu-item"><AppIcon name="arrows"/> Movimientos de Inventario</RouterLink>
            <RouterLink to="/pedidos" class="menu-item"><AppIcon name="clipboard"/> Pedidos de abastecimiento</RouterLink>
            </div><button class="menu-section exc-group" @click="abiertos.eventos=!abiertos.eventos">EVENTOS <span>{{abiertos.eventos?'⌃':'⌄'}}</span></button><div v-show="abiertos.eventos" class="exc-group-links">
            <RouterLink to="/eventos" class="menu-item"><AppIcon name="calendar"/> Eventos</RouterLink>
            </div><button class="menu-section exc-group" @click="abiertos.personal=!abiertos.personal">PERSONAL <span>{{abiertos.personal?'⌃':'⌄'}}</span></button><div v-show="abiertos.personal" class="exc-group-links">
            <RouterLink to="/personas" class="menu-item"><AppIcon name="users"/> Personas</RouterLink>
            <RouterLink to="/cargos" class="menu-item"><AppIcon name="briefcase"/> Cargos</RouterLink>
            <RouterLink to="/personal" class="menu-item"><AppIcon name="users"/> Personal</RouterLink>
            <RouterLink to="/asignaciones" class="menu-item"><AppIcon name="pin"/> Asignaciones</RouterLink>
            <RouterLink to="/usuarios" class="menu-item"> <AppIcon name="shield"/> Gestión de Usuarios
            </RouterLink>
          </div></nav>
        </aside>
        <div class="exc-content-area"><main class="main-content"><RouterView /></main><footer class="exc-footer">EXCERNAT · Excelentes Cereales Naturales © {{new Date().getFullYear()}} <span>Sistema de gestión empresarial</span></footer></div> 
      </div>
    </template> 
    <RouterView v-else /> 
  </div>
</template>

<style>

/* ================================
   CONFIGURACIÓN GENERAL
================================ */

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

html,
body,
#app {
  width: 100%;
  min-height: 100vh;
}

body {
  font-family: Arial, Helvetica, sans-serif;
  background-color: #f4f6f9;
  color: #333;
}


/* ================================
   BARRA SUPERIOR
================================ */

.topbar {
  height: 60px;
  background-color: #1f2937;

  display: flex;
  align-items: center;
  justify-content: space-between;

  padding: 0 25px;

  color: white;

  position: fixed;
  top: 0;
  left: 0;
  right: 0;

  z-index: 1000;

  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
}


/* Logo */

.logo {
  font-size: 21px;
  font-weight: bold;
  letter-spacing: 1px;
}


/* Usuario / información */

.topbar-user {
  font-size: 14px;
  color: #d1d5db;
}


/* ================================
   CONTENEDOR GENERAL
================================ */

.app-container {
  display: flex;
  min-height: 100vh;

  padding-top: 60px;
}


/* ================================
   SIDEBAR
================================ */

.sidebar {
  width: 240px;

  background-color: #111827;

  min-height: calc(100vh - 60px);

  position: fixed;

  left: 0;
  top: 60px;

  padding: 20px 12px;

  overflow-y: auto;
}


/* Título del menú */

.sidebar-title {
  color: #9ca3af;

  font-size: 11px;
  font-weight: bold;

  padding: 10px 14px;

  margin-bottom: 10px;

  letter-spacing: 1px;
}


/* ================================
   MENÚ
================================ */

.menu {
  display: flex;
  flex-direction: column;
}


/* Elementos del menú */

.menu-item {
  display: block;

  color: #d1d5db;

  text-decoration: none;

  padding: 11px 14px;

  margin-bottom: 4px;

  border-radius: 6px;

  font-size: 14px;

  transition: 0.2s;
}


/* Mouse encima */

.menu-item:hover {
  background-color: #374151;
  color: white;
}


/* Ruta seleccionada */

.menu-item.router-link-active {
  background-color: #2563eb;
  color: white;
}


/* Secciones */

.menu-section {
  color: #6b7280;

  font-size: 11px;

  font-weight: bold;

  margin-top: 20px;

  margin-bottom: 7px;

  padding-left: 14px;

  letter-spacing: 0.8px;
}


/* ================================
   CONTENIDO PRINCIPAL
================================ */

.main-content {

  margin-left: 240px;

  width: calc(100% - 240px);

  padding: 25px;

  min-height: calc(100vh - 60px);

}


/* ================================
   CLASES GENERALES
================================ */

.page-title {
  font-size: 25px;
  font-weight: 600;

  margin-bottom: 20px;

  color: #1f2937;
}


/* Contenedor tipo tarjeta */

.card {

  background-color: white;

  border-radius: 8px;

  padding: 20px;

  margin-bottom: 20px;

  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);

}


/* ================================
   TABLAS
================================ */

.table-container {
  width: 100%;

  overflow-x: auto;

  background-color: white;

  border-radius: 8px;

  padding: 15px;
}


table {
  width: 100%;

  border-collapse: collapse;
}


table th {

  background-color: #f3f4f6;

  color: #374151;

  text-align: left;

  padding: 12px;

  font-size: 14px;

}


table td {

  padding: 11px 12px;

  border-bottom: 1px solid #e5e7eb;

  font-size: 14px;

}


table tbody tr:hover {

  background-color: #f9fafb;

}


/* ================================
   BOTONES
================================ */

.btn {

  border: none;

  padding: 9px 14px;

  border-radius: 5px;

  cursor: pointer;

  font-size: 14px;

  text-decoration: none;

  display: inline-block;

}


.btn-primary {

  background-color: #2563eb;

  color: white;

}


.btn-primary:hover {

  background-color: #1d4ed8;

}


.btn-success {

  background-color: #16a34a;

  color: white;

}


.btn-danger {

  background-color: #dc2626;

  color: white;

}


.btn-warning {

  background-color: #f59e0b;

  color: white;

}


/* ================================
   FORMULARIOS
================================ */

.form-group {

  margin-bottom: 15px;

}


.form-group label {

  display: block;

  margin-bottom: 6px;

  font-size: 14px;

  font-weight: 500;

}


.form-control {

  width: 100%;

  padding: 10px;

  border: 1px solid #d1d5db;

  border-radius: 5px;

  font-size: 14px;

  outline: none;

}


.form-control:focus {

  border-color: #2563eb;

}


/* ================================
   RESPONSIVE
================================ */

@media (max-width: 768px) {

  .sidebar {

    width: 70px;

  }


  .sidebar-title,
  .menu-section {

    display: none;

  }


  .menu-item {

    text-align: center;

    font-size: 0;

  }


  .menu-item::first-letter {

    font-size: 20px;

  }


  .main-content {

    margin-left: 70px;

    width: calc(100% - 70px);

    padding: 15px;

  }


  .topbar-user {

    display: none;

  }

}


.btn-salir { margin-left: 16px; background: #ef4444; color: white; border: 0; border-radius: 6px; padding: 8px 12px; cursor: pointer; }
.btn-salir:disabled { opacity: .6; cursor: wait; }

/* EXCERNAT: menú plegable, iconos y pie institucional */
.topbar{background:#0b3025}.logo{display:flex;align-items:center;gap:10px;color:white}.exc-logo-img{width:40px;height:40px;border-radius:50%;object-fit:cover;border:2px solid #ffffff80}.exc-toggle{background:#ffffff20;border:1px solid #ffffff40;color:white;border-radius:8px;padding:7px;display:inline-flex;cursor:pointer}
.sidebar{background:#0b3025;overflow-y:auto;max-height:calc(100vh - 60px);scrollbar-width:thin;scrollbar-color:#67947e #0b3025}.menu-item{display:flex;align-items:center;gap:10px;color:#e6f2eb}.menu-item svg{flex-shrink:0}.menu-item.router-link-active{background:#f47b20}.exc-group{display:flex;justify-content:space-between;width:100%;background:transparent;border:0;text-align:left;cursor:pointer;color:#b5d7c4;padding:12px 14px}.exc-group-links{display:flex;flex-direction:column}.exc-content-area{flex:1;min-width:0;display:flex;flex-direction:column;margin-left:240px}.exc-content-area .main-content{margin-left:0;width:100%;flex:1}.exc-menu-hidden .exc-content-area{margin-left:0}.exc-footer{background:white;border-top:1px solid #d8e5dc;color:#587567;display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:16px 25px;font-size:12px}.exc-menu-hidden .main-content{width:100%}
@media(max-width:750px){.sidebar{z-index:900;box-shadow:4px 0 20px #0003}.exc-content-area{margin-left:0}.main-content{padding:14px}.exc-footer{padding:12px}}
</style>