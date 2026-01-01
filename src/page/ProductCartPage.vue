<template>
  <div class="ppage">
    <!-- ===== TOP BAR ===== -->
    <header class="topbar">
      <div class="shell topbar-inner">
        <button class="iconbtn backbtn" @click="goBack" aria-label="Назад">
          <Fa :icon="['fas', 'arrow-left']" />
          <span class="backtxt">Назад</span>
        </button>

        <div class="topmid">
          <div class="crumbs" v-if="crumbs.length">
            <span class="crumb home">
              <Fa :icon="['fas', 'house']" />
              <span>Каталог</span>
            </span>
            <span class="sep">
              <Fa :icon="['fas', 'chevron-right']" />
            </span>

            <span v-for="(c, i) in crumbs" :key="c + i" class="crumb" :title="c">
              <span class="crumbtxt">{{ c }}</span>
              <span v-if="i !== crumbs.length - 1" class="sep">
                <Fa :icon="['fas', 'chevron-right']" />
              </span>
            </span>
          </div>

          <div class="pagetitle">Карточка товара</div>
        </div>

        <!-- справа пусто, чтобы центр держался ровно -->
        <div class="topspacer"></div>
      </div>
    </header>

    <main class="shell">
      <!-- ===== STATES ===== -->
      <section v-if="loading" class="state">
        <div class="skel grid">
          <div class="skelbox big"></div>
          <div class="skelbox side"></div>
        </div>
        <div class="skellines">
          <div class="skelline"></div>
          <div class="skelline w70"></div>
          <div class="skelline w40"></div>
        </div>
      </section>

      <section v-else-if="error" class="state card">
        <div class="state-title">
          <Fa :icon="['fas', 'triangle-exclamation']" />
          Ошибка загрузки
        </div>
        <div class="state-text">{{ error }}</div>
        <button class="btn solid" @click="loadOne">
          <Fa :icon="['fas', 'rotate-right']" />
          Повторить
        </button>
      </section>

      <section v-else-if="!product" class="state card">
        <div class="state-title">
          <Fa :icon="['fas', 'circle-question']" />
          Товар не найден
        </div>
        <div class="state-text">Проверь ID или обнови страницу.</div>
        <button class="btn" @click="goBack">
          <Fa :icon="['fas', 'arrow-left']" />
          Назад
        </button>
      </section>

      <!-- ===== CONTENT ===== -->
      <section v-else class="product">
        <!-- LEFT -->
        <div class="leftCol">
          <!-- GALLERY -->
          <div class="gallery card">
            <div class="stage">
              <template v-if="images.length">
                <button ref="mainPrevEl" class="navbtn prev" aria-label="Предыдущее фото" title="Предыдущее" @click.stop>
                  <Fa :icon="['fas', 'chevron-left']" />
                </button>

                <button ref="mainNextEl" class="navbtn next" aria-label="Следующее фото" title="Следующее" @click.stop>
                  <Fa :icon="['fas', 'chevron-right']" />
                </button>

                <Swiper :key="galleryKey" class="mainSwiper" :modules="swiperModules" :slides-per-view="1"
                  :space-between="10" :navigation="false" :pagination="{ clickable: true }" :keyboard="{ enabled: true }"
                  :thumbs="{ swiper: thumbsSwiper }" @swiper="onMainSwiper" @slideChange="onMainSlideChange">
                  <SwiperSlide v-for="(img, i) in images" :key="img + i">
                    <div class="zoomwrap">
                      <img :src="img" :alt="product?.name || ''" class="img" @error="onSlideImgError(i)" />
                    </div>
                  </SwiperSlide>
                </Swiper>
              </template>

              <div v-else class="nofoto">
                <div class="nofoto-ico">
                  <Fa :icon="['far', 'image']" />
                </div>
                <div class="nofoto-t">Нет фото</div>
                <div class="nofoto-s">Фото появится — покажем автоматически.</div>
              </div>
            </div>

            <div v-if="images.length > 1" class="thumbs">
              <Swiper :key="galleryKey + '-thumbs'" class="thumbSwiper" :modules="swiperModules" :slides-per-view="'auto'"
                :space-between="10" :watch-slides-progress="true" :free-mode="true" @swiper="onThumbsSwiper">
                <SwiperSlide v-for="(img, i) in images" :key="img + i" class="thumbSlide">
                  <button class="thumb" :class="{ on: i === activeIndex }" @click="goTo(i)" :title="`Фото ${i + 1}`">
                    <img :src="img" alt="" @error="onThumbError(i)" />
                  </button>
                </SwiperSlide>
              </Swiper>
            </div>
          </div>

          <!-- DETAILS UNDER GALLERY -->
          <div class="card details">
            <div class="tabs">
              <button class="tab" :class="{ on: tab === 'desc' }" @click="tab = 'desc'">
                <Fa :icon="['far', 'file-lines']" />
                Описание
              </button>

              <button class="tab" :class="{ on: tab === 'attrs' }" @click="tab = 'attrs'">
                <Fa :icon="['fas', 'list-check']" />
                Характеристики
                <span v-if="attrs.length" class="count">{{ attrs.length }}</span>
              </button>

              <button class="tab" :class="{ on: tab === 'info' }" @click="tab = 'info'">
                <Fa :icon="['fas', 'circle-info']" />
                Инфо
              </button>
            </div>

            <div class="tabBody">
              <!-- DESCRIPTION -->
              <div v-if="tab === 'desc'" class="desc">
                <div v-if="hasDescription" class="descText" :class="{ clamp: !descExpanded && descCanToggle }">
                  {{ String(product.description || "") }}
                </div>

                <div v-else class="empty">
                  <Fa :icon="['far', 'face-meh']" />
                  Описание отсутствует
                </div>

                <button v-if="hasDescription && descCanToggle" class="btn small" @click="descExpanded = !descExpanded">
                  <i class="fa-solid" :class="descExpanded ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                  {{ descExpanded ? "Свернуть" : "Показать полностью" }}
                </button>
              </div>

              <!-- ATTRS -->
              <div v-else-if="tab === 'attrs'" class="spec">
                <div v-if="attrs.length" class="specTable">
                  <div v-for="(a, i) in attrs" :key="a.name + i" class="specRow">
                    <div class="sk">{{ a.name }}</div>
                    <div class="sv">
                      <span v-if="a.ui_render === 'color'" class="colorDot" :class="{ empty: !a.meta?.color }"
                        :style="a.meta?.color ? { background: a.meta.color } : {}" :title="a.meta?.color || ''"></span>
                      <span class="svt">{{ a.value }}</span>
                    </div>
                  </div>
                </div>

                <div v-else class="empty">
                  <Fa :icon="['far', 'rectangle-list']" />
                  Характеристики не заполнены
                </div>
              </div>

              <!-- INFO -->
              <div v-else class="info">
                <div class="infoTable">
                  <div class="infoRow">
                    <div class="ik">ID</div>
                    <div class="iv mono">{{ product.id }}</div>
                  </div>

                  <div class="infoRow">
                    <div class="ik">Штрихкод</div>
                    <div class="iv mono">{{ product.barcode || "—" }}</div>
                  </div>
                  <div class="infoRow">
  <div class="ik">Остаток</div>
  <div class="iv">{{ qtyPretty }}</div>
</div>

                  <div class="infoRow" v-if="product.article">
                    <div class="ik">Артикул</div>
                    <div class="iv mono">{{ product.article }}</div>
                  </div>

                  <div class="infoRow" v-if="product.category_path && product.category_path !== '—'">
                    <div class="ik">Категория</div>
                    <div class="iv">{{ product.category_path }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT -->
        <aside class="panel">
          <div class="card pcard">
            <h1 class="title">{{ product.name }}</h1>

            <div class="priceRow">
              <div class="price">
                <span class="pr">{{ pricePretty }}</span>
                <span class="cur">₽</span>
              </div>

              <div class="metaBadges">
                <div v-if="product.brand" class="badge">
                  <Fa :icon="['fas', 'tag']" />
                  <span class="btxt" :title="product.brand">{{ product.brand }}</span>
                </div>
              </div>
            </div>
            <div class="stockRow">
  Остаток: <b>{{ qtyPretty }}</b>
</div>

          </div>
        </aside>
      </section>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useHead } from "@vueuse/head";

/* Swiper */
import { Swiper, SwiperSlide } from "swiper/vue";
import { Navigation, Pagination, Thumbs, Keyboard, FreeMode } from "swiper/modules";
import "swiper/css";
import "swiper/css/navigation";
import "swiper/css/pagination";
import "swiper/css/thumbs";
import "swiper/css/free-mode";

const swiperModules = [Navigation, Pagination, Thumbs, Keyboard, FreeMode];

const route = useRoute();
const router = useRouter();

const loading = ref(true);
const error = ref(null);

const product = ref(null);
const images = ref([]);

const galleryKey = ref(0);
const activeIndex = ref(0);

const mainSwiper = ref(null);
const thumbsSwiper = ref(null);

const tab = ref("desc");
const descExpanded = ref(false);

const pkey = computed(() => String(route.params.slug || "")); // /product/:slug
const isId = (v) => /^\d+$/.test(String(v || ""));

const safeJson = (s) => {
  try {
    return JSON.parse(s);
  } catch {
    return null;
  }
};

const toImgUrl = (s) => {
  if (!s) return "";
  const str = String(s).trim();
  if (!str) return "";
  if (/^https?:\/\//i.test(str) || str.startsWith("data:")) return str;
  if (str.startsWith("/")) return str;
  return "/photo_product_vitrina/" + str;
};

/* Breadcrumbs */
const crumbs = computed(() => {
  const p = String(product.value?.category_path || "").trim();
  if (!p || p === "—") return [];
  const parts = p.split("/").map((x) => x.trim()).filter(Boolean);
  return parts.length > 3 ? ["…", ...parts.slice(-2)] : parts;
});

const pricePretty = computed(() => {
  const n = Number(product.value?.price ?? 0);
  if (!Number.isFinite(n)) return "—";
  return new Intl.NumberFormat("ru-RU", { maximumFractionDigits: 0 }).format(n);
});
const qtyPretty = computed(() => {
  const p = product.value || {};
  const q = p.quantity;

  if (q === undefined || q === null || String(q).trim() === "") return "—";

  const m = String(p.measureName || "").trim();
  return m ? `${q} ${m}` : String(q);
});

/* ===== SEO HEAD (вставить сюда) ===== */
const SITE = "XOZMAG.RU";

function clip(s, n = 160) {
  const t = String(s || "").replace(/\s+/g, " ").trim();
  return t.length > n ? t.slice(0, n - 1) + "…" : t;
}

const headUrl = computed(() => `https://xozmag.ru${route.fullPath}`);

const ogImage = computed(() => {
  const img = images.value?.[0] || "";
  if (!img) return "https://xozmag.ru/img/no-photo.png";
  if (/^https?:\/\//i.test(img)) return img;
  return `https://xozmag.ru${img.startsWith("/") ? img : "/" + img}`;
});

const headTitle = computed(() => {
  const p = product.value;
  if (!p?.name) return `Товар #${pkey.value} — ${SITE}`; const price = Number(p.price);
  const pricePart = Number.isFinite(price) && price > 0 ? ` — ${pricePretty.value} ₽` : "";
  return `${p.name}${pricePart} | ${SITE}`;
});

const headDesc = computed(() => {
  const p = product.value;
  if (!p?.name) return "Товар в магазине XOZMAG.RU: цена, наличие, характеристики.";

  const desc = String(p.description || "").trim();
  if (desc) return clip(desc);

  const parts = [
    p.brand ? `Бренд: ${p.brand}` : "",
    p.article ? `Арт: ${p.article}` : "",
    p.barcode ? `Штрихкод: ${p.barcode}` : "",
    p.category_path && p.category_path !== "—" ? `Категория: ${p.category_path}` : "",
  ].filter(Boolean);

  return clip(`${p.name}. ${parts.join(" · ")}. Цена и наличие в ${SITE}.`);
});

useHead(() => ({
  title: headTitle.value,
  meta: [
    { name: "description", content: headDesc.value },
    { property: "og:title", content: headTitle.value },
    { property: "og:description", content: headDesc.value },
    { property: "og:type", content: "product" },
    { property: "og:url", content: headUrl.value },
    { property: "og:image", content: ogImage.value },
  ],
  link: [{ rel: "canonical", href: headUrl.value }],
}));
/* ===== /SEO HEAD ===== */
const hasDescription = computed(() => String(product.value?.description || "").trim().length > 0);
const descCanToggle = computed(() => String(product.value?.description || "").trim().length > 260);

const attrs = computed(() => {
  const raw = product.value?.attributes;
  const list = Array.isArray(raw) ? raw : [];

  const arr = list.map((a) => {
    let metaObj = a?.meta ?? null;
    if (typeof metaObj === "string") metaObj = safeJson(metaObj) || null;

    return {
      name: a?.name || "",
      value: a?.value ?? "",
      ui_render: a?.ui_render || "text",
      meta: metaObj,
    };
  });

  return arr
    .filter((x) => x.name && String(x.value).trim().length)
    .sort((x, y) => String(x.name).localeCompare(String(y.name), "ru", { sensitivity: "base" }));
});

/* Swiper navigation elements */
const mainPrevEl = ref(null);
const mainNextEl = ref(null);

function attachMainNav() {
  const sw = mainSwiper.value;
  if (!sw || !mainPrevEl.value || !mainNextEl.value) return;

  sw.params.navigation = {
    ...(sw.params.navigation || {}),
    prevEl: mainPrevEl.value,
    nextEl: mainNextEl.value,
  };

  sw.navigation?.destroy?.();
  sw.navigation?.init?.();
  sw.navigation?.update?.();
}

function onMainSwiper(sw) {
  mainSwiper.value = sw;
  nextTick(attachMainNav);
}

function onThumbsSwiper(sw) {
  thumbsSwiper.value = sw;
}

function onMainSlideChange(sw) {
  activeIndex.value = sw.activeIndex || 0;
}

function goTo(i) {
  const idx = Number(i);
  if (!Number.isFinite(idx)) return;
  activeIndex.value = idx;
  mainSwiper.value?.slideTo(idx);
}

/* Image error handling */
function onThumbError(i) {
  return () => {
    const idx = Number(i);
    if (!Number.isFinite(idx)) return;

    const arr = images.value.slice();
    if (!arr[idx]) return;

    arr.splice(idx, 1);
    images.value = arr;

    galleryKey.value++;
    if (activeIndex.value >= images.value.length) activeIndex.value = 0;

    nextTick(() => mainSwiper.value?.slideTo(activeIndex.value, 0));
  };
}
function onSlideImgError(i) {
  return () => onThumbError(i)();
}


async function loadOne() {
  try {
    loading.value = true;
    error.value = null;
    product.value = null;
    images.value = [];
    activeIndex.value = 0;
    descExpanded.value = false;
    tab.value = "desc";
    galleryKey.value++;

    const key = pkey.value;

    const url = isId(key)
      ? `/api/admin/product/get_products.php?id=${encodeURIComponent(key)}&desc=1&attrs=1`
      : `/api/admin/product/get_products.php?slug=${encodeURIComponent(key)}&desc=1&attrs=1`;

    const r = await fetch(url, { cache: "no-store" });
    const j = await r.json();

    const found = j?.item || null;
    if (!found) return;

    // ✅ если пришли по /product/123 — перекидываем на каноничный /product/<slug>
    if (isId(key) && found?.slug) {
      router.replace({ name: "product", params: { slug: found.slug } });
    }

    product.value = found;

    // ✅ теперь фотки приходят сразу массивом images[]
    let imgArr = Array.isArray(found.images) ? found.images.filter(Boolean) : [];
    imgArr = imgArr.map(toImgUrl).filter(Boolean);

    images.value = imgArr;
    galleryKey.value++;

    if (!hasDescription.value && attrs.value.length) tab.value = "attrs";
  } catch (e) {
    error.value = e?.message || "Ошибка загрузки";
  } finally {
    loading.value = false;
  }
}

function goBack() {
  if (window.history.length > 1) router.back();
  else router.push({ path: "/" });
}

onMounted(() => {
  loadOne();
});

watch(pkey, loadOne);
</script>

<style scoped>
/* =========================================================
   PRODUCT PAGE — no lightbox / no zoom
   Accent: #0400ff
   ========================================================= */

.ppage {
  --accent: #0400ff;
  --accent2: #00c2ff;

  --bg: #ffffff;
  --text: #0f172a;
  --muted: #64748b;

  --line: rgba(15, 23, 42, 0.10);
  --soft: rgba(15, 23, 42, 0.06);

  min-height: 100vh;
  background:
    radial-gradient(900px 420px at 12% -160px, rgba(4, 0, 255, 0.10), transparent 60%),
    radial-gradient(900px 420px at 90% -140px, rgba(0, 194, 255, 0.10), transparent 60%),
    var(--bg);
  color: var(--text);
  font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

/* ===== shell ===== */
.shell {
  max-width: 1180px;
  margin: 0 auto;
  padding: 18px 16px 60px;
}

/* ===== topbar ===== */
.topbar {
  position: sticky;
  top: 0;
  z-index: 50;
  background: rgba(255, 255, 255, 0.78);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  border-bottom: 1px solid var(--line);
}

.topbar-inner {
  padding: 12px 16px;
  display: grid;
  grid-template-columns: 220px 1fr 220px;
  gap: 12px;
  align-items: center;
}

.topspacer {
  height: 42px;
}

.topmid {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  min-width: 0;
}

.crumbs {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: rgba(100, 116, 139, 0.95);
  max-width: 100%;
  overflow: hidden;
  white-space: nowrap;
}

.crumb {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
}

.crumb.home {
  font-weight: 800;
  color: rgba(15, 23, 42, 0.72);
}

.crumbtxt {
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 240px;
}

.sep {
  display: inline-flex;
  align-items: center;
  color: rgba(100, 116, 139, 0.60);
}

.pagetitle {
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: .14em;
  font-weight: 900;
  color: rgba(15, 23, 42, 0.70);
}

/* ===== Buttons ===== */
.iconbtn {
  height: 42px;
  width: 42px;
  border-radius: 14px;
  border: 1px solid var(--line);
  background: #fff;
  color: rgba(15, 23, 42, 0.92);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease;
}

.iconbtn:hover {
  transform: translateY(-1px);
  border-color: rgba(4, 0, 255, 0.30);
  box-shadow: 0 14px 30px rgba(2, 6, 23, 0.10);
}

.iconbtn:active {
  transform: translateY(0);
}

.backbtn {
  width: auto;
  padding: 0 14px;
  gap: 10px;
}

.backtxt {
  font-weight: 900;
  letter-spacing: .2px;
}

.btn {
  height: 44px;
  border-radius: 14px;
  border: 1px solid var(--line);
  background: #fff;
  color: rgba(15, 23, 42, 0.92);
  font-weight: 900;
  cursor: pointer;
  padding: 0 14px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease;
}

.btn:hover {
  transform: translateY(-1px);
  border-color: rgba(4, 0, 255, 0.28);
  box-shadow: 0 14px 30px rgba(2, 6, 23, 0.10);
}

.btn:active {
  transform: translateY(0);
}

.btn.solid {
  color: #fff;
  border-color: rgba(4, 0, 255, 0.20);
  background: linear-gradient(135deg, rgba(4, 0, 255, 0.98), rgba(0, 194, 255, 0.88));
  box-shadow: 0 14px 30px rgba(4, 0, 255, 0.18);
}

.btn.small {
  height: 38px;
  border-radius: 13px;
  font-size: 13px;
  padding: 0 12px;
}

/* ===== Cards ===== */
.card {
  background: rgba(255, 255, 255, 0.92);
  border: 1px solid var(--line);
  border-radius: 22px;
  box-shadow: 0 18px 45px rgba(2, 6, 23, 0.10);
}

/* ===== states ===== */
.state {
  margin-top: 18px;
}

.state.card {
  padding: 18px;
}

.state-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 950;
  font-size: 15px;
}

.state-text {
  margin: 10px 0 14px;
  color: rgba(100, 116, 139, 0.95);
  line-height: 1.45;
  font-weight: 700;
}

/* skeleton */
.skel.grid {
  display: grid;
  grid-template-columns: 1.55fr 1fr;
  gap: 18px;
}

.skelbox {
  border-radius: 22px;
  border: 1px solid var(--soft);
  background: linear-gradient(90deg, rgba(255, 255, 255, 0.55), rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.55));
  background-size: 200% 100%;
  animation: sk 1.1s ease-in-out infinite;
  box-shadow: 0 18px 45px rgba(2, 6, 23, 0.08);
}

.skelbox.big {
  height: 520px;
}

.skelbox.side {
  height: 520px;
}

.skellines {
  margin-top: 14px;
  display: grid;
  gap: 10px;
}

.skelline {
  height: 14px;
  border-radius: 999px;
  border: 1px solid var(--soft);
  background: linear-gradient(90deg, rgba(255, 255, 255, 0.55), rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.55));
  background-size: 200% 100%;
  animation: sk 1.1s ease-in-out infinite;
}

.skelline.w70 {
  width: 70%;
}

.skelline.w40 {
  width: 40%;
}

@keyframes sk {
  0% {
    background-position: 200% 0
  }

  100% {
    background-position: -200% 0
  }
}

/* ===== layout ===== */
.product {
  display: grid;
  grid-template-columns: minmax(360px, 680px) minmax(320px, 420px);
  gap: 42px;
  align-items: start;
  margin-top: 18px;
}

.leftCol {
  display: flex;
  flex-direction: column;
  gap: 14px;
  min-width: 0;
}

/* ===== gallery ===== */
.gallery {
  padding: 14px;
  top: 78px;
}

.stage {
  position: relative;
  border-radius: 18px;
  overflow: hidden;
  border: 1px solid var(--soft);
  background: radial-gradient(900px 320px at 50% -120px, rgba(4, 0, 255, 0.08), transparent 60%), #fff;
  height: 520px;
}

.mainSwiper {
  height: 100%;
}

.mainSwiper :deep(.swiper-wrapper) {
  height: 100%;
}

.mainSwiper :deep(.swiper-slide) {
  height: 100%;
  display: flex;
}

.zoomwrap {
  height: 100%;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: default;
  /* ✅ больше не zoom-in */
  outline: none;
  padding: 10px;
}

.img {
  width: 100% !important;
  height: 100% !important;
  object-fit: contain !important;
  object-position: center !important;
  display: block;
}

/* Swiper dots */
:deep(.swiper-pagination-bullet) {
  width: 7px;
  height: 7px;
  background: rgba(15, 23, 42, 0.22);
  opacity: 1;
}

:deep(.swiper-pagination-bullet-active) {
  background: var(--accent);
}

/* nav buttons */
.navbtn {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  z-index: 5;
  height: 44px;
  width: 44px;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.55);
  background: rgba(15, 23, 42, 0.55);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: transform .12s ease, background .12s ease;
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
}

.navbtn:hover {
  transform: translateY(-50%) scale(1.03);
  background: rgba(15, 23, 42, 0.70);
}

.navbtn.prev {
  left: 12px;
}

.navbtn.next {
  right: 12px;
}

/* thumbs */
.thumbs {
  margin-top: 12px;
}

.thumbSwiper :deep(.swiper-slide) {
  width: 86px !important;
}

.thumb {
  width: 86px;
  height: 64px;
  border-radius: 14px;
  border: 1px solid var(--soft);
  background: #fff;
  overflow: hidden;
  cursor: pointer;
  transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease;
}

.thumb:hover {
  transform: translateY(-1px);
  box-shadow: 0 14px 26px rgba(2, 6, 23, 0.10);
}

.thumb.on {
  border-color: rgba(4, 0, 255, 0.35);
  box-shadow: 0 0 0 4px rgba(4, 0, 255, 0.10);
}

.thumb img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  object-position: center;
  display: block;
  background: #fff;
}

.nofoto {
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  color: rgba(100, 116, 139, 0.95);
  text-align: center;
  padding: 18px;
}

.nofoto-ico {
  font-size: 30px;
  color: rgba(15, 23, 42, 0.55);
}

.nofoto-t {
  font-weight: 950;
  color: rgba(15, 23, 42, 0.75);
}

.nofoto-s {
  font-weight: 700;
  font-size: 13px;
  line-height: 1.4;
}

/* ===== details under gallery ===== */
.details {
  padding: 16px;
}

/* tabs */
.tabs {
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
}

.tab {
  height: 40px;
  padding: 0 12px;
  border-radius: 999px;
  border: 1px solid var(--soft);
  background: rgba(15, 23, 42, 0.03);
  color: rgba(15, 23, 42, 0.86);
  font-weight: 950;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease, background .12s ease;
}

.tab:hover {
  transform: translateY(-1px);
  box-shadow: 0 14px 26px rgba(2, 6, 23, 0.08);
}

.tab.on {
  border-color: rgba(4, 0, 255, 0.24);
  background: rgba(4, 0, 255, 0.10);
  color: rgba(4, 0, 255, 0.95);
}

.count {
  font-size: 11px;
  font-weight: 980;
  padding: 4px 8px;
  border-radius: 999px;
  background: rgba(4, 0, 255, 0.14);
  border: 1px solid rgba(4, 0, 255, 0.18);
  color: rgba(4, 0, 255, 0.95);
}

.tabBody {
  margin-top: 14px;
}

/* description */
.descText {
  white-space: pre-wrap;
  line-height: 1.65;
  font-size: 13px;
  color: rgba(15, 23, 42, 0.82);
}

.descText.clamp {
  display: -webkit-box;
  -webkit-line-clamp: 7;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* spec table */
.specTable {
  border-top: 1px solid var(--soft);
}

.specRow {
  display: grid;
  grid-template-columns: 160px 1fr;
  gap: 12px;
  padding: 12px 0;
  border-bottom: 1px solid var(--soft);
}

.sk {
  font-size: 12px;
  font-weight: 950;
  color: rgba(100, 116, 139, 0.95);
}

.sv {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 900;
  color: rgba(15, 23, 42, 0.88);
  font-size: 13px;
}

.svt {
  word-break: break-word;
}

.colorDot {
  width: 12px;
  height: 12px;
  border-radius: 999px;
  border: 1px solid rgba(15, 23, 42, 0.20);
  flex: 0 0 12px;
}

.colorDot.empty {
  background: repeating-linear-gradient(45deg, #f3f4f6, #f3f4f6 3px, #e5e7eb 3px, #e5e7eb 6px);
}

/* info table */
.infoTable {
  border-top: 1px solid var(--soft);
}

.infoRow {
  display: grid;
  grid-template-columns: 140px 1fr;
  gap: 12px;
  padding: 12px 0;
  border-bottom: 1px solid var(--soft);
}
.stockRow {
  margin-top: 10px;
  font-size: 13px;
  color: rgba(15, 23, 42, 0.78);
  font-weight: 800;
}

.ik {
  font-size: 12px;
  font-weight: 950;
  color: rgba(100, 116, 139, 0.95);
}

.iv {
  font-size: 13px;
  font-weight: 900;
  color: rgba(15, 23, 42, 0.88);
  word-break: break-word;
}

.mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
  letter-spacing: .08em;
}

/* empty */
.empty {
  padding: 12px 0;
  color: rgba(100, 116, 139, 0.95);
  font-weight: 900;
  display: flex;
  align-items: center;
  gap: 10px;
}

/* ===== panel ===== */
.panel {
  position: sticky;
  top: 78px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.pcard {
  padding: 18px;
}

.title {
  margin: 0 0 10px;
  font-size: 20px;
  line-height: 1.25;
  letter-spacing: -0.2px;
  font-weight: 950;
}

.priceRow {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.price {
  display: flex;
  align-items: baseline;
  gap: 10px;
}

.pr {
  font-size: 30px;
  font-weight: 980;
  color: var(--accent);
  letter-spacing: -0.6px;
}

.cur {
  font-size: 18px;
  font-weight: 950;
  color: rgba(4, 0, 255, 0.75);
}

.metaBadges {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
}

.badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 9px 10px;
  border-radius: 999px;
  border: 1px solid var(--soft);
  background: rgba(15, 23, 42, 0.03);
  font-weight: 900;
  font-size: 12px;
  color: rgba(15, 23, 42, 0.85);
  max-width: 210px;
}

.btxt {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* ===== responsive ===== */
@media (max-width: 980px) {
  .topbar-inner {
    grid-template-columns: 180px 1fr 180px;
  }

  .crumbtxt {
    max-width: 140px;
  }

  .product {
    grid-template-columns: 1fr;
    gap: 18px;
  }

  .gallery,
  .panel {
    position: relative;
    top: 0;
  }

  .stage {
    height: 380px;
  }
}

@media (max-width: 560px) {
  .topbar-inner {
    grid-template-columns: 160px 1fr 160px;
  }

  .backtxt {
    display: none;
  }

  .stage {
    height: 320px;
  }

  .specRow {
    grid-template-columns: 1fr;
    gap: 6px;
  }

  .infoRow {
    grid-template-columns: 1fr;
    gap: 6px;
  }

  .crumbs {
    display: none;
  }

  .pagetitle {
    letter-spacing: .12em;
  }
}

/* motion safety */
@media (prefers-reduced-motion: reduce) {
  * {
    animation: none !important;
    transition: none !important;
    scroll-behavior: auto !important;
  }
}</style>
