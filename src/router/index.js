import { createRouter, createWebHistory } from "vue-router";

import HomePage from "../page/HomePage.vue";
import LoginPage from "../page/LoginPage.vue";
import BarcodePage from "../page/BarcodePage.vue";
import ProductPage from "../page/ProductPage.vue";

const routes = [
  { path: "/", name: "home", component: HomePage },
  { path: "/product", name: "product", component: ProductPage },
  { path: "/login", name: "login", component: LoginPage },

  {
    path: "/barcode",
    name: "barcode",
    component: BarcodePage,
    meta: { requiresAuth: true }
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

// защита маршрутов
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem("token");

  if (to.meta.requiresAuth && !token) next("/login");
  else next();
});

export default router;
