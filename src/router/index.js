import { createRouter, createWebHistory } from "vue-router";

/* ✅ Главную страницу — НЕ lazy */
import HomePage from "../page/HomePage.vue";

/* Остальные — lazy */
const LoginPage   = () => import("../page/LoginPage.vue");
const BarcodePage = () => import("../page/BarcodePage.vue");
const ProductPage = () => import("../page/ProductPage.vue");
const CatalogV2   = () => import("../page/CatalogV2.vue");

const routes = [
  { path: "/", name: "home", component: HomePage },

  { path: "/product", name: "product_v1", component: ProductPage },

  { path: "/login", name: "login", component: LoginPage },

  { path: "/catalogv2", name: "catalog", component: CatalogV2 },

  {
    path: "/barcode",
    name: "barcode",
    component: BarcodePage,
    meta: { requiresAuth: true },
  },

  /* admin */
  { path: "/admin", component: () => import("@/admin/AdminPanel.vue") },
  { path: "/admin/barcode", component: () => import("@/admin/BarcodeLabelSizesPage.vue") },
  { path: "/admin/categories", component: () => import("@/admin/AdminCategoriesPage.vue") },
  { path: "/admin/products", component: () => import("@/admin/AdminProductsPage.vue") },
  { path: "/admin/attributes", component: () => import("@/admin/AdminAttributes.vue") },
  { path: "/admin/functions", component: () => import("@/admin/AdminFunctions.vue") },
  { path: "/admin/order", component: () => import("@/admin/AdminOrder.vue") },

  /* карточка товара v2 */
  {
    path: "/product/:id",
    name: "product",
    component: () => import("@/page/ProductCartPage.vue"),
    props: true,
  },
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) return savedPosition;
    return { top: 0 };
  },
});

/** ===== auth cache ===== */
const AUTH_CACHE_MS = 30_000;
let meCache = { t: 0, user: null, pending: null };

async function fetchMe() {
  const res = await fetch("/api/auth/me.php", {
    method: "GET",
    credentials: "same-origin",
    headers: { "Accept": "application/json" },
  }).catch(() => null);

  if (!res) return null;

  const data = await res.json().catch(() => null);
  if (data?.status === "success" && data?.user) return data.user;
  return null;
}

async function getMe(force = false) {
  const now = Date.now();
  if (!force && meCache.user && now - meCache.t < AUTH_CACHE_MS) return meCache.user;
  if (!force && meCache.pending) return await meCache.pending;

  meCache.pending = fetchMe()
    .then((u) => {
      meCache.user = u;
      meCache.t = Date.now();
      return u;
    })
    .finally(() => {
      meCache.pending = null;
    });

  return await meCache.pending;
}

/** ===== route guard (реальная проверка сервером) ===== */
router.beforeEach(async (to) => {
  const isAdminPath = to.path.startsWith("/admin");
  const needsAuth = Boolean(to.meta.requiresAuth) || isAdminPath;

  // Если пользователь уже залогинен и лезет на /login — отправим дальше
  if (to.path === "/login") {
    const me = await getMe();
    if (me) {
      const redirect = to.query.redirect;
      if (typeof redirect === "string" && redirect.startsWith("/")) return redirect;
      return me.role === "admin" ? "/admin" : "/barcode";
    }
    return true;
  }

  if (!needsAuth) return true;

  const me = await getMe();
  if (!me) {
    return { path: "/login", query: { redirect: to.fullPath } };
  }

  if (isAdminPath && me.role !== "admin") {
    // не админ — в админку нельзя
    return "/";
  }

  return true;
});

export default router;
