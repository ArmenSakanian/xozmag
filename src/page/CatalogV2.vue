<template>
  <div class="catalog-page">
    <!-- ================= DESKTOP LEFT CATEGORIES ================= -->
    <aside class="catalog-sidebar">
      <div class="sidebar-header">
        <h2 class="sidebar-title">Категории</h2>
      </div>

      <nav class="sidebar-categories">
        <ul class="category-tree-root">
          <CategoryNode
            v-for="node in categoryTree"
            :key="node.id"
            :node="node"
            :selectedCategories="selectedCategories"
            @toggle-category="toggleCategory"
          />
        </ul>
      </nav>
    </aside>

    <!-- ================= MAIN CONTENT ================= -->
    <section class="catalog-content">
      <!-- ===== TOP AREA ===== -->
      <div class="catalog-top">
        <!-- TITLE + BREADCRUMBS -->
        <div class="catalog-heading">
          <div class="breadcrumbs">
            <span class="breadcrumb-home">Каталог</span>
            <span v-if="currentCategory" class="breadcrumb-separator">/</span>
            <span v-if="currentCategory" class="breadcrumb-current">
              {{ currentCategoryName }}
            </span>
          </div>

          <h1 class="catalog-title">
            {{ currentCategoryName || (selectedCategories.length ? `Выбрано категорий: ${selectedCategories.length}` : "Все товары") }}
          </h1>
        </div>

        <!-- ================= SEARCH (CENTER) ================= -->
        <div class="catalog-search">
          <div class="search-box">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <input
              v-model="searchModel"
              class="search-input"
              type="text"
              placeholder="Поиск по названию, бренду или штрихкоду…"
              @input="onSearchInput"
              @keydown.enter.prevent="applyFilters"
            />
            <button
              v-if="searchModel"
              class="search-clear"
              @click="clearSearch"
              aria-label="Очистить поиск"
              title="Очистить"
            >
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <div v-if="searchModel && !loading" class="search-meta">
            Найдено: <b>{{ filteredProducts.length }}</b>
          </div>
        </div>

        <!-- ===== FILTERS BAR (DESKTOP) ===== -->
        <div v-if="hasActiveCategory && !isMobile" class="filters-bar">
          <!-- PRICE -->
          <div class="filter-block filter-price">
            <div class="filter-label">Цена</div>
            <div class="price-inputs">
              <input
                type="number"
                placeholder="От"
                v-model.number="priceFromModel"
                @change="applyFilters"
              />
              <input
                type="number"
                placeholder="До"
                v-model.number="priceToModel"
                @change="applyFilters"
              />
            </div>
          </div>

          <!-- BRAND -->
          <div
            class="filter-block filter-brand"
            :class="{ open: openFilters.brand }"
          >
            <div class="filter-label">Бренд</div>
            <div class="filter-dropdown">
              <div class="filter-dropdown-head" @click="toggleFilter('brand')">
                <span class="filter-head-text">
                  {{ brandModel.length ? (brandModel.length <= 2 ? brandModel.join(" · ") : `Выбрано: ${brandModel.length}`) : "Все" }}
                </span>
                <span class="arrow" :class="{ open: openFilters.brand }">▾</span>
              </div>

              <div
                v-show="openFilters.brand"
                class="filter-dropdown-body"
                :class="{ scrollable: !isMobile && brands.length > 6 }"
              >
                <label class="filter-checkbox filter-all">
                  <input
                    type="checkbox"
                    :checked="!brandModel.length"
                    @change="brandModel = []; applyFilters();"
                  />
                  <span>Все</span>
                </label>

                <label v-for="b in brands" :key="b" class="filter-checkbox">
                  <input
                    type="checkbox"
                    :value="b"
                    v-model="brandModel"
                    @change="applyFilters"
                  />
                  <span>{{ b }}</span>
                </label>
              </div>
            </div>
          </div>

          <!-- ATTRIBUTES -->
          <div
            class="filter-block filter-attribute"
            :class="{ open: openFilters[attr] }"
            v-for="(block, attr) in attributeFilters"
            :key="attr"
          >
            <div class="filter-label">{{ attr }}</div>

            <div class="filter-dropdown">
              <div class="filter-dropdown-head" @click="toggleFilter(attr)">
                <span class="filter-head-text">
                  {{ attributeHeadText(attr) }}
                </span>
                <span class="arrow" :class="{ open: openFilters[attr] }">▾</span>
              </div>

              <div
                v-show="openFilters[attr]"
                class="filter-dropdown-body"
                :class="{ scrollable: !isMobile && (block.values?.length || 0) > 6 }"
              >
                <label class="filter-checkbox filter-all">
                  <input
                    type="checkbox"
                    :checked="isAttrAllSelected(attr)"
                    @change="selectAttrAll(attr)"
                  />
                  <span>Все</span>
                </label>

                <label
                  v-for="v in block.values"
                  :key="v.value"
                  class="filter-checkbox"
                >
                  <input
                    type="checkbox"
                    :value="v.value"
                    v-model="attributeModels[attr]"
                    @change="onAttrValueChange(attr)"
                  />

                  <span class="filter-option">
                    <span
                      v-if="block.ui_render === 'color'"
                      class="color-dot"
                      :class="{ empty: !v.meta?.color }"
                      :style="v.meta?.color ? { background: v.meta.color } : {}"
                    ></span>

                    <span class="filter-option-text">{{ v.value }}</span>
                  </span>
                </label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ================= MOBILE FILTER BAR ================= -->
      <div v-if="isMobile && hasActiveCategory" class="mobile-filter-bar">
        <div class="mobile-price">
          <input
            type="number"
            placeholder="От"
            v-model.number="priceFromModel"
            @change="applyFilters"
          />
          <input
            type="number"
            placeholder="До"
            v-model.number="priceToModel"
            @change="applyFilters"
          />
        </div>

        <button class="mobile-filter-btn" @click="showMobileFilters = true">
          <i class="fa-solid fa-filter"></i> Фильтры
        </button>
      </div>

      <!-- ================= PRODUCTS ================= -->
      <div class="catalog-products">
        <div v-if="loading" class="catalog-loader">
          <div class="loader-spinner"></div>
          <div class="loader-text">Загрузка товаров…</div>
        </div>

        <div v-else class="products-grid">
          <article v-for="p in visibleProducts" :key="p.id" class="product-card">
            <div class="product-image">
              <ProductCardGallery :images="p.images" :alt="p.name" :compact="isMobile" />
            </div>

            <div class="product-info">
              <div class="product-name">{{ p.name }}</div>

              <div class="product-row">
                <div class="product-price">{{ p.price }} ₽</div>
                <div v-if="p.brand" class="product-brand">{{ p.brand }}</div>
              </div>

              <div class="product-meta">
                <span class="product-barcode">{{ p.barcode }}</span>
              </div>
            </div>
          </article>

          <div v-if="filteredProducts.length === 0" class="products-empty">
            <div class="empty-title">Товары не найдены</div>
            <div class="empty-text">Попробуйте изменить категорию или фильтры</div>
          </div>
        </div>

        <!-- LOAD MORE (ускоряет — меньше карточек/свайперов в DOM) -->
        <div v-if="!loading && canLoadMore" class="load-more">
          <button class="load-more-btn" @click="loadMore">
            Показать ещё <!-- <span class="load-more-count">({{ remainingCount }})</span> -->
          </button>
        </div>
      </div>
    </section>

    <!-- ================= MOBILE FILTERS MODAL ================= -->
    <div
      v-if="showMobileFilters"
      class="mobile-filters-overlay"
      @click.self="
        showMobileFilters = false;
        mobileView = 'root';
        activeMobileAttr = null;
      "
    >
      <div class="mobile-filters-panel">
        <div class="mobile-filters-header">
          <button
            v-if="mobileView !== 'root'"
            class="back-btn"
            @click="mobileView = 'root'"
            title="Назад"
          >
            <i class="fa-solid fa-arrow-left"></i>
          </button>

          <div class="title">
            {{ mobileView === "root" ? "Фильтры" : activeMobileAttr || "Бренд" }}
          </div>

          <button
            class="close-btn"
            @click="
              showMobileFilters = false;
              mobileView = 'root';
              activeMobileAttr = null;
            "
            title="Закрыть"
          >
            <i class="fa-solid fa-x"></i>
          </button>
        </div>

        <div v-if="mobileView === 'root'" class="mobile-filters-list">
          <div class="mobile-filter-item" @click="mobileView = 'brand'">
            Бренд
            <i class="fa-solid fa-chevron-right cat-arrow"></i>
          </div>

          <div
            v-for="(vals, attr) in attributeFilters"
            :key="attr"
            class="mobile-filter-item"
            @click="activeMobileAttr = attr; mobileView = 'attr';"
          >
            {{ attr }}
            <i class="fa-solid fa-chevron-right cat-arrow"></i>
          </div>
        </div>

        <div v-if="mobileView === 'brand'" class="mobile-filter-values">
          <label class="filter-checkbox filter-all">
            <input
              type="checkbox"
              :checked="!brandModel.length"
              @change="brandModel = []; applyFilters();"
            />
            <span>Все</span>
          </label>

          <label v-for="b in brands" :key="b" class="filter-checkbox">
            <input
              type="checkbox"
              :value="b"
              v-model="brandModel"
              @change="applyFilters"
            />
            <span>{{ b }}</span>
          </label>
        </div>

        <div v-if="mobileView === 'attr'" class="mobile-filter-values">
          <label class="filter-checkbox filter-all">
            <input
              type="checkbox"
              :checked="isAttrAllSelected(activeMobileAttr)"
              @change="selectAttrAll(activeMobileAttr)"
            />
            <span>Все</span>
          </label>

          <label
            v-for="v in attributeFilters[activeMobileAttr]?.values || []"
            :key="v.value"
            class="filter-checkbox"
          >
            <input
              type="checkbox"
              :value="v.value"
              v-model="attributeModels[activeMobileAttr]"
              @change="applyFilters"
            />

            <span class="filter-option">
              <span
                v-if="attributeFilters[activeMobileAttr]?.ui_render === 'color'"
                class="color-dot"
                :class="{ empty: !v.meta?.color }"
                :style="v.meta?.color ? { background: v.meta.color } : {}"
              ></span>

              <span class="filter-option-text">{{ v.value }}</span>
            </span>
          </label>
        </div>
      </div>
    </div>

    <!-- ================= MOBILE CATEGORIES ================= -->
    <div v-if="isMobile" class="mobile-cats-btn" @click="showMobileCats = true">
      ☰
    </div>

    <div v-if="showMobileCats" class="mobile-cats-overlay" @click.self="showMobileCats = false">
      <div class="mobile-cats-panel">
        <div class="mobile-cats-header">
          <span>Категории</span>
          <button @click="showMobileCats = false">✕</button>
        </div>

        <ul class="category-tree-root">
          <CategoryNode
            v-for="node in categoryTree"
            :key="node.id"
            :node="node"
            :selectedCategories="selectedCategories"
            @toggle-category="toggleCategory"
          />
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, onBeforeUnmount } from "vue";
import { useRoute, useRouter } from "vue-router";
import CategoryNode from "@/components/CategoryNode.vue";
import ProductCardGallery from "@/components/ProductCardGallery.vue";

const route = useRoute();
const router = useRouter();

/* ================= helpers ================= */
const normalize = (s) =>
  String(s || "")
    .toLowerCase()
    .replace(/ё/g, "е")
    // все символы кроме букв/цифр → пробел (чтобы "2.5м", "2,5 м" и т.п. работали)
    .replace(/[^\p{L}\p{N}]+/gu, " ")
    .replace(/\s+/g, " ")
    .trim();


const toArr = (v) => (v == null ? [] : Array.isArray(v) ? v.map(String) : [String(v)]);

/* ================= STATE ================= */
const loading = ref(true);
const error = ref(null);

const products = ref([]);
const categories = ref([]);

const selectedCategories = ref([]);
const showMobileCats = ref(false);

function toggleCategory(code) {
  if (selectedCategories.value.includes(code)) {
    selectedCategories.value = selectedCategories.value.filter((c) => c !== code);
  } else {
    selectedCategories.value.push(code);
  }

  if (isMobile.value) showMobileCats.value = false;
}

/* ================= mobile detect (clean) ================= */
const isMobile = ref(false);
const handleResize = () => {
  isMobile.value = window.innerWidth < 1024;
};
onMounted(() => {
  handleResize();
  window.addEventListener("resize", handleResize, { passive: true });
});
onBeforeUnmount(() => {
  window.removeEventListener("resize", handleResize);
});

/* ================= filters ui ================= */
const openFilters = ref({});
function toggleFilter(key) {
  const next = {};
  Object.keys(openFilters.value).forEach((k) => (next[k] = false));
  if (!openFilters.value[key]) next[key] = true;
  openFilters.value = next;
}

/* ================= URL SOURCE OF TRUTH ================= */
const currentCategory = computed(() => {
  const v = route.query.cat;
  return v ? String(Array.isArray(v) ? v[0] : v) : null;
});

const showMobileFilters = ref(false);
const mobileView = ref("root"); // root | brand | attr
const activeMobileAttr = ref(null);

/* ================= FILTER MODELS ================= */
const brandModel = ref([]);
const priceFromModel = ref(route.query.price_from ? Number(route.query.price_from) : null);
const priceToModel = ref(route.query.price_to ? Number(route.query.price_to) : null);

const searchModel = ref(route.query.q ? String(route.query.q) : "");
const attributeModels = ref({});

/* ===== debounce for search (чтобы не лагало при вводе) ===== */
let searchTimer = null;
function onSearchInput() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => applyFilters(), 220);
}
onBeforeUnmount(() => clearTimeout(searchTimer));

function clearSearch() {
  searchModel.value = "";
  applyFilters();
}

/* ================= DATA LOAD (parallel) ================= */
async function loadData() {
  try {
    loading.value = true;

    const [r1, r2, r3] = await Promise.all([
      fetch("/api/admin/product/get_categories_flat.php"),
      fetch("/api/admin/product/get_products.php"),
      fetch("/api/vitrina/evotor_catalog.php"),
    ]);

    const rawCats = await r1.json();
    const baseProducts = await r2.json();
    const evotor = await r3.json();

    categories.value = rawCats.map((c) => ({
      id: c.id,
      name: c.name,
      code: c.code,
      parent: c.parent_id,
    }));

    const imgMap = {};
    (evotor.products || []).forEach((p) => {
      imgMap[p.barcode] = p.images || [];
    });

    products.value = baseProducts.map((p) => {
      const images = imgMap[p.barcode] || [];
      return {
        ...p,
        images,
        _search: normalize(`${p.name || ""} ${p.brand || ""} ${p.barcode || ""}`),
      };
    });
  } catch (e) {
    error.value = e.message || "Ошибка загрузки";
  } finally {
    loading.value = false;
  }
}
onMounted(loadData);

/* ================= CATEGORY TREE ================= */
const categoryTree = computed(() => {
  const map = {};
  categories.value.forEach((c) => (map[c.id] = { ...c, children: [] }));
  const roots = [];
  categories.value.forEach((c) => {
    if (!c.parent) roots.push(map[c.id]);
    else map[c.parent]?.children.push(map[c.id]);
  });
  return roots;
});

const currentCategoryName = computed(() => {
  if (!currentCategory.value) return null;
  const found = categories.value.find((c) => c.code === currentCategory.value);
  return found ? found.name : null;
});

const hasActiveCategory = computed(() => !!currentCategory.value || selectedCategories.value.length > 0);

const activeCategoryCodes = computed(() => {
  if (selectedCategories.value.length) return selectedCategories.value;
  if (currentCategory.value) return [currentCategory.value];
  return [];
});

/* ================= base list by category (ускорение) ================= */
const categoryProducts = computed(() => {
  const codes = activeCategoryCodes.value;
  if (!codes.length) return products.value;

  return products.value.filter(
    (p) =>
      typeof p.category_code === "string" &&
      codes.some((code) => p.category_code.startsWith(code))
  );
});

/* ================= BRANDS (BY CATEGORY) ================= */
const brands = computed(() => {
  const set = new Set();
  categoryProducts.value.forEach((p) => p.brand && set.add(p.brand));
  return Array.from(set).sort((a, b) => a.localeCompare(b, "ru", { sensitivity: "base" }));
});

/* ================= ATTRIBUTES (BY CATEGORY) ================= */
const attributeFilters = computed(() => {
  const temp = {};
  categoryProducts.value.forEach((p) => {
    (p.attributes || []).forEach((a) => {
      if (!a?.name || !a?.value) return;
      if (!temp[a.name]) temp[a.name] = { ui_render: a.ui_render || "text", map: new Map() };
      if (!temp[a.name].map.has(a.value)) temp[a.name].map.set(a.value, { value: a.value, meta: a.meta || null });
    });
  });

  const res = {};
  for (const k in temp) {
    res[k] = {
      ui_render: temp[k].ui_render,
      values: Array.from(temp[k].map.values()).sort((x, y) =>
        String(x.value).localeCompare(String(y.value), "ru", { sensitivity: "base" })
      ),
    };
  }
  return res;
});

/* ensure keys exist in attributeModels + openFilters */
watch(
  attributeFilters,
  () => {
    const nextAttrs = { ...attributeModels.value };
    Object.keys(attributeFilters.value).forEach((k) => {
      if (!Array.isArray(nextAttrs[k])) nextAttrs[k] = [];
    });
    attributeModels.value = nextAttrs;

    const nextOpen = { brand: openFilters.value.brand ?? false };
    Object.keys(attributeFilters.value).forEach((k) => (nextOpen[k] = openFilters.value[k] ?? false));
    openFilters.value = nextOpen;
  },
  { immediate: true }
);

/* ================= HEAD TEXT HELPERS ================= */
function attributeHeadText(attr) {
  const selected = attributeModels.value[attr] || [];
  if (!selected.length) return "Все";
  if (selected.length <= 2) return selected.join(" · ");
  return `Выбрано: ${selected.length}`;
}
function isAttrAllSelected(attr) {
  if (!attr) return true;
  return !attributeModels.value[attr]?.length;
}
function selectAttrAll(attr) {
  if (!attr) return;
  attributeModels.value[attr] = [];
  applyFilters();
}
function onAttrValueChange() {
  applyFilters();
}

/* ================= APPLY FILTERS (router.replace чтобы не спамить историю) ================= */
const syncingFromRoute = ref(false);

function applyFilters() {
  if (syncingFromRoute.value) return;

const qRaw = String(searchModel.value || "").trim();

const query = {
  cat: currentCategory.value || undefined,
  q: qRaw || undefined,
  brand: brandModel.value.length ? brandModel.value : undefined,
  price_from: priceFromModel.value !== null ? priceFromModel.value : undefined,
  price_to: priceToModel.value !== null ? priceToModel.value : undefined,
};

for (const [k, v] of Object.entries(attributeModels.value)) {
  if (Array.isArray(v) && v.length) query[`attr_${k}`] = v;
}

router.replace({ query });

}

/* ================= URL → MODELS (чтобы работали back/forward и ссылки) ================= */
watch(
  () => route.query,
  (q) => {
    syncingFromRoute.value = true;

    searchModel.value = q.q ? String(Array.isArray(q.q) ? q.q[0] : q.q) : "";

    brandModel.value = toArr(q.brand);

    priceFromModel.value = q.price_from != null && q.price_from !== "" ? Number(Array.isArray(q.price_from) ? q.price_from[0] : q.price_from) : null;
    priceToModel.value = q.price_to != null && q.price_to !== "" ? Number(Array.isArray(q.price_to) ? q.price_to[0] : q.price_to) : null;

    const nextAttrs = { ...attributeModels.value };
    Object.keys(q).forEach((key) => {
      if (!key.startsWith("attr_")) return;
      const name = key.slice(5);
      nextAttrs[name] = toArr(q[key]);
    });
    attributeModels.value = nextAttrs;

    syncingFromRoute.value = false;
  },
  { immediate: true, deep: true }
);

/* ================= MOBILE UX WATCHERS ================= */
watch([showMobileFilters, showMobileCats], ([f, c]) => {
  document.body.style.overflow = f || c ? "hidden" : "";
});

watch(currentCategory, () => {
  showMobileFilters.value = false;
  mobileView.value = "root";
  activeMobileAttr.value = null;
});

/* ================= FINAL PRODUCTS ================= */
const filteredProducts = computed(() => {
  let list = categoryProducts.value;

  // search
const qRaw = String(searchModel.value || "");
const tokens = normalize(qRaw)
  .split(" ")
  .filter(Boolean)
  // можно игнорить 1-буквенные (но цифры оставить)
  .filter((t) => t.length >= 2 || /^\d+$/.test(t));

if (tokens.length) {
  list = list.filter((p) => {
    const hay = p._search || "";
    return tokens.every((t) => hay.includes(t));
  });
}


  // brand
  if (brandModel.value.length) list = list.filter((p) => brandModel.value.includes(p.brand));

  // price
  if (priceFromModel.value !== null) list = list.filter((p) => Number(p.price) >= priceFromModel.value);
  if (priceToModel.value !== null) list = list.filter((p) => Number(p.price) <= priceToModel.value);

  // attributes
  for (const [k, arr] of Object.entries(attributeModels.value)) {
    if (!Array.isArray(arr) || !arr.length) continue;
    list = list.filter((p) => p.attributes?.some((a) => a.name === k && arr.includes(a.value)));
  }

  return list;
});

/* ================= pagination (ускорение) ================= */
const step = computed(() => (isMobile.value ? 10 : 24));
const displayLimit = ref(step.value);

watch(
  () => [isMobile.value, currentCategory.value, selectedCategories.value.join("|"), route.query],
  () => {
    displayLimit.value = step.value;
  },
  { deep: true }
);

const visibleProducts = computed(() => filteredProducts.value.slice(0, displayLimit.value));
const canLoadMore = computed(() => filteredProducts.value.length > displayLimit.value);
const remainingCount = computed(() => Math.max(0, filteredProducts.value.length - visibleProducts.value.length));

function loadMore() {
  displayLimit.value += step.value;
}
</script>

<style scoped>
/* ✅ важно для scoped */
:global(:root) {
  --bg-main: #f4f6fb;
  --bg-panel: #ffffff;
  --bg-soft: #f0f2f7;

  --text-main: #1b1e28;
  --text-muted: #6b7280;
  --text-light: #9aa1b2;

  --accent: #0400ff;
  --accent-2: #16a34a;
  --accent-danger: #dc2626;

  --border-soft: #e4e7ef;

  --radius-sm: 6px;
  --radius-md: 10px;
  --radius-lg: 16px;

  --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.05);
  --shadow-md: 0 6px 20px rgba(0, 0, 0, 0.08);
  --shadow-lg: 0 12px 40px rgba(0, 0, 0, 0.12);
}

:global(body) {
  background: var(--bg-main);
}

* { box-sizing: border-box; }

/* =========================
   PAGE LAYOUT
========================= */
.catalog-page {
  display: flex;
  min-height: 100vh;
  background: var(--bg-main);
  color: var(--text-main);
  font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
}

/* =========================
   SIDEBAR (DESKTOP)
========================= */
.catalog-sidebar {
  width: 300px;
  background: var(--bg-panel);
  border-right: 1px solid var(--border-soft);
  padding: 22px 18px;
  display: flex;
  flex-direction: column;
}

.sidebar-header {
  padding-bottom: 12px;
  border-bottom: 1px solid var(--border-soft);
  margin-bottom: 16px;
}

.sidebar-title {
  font-size: 20px;
  font-weight: 800;
  color: var(--text-main);
}

.sidebar-categories {
  flex: 1;
  overflow-y: auto;
  overflow-x: visible;
  padding-right: 4px;
}

.category-tree-root {
  list-style: none;
  padding: 0;
  margin: 0;
}

/* =========================
   MAIN CONTENT
========================= */
.catalog-content {
  flex: 1;
  padding: 26px 30px;
  display: flex;
  flex-direction: column;
  gap: 18px;
}

/* =========================
   TOP AREA
========================= */
.catalog-top {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.catalog-heading {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.breadcrumbs {
  font-size: 13px;
  color: var(--text-muted);
}

.breadcrumb-separator { margin: 0 6px; }
.breadcrumb-current { color: var(--text-main); font-weight: 600; }

.catalog-title {
  font-size: 28px;
  font-weight: 900;
  color: var(--text-main);
}

/* =========================
   SEARCH
========================= */
.catalog-search {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  margin-top: 2px;
}

.search-box {
  width: min(760px, 100%);
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 14px;
  border-radius: 999px;
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  box-shadow: var(--shadow-sm);
}

.search-icon {
  color: var(--text-muted);
  font-size: 14px;
}

.search-input {
  flex: 1;
  border: none;
  outline: none;
  background: transparent;
  font-size: 15px;
  color: var(--text-main);
}

.search-clear {
  width: 34px;
  height: 34px;
  border-radius: 999px;
  border: 1px solid var(--border-soft);
  background: #fff;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: transform .15s ease, box-shadow .15s ease;
}

.search-clear:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-sm);
}

.search-meta {
  font-size: 12px;
  color: var(--text-muted);
}

/* =========================
   FILTERS BAR
========================= */
.filters-bar {
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  padding: 18px;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 16px;
  box-shadow: var(--shadow-sm);
}

.filter-price { grid-column: span 2; }

.filter-block {
  min-width: 180px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  background: #ffffff;
  border-radius: 14px;
  padding: 10px 12px;
  border: 1px solid #e4e7ef;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
  transition: box-shadow 0.2s ease, transform 0.2s ease;
  position: relative;
  z-index: 1;
}

.filter-block:hover {
  box-shadow: 0 10px 26px rgba(0, 0, 0, 0.10);
  transform: translateY(-1px);
}

.filter-block.open { z-index: 200; }

.filter-label {
  font-size: 11px;
  font-weight: 800;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  padding-left: 6px;
}

.filter-head-text {
  font-size: 14px;
  color: #1b1e28;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.price-inputs { display: flex; gap: 8px; }
.price-inputs input {
  padding: 11px 14px;
  border-radius: 12px;
  border: 1px solid #e4e7ef;
  width: 50%;
  font-size: 14px;
  background: #fff;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}
.price-inputs input:focus {
  outline: none;
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(4, 0, 255, 0.14);
}

.filter-dropdown {
  position: relative;
  border-radius: 12px;
  background: linear-gradient(180deg, #ffffff, #f9faff);
  border: 1px solid #e4e7ef;
}

.filter-dropdown-head {
  padding: 12px 14px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
  font-size: 14px;
  font-weight: 700;
  color: #1b1e28;
  background: transparent;
  transition: background 0.2s ease;
}
.filter-dropdown-head:hover { background: rgba(4, 0, 255, 0.05); }

.filter-dropdown-body {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  right: 0;
  z-index: 210;
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  background: #ffffff;
  border-radius: 14px;
  border: 1px solid #e4e7ef;
  box-shadow: 0 18px 40px rgba(0, 0, 0, 0.14);
}

.filter-dropdown-body.scrollable {
  max-height: min(260px, 50vh);
  overflow-y: auto;
  padding-right: 6px;
}

.filter-checkbox {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  font-size: 13px;
  font-weight: 600;
  color: #1b1e28;
  padding: 6px 8px;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s ease;
}
.filter-checkbox:hover { background: rgba(4, 0, 255, 0.06); }
.filter-checkbox input { accent-color: var(--accent); cursor: pointer; margin-top: 2px; }

.filter-all {
  font-weight: 800;
  padding-bottom: 8px;
  margin-bottom: 8px;
  border-bottom: 1px dashed #e4e7ef;
}

.arrow {
  font-size: 12px;
  color: var(--accent);
  transition: transform 0.25s ease;
}
.arrow.open { transform: rotate(180deg); }

.filter-option {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  flex: 1;
  min-width: 0;
}
.color-dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  border: 1px solid #d1d5db;
  flex: 0 0 12px;
  margin-top: 2px;
}
.filter-option-text {
  flex: 1;
  min-width: 0;
  white-space: normal;
  overflow-wrap: anywhere;
  line-height: 1.2;
}
.color-dot.empty { background: transparent; position: relative; }
.color-dot.empty::before,
.color-dot.empty::after {
  content: "";
  position: absolute;
  left: 50%;
  top: 50%;
  width: 10px;
  height: 2px;
  background: #ff0000;
  transform-origin: center;
  border-radius: 2px;
}
.color-dot.empty::before { transform: translate(-50%, -50%) rotate(45deg); }
.color-dot.empty::after  { transform: translate(-50%, -50%) rotate(-45deg); }

/* =========================
   PRODUCTS
========================= */
.catalog-products {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 18px;
}

/* product card (быстрее + красивее) */
.product-card {
  min-width: 0;
  background: var(--bg-panel);
  border-radius: 16px;
  border: 1px solid var(--border-soft);
  box-shadow: var(--shadow-sm);
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  cursor: pointer;
  transition: transform 0.18s ease, box-shadow 0.18s ease;
}

.product-card:hover {
  transform: translateY(-3px);
  box-shadow: var(--shadow-md);
}

/* одинаковая высота фото */
.product-image {
  border-radius: 14px;
  overflow: hidden;
  border: 1px solid var(--border-soft);
  background: #fff;
  aspect-ratio: 1 / 1;
  display: flex;
  min-width: 0; overflow: hidden;
}

/* галерея внутри должна занимать 100% */
.product-image :deep(.pg) {
  width: 100%;
  height: 100%;
}

/* стрелки показываем только при hover карточки (и не мешают на мобилке) */
.product-card :deep(.swiper-button-next),
.product-card :deep(.swiper-button-prev) {
  opacity: 0;
  pointer-events: none;
  transition: opacity .18s ease, transform .18s ease;
}
.product-card:hover :deep(.swiper-button-next),
.product-card:hover :deep(.swiper-button-prev) {
  opacity: 1;
  pointer-events: auto;
}

.product-info {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.product-name {
  font-size: 14px;
  line-height: 1.35;
  font-weight: 700;
  color: var(--text-main);

  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  min-height: 2.7em;
}

.product-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.product-price {
  font-size: 18px;
  font-weight: 900;
  color: var(--accent);
  letter-spacing: -0.01em;
}

.product-brand {
  font-size: 12px;
  font-weight: 800;
  color: #111827;
  background: rgba(4, 0, 255, 0.08);
  border: 1px solid rgba(4, 0, 255, 0.16);
  padding: 6px 10px;
  border-radius: 999px;
  white-space: nowrap;
  max-width: 45%;
  overflow: hidden;
  text-overflow: ellipsis;
}

.product-meta {
  display: flex;
  gap: 8px;
  align-items: center;
  justify-content: flex-start;
}

.product-barcode {
  font-size: 12px;
  color: var(--text-muted);
  background: #f3f4f6;
  border: 1px solid #e5e7eb;
  padding: 6px 10px;
  border-radius: 999px;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
}

/* EMPTY */
.products-empty {
  grid-column: 1 / -1;
  background: var(--bg-panel);
  border: 1px dashed #cbd5e1;
  border-radius: var(--radius-lg);
  padding: 40px;
  text-align: center;
}
.empty-title { font-size: 18px; font-weight: 800; margin-bottom: 6px; }
.empty-text { font-size: 14px; color: var(--text-muted); }

/* LOADER */
.catalog-loader {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 50vh;
  gap: 14px;
}
.loader-spinner {
  width: 48px;
  height: 48px;
  border: 4px solid #dbe0ec;
  border-top-color: var(--accent);
  border-radius: 50%;
  animation: spin 0.9s linear infinite;
}
.loader-text { font-size: 14px; color: var(--text-muted); }
@keyframes spin { to { transform: rotate(360deg); } }

/* LOAD MORE */
.load-more {
  margin-top: 18px;
  display: flex;
  justify-content: center;
}
.load-more-btn {
  border: 1px solid var(--border-soft);
  background: var(--bg-panel);
  box-shadow: var(--shadow-sm);
  padding: 12px 16px;
  border-radius: 999px;
  cursor: pointer;
  font-weight: 900;
  transition: transform .15s ease, box-shadow .15s ease;
}
.load-more-btn:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}
.load-more-count { color: var(--text-muted); font-weight: 800; }

/* =========================
   MOBILE
========================= */
@media (max-width: 1024px) {
  .catalog-sidebar { width: 260px; }

  .products-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
  }

  .mobile-filter-bar {
    display: flex;
    flex-direction: column;
    gap: 10px;
  }

  .mobile-price {
    flex: 1;
    display: flex;
    gap: 8px;
  }

  .mobile-price input {
    flex: 1;
    width: 50%;
    padding: 10px 12px;
    border-radius: 12px;
    border: 1px solid var(--border-soft);
    background: var(--bg-panel);
    box-shadow: var(--shadow-sm);
  }

  .mobile-filter-btn {
    border-radius: 12px;
    border: 1px solid var(--border-soft);
    background: var(--bg-panel);
    box-shadow: var(--shadow-sm);
    padding: 10px 12px;
    font-weight: 900;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
  }

  .mobile-filters-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    z-index: 300;
  }

  .mobile-filters-panel {
    position: fixed;
    inset: 0;
    width: 100%;
    height: 100vh;
    background: #fff;
    display: flex;
    flex-direction: column;
    padding: 0;
    padding-top: 70px;
    z-index: 310;
  }

  .mobile-filters-header {
    position: sticky;
    top: 0;
    height: 56px;
    padding: 0 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--border-soft);
    background: #fff;
    z-index: 5;
  }

  .mobile-filters-header .title { font-size: 16px; font-weight: 900; }

  .mobile-filters-list,
  .mobile-filter-values {
    flex: 1;
    overflow-y: auto;
    padding: 12px 14px 90px;
  }

  .back-btn,
  .close-btn {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
  }

  .mobile-filter-item {
    padding: 14px 12px;
    border-bottom: 1px solid var(--border-soft);
    display: flex;
    justify-content: space-between;
    font-size: 16px;
    font-weight: 800;
  }

  .mobile-filter-values {
    display: flex;
    flex-direction: column;
    gap: 10px;
  }

  .mobile-cats-btn {
    position: fixed;
    bottom: 16px;
    left: 16px;
    width: 52px;
    height: 52px;
    background: #000;
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    font-weight: 900;
    z-index: 1000;
  }

  .mobile-cats-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.4);
    z-index: 500;
  }

  .mobile-cats-panel {
    position: fixed;
    inset: 0;
    background: #fff;
    padding: 70px 16px 16px;
    overflow-y: auto;
    z-index: 1100;
  }

  .mobile-cats-header {
    position: sticky;
    top: 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    background: #fff;
    z-index: 10;
    padding: 10px 0;
  }

  .mobile-cats-header span { font-size: 18px; font-weight: 900; }
  .mobile-cats-header button { background: none; border: none; font-size: 22px; cursor: pointer; }
}

@media (max-width: 768px) {
  .catalog-page { flex-direction: column; }
  .catalog-sidebar { display: none; }
  .catalog-content { padding: 16px; padding-bottom: 90px; }

  .products-grid { grid-template-columns: 1fr; }

  .catalog-title { font-size: 24px; }
  .product-price { font-size: 17px; }
}

/* disable selection только на элементах фильтра */
.filters-bar,
.filter-block,
.filter-label,
.filter-dropdown,
.filter-dropdown-head,
.filter-dropdown-body,
.filter-checkbox,
.filter-checkbox span,
.filter-head-text,
.arrow {
  user-select: none;
  -webkit-user-select: none;
  -ms-user-select: none;
}
input,
input[type="checkbox"],
input[type="number"],
.search-input {
  user-select: auto;
}
</style>
