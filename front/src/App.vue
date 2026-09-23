<template>
  <div id="app">

    <!-- Barra superior -->
    <header class="topbar">
      <div class="logo">
        EXCERNAT
      </div>

      <div class="topbar-user">
        Sistema de Gestión Administrativa
      </div>
    </header>

    <!-- Contenedor general -->
    <div class="app-container">

      <!-- Menú lateral -->
      <aside class="sidebar">

        <div class="sidebar-title">
          MENÚ PRINCIPAL
        </div>

        <nav class="menu">

          <RouterLink to="/" class="menu-item">
            🏠 Dashboard principal
          </RouterLink>

          <div class="menu-section">
            PRODUCTOS
          </div>

          <RouterLink to="/categorias" class="menu-item">
            📁 Categorías
          </RouterLink>

          <RouterLink to="/productos" class="menu-item">
            📦 Productos
          </RouterLink>

        </nav>

      </aside>

      <!-- Contenido -->
      <main class="main-content">
        <RouterView />
      </main>

    </div>

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

</style>