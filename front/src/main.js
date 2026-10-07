import { createApp } from "vue";
//import './style.css';
import App from "./App.vue";
import Productosview from "./components/paginas/productos/Productosview.vue";
import CategoriasView from "./components/paginas/categorias/CategoriasView.vue";    
import Productoscreate from "./components/paginas/productos/Productoscreate.vue";
import ProductosEditar from "./components/paginas/productos/ProductosEditar.vue";
import PresentacionesView from "./components/paginas/presentaciones/PresentacionesView.vue";
import PresentacionesCreate from "./components/paginas/presentaciones/PresentacionesCreate.vue";
import PresentacionesEditar from "./components/paginas/presentaciones/PresentacionesEditar.vue";
import LotesView from './components/paginas/lotes/LotesView.vue';
import LotesCreate from './components/paginas/lotes/LotesCreate.vue';
import LotesEditar from './components/paginas/lotes/LotesEditar.vue';
import UbicacionesView from './components/inventario/ubicaciones/UbicacionesView.vue';
import UbicacionesCreate from './components/inventario/ubicaciones/UbicacionesCreate.vue';
import UbicacionesEditar from './components/inventario/ubicaciones/UbicacionesEditar.vue';
import InventariosCreate from "./components/inventario/inventarios/InventariosCreate.vue";
import InventariosEditar from "./components/inventario/inventarios/InventariosEditar.vue";
import InventariosView from "./components/inventario/inventarios/InventariosView.vue";  
import MovimientosView from './components/inventario/movimientos/MovimientosView.vue';
import MovimientosCreate from './components/inventario/movimientos/MovimientosCreate.vue';
import MovimientosDetalle from './components/inventario/movimientos/MovimientosDetalle.vue';  


import { createWebHistory, createRouter } from "vue-router"
import HomeView from "./components/HomeView.vue";
import AboutView from "./components/AboutView.vue";

const routes = [
  { path: "/", component: HomeView },
  { path: "/about", component: AboutView },
  { path: "/productos", component: Productosview },
  { path: "/productos/crear", component: () => import("./components/paginas/productos/Productoscreate.vue") },
  { path: "/productos/editar/:id", component: () => import("./components/paginas/productos/ProductosEditar.vue")},
  { path: "/categorias", component: () => import("./components/paginas/categorias/CategoriasView.vue") },
  { path: "/presentaciones", component: () => import("./components/paginas/presentaciones/PresentacionesView.vue") },
  { path: "/presentaciones/crear", component: () => import("./components/paginas/presentaciones/PresentacionesCreate.vue") },
  { path: "/presentaciones/editar/:id", component: () => import("./components/paginas/presentaciones/PresentacionesEditar.vue") },
  { path: "/lotes", component: () => import("./components/paginas/lotes/LotesView.vue") },
  { path: "/lotes/crear", component: () => import("./components/paginas/lotes/LotesCreate.vue") },
  { path: "/lotes/editar/:id", component: () => import("./components/paginas/lotes/LotesEditar.vue") },
  { path: "/ubicaciones", component: () => import("./components/inventario/ubicaciones/UbicacionesView.vue")},
  { path: "/ubicaciones/editar/:id", component: () => import("./components/inventario/ubicaciones/UbicacionesEditar.vue")},
  { path: "/ubicaciones/crear", component: () => import("./components/inventario/ubicaciones/UbicacionesCreate.vue")},
  { path: "/inventarios", component: () => import("./components/inventario/inventarios/InventariosView.vue")},
  { path: "/inventarios/crear", component: () => import("./components/inventario/inventarios/InventariosCreate.vue")},
  { path: "/inventarios/editar/:id", component: () => import("./components/inventario/inventarios/InventariosEditar.vue")},
  { path: "/movimientos", component: () => import("./components/inventario/movimientos/MovimientosView.vue")},
  { path: "/movimientos/crear", component: () => import("./components/inventario/movimientos/MovimientosCreate.vue")},
  { path: "/movimientos/detalle/:id",component: () => import("./components/inventario/movimientos/MovimientosDetalle.vue")},
  { path: "/eventos", component: () => import("./components/eventos/EventosView.vue") },
  { path: "/eventos/crear", component: () => import("./components/eventos/EventosCreate.vue") },
  { path: "/eventos/editar/:id", component: () => import("./components/eventos/EventosEditar.vue") },
  { path: "/eventos/:id/stands", component: () => import("./components/eventos/StandsView.vue") },

  { path: "/personas", component: () => import("./components/personal/personas/PersonasView.vue") },
  { path: "/personas/crear", component: () => import("./components/personal/personas/PersonasCreate.vue") },

  { path: "/cargos", component: () => import("./components/personal/cargos/CargosView.vue") },
  { path: "/cargos/crear", component: () => import("./components/personal/cargos/CargosCreate.vue") },

  { path: "/personal", component: () => import("./components/personal/empleados/PersonalView.vue") },
  { path: "/personal/crear", component: () => import("./components/personal/empleados/PersonalCreate.vue") },

   ] 

const router = createRouter({
  history: createWebHistory(),
  routes,
})
createApp(App).use(router).mount("#app")