import { createApp } from "vue";
//import './style.css';
import App from "./App.vue";
import api from "./services/api";
import { sesion, establecerUsuario } from "./services/sesion";
import Login from './components/auth/Login.vue';
 


import { createWebHistory, createRouter } from "vue-router"
import HomeView from "./components/HomeView.vue";
import AboutView from "./components/AboutView.vue";

const routes = [
  { path: '/login', name: 'Login', component: Login, meta: { publica: true } },
  { path: '/usuarios', name: 'Usuarios', component: () => import('./components/seguridad/UsuariosView.vue')},
  { path: "/", component: HomeView },
  { path: "/about", component: AboutView },
  { path: "/productos", component: () => import("./components/paginas/productos/ProductosView.vue") },
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
  { path: "/asignaciones", component: () => import("./components/personal/asignaciones/AsignacionesView.vue") },
  { path: "/asignaciones/crear", component: () => import("./components/personal/asignaciones/AsignacionesCreate.vue") },
   ] 

const router = createRouter({
  history: createWebHistory(),
  routes,
})
router.beforeEach(async (to) => {
  if (!sesion.comprobada) {
    try {
      const { data } = await api.get('/api/usuario');
      establecerUsuario(data);
    } catch {
      establecerUsuario(null);
    }
    sesion.comprobada = true;
  }
  if (to.meta.publica) return sesion.usuario ? '/' : true;
  if (!sesion.usuario) return { path: '/login', query: { redirect: to.fullPath } };
  return true;
});

createApp(App).use(router).mount('#app');