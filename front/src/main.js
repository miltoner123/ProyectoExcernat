import { createApp } from "vue";
//import './style.css';
import App from "./App.vue";
import Productosview from "./components/paginas/productos/Productosview.vue";

import { createWebHistory, createRouter } from "vue-router"
import HomeView from "./components/paginas/HomeView.vue";
import AboutView from "./components/paginas/AboutView.vue";

const routes = [
  { path: "/", component: HomeView },
  { path: "/about", component: AboutView },
  { path: "/productos", component: Productosview },
  { path: "/productos/crear", component: () => import("./components/paginas/productos/Productoscreate.vue") },
  {path: "/productos/editar/:id", component: () => import("./components/paginas/productos/ProductosEditar.vue")},
  { path: "/categorias", component: () => import("./components/paginas/categorias/CategoriasView.vue") }
  
];


const router = createRouter({
  history: createWebHistory(),
  routes,
})
createApp(App).use(router).mount("#app")