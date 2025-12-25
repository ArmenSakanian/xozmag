<template>
  <div class="catalog-page">
    <!-- ================= MAIN CONTENT ================= -->
    <section class="catalog-content">
      <div class="catalog-top">
        <!-- ✅ SEARCH ABOVE HEADING -->
        <div class="catalog-search">
          <div class="search-box" ref="searchBoxRef">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>

            <!-- ✅ CATEGORY BUTTON INSIDE SEARCH (CLICK) -->
            <div class="catpick-wrap" ref="catPickRef">
              <button
                class="catpick-btn"
                :class="{ on: showCatPopover && !isMobile }"
                type="button"
                title="Категории"
                aria-label="Категории"
                @click.prevent="toggleCatPopover"
              >
                <i class="fa-solid fa-bars-staggered"></i>
              </button>
            </div>
            <!-- DESKTOP POPOVER (click) -->
            <div
              v-if="showCatPopover && !isMobile"
              class="catpop"
              ref="catPopRef"
            >
              <div class="catpop-head">
                <div class="catpop-title">Категории</div>

                <button
                  class="catpop-close"
                  type="button"
                  @click="closeCatPopover"
                  title="Закрыть"
                >
                  <i class="fa-solid fa-xmark"></i>
                </button>
              </div>

              <div class="catpop-cols">
<div
  v-for="(col, level) in desktopColumns"
  :key="colKey(level)"
  class="catpop-col"
  :ref="(el) => setColRef(el, level)"
>

                  <button
                    v-for="n in col"
                    :key="n.id"
                    class="catpop-item"
                    :class="{
                      active: isCatActive(n),
                      picked: isCatPicked(n),
                    }"
                    @mouseenter="desktopHover(level, n)"
                    @click="pickCategory(n)"
                    :title="n.name"
                    type="button"
                  >
                    <span class="catpop-text">{{ n.name }}</span>
                    <i
                      v-if="n.children?.length"
                      class="fa-solid fa-chevron-right catpop-chev"
                    ></i>
                  </button>
                </div>
              </div>
            </div>
            <input
              v-model="searchModel"
              class="search-input"
              type="text"
              placeholder="Поиск по названию / бренду / штрихкоду…"
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

          <div
            v-if="searchModel && !loading && !searchLoading"
            class="search-meta"
          >
            Найдено: <b>{{ filteredProducts.length }}</b>
          </div>

          <div v-if="searchLoading" class="search-meta">
            <span class="dot"></span> Поиск…
          </div>
        </div>

        <!-- TITLE + BREADCRUMBS -->
        <div class="catalog-heading">
          <div class="breadcrumbs">
            <span class="breadcrumb-home">Каталог</span>
            <span v-if="currentCategory" class="breadcrumb-separator">/</span>
            <span v-if="currentCategory" class="breadcrumb-current">{{
              currentCategoryName
            }}</span>
          </div>

          <h1 class="catalog-title">
            <template v-if="!hasActiveCategory && !searchModel">
              Категории
            </template>

            <template v-else-if="searchModel && !hasActiveCategory">
              Результаты поиска
            </template>

            <template v-else>
              {{ currentCategoryName || "Каталог" }}
            </template>
          </h1>
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
                  {{
                    brandModel.length
                      ? brandModel.length <= 2
                        ? brandModel.join("· ")
                        : `Выбрано: ${brandModel.length}`
                      : " Все"
                  }}
                </span>
                <span class="arrow" :class="{ open: openFilters.brand }"
                  >▾</span
                >
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
                    @change="
                      brandModel = [];
                      applyFilters();
                    "
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

          <!-- PHOTO -->
          <div
            class="filter-block filter-photo"
            :class="{ open: openFilters.photo }"
          >
            <div class="filter-label">Фото</div>

            <div class="filter-dropdown">
              <div class="filter-dropdown-head" @click="toggleFilter('photo')">
                <span class="filter-head-text">{{ photoHeadText }}</span>
                <span class="arrow" :class="{ open: openFilters.photo }"
                  >▾</span
                >
              </div>

              <div v-show="openFilters.photo" class="filter-dropdown-body">
                <label class="filter-checkbox filter-all">
                  <input
                    type="radio"
                    value="all"
                    v-model="photoModel"
                    @change="applyFilters"
                  />
                  <span>Все</span>
                </label>

                <label class="filter-checkbox">
                  <input
                    type="radio"
                    value="with"
                    v-model="photoModel"
                    @change="applyFilters"
                  />
                  <span>С фото</span>
                </label>

                <label class="filter-checkbox">
                  <input
                    type="radio"
                    value="without"
                    v-model="photoModel"
                    @change="applyFilters"
                  />
                  <span>Без фото</span>
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
                <span class="filter-head-text">{{
                  attributeHeadText(attr)
                }}</span>
                <span class="arrow" :class="{ open: openFilters[attr] }"
                  >▾</span
                >
              </div>

              <div
                v-show="openFilters[attr]"
                class="filter-dropdown-body"
                :class="{
                  scrollable: !isMobile && (block.values?.length || 0) > 6,
                }"
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

        <!-- ================= MOBILE FILTER BAR ================= -->
        <div v-if="isMobile && hasActiveCategory" class="mobile-filter-bar">
          <div class="mfb-left">
            <div class="mfb-title">Цена</div>
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
          </div>

          <button class="mobile-filter-btn" @click="showMobileFilters = true">
            <i class="fa-solid fa-filter"></i> Фильтры
          </button>
        </div>
      </div>

      <!-- ================= PRODUCTS ================= -->
      <div class="catalog-products">
        <div v-if="loading" class="catalog-loader">
          <div class="loader-spinner"></div>
          <div class="loader-text">Загрузка товаров…</div>
        </div>

        <template v-else>
          <div
            v-if="!hasActiveCategory && !searchModel"
            class="categories-landing"
          >
            <HomeCatalogEntry
              :show-search="false"
              :show-head="false"
              :items="topCats"
              :navigate-on-pick="false"
              @select-category="pickCategoryFromGrid"
            />
          </div>

          <div v-else class="products-grid">
            <article
              v-for="p in visibleProducts"
              :key="p.id"
              class="product-card"
            >
              <div
                class="product-image"
                @click.stop
                @pointerdown.stop
                @mousedown.stop
                @touchstart.stop
              >
                <ProductCardGallery
                  :images="p.images"
                  :alt="p.name"
                  :compact="isMobile"
                />
              </div>

              <div class="product-info" @click="openProduct(p)">
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
              <div class="empty-text">
                Попробуйте изменить категорию, поиск или фильтры
              </div>
            </div>
          </div>

          <!-- LOAD MORE -->
          <div
            v-if="(hasActiveCategory || searchModel) && canLoadMore"
            class="load-more"
          >
            <button class="load-more-btn" @click="loadMore">
              Показать ещё
            </button>
          </div>
        </template>
      </div>
    </section>

    <!-- ================= MOBILE FILTERS MODAL ================= -->
    <div
      v-if="showMobileFilters"
      class="moverlay-overlay"
      @click.self="
        showMobileFilters = false;
        mobileView = 'root';
        activeMobileAttr = null;
      "
    >
      <div class="moverlay-panel">
        <div class="moverlay-header">
          <button
            class="moverlay-btn moverlay-back"
            :class="{ ghost: mobileView === 'root' }"
            :disabled="mobileView === 'root'"
            @click="mobileView = 'root'"
            title="Назад"
          >
            <i class="fa-solid fa-arrow-left"></i>
          </button>

          <span class="moverlay-title">
            {{
              mobileView === "root"
                ? "Фильтры"
                : mobileView === "brand"
                ? "Бренд"
                : mobileView === "photo"
                ? "Фото"
                : activeMobileAttr || "Фильтры"
            }}
          </span>

          <button
            class="moverlay-btn moverlay-close"
            @click="
              showMobileFilters = false;
              mobileView = 'root';
              activeMobileAttr = null;
            "
            title="Закрыть"
          >
            ✕
          </button>
        </div>

        <div class="moverlay-body">
          <div v-if="mobileView === 'root'" class="mfil-list">
            <div class="mfil-item" @click="mobileView = 'brand'">
              Бренд <i class="fa-solid fa-chevron-right mfil-arrow"></i>
            </div>
            <div class="mfil-item" @click="mobileView = 'photo'">
              Фото <i class="fa-solid fa-chevron-right mfil-arrow"></i>
            </div>

            <div
              v-for="(vals, attr) in attributeFilters"
              :key="attr"
              class="mfil-item"
              @click="
                activeMobileAttr = attr;
                mobileView = 'attr';
              "
            >
              {{ attr }} <i class="fa-solid fa-chevron-right mfil-arrow"></i>
            </div>
          </div>

          <div v-if="mobileView === 'brand'" class="mfil-values">
            <label class="filter-checkbox filter-all">
              <input
                type="checkbox"
                :checked="!brandModel.length"
                @change="
                  brandModel = [];
                  applyFilters();
                "
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

          <div v-if="mobileView === 'photo'" class="mfil-values">
            <label class="filter-checkbox">
              <input
                type="radio"
                value="all"
                v-model="photoModel"
                @change="applyFilters"
              />
              <span>Все</span>
            </label>

            <label class="filter-checkbox">
              <input
                type="radio"
                value="with"
                v-model="photoModel"
                @change="applyFilters"
              />
              <span>С фото</span>
            </label>

            <label class="filter-checkbox">
              <input
                type="radio"
                value="without"
                v-model="photoModel"
                @change="applyFilters"
              />
              <span>Без фото</span>
            </label>
          </div>

          <div v-if="mobileView === 'attr'" class="mfil-values">
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
                  v-if="
                    attributeFilters[activeMobileAttr]?.ui_render === 'color'
                  "
                  class="color-dot"
                  :class="{ empty: !v.meta?.color }"
                  :style="v.meta?.color ? { background: v.meta.color } : {}"
                ></span>

                <span class="filter-option-text">{{ v.value }}</span>
              </span>
            </label>
          </div>
        </div>

        <div class="moverlay-footer">
          <button
            class="moverlay-main"
            @click="
              showMobileFilters = false;
              mobileView = 'root';
              activeMobileAttr = null;
            "
          >
            Готово
          </button>

          <button
            class="moverlay-ghost"
            @click="resetAllFilters"
            title="Сбросить все фильтры"
          >
            Сбросить
          </button>
        </div>
      </div>
    </div>

    <!-- ================= MOBILE CATEGORIES PANEL ================= -->
    <div
      v-if="showMobileCats"
      class="moverlay-overlay"
      @click.self="closeMobileCats"
    >
      <div class="moverlay-panel">
        <div class="moverlay-header">
          <button
            class="moverlay-btn moverlay-back"
            :class="{ ghost: !mobileCatsStack.length }"
            :disabled="!mobileCatsStack.length"
            @click="mobileCatsStack.length && backMobileCat()"
            title="Назад"
          >
            <i class="fa-solid fa-arrow-left"></i>
          </button>

          <span class="moverlay-title">{{ mobileCatsTitle }}</span>

          <button
            class="moverlay-btn moverlay-close"
            @click="closeMobileCats"
            title="Закрыть"
          >
            ✕
          </button>
        </div>

        <div class="moverlay-body">
          <div class="mcat-list">
            <div v-for="c in mobileCatsList" :key="c.id" class="mcat-item">
              <!-- ✅ tap чекбокс-зона = выбрать категорию -->
              <button
                class="mcat-check"
                :class="{ on: String(c.code) === String(currentCategory) }"
                @click.stop="pickCategory(c, { close: true })"
                :title="
                  String(c.code) === String(currentCategory)
                    ? 'Активно'
                    : 'Выбрать категорию'
                "
                type="button"
              >
                <i
                  v-if="String(c.code) === String(currentCategory)"
                  class="fa-solid fa-check"
                ></i>
              </button>

              <!-- ✅ tap текст = открыть подкатегории (или выбрать если детей нет) -->
              <div
                class="mcat-name"
                @click="
                  hasChildren(c)
                    ? openMobileCat(c)
                    : pickCategory(c, { close: true })
                "
              >
                {{ c.name }}
              </div>

              <button
                v-if="hasChildren(c)"
                class="mcat-next"
                @click.stop="openMobileCat(c)"
                title="Подкатегории"
              >
                <i class="fa-solid fa-chevron-right"></i>
              </button>
            </div>

            <div v-if="!mobileCatsList.length" class="mcat-empty">
              Нет подкатегорий
            </div>
          </div>
        </div>

        <div class="moverlay-footer">
          <button class="moverlay-main" @click="closeMobileCats">Готово</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  ref,
  computed,
  onMounted,
  watch,
  onBeforeUnmount,
  nextTick,
} from "vue";
import { useRoute, useRouter } from "vue-router";
import ProductCardGallery from "@/components/ProductCardGallery.vue";
import HomeCatalogEntry from "@/components/HomeCatalogEntry.vue";

const colRefs = ref([]);

function setColRef(el, level) {
  if (!el) return;
  colRefs.value[level] = el;
}

function resetColsScroll(fromLevel) {
  // сбрасываем скролл у колонок глубже текущего уровня
  nextTick(() => {
    for (let i = fromLevel + 1; i < colRefs.value.length; i++) {
      const el = colRefs.value[i];
      if (el) el.scrollTop = 0;
    }
  });
}

const route = useRoute();
const router = useRouter();

/* ================= helpers ================= */
const normalize = (s) =>
  String(s || "")
    .toLowerCase()
    .replace(/ё/g, "е")
    .replace(/[^\p{L}\p{N}]+/gu, " ")
    .replace(/\s+/g, " ")
    .trim();

const toArr = (v) =>
  v == null ? [] : Array.isArray(v) ? v.map(String) : [String(v)];
const hasImages = (p) =>
  Array.isArray(p.images) && p.images.filter(Boolean).length > 0;

function getCatCodeOfProduct(p) {
  const v = p?.category_code ?? p?.categoryCode ?? p?.category ?? "";
  return String(v || "");
}

/* ================= STATE ================= */
const loading = ref(true);
const error = ref(null);

const products = ref([]);
const categories = ref([]);

/* ================= URL SOURCE OF TRUTH ================= */
const currentCategory = computed(() => {
  const v = route.query.cat;
  return v ? String(Array.isArray(v) ? v[0] : v) : null;
});
const hasActiveCategory = computed(() => !!currentCategory.value);

const currentCategoryName = computed(() => {
  if (!currentCategory.value) return null;
  const found = categories.value.find(
    (c) => String(c.code) === String(currentCategory.value)
  );
  return found ? found.name : null;
});

/* ===== photo filter ===== */
const photoModel = ref(
  route.query.photo
    ? String(
        Array.isArray(route.query.photo)
          ? route.query.photo[0]
          : route.query.photo
      )
    : "all"
);

const photoHeadText = computed(() => {
  if (photoModel.value === "with") return "С фото";
  if (photoModel.value === "without") return "Без фото";
  return "Все";
});

/* ================= FILTER MODELS ================= */
const brandModel = ref([]);
const priceFromModel = ref(
  route.query.price_from ? Number(route.query.price_from) : null
);
const priceToModel = ref(
  route.query.price_to ? Number(route.query.price_to) : null
);

const searchModel = ref(route.query.q ? String(route.query.q) : "");
const attributeModels = ref({});

/* ================= DATA LOAD (parallel) ================= */
async function loadData() {
  try {
    loading.value = true;

    const [r1, r2] = await Promise.all([
      fetch("/api/admin/product/get_categories_flat.php"),
      fetch("/api/admin/product/get_products.php"),
    ]);

    const rawCats = await r1.json();
    const baseProducts = await r2.json();

categories.value = rawCats.map((c) => {
  const pid = c.parent_id;
  const parent =
    pid === null || pid === undefined || String(pid) === "0" || String(pid) === ""
      ? null
      : String(pid);

  return {
    id: c.id,
    name: c.name,
    code: c.code,
    parent, // ✅ нормализованный
    photo:
      c.photo_url_abs ||
      c.photo_url ||
      (c.photo_categories ? `/photo_categories_vitrina/${c.photo_categories}` : null),
  };
});


    const list = Array.isArray(baseProducts)
      ? baseProducts
      : baseProducts.products || [];

    products.value = (list || []).filter(Boolean).map((p) => {
      let images = [];

      // 1) если бек уже отдаёт images массивом
      if (Array.isArray(p.images)) {
        images = p.images.filter(Boolean);
      } else {
        // 2) иначе пробуем взять из photo (у тебя это строка JSON)
        const ph = p.photo ?? "";
        if (Array.isArray(ph)) {
          images = ph.filter(Boolean);
        } else if (typeof ph === "string" && ph.trim()) {
          try {
            const arr = JSON.parse(ph);
            if (Array.isArray(arr)) images = arr.filter(Boolean);
          } catch {
            // если вдруг там просто строка-путь
            if (ph.startsWith("/photo_product_vitrina/")) images = [ph];
          }
        }
      }

      return {
        ...p,
        images,
        _search: normalize(
          `${p.name || ""} ${p.brand || ""} ${p.article || ""} ${
            p.barcode || ""
          }`
        ),
      };
    });
  } catch (e) {
    error.value = e.message || "Ошибка загрузки";
  } finally {
    loading.value = false;
  }
}

function colKey(level) {
  if (level === 0) return "col-0";
  const parent = hoverPath.value[level - 1];
  return `col-${level}-${parent?.id ?? "none"}`;
}


onMounted(loadData);

/* ================= TREE FROM FLAT ================= */
const treeData = computed(() => {
  const byId = new Map();
  categories.value.forEach((c) =>
    byId.set(String(c.id), { ...c, children: [] })
  );

  const roots = [];
  categories.value.forEach((c) => {
    const n = byId.get(String(c.id));
    if (!n) return;
    if (!c.parent) roots.push(n);
    else byId.get(String(c.parent))?.children.push(n);
  });

  const sortNode = (n) => {
    n.children.sort((a, b) =>
      a.name.localeCompare(b.name, "ru", { sensitivity: "base" })
    );
    n.children.forEach(sortNode);
  };
  roots.sort((a, b) =>
    a.name.localeCompare(b.name, "ru", { sensitivity: "base" })
  );
  roots.forEach(sortNode);

  const byCode = new Map();
  for (const n of byId.values()) byCode.set(String(n.code), n);

  return { roots, byId, byCode };
});

const topCats = computed(() => treeData.value.roots);

/* ================= MOBILE detect ================= */
const isMobile = ref(false);
const handleResize = () => (isMobile.value = window.innerWidth < 1024);

onMounted(() => {
  handleResize();
  window.addEventListener("resize", handleResize, { passive: true });
});
onBeforeUnmount(() => {
  window.removeEventListener("resize", handleResize);
  unlockBody();
});

const searchLoading = ref(false);
const searchHits = ref([]);

const productsById = computed(() => {
  const m = new Map();
  products.value.forEach((p) => m.set(String(p.id), p));
  return m;
});

async function runServerSearch(q) {
  const s = String(q || "").trim();
  if (!s) {
    searchHits.value = [];
    return;
  }

  const norm = normalize(s);
  const isDigits = /^\d{5,}$/.test(norm);

  if (!isDigits && norm.length < 2) {
    searchHits.value = [];
    return;
  }

  searchLoading.value = true;
  try {
    const r = await fetch(
      `/api/admin/product/search_products.php?q=${encodeURIComponent(
        s
      )}&limit=30`,
      {
        headers: { Accept: "application/json" },
      }
    );
    const data = await r.json();
    const list = Array.isArray(data) ? data : data.products || data.items || [];
    searchHits.value = (list || []).filter(Boolean).slice(0, 30);
  } catch {
    searchHits.value = [];
  } finally {
    searchLoading.value = false;
  }
}

/* ===== debounce for search ===== */
let searchTimer = null;
const syncingFromRoute = ref(false);

function onSearchInput() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(async () => {
    if (syncingFromRoute.value) return;
    await runServerSearch(searchModel.value);
    applyFilters();
  }, 220);
}
onBeforeUnmount(() => clearTimeout(searchTimer));

async function clearSearch() {
  searchModel.value = "";
  await runServerSearch("");
  applyFilters();
}

/* ================= CATEGORY PICKER (DESKTOP CLICK + MOBILE PANEL) ================= */
const showCatPopover = ref(false);
const catPickRef = ref(null);
const catPopRef = ref(null);
const searchBoxRef = ref(null);

/* desktop hover columns */
const hoverPath = ref([]);

function hydrateHoverPathFromActive() {
  const code = currentCategory.value;
  if (!code) {
    hoverPath.value = [];
    return;
  }
  const node = treeData.value.byCode.get(String(code));
  if (!node) {
    hoverPath.value = [];
    return;
  }

  const path = [];
  let cur = node;
  while (cur) {
    path.unshift(cur);
    if (!cur.parent) break;
    cur = treeData.value.byId.get(String(cur.parent));
    if (!cur) break;
  }
  hoverPath.value = path;
}

function desktopHover(level, node) {
  const next = hoverPath.value.slice(0, level);
  next[level] = node;
  hoverPath.value = next;

  // ✅ сбросить скролл у "следующих" колонок
  resetColsScroll(level);
}

const desktopColumns = computed(() => {
  const cols = [];
  cols.push(topCats.value || []);
  for (let i = 0; i < hoverPath.value.length; i++) {
    const n = hoverPath.value[i];
    if (n?.children?.length) cols.push(n.children);
    else break;
  }
  return cols.slice(0, 6);
});

function isCatActive(n) {
  if (!currentCategory.value) return false;
  return String(currentCategory.value).startsWith(String(n.code));
}
function isCatPicked(n) {
  if (!currentCategory.value) return false;
  return String(currentCategory.value) === String(n.code);
}

function openCatPopover() {
  showCatPopover.value = true;
  hydrateHoverPathFromActive();

  nextTick(() => {
    colRefs.value.forEach((el) => el && (el.scrollTop = 0));
  });
}

function closeCatPopover() {
  showCatPopover.value = false;
}
function toggleCatPopover() {
  if (isMobile.value) {
    openMobileCats();
    return;
  }
  showCatPopover.value ? closeCatPopover() : openCatPopover();
}
function pickCategoryFromGrid(cat) {
  pickCategory(cat, { close: true });
}

function pickCategory(nodeOrCat, { close = false } = {}) {
  const code = String(nodeOrCat?.code || "");
  if (!code) return;

  // при выборе категории — фильтры сбрасываем, поиск оставляем
  resetAllFilters({ keepSearch: true, silent: true });

  const qRaw = String(searchModel.value || "").trim();

  router.push({
    path: "/catalogv2",
    query: {
      cat: code,
      q: qRaw || undefined,
    },
  });

  if (!isMobile.value) closeCatPopover();
  if (isMobile.value && close) closeMobileCats();
}

/* close popover on outside click (desktop) */
function onDocDown(e) {
  if (isMobile.value) return;
  if (!showCatPopover.value) return;

  const t = e.target;
  const box = searchBoxRef.value;
  const pop = catPopRef.value;
  const btn = catPickRef.value;

  const inside =
    (box && box.contains(t)) ||
    (pop && pop.contains(t)) ||
    (btn && btn.contains(t));

  if (!inside) closeCatPopover();
}

onMounted(() =>
  document.addEventListener("mousedown", onDocDown, { passive: true })
);
onBeforeUnmount(() => document.removeEventListener("mousedown", onDocDown));

/* ================= MOBILE CATS (DRILLDOWN) ================= */
const showMobileCats = ref(false);
const mobileCatsParent = ref(null);
const mobileCatsStack = ref([]);

const childrenByParent = computed(() => {
  const map = {};
  categories.value.forEach((c) => {
    const parentKey = c.parent ? String(c.parent) : "root";
    (map[parentKey] ||= []).push(c);
  });
  Object.keys(map).forEach((k) =>
    map[k].sort((a, b) =>
      a.name.localeCompare(b.name, "ru", { sensitivity: "base" })
    )
  );
  return map;
});

const catsById = computed(() => {
  const m = new Map();
  categories.value.forEach((c) => m.set(String(c.id), c));
  return m;
});

const mobileCatsTitle = computed(() => {
  if (!mobileCatsParent.value) return "Категории";
  const node = catsById.value.get(String(mobileCatsParent.value));
  return node?.name || "Категории";
});

const mobileCatsList = computed(() => {
  const key = mobileCatsParent.value ? String(mobileCatsParent.value) : "root";
  return childrenByParent.value[key] || [];
});

function hasChildren(cat) {
  return (childrenByParent.value[String(cat.id)] || []).length > 0;
}

function openMobileCats() {
  showMobileCats.value = true;
  mobileCatsStack.value = [];
  mobileCatsParent.value = null;
}

function closeMobileCats() {
  showMobileCats.value = false;
  mobileCatsParent.value = null;
  mobileCatsStack.value = [];
}

function openMobileCat(cat) {
  mobileCatsStack.value.push(mobileCatsParent.value);
  mobileCatsParent.value = String(cat.id);
}

function backMobileCat() {
  const prev = mobileCatsStack.value.pop();
  mobileCatsParent.value = prev ?? null;
}

/* ================= BRANDS / ATTRS / FILTERS ================= */
const categoryProducts = computed(() => {
  if (!currentCategory.value) return [];
  const pref = String(currentCategory.value);
  return products.value.filter((p) => getCatCodeOfProduct(p).startsWith(pref));
});

const brands = computed(() => {
  const set = new Set();
  categoryProducts.value.forEach((p) => p.brand && set.add(p.brand));
  return Array.from(set).sort((a, b) =>
    a.localeCompare(b, "ru", { sensitivity: "base" })
  );
});

const attributeFilters = computed(() => {
  const temp = {};

  categoryProducts.value.forEach((p) => {
    (p.attributes || []).forEach((a) => {
      if (!a?.name || !a?.value) return;

      if (!temp[a.name])
        temp[a.name] = { ui_render: a.ui_render || "text", map: new Map() };
      if (a.ui_render === "color") temp[a.name].ui_render = "color";

      let metaObj = a.meta ?? null;
      if (typeof metaObj === "string") {
        try {
          metaObj = JSON.parse(metaObj);
        } catch {
          metaObj = null;
        }
      }

      const existed = temp[a.name].map.get(a.value);
      if (!existed)
        temp[a.name].map.set(a.value, { value: a.value, meta: metaObj });
      else if (!existed.meta?.color && metaObj?.color) existed.meta = metaObj;
    });
  });

  const res = {};
  for (const k in temp) {
    res[k] = {
      ui_render: temp[k].ui_render,
      values: Array.from(temp[k].map.values()).sort((x, y) =>
        String(x.value).localeCompare(String(y.value), "ru", {
          sensitivity: "base",
        })
      ),
    };
  }
  return res;
});

const openFilters = ref({});
watch(
  attributeFilters,
  () => {
    const nextAttrs = { ...attributeModels.value };
    Object.keys(attributeFilters.value).forEach((k) => {
      if (!Array.isArray(nextAttrs[k])) nextAttrs[k] = [];
    });
    attributeModels.value = nextAttrs;

    const nextOpen = {
      brand: openFilters.value.brand ?? false,
      photo: openFilters.value.photo ?? false,
    };
    Object.keys(attributeFilters.value).forEach(
      (k) => (nextOpen[k] = openFilters.value[k] ?? false)
    );
    openFilters.value = nextOpen;
  },
  { immediate: true }
);

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

function toggleFilter(key) {
  const next = {};
  Object.keys(openFilters.value).forEach((k) => (next[k] = false));
  if (!openFilters.value[key]) next[key] = true;
  openFilters.value = next;
}

/* ================= APPLY FILTERS (router.replace) ================= */
function applyFilters() {
  if (syncingFromRoute.value) return;

  const qRaw = String(searchModel.value || "").trim();

  // если категории нет — пишем только q
  if (!hasActiveCategory.value) {
    router.replace({ query: { q: qRaw || undefined } });
    return;
  }

  const query = {
    cat: currentCategory.value || undefined,
    q: qRaw || undefined,
    brand: brandModel.value.length ? brandModel.value : undefined,
    price_from:
      priceFromModel.value !== null ? priceFromModel.value : undefined,
    photo: photoModel.value !== "all" ? photoModel.value : undefined,
    price_to: priceToModel.value !== null ? priceToModel.value : undefined,
  };

  for (const [k, v] of Object.entries(attributeModels.value)) {
    if (Array.isArray(v) && v.length) query[`attr_${k}`] = v;
  }

  router.replace({ query });
}

/* ================= URL → MODELS ================= */
watch(
  () => route.query,
  async (q) => {
    syncingFromRoute.value = true;

    searchModel.value = q.q ? String(Array.isArray(q.q) ? q.q[0] : q.q) : "";
    brandModel.value = toArr(q.brand);

    priceFromModel.value =
      q.price_from != null && q.price_from !== ""
        ? Number(Array.isArray(q.price_from) ? q.price_from[0] : q.price_from)
        : null;

    priceToModel.value =
      q.price_to != null && q.price_to !== ""
        ? Number(Array.isArray(q.price_to) ? q.price_to[0] : q.price_to)
        : null;

    const nextAttrs = { ...attributeModels.value };
    Object.keys(q).forEach((key) => {
      if (!key.startsWith("attr_")) return;
      const name = key.slice(5);
      nextAttrs[name] = toArr(q[key]);
    });
    attributeModels.value = nextAttrs;

    photoModel.value = q.photo
      ? String(Array.isArray(q.photo) ? q.photo[0] : q.photo)
      : "all";

    syncingFromRoute.value = false;

    await runServerSearch(searchModel.value);
  },
  { immediate: true }
);

/* ================= reset filters helper ================= */
const showMobileFilters = ref(false);
const mobileView = ref("root");
const activeMobileAttr = ref(null);

function resetAllFilters({ keepSearch = false, silent = false } = {}) {
  brandModel.value = [];
  priceFromModel.value = null;
  priceToModel.value = null;
  photoModel.value = "all";

  if (!keepSearch) searchModel.value = "";

  const next = { ...attributeModels.value };
  Object.keys(next).forEach((k) => (next[k] = []));
  attributeModels.value = next;

  openFilters.value = {};
  showMobileFilters.value = false;
  mobileView.value = "root";
  activeMobileAttr.value = null;

  if (!silent) applyFilters();
}

watch(currentCategory, () => {
  resetAllFilters({ keepSearch: true, silent: true });
  applyFilters();
});

/* ================= FINAL PRODUCTS ================= */
const mergedSearchProducts = computed(() => {
  if (!searchHits.value.length) return [];

  const map = productsById.value;

  return searchHits.value
    .map((hit) => {
      const full = map.get(String(hit.id));

      const hitImages = Array.isArray(hit.images)
        ? hit.images.filter(Boolean)
        : [];
      const hitThumb = hit.thumb ? [hit.thumb] : [];
      const fromHit = hitImages.length ? hitImages : hitThumb;

      if (full) {
        const images = hasImages(full) ? full.images : fromHit;
        return {
          ...full,
          name: full.name ?? hit.name,
          brand: full.brand ?? hit.brand,
          price: full.price ?? hit.price,
          barcode: full.barcode ?? hit.barcode,
          images,
        };
      }

      return {
        id: hit.id,
        name: hit.name,
        price: hit.price,
        brand: hit.brand,
        barcode: hit.barcode,
        images: fromHit,
        attributes: [],
        category_code: "",
        _search: normalize(
          `${hit.name || ""} ${hit.brand || ""} ${hit.barcode || ""}`
        ),
      };
    })
    .filter((p) => p?.id != null && p?.name);
});

const filteredProducts = computed(() => {
  let list = [];

  const qRaw = String(searchModel.value || "").trim();
  const hasQ = !!qRaw;

  if (hasQ) list = mergedSearchProducts.value;
  else if (hasActiveCategory.value) list = categoryProducts.value;
  else list = [];

  // если и cat и q — пересечение по категории
  if (hasActiveCategory.value) {
    const pref = String(currentCategory.value);
    list = list.filter((p) => getCatCodeOfProduct(p).startsWith(pref));
  }

  if (brandModel.value.length)
    list = list.filter((p) => brandModel.value.includes(p.brand));

  if (priceFromModel.value !== null)
    list = list.filter((p) => Number(p.price) >= priceFromModel.value);
  if (priceToModel.value !== null)
    list = list.filter((p) => Number(p.price) <= priceToModel.value);

  if (photoModel.value === "with") list = list.filter((p) => hasImages(p));
  else if (photoModel.value === "without")
    list = list.filter((p) => !hasImages(p));

  for (const [k, arr] of Object.entries(attributeModels.value)) {
    if (!Array.isArray(arr) || !arr.length) continue;
    list = list.filter((p) =>
      p.attributes?.some((a) => a.name === k && arr.includes(a.value))
    );
  }

  return list;
});

/* ================= pagination ================= */
const step = computed(() => (isMobile.value ? 10 : 24));
const displayLimit = ref(step.value);

watch(
  () => [isMobile.value, currentCategory.value, route.fullPath],
  () => {
    displayLimit.value = step.value;
  }
);

const visibleProducts = computed(() =>
  filteredProducts.value.slice(0, displayLimit.value)
);
const canLoadMore = computed(
  () => filteredProducts.value.length > displayLimit.value
);

function loadMore() {
  displayLimit.value += step.value;
}

/* ================= navigation ================= */
function openProduct(p) {
  router.push({ name: "product", params: { id: p.id } });
}

/* ================= BODY LOCK (mobile overlays) ================= */
let savedScrollY = 0;

function lockBody() {
  savedScrollY = window.scrollY || 0;
  document.body.style.position = "fixed";
  document.body.style.top = `-${savedScrollY}px`;
  document.body.style.left = "0";
  document.body.style.right = "0";
  document.body.style.width = "100%";
  document.body.style.overflow = "hidden";
  document.body.style.touchAction = "none";
}
function unlockBody() {
  document.body.style.position = "";
  document.body.style.top = "";
  document.body.style.left = "";
  document.body.style.right = "";
  document.body.style.width = "";
  document.body.style.overflow = "";
  document.body.style.touchAction = "";
  window.scrollTo(0, savedScrollY);
}

watch([showMobileFilters, showMobileCats], ([f, c]) => {
  const open = f || c;
  if (open) lockBody();
  else unlockBody();
});
</script>

<style scoped>
/* ====== shared small ui ====== */
.color-dot {
  width: 12px;
  height: 12px;
  border-radius: 999px;
  border: 1px solid #d1d5db;
  display: inline-block;
  flex: 0 0 12px;
}
.color-dot.empty {
  background: repeating-linear-gradient(
    45deg,
    #f3f4f6,
    #f3f4f6 3px,
    #e5e7eb 3px,
    #e5e7eb 6px
  );
}
.filter-option {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

/* ========================= PAGE ========================= */
.catalog-page {
  display: flex;
  min-height: 100vh;
  background: var(--bg-main);
  color: var(--text-main);
  font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
}

/* ========================= MAIN ========================= */
.catalog-content {
  flex: 1;
  padding: 26px 30px;
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.catalog-top {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

/* ========================= SEARCH ========================= */
.catalog-search {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  position: relative;
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
  position: relative;
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
  min-width: 0;
}

/* ✅ category button */
.catpick-wrap {
  position: relative;
  display: inline-flex;
  align-items: center;
  flex: 0 0 auto;
}

.catpick-btn {
  width: 34px;
  height: 34px;
  border-radius: 999px;
  border: 1px solid var(--border-soft);
  background: #fff;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
}
.catpick-btn:hover {
  background: rgba(4, 0, 255, 0.05);
  box-shadow: var(--shadow-sm);
  transform: translateY(-1px);
}
.catpick-btn i {
  color: var(--accent);
  font-size: 14px;
}

/* DESKTOP popover */
/* ================= DESKTOP POPUP CATEGORIES (BEAUTY) ================= */

/* кнопка категорий когда открыто */
.catpick-btn.on {
  background: rgba(4, 0, 255, 0.08);
  border-color: rgba(4, 0, 255, 0.22);
  box-shadow: 0 10px 26px rgba(4, 0, 255, 0.1);
}

/* сам поповер — центрируем под поиском */
.catpop {
  position: absolute;
  top: calc(100% + 12px);
  left: 50%;
  transform: translateX(-50%);
  width: min(980px, calc(100vw - 24px));
  max-height: min(560px, 70vh);

  border-radius: 20px;
  border: 1px solid rgba(15, 23, 42, 0.1);
  background: rgba(255, 255, 255, 0.92);

  box-shadow: 0 30px 80px rgba(2, 6, 23, 0.18);
  overflow: hidden;
  z-index: 260;

  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);

  animation: catpopIn 0.14s ease-out;
}

@keyframes catpopIn {
  from {
    opacity: 0;
    transform: translateX(-50%) translateY(-6px);
  }
  to {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
  }
}

/* стрелочка */
.catpop::before {
  content: "";
  position: absolute;
  top: -8px;
  left: 84px; /* визуально ближе к кнопке, но не обязательно */
  width: 16px;
  height: 16px;
  background: rgba(255, 255, 255, 0.92);
  border-left: 1px solid rgba(15, 23, 42, 0.1);
  border-top: 1px solid rgba(15, 23, 42, 0.1);
  transform: rotate(45deg);
}

/* шапка — сделать “дороже” и липкой */
.catpop-head {
  position: sticky;
  top: 0;
  z-index: 2;

  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;

  padding: 12px 14px;
  border-bottom: 1px solid rgba(15, 23, 42, 0.1);
  background: linear-gradient(
    180deg,
    rgba(255, 255, 255, 0.96),
    rgba(255, 255, 255, 0.88)
  );
}

.catpop-title {
  font-size: 13px;
  font-weight: 950;
  letter-spacing: 0.02em;
  color: var(--text-main);
}

.catpop-close {
  width: 36px;
  height: 36px;
  border-radius: 12px;
  border: 1px solid rgba(15, 23, 42, 0.1);
  background: #fff;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.12s ease, box-shadow 0.12s ease,
    border-color 0.12s ease;
}
.catpop-close:hover {
  transform: translateY(-1px);
  border-color: rgba(4, 0, 255, 0.22);
  box-shadow: 0 14px 30px rgba(2, 6, 23, 0.12);
}

/* колонки */
.catpop-cols{
  display:flex;
  height: min(520px, 62vh);   /* ✅ фиксируем высоту */
  overflow-x: auto;           /* ✅ горизонталь */
  overflow-y: hidden;         /* ✅ вертикаль не тут */
}




/* красивый скролл */
.catpop-cols::-webkit-scrollbar {
  height: 12px;
  width: 12px;
}
.catpop-cols::-webkit-scrollbar-thumb {
  background: rgba(15, 23, 42, 0.18);
  border-radius: 999px;
  border: 3px solid rgba(255, 255, 255, 0.9);
}
.catpop-cols::-webkit-scrollbar-track {
  background: transparent;
}

.catpop-col {
    height: 100%;               /* ✅ */
  overflow-y: auto;   
  width: 280px;
  flex: 0 0 280px;
  padding: 12px;
  border-right: 1px solid rgba(15, 23, 42, 0.08);
  background: radial-gradient(
      600px 220px at 50% -140px,
      rgba(4, 0, 255, 0.08),
      transparent 60%
    ),
    rgba(255, 255, 255, 0.86);
}
.catpop-col:last-child {
  border-right: none;
}

/* элементы */
.catpop-item {
  width: 100%;
  text-align: left;
  border: 1px solid transparent;
  background: transparent;
  cursor: pointer;
  border-radius: 14px;
  padding: 10px 10px;
  margin-bottom: 5px;
  display: flex;
  align-items: center;
  justify-content: space-between;
    margin-bottom: 0; /* убрать */

  gap: 10px;

  color: var(--text-main);
  font-weight: 900;

  transition: background 0.12s ease, border-color 0.12s ease,
    transform 0.12s ease, box-shadow 0.12s ease;
}

.catpop-item:hover {
  background: rgba(4, 0, 255, 0.06);
  border-color: rgba(4, 0, 255, 0.14);
  transform: translateY(-1px);
  box-shadow: 0 10px 22px rgba(2, 6, 23, 0.1);
}

/* активная ветка (prefix) */
.catpop-item.active {
  background: rgba(4, 0, 255, 0.08);
  border-color: rgba(4, 0, 255, 0.18);
}

/* выбранная (exact) */
.catpop-item.picked {
  color: var(--accent);
  background: rgba(4, 0, 255, 0.1);
  border-color: rgba(4, 0, 255, 0.26);
}

/* текст */
.catpop-text {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* стрелка вправо */
.catpop-chev {
  opacity: 0.35;
  font-size: 12px;
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
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.search-clear:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-sm);
}

.search-meta {
  font-size: 12px;
  color: var(--text-muted);
  display: inline-flex;
  align-items: center;
  gap: 8px;
}
.search-meta .dot {
  width: 7px;
  height: 7px;
  border-radius: 999px;
  background: var(--accent);
  opacity: 0.7;
}

/* ========================= HEADING ========================= */
.catalog-heading {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.breadcrumbs {
  font-size: 13px;
  color: var(--text-muted);
}
.breadcrumb-separator {
  margin: 0 6px;
}
.breadcrumb-current {
  color: var(--text-main);
  font-weight: 600;
}
.catalog-title {
  font-size: 28px;
  font-weight: 900;
  color: var(--text-main);
}

/* ========================= FILTERS DESKTOP ========================= */
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

.filter-price {
  grid-column: span 2;
}

.filter-block {
  min-width: 180px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  background: #fff;
  border-radius: 14px;
  padding: 10px 12px;
  border: 1px solid #e4e7ef;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
  transition: box-shadow 0.2s ease, transform 0.2s ease;
  position: relative;
  z-index: 1;
}
.filter-block:hover {
  box-shadow: 0 10px 26px rgba(0, 0, 0, 0.1);
  transform: translateY(-1px);
}
.filter-block.open {
  z-index: 200;
}

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

.price-inputs {
  display: flex;
  gap: 8px;
}
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
  background: linear-gradient(180deg, #fff, #f9faff);
  border: 1px solid #e4e7ef;
}

.filter-dropdown-head {
  padding: 12px 14px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
  font-size: 14px;
  font-weight: 650;
  color: #1b1e28;
  transition: background 0.2s ease;
}
.filter-dropdown-head:hover {
  background: rgba(4, 0, 255, 0.05);
}

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
  background: #fff;
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
.filter-checkbox:hover {
  background: rgba(4, 0, 255, 0.06);
}
.filter-checkbox input {
  accent-color: var(--accent);
  cursor: pointer;
  margin-top: 2px;
}

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
.arrow.open {
  transform: rotate(180deg);
}

/* ========================= MOBILE FILTER BAR ========================= */
.mobile-filter-bar {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 12px;
  width: 100%;
}
.mfb-left {
  flex: 1;
  min-width: 0;
}
.mfb-title {
  font-size: 12px;
  font-weight: 900;
  color: var(--text-muted);
  margin-bottom: 6px;
}
.mobile-price {
  display: flex;
  gap: 8px;
}
.mobile-price input {
  width: 50%;
  padding: 11px 12px;
  border-radius: 12px;
  border: 1px solid var(--border-soft);
  background: #fff;
  box-shadow: var(--shadow-sm);
  font-size: 14px;
}
.mobile-price input:focus {
  outline: none;
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(4, 0, 255, 0.12);
}

.mobile-filter-btn {
  flex: 0 0 auto;
  border: 1px solid var(--border-soft);
  background: var(--bg-panel);
  box-shadow: var(--shadow-sm);
  padding: 12px 14px;
  border-radius: 999px;
  cursor: pointer;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}
.mobile-filter-btn:active {
  transform: scale(0.99);
}

/* ========================= PRODUCTS ========================= */
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
  cursor: default;
  transition: transform 0.18s ease, box-shadow 0.18s ease;
}
.product-card:hover {
  transform: translateY(-3px);
  box-shadow: var(--shadow-md);
}

.product-image {
  border-radius: 14px;
  overflow: hidden;
  border: 1px solid var(--border-soft);
  background: #fff;
  aspect-ratio: 1 / 1;
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 0;
}

.product-image :deep(.pg),
.product-image :deep(.pcg),
.product-image :deep(.swiper),
.product-image :deep(.swiper-wrapper),
.product-image :deep(.swiper-slide) {
  width: 100% !important;
  height: 100% !important;
}
.product-image :deep(.swiper-slide) {
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
}
.product-image :deep(img) {
  width: 100% !important;
  height: 100% !important;
  max-width: 100% !important;
  max-height: 100% !important;
  object-fit: contain !important;
  object-position: center !important;
  display: block !important;
}

.product-card :deep(.swiper-button-next),
.product-card :deep(.swiper-button-prev) {
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.18s ease, transform 0.18s ease;
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
  cursor: pointer;
}

.product-name {
  font-size: 14px;
  line-height: 1.35;
  font-weight: 650;
  color: var(--text-main);
  display: -webkit-box;
  -webkit-line-clamp: 5;
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
  font-weight: 750;
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
}
.product-barcode {
  font-size: 12px;
  color: var(--text-muted);
  background: #f3f4f6;
  border: 1px solid #e5e7eb;
  padding: 6px 10px;
  border-radius: 999px;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas,
    "Liberation Mono", "Courier New", monospace;
}

/* EMPTY / LOADER / LOADMORE */
.products-empty {
  background: var(--bg-panel);
  border: 1px dashed #cbd5e1;
  border-radius: var(--radius-lg);
  padding: 40px;
  text-align: center;
}
.empty-title {
  font-size: 18px;
  font-weight: 850;
  margin-bottom: 6px;
}
.empty-text {
  font-size: 14px;
  color: var(--text-muted);
}

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
.loader-text {
  font-size: 14px;
  color: var(--text-muted);
}
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

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
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.load-more-btn:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

/* ========================= MOBILE OVERLAY (filters + cats) ========================= */
.moverlay-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.38);
  z-index: 400;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  padding: 12px;
}

.moverlay-panel {
  width: min(520px, 100%);
  background: rgba(255, 255, 255, 0.98);
  border: 1px solid var(--border-soft);
  border-radius: 18px;
  box-shadow: 0 28px 80px rgba(0, 0, 0, 0.22);
  overflow: hidden;
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
}

.moverlay-header {
  display: grid;
  grid-template-columns: 46px 1fr 46px;
  align-items: center;
  gap: 8px;
  padding: 12px 12px;
  border-bottom: 1px solid var(--border-soft);
  background: rgba(255, 255, 255, 0.92);
}

.moverlay-title {
  text-align: center;
  font-weight: 900;
  font-size: 14px;
  color: var(--text-main);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.moverlay-btn {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  border: 1px solid var(--border-soft);
  background: #fff;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 900;
}
.moverlay-btn.ghost {
  opacity: 0.35;
}

.moverlay-body {
  max-height: min(64vh, 520px);
  overflow: auto;
  padding: 12px;
}

.moverlay-footer {
  display: flex;
  gap: 10px;
  padding: 12px;
  border-top: 1px solid var(--border-soft);
  background: rgba(255, 255, 255, 0.92);
}

.moverlay-main {
  flex: 1;
  border: none;
  border-radius: 14px;
  padding: 12px 14px;
  background: var(--accent);
  color: #fff;
  font-weight: 900;
  cursor: pointer;
}
.moverlay-ghost {
  flex: 0 0 auto;
  border: 1px solid var(--border-soft);
  background: #fff;
  border-radius: 14px;
  padding: 12px 14px;
  font-weight: 900;
  cursor: pointer;
}

/* mobile filters list */
.mfil-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.mfil-item {
  border: 1px solid var(--border-soft);
  background: #fff;
  border-radius: 14px;
  padding: 12px 12px;
  font-weight: 900;
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
}
.mfil-arrow {
  opacity: 0.45;
}
.mfil-values {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

/* mobile cats */
.mcat-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.mcat-item {
  display: grid;
  grid-template-columns: 40px 1fr 40px;
  gap: 10px;
  align-items: center;

  border: 1px solid var(--border-soft);
  background: #fff;
  border-radius: 14px;
  padding: 10px 10px;
}

.mcat-check {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  border: 1px solid var(--border-soft);
  background: #fff;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.mcat-check.on {
  border-color: rgba(4, 0, 255, 0.25);
  background: rgba(4, 0, 255, 0.08);
  color: var(--accent);
}
.mcat-name {
  font-weight: 900;
  color: var(--text-main);
  line-height: 1.2;
  overflow-wrap: anywhere;
  cursor: pointer;
}
.mcat-next {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  border: 1px solid var(--border-soft);
  background: #fff;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  opacity: 0.8;
}
.mcat-empty {
  padding: 14px 8px;
  text-align: center;
  color: var(--text-muted);
  font-weight: 900;
}
.categories-landing {
  width: 100%;
  display: flex;
  justify-content: center;
}

/* убираем лишние отступы у HomeCatalogEntry внутри CatalogV2 */
.categories-landing :deep(.home-entry) {
  width: min(1120px, 100%);
  padding: 0;
  margin: 0;
}

/* ================= MOBILE ================= */
@media (max-width: 1024px) {
  .products-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
  }
}
@media (max-width: 768px) {
  .catalog-content {
    padding: 16px;
    padding-bottom: 90px;
  }
  .products-grid {
    grid-template-columns: 1fr;
  }
  .catalog-title {
    font-size: 24px;
  }
  .product-price {
    font-size: 17px;
  }
}

/* disable selection только на элементах фильтра (desktop) */
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
