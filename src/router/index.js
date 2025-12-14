import { createRouter, createWebHistory } from "vue-router";

import HomePage from "../page/HomePage.vue";
import LoginPage from "../page/LoginPage.vue";
import BarcodePage from "../page/BarcodePage.vue";
import ProductPage from "../page/ProductPage.vue";
import ButtonExport from "../page/ButtonExport.vue";
import catalog from "../page/CatalogV2.vue";

const routes = [
  { path: "/", name: "home", component: HomePage },
  { path: "/product", name: "product", component: ProductPage },
  { path: "/login", name: "login", component: LoginPage },
  { path: "/ButtonExport", name: "ButtonExport", component: ButtonExport },
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
  path: "/admin/addproducts",
  component: () => import("@/admin/AdminAddProductsPage.vue"),
},
{
  path: "/admin/attributes",
  component: () => import("@/admin/AdminAttributes.vue"),
},

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
