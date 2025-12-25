import { createRouter, createWebHistory } from "vue-router";

// ✅ ВСЕ страницы — lazy
const HomePage = () => import("../page/HomePage.vue");
const LoginPage = () => import("../page/LoginPage.vue");
const BarcodePage = () => import("../page/BarcodePage.vue");

// ⚠️ этот ProductPage у тебя был /product (v1) — оставляю, но тоже lazy
const ProductPage = () => import("../page/ProductPage.vue");

const CatalogV2 = () => import("../page/CatalogV2.vue");

const routes = [
  { path: "/", name: "home", component: HomePage },

  // v1 страница (если реально нужна)
  { path: "/product", name: "product_v1", component: ProductPage },

  { path: "/login", name: "login", component: LoginPage },

  { path: "/catalogv2", name: "catalog", component: CatalogV2 },

  {
    path: "/barcode",
    name: "barcode",
    component: BarcodePage,
    meta: { requiresAuth: true },
  },

  // ✅ admin уже lazy — оставляем
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

  // ✅ карточка товара (v2) — уже lazy, ок
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

  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) return savedPosition;
    return { top: 0 };
  },
});

// защита маршрутов
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem("token");
  if (to.meta.requiresAuth && !token) next("/login");
  else next();
});

export default router;
 