import { createRouter, createWebHistory } from "vue-router";

import HomePage from "../components/HomePage.vue";
import LoginPage from "../components/LoginPage.vue";
import BarcodePage from "../components/BarcodePage.vue";

const routes = [
  { path: "/", name: "home", component: HomePage },

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
