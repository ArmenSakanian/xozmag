import { createRouter, createWebHistory } from "vue-router";

import HomePage from "../page/HomePage.vue";
import LoginPage from "../page/LoginPage.vue";
import BarcodePage from "../page/BarcodePage.vue";
import ProductPage from "../page/ProductPage.vue";
import catalog from "../page/CatalogV2.vue";

const routes = [
  { path: "/", name: "home", component: HomePage },
  { path: "/product", name: "product", component: ProductPage },
  { path: "/login", name: "login", component: LoginPage },
  { path: "/catalogv2", name: "catalog", component: catalog },
  { path: "/barcode", name: "barcode", component: BarcodePage, meta: { requiresAuth: true } },
  {
  path: "/admin",
  component: () => import("@/admin/AdminPanel.vue"),
},
{
  path: "/admin/categories",
  component: () => import("@/admin/AdminCategoriesPage.vue"),
},
{
  path: "/admin/products",
  component: () => import("@/admin/AdminProductsPage.vue"),
},
{
  path: "/admin/attributes",
  component: () => import("@/admin/AdminAttributes.vue"),
},
{
  path: "/admin/functions",
  component: () => import("@/admin/AdminFunctions.vue"),
},
{
  path: "/admin/order",
  component: () => import("@/admin/AdminOrder.vue"),
},
{
  path: "/admin/barcode",
  component: () => import("@/admin/BarcodeLabelSizesPage.vue"),
},
{
  path: "/product/:id",
  name: "product",
  component: () => import("@/page/ProductCartPage.vue"),
  props: true,
},

];

const router = createRouter({
  history: createWebHistory(),
  routes,

  // ✅ вот тут
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) return savedPosition; // назад/вперёд
    return { top: 0 }; // обычный переход
  },
});

// защита маршрутов
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem("token");

  if (to.meta.requiresAuth && !token) next("/login");
  else next();
});

export default router;
