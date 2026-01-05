import { createRouter, createWebHistory } from "vue-router";

/* ✅ Главную страницу - НЕ lazy */
import HomePage from "../page/HomePage.vue";

/* Остальные - lazy */
const LoginPage = () => import("../page/LoginPage.vue");
const BarcodePage = () => import("../page/BarcodePage.vue");

const CatalogPage = () => import("../page/catalog.vue");
const AboutusPage = () => import("../page/aboutus.vue");
const ContactPage = () => import("../page/contact.vue");

/* ✅ SEO карточка товара (то, что в sitemap: /product/:slug) */
const ProductCartPage = () => import("@/page/ProductCartPage.vue");

/* admin */
const AdminPanel = () => import("@/admin/AdminPanel.vue");
const AdminBarcodeLabelSizesPage = () => import("@/admin/BarcodeLabelSizesPage.vue");
const AdminCategoriesPage = () => import("@/admin/AdminCategoriesPage.vue");
const AdminProductsPage = () => import("@/admin/AdminProductsPage.vue");
const AdminAttributes = () => import("@/admin/AdminAttributes.vue");
const AdminFunctions = () => import("@/admin/AdminFunctions.vue");
const AdminOrder = () => import("@/admin/AdminOrder.vue");
const AdminPhotoGallery = () => import("@/admin/AdminPhotoGallery.vue");
const NotFoundPage = () => import("../page/NotFoundPage.vue");

const routes = [
  { path: "/", name: "home", component: HomePage },

  // ✅ Старый путь /product не используем: чтобы не было дублей - редиректим в каталог
  { path: "/product", redirect: "/catalog" },

  { path: "/catalog", name: "catalog", component: CatalogPage },
  { path: "/aboutus", name: "aboutus", component: AboutusPage },
  { path: "/contact", name: "contact", component: ContactPage },

  // ✅ SEO карточка товара (именно этот URL у тебя в sitemap)
  {
    path: "/product/:slug",
    name: "product",
    component: ProductCartPage,
    props: true,
  },

  // тех.страницы
  { path: "/login", name: "login", component: LoginPage, meta: { noindex: true } },

  {
    path: "/barcode",
    name: "barcode",
    component: BarcodePage,
    meta: { requiresAuth: true, noindex: true },
  },

  // admin (тех.раздел)
  { path: "/admin", component: AdminPanel, meta: { noindex: true } },
  { path: "/admin/barcode", component: AdminBarcodeLabelSizesPage, meta: { noindex: true } },
  { path: "/admin/categories", component: AdminCategoriesPage, meta: { noindex: true } },
  { path: "/admin/products", component: AdminProductsPage, meta: { noindex: true } },
  { path: "/admin/attributes", component: AdminAttributes, meta: { noindex: true } },
  { path: "/admin/functions", component: AdminFunctions, meta: { noindex: true } },
  { path: "/admin/order", component: AdminOrder, meta: { noindex: true } },
  { path: "/admin/photogallery", component: AdminPhotoGallery, meta: { noindex: true } },

  // ✅ 404 - последний
  { path: "/:pathMatch(.*)*", name: "notfound", component: NotFoundPage, meta: { noindex: true } },
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
    headers: { Accept: "application/json" },
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

  // Если пользователь уже залогинен и лезет на /login - отправим дальше
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
    return "/";
  }

  return true;
});

export default router;
