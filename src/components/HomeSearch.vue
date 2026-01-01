<!-- src/components/HomeSearch.vue -->
<template>
  <div class="search-wrap">
    <div class="search-box" ref="searchBoxRef">
      <Fa class="search-icon" :icon="['fas', 'magnifying-glass']" />

      <!-- ✅ CATALOG BUTTON INSIDE SEARCH (ONLY /catalog) -->
      <div v-if="showCatalogButton" class="catpick-wrap" ref="catPickRef">
        <button
          class="catpick-btn"
          :class="{ on: showCatPopover && !isMobile }"
          :disabled="!catsList.length"
          type="button"
          title="Каталог"
          aria-label="Каталог"
          @click.prevent="toggleCatPopover"
        >
          <span class="catpick-text">Каталог</span>
          <Fa class="catpick-ico" :icon="['fas', 'bars-staggered']" />
        </button>
      </div>

      <!-- ✅ DESKTOP POPOVER CATEGORIES -->
      <div
        v-if="
          showCatalogButton && catsList.length && showCatPopover && !isMobile
        "
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
            <Fa :icon="['fas', 'xmark']" />
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
              <Fa
                v-if="n.children?.length"
                class="catpop-chev"
                :icon="['fas', 'chevron-right']"
              />
            </button>
          </div>
        </div>
      </div>

      <!-- ✅ INPUT -->
      <input
        v-model="q"
        class="search-input"
        type="text"
        :placeholder="placeholder"
        @input="onInput"
        @keydown.down.prevent="move(1)"
        @keydown.up.prevent="move(-1)"
        @keydown.enter.prevent="enterPick"
        @focus="q && (openDd = true)"
        @blur="onBlur"
      />

      <!-- ✅ SCAN BARCODE -->
      <button
        v-if="canScan"
        class="search-scan"
        @click="openScanner"
        title="Сканировать штрихкод"
        type="button"
        aria-label="Сканировать штрихкод"
      >
        <Fa :icon="['fas', 'expand']" />
      </button>

      <!-- ✅ CLEAR -->
      <button
        v-if="q"
        class="search-clear"
        @click="clear"
        title="Очистить"
        type="button"
        aria-label="Очистить"
      >
        <Fa :icon="['fas', 'xmark']" />
      </button>
    </div>

    <!-- ✅ SEARCH DROPDOWN -->
    <div v-if="openDd && qTrim && !loadingSearch && !showCatPopover" class="dd">
      <div class="dd-list">
        <div v-if="!results.length" class="dd-empty">Ничего не найдено</div>

        <button
          v-for="(r, idx) in results"
          :key="r.id"
          class="dd-item"
          :class="{ active: idx === activeIdx }"
          @mousedown.prevent="goProduct(r)"
          :ref="(el) => (itemRefs[idx] = el)"
          type="button"
        >
          <div class="dd-thumb-wrap">
            <img
              v-if="thumbUrl(r) && !thumbErr[r.id]"
              class="dd-thumb"
              :src="thumbUrl(r)"
              :alt="r.name"
              loading="lazy"
              @error="thumbErr[r.id] = true"
            />
            <div v-else class="dd-thumb dd-thumb-ph" aria-hidden="true">
              <Fa :icon="['far', 'image']" />
            </div>
          </div>

          <div class="dd-main">
            <div class="dd-title">{{ r.name }}</div>
            <div class="dd-sub">
              <span v-if="r.brand" class="dd-pill">{{ r.brand }}</span>
              <span v-if="r.price != null" class="dd-price"
                >{{ r.price }} ₽</span
              >
              <span v-if="r.barcode" class="dd-code">{{ r.barcode }}</span>
            </div>
          </div>

          <Fa
            aria-hidden="true"
            class="dd-arrow"
            :icon="['fas', 'chevron-right']"
          />
        </button>
      </div>

      <button class="dd-all" @mousedown.prevent="goAllResults" type="button">
        Показать все результаты в каталоге →
      </button>
    </div>

    <div v-if="loadingSearch" class="search-hint">
      <span class="dot"></span> Поиск…
    </div>

    <!-- ✅ MOBILE CATEGORIES PANEL -->
    <div
      v-if="showCatalogButton && catsList.length && showMobileCats"
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
            <Fa :icon="['fas', 'arrow-left']" />
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
              <button
                class="mcat-check"
                :class="{
                  on: String(c.code) === String(currentCategory || ''),
                }"
                @click.stop="pickCategory(c, { close: true })"
                :title="
                  String(c.code) === String(currentCategory || '')
                    ? 'Активно'
                    : 'Выбрать категорию'
                "
                type="button"
              >
                <Fa
                  v-if="String(c.code) === String(currentCategory || '')"
                  :icon="['fas', 'check']"
                />
              </button>

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
                <Fa :icon="['fas', 'chevron-right']" />
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

    <!-- ✅ TOAST -->
    <transition name="toast">
      <div v-if="toastShow" class="toast" :class="toastKind">
        {{ toastText }}
      </div>
    </transition>

    <!-- ✅ SCANNER OVERLAY -->
    <div v-if="showScanner" class="scan-overlay" @click.self="closeScanner">
      <div class="scan-panel" :class="scanPanelClass">
        <div class="scan-head">
          <div class="scan-title">Сканирование штрих-кода</div>

          <button
            class="scan-close"
            type="button"
            @click="closeScanner"
            title="Закрыть"
          >
            <Fa :icon="['fas', 'xmark']" />
          </button>
        </div>

        <div class="scan-video-wrap">
<video
  ref="scanVideoRef"
  class="scan-video"
  :class="{ flip: camFlip }"
  autoplay
  playsinline
  muted
></video>

          <!-- ✅ TORCH BUTTON (OVER VIDEO) -->
          <button
            v-if="torchSupported"
            class="scan-torch"
            :class="{ on: torchOn }"
            type="button"
            @click.stop="toggleTorch"
            :title="torchOn ? 'Выключить фонарь' : 'Включить фонарь'"
            :aria-label="torchOn ? 'Выключить фонарь' : 'Включить фонарь'"
          >
            <Fa :icon="['fas', 'bolt']" />
          </button>
          <!-- ✅ SWITCH CAMERA BUTTON (BOTTOM RIGHT) -->
<button
  class="scan-switch"
  type="button"
  @click.stop="switchCameraAnimated"
  title="Сменить камеру"
  aria-label="Сменить камеру"
>
  <Fa :icon="['fas','camera-rotate']" />
</button>

          <!-- рамка/анимация -->
          <div
            class="scan-frame"
            :class="{ bad: scanBad, ok: scanState === 'found' }"
            aria-hidden="true"
          >
            <div class="scan-corner tl"></div>
            <div class="scan-corner tr"></div>
            <div class="scan-corner bl"></div>
            <div class="scan-corner br"></div>
            <div class="scan-line"></div>
          </div>

          <!-- UI card -->
          <div class="scan-ui">
            <div class="scan-badge" :class="scanBadgeClass">
              <span class="scan-bdot"></span>
              <span class="scan-btxt">{{ scanBadgeText }}</span>
            </div>

            <div
              class="scan-msg"
              :class="{ bad: scanBad || scanState === 'error' }"
            >
              <div class="scan-msg-top">
                <div class="scan-msg-title">{{ scanTitleText }}</div>
                <div v-if="scanCode" class="scan-code">{{ scanCode }}</div>
              </div>

              <div class="scan-msg-sub">{{ scanMessage }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  ref,
  computed,
  watch,
  nextTick,
  onMounted,
  onBeforeUnmount,
} from "vue";
import { useRoute, useRouter } from "vue-router";

const route = useRoute();
const router = useRouter();

/* ================== SHARED CATEGORIES CACHE (MODULE SCOPE) ================== */
const CATS_URL = "/api/admin/product/get_categories_flat.php";
let _catsCache = null;
let _catsPromise = null;

function normalizeCat(c) {
  const pid = c?.parent_id ?? c?.parent ?? null;
  const parent =
    pid === null ||
    pid === undefined ||
    String(pid) === "0" ||
    String(pid) === ""
      ? null
      : String(pid);

  return {
    id: c.id,
    name: c.name,
    slug: c.slug ?? null,
    code: c.code,
    parent,
    photo:
      c.photo_url_abs ||
      c.photo_url ||
      c.photo ||
      (c.photo_categories
        ? `/photo_categories_vitrina/${c.photo_categories}`
        : null),
  };
}

async function fetchCategoriesOnce() {
  if (_catsCache) return _catsCache;
  if (_catsPromise) return _catsPromise;

  _catsPromise = fetch(CATS_URL, { headers: { Accept: "application/json" } })
    .then((r) => r.json())
    .then((data) => {
      const list = Array.isArray(data)
        ? data
        : data.categories || data.items || data.data || [];
      _catsCache = (list || []).filter(Boolean).map(normalizeCat);
      return _catsCache;
    })
    .catch(() => {
      _catsCache = [];
      return _catsCache;
    })
    .finally(() => {
      _catsPromise = null;
    });

  return _catsPromise;
}

/* ================== PROPS / EMITS ================== */
const props = defineProps({
  showCategory: { type: Boolean, default: false },
  categories: { type: Array, default: () => [] },
  currentCategory: { type: [String, Number, null], default: null },

  syncRoute: { type: Boolean, default: false },
  routeKey: { type: String, default: "q" },

  catalogPath: { type: String, default: "/catalog" },

  placeholder: {
    type: String,
    default: "Поиск по названию / бренду / штрихкоду…",
  },

  dropdownLimit: { type: Number, default: 12 },
  serverLimit: { type: Number, default: 30 },
});

const emit = defineEmits([
  "search-hits",
  "categories-loaded",
  "categories-loading",
]);

/* ================== SHOW BUTTON ONLY ON /catalog ================== */
const showCatalogButton = computed(() => {
  if (!props.showCategory) return false;
  return route.path === "/catalog" || route.path.startsWith("/catalog/");
});

/* ================== LOCAL CATS (WHEN NOT PASSED) ================== */
const localCats = ref([]);
const localCatsLoading = ref(false);

const catsList = computed(() => {
  const fromProps = Array.isArray(props.categories) ? props.categories : [];
  return fromProps.length ? fromProps : localCats.value;
});

const currentCategorySlug = computed(() => {
  const code = props.currentCategory;
  if (code == null || String(code) === "") return "";
  const found = (catsList.value || []).find(
    (c) => String(c.code) === String(code)
  );
  return found?.slug ? String(found.slug) : String(code);
});

async function ensureCategories() {
  if (!showCatalogButton.value) return;

  const fromProps = Array.isArray(props.categories) ? props.categories : [];
  if (fromProps.length) {
    emit("categories-loaded", fromProps);
    emit("categories-loading", false);
    return;
  }

  localCatsLoading.value = true;
  emit("categories-loading", true);

  localCats.value = await fetchCategoriesOnce();

  localCatsLoading.value = false;
  emit("categories-loading", false);
  emit("categories-loaded", localCats.value);
}

watch(
  () => showCatalogButton.value,
  () => ensureCategories(),
  { immediate: true }
);

watch(
  () => props.categories,
  (val) => {
    const arr = Array.isArray(val) ? val : [];
    if (arr.length) emit("categories-loaded", arr);
  },
  { immediate: true }
);

/* ================= SEARCH ================= */
const q = ref("");
const openDd = ref(false);
const results = ref([]);
const allHits = ref([]);
const loadingSearch = ref(false);
const activeIdx = ref(-1);
const itemRefs = ref([]);
const thumbErr = ref({});
const camFlip = ref(false);

function pulseCamFlip() {
  camFlip.value = true;
  setTimeout(() => (camFlip.value = false), 260);
}

async function switchCameraAnimated() {
  pulseCamFlip();
  await switchCamera();
}

const qTrim = computed(() => String(q.value || "").trim());

function thumbUrl(r) {
  if (r?.thumb) return r.thumb;
  if (r?.image) return r.image;
  if (Array.isArray(r?.images) && r.images[0]) return r.images[0];
  return "";
}

const normalize = (s) =>
  String(s || "")
    .toLowerCase()
    .replace(/ё/g, "е")
    .replace(/[^\p{L}\p{N}]+/gu, " ")
    .replace(/\s+/g, " ")
    .trim();

function canSearch(text) {
  const norm = normalize(text);
  const isDigits = /^\d{5,}$/.test(norm);
  if (isDigits) return true;
  return norm.length >= 2;
}

let t = null;
let ac = null;

function setRouteQ(nextQ) {
  const next = { ...route.query };
  if (nextQ) next[props.routeKey] = nextQ;
  else delete next[props.routeKey];
  router.replace({ path: route.path, query: next });
}

function onInput() {
  clearTimeout(t);

  closeCatPopover();
  closeMobileCats();

  const s = qTrim.value;
  if (!s) {
    openDd.value = false;
    results.value = [];
    allHits.value = [];
    activeIdx.value = -1;
    itemRefs.value = [];
    emit("search-hits", []);
    if (props.syncRoute) setRouteQ("");
    return;
  }

  openDd.value = true;
  t = setTimeout(() => {
    if (props.syncRoute) setRouteQ(s);
    else doSearch(s);
  }, 220);
}

watch(activeIdx, async () => {
  if (!openDd.value) return;
  await nextTick();
  const el = itemRefs.value?.[activeIdx.value];
  if (el && typeof el.scrollIntoView === "function") {
    el.scrollIntoView({ block: "nearest" });
  }
});

function readRouteQ() {
  const v = route.query?.[props.routeKey];
  return v ? String(Array.isArray(v) ? v[0] : v) : "";
}

watch(
  () => (props.syncRoute ? readRouteQ() : null),
  (val) => {
    if (!props.syncRoute) return;

    q.value = String(val || "");
    const s = String(val || "").trim();

    if (!s) {
      openDd.value = false;
      results.value = [];
      allHits.value = [];
      activeIdx.value = -1;
      itemRefs.value = [];
      emit("search-hits", []);
      return;
    }

    if (!canSearch(s)) {
      results.value = [];
      allHits.value = [];
      activeIdx.value = -1;
      itemRefs.value = [];
      emit("search-hits", []);
      return;
    }

    doSearch(s);
  },
  { immediate: true }
);

async function doSearch(text) {
  const s = String(text || "").trim();
  if (!s) return;

  if (!canSearch(s)) {
    results.value = [];
    allHits.value = [];
    activeIdx.value = -1;
    itemRefs.value = [];
    emit("search-hits", []);
    return;
  }

  if (ac) ac.abort();
  ac = new AbortController();

  loadingSearch.value = true;
  try {
    const r = await fetch(
      `/api/admin/product/search_products.php?q=${encodeURIComponent(
        s
      )}&limit=${encodeURIComponent(String(props.serverLimit || 30))}`,
      { headers: { Accept: "application/json" }, signal: ac.signal }
    );

    const data = await r.json();
    const list = Array.isArray(data) ? data : data.products || data.items || [];

    const mapped = (list || [])
      .filter(Boolean)
      .slice(0, Number(props.serverLimit || 30))
      .map((p) => ({
        id: p.id,
        slug: p.slug ?? null,

        name: p.name,
        price: p.price,
        brand: p.brand,
        barcode: p.barcode,

        quantity_value: p.quantity_value ?? null,
        measureName: p.measureName ?? p.measure_name ?? null,

        thumb: p.thumb || p.image || null,
        images: p.images || null,
      }))
      .filter((p) => p.id != null && p.name);

    allHits.value = mapped;
    emit("search-hits", mapped);

    results.value = mapped.slice(0, Number(props.dropdownLimit || 12));
    activeIdx.value = results.value.length ? 0 : -1;

    const nextErr = { ...thumbErr.value };
    mapped.forEach((x) => {
      if (x?.id != null) nextErr[x.id] = false;
    });
    thumbErr.value = nextErr;

    itemRefs.value = [];
  } catch (e) {
    if (e?.name !== "AbortError") {
      results.value = [];
      allHits.value = [];
      activeIdx.value = -1;
      itemRefs.value = [];
      emit("search-hits", []);
    }
  } finally {
    loadingSearch.value = false;
  }
}

function clear() {
  q.value = "";
  results.value = [];
  allHits.value = [];
  openDd.value = false;
  activeIdx.value = -1;
  itemRefs.value = [];
  emit("search-hits", []);
  if (props.syncRoute) setRouteQ("");
}

function onBlur() {
  setTimeout(() => {
    openDd.value = false;
  }, 120);
}

function move(dir) {
  if (!results.value.length) return;
  const n = results.value.length;
  let i = activeIdx.value;
  if (i < 0) i = 0;
  i = (i + dir + n) % n;
  activeIdx.value = i;
}

function enterPick() {
  if (!openDd.value) return;
  const i = activeIdx.value;
  if (i >= 0 && results.value[i]) goProduct(results.value[i]);
  else goAllResults();
}

function goProduct(p) {
  router.push({ name: "product", params: { slug: p.slug || String(p.id) } });
  openDd.value = false;
}

function goAllResults() {
  const s = qTrim.value;
  if (!s) return;

  router.push({
    path: props.catalogPath,
    query: {
      q: s,
      cat: currentCategorySlug.value || undefined,
    },
  });

  openDd.value = false;
}

/* ================= CATEGORIES (DESKTOP + MOBILE) ================= */
const isMobile = ref(false);
const handleResize = () => (isMobile.value = window.innerWidth < 1024);

onMounted(() => {
  handleResize();
  window.addEventListener("resize", handleResize, { passive: true });
});
onBeforeUnmount(() => window.removeEventListener("resize", handleResize));

const showCatPopover = ref(false);
const catPickRef = ref(null);
const catPopRef = ref(null);
const searchBoxRef = ref(null);

const colRefs = ref([]);
function setColRef(el, level) {
  if (!el) return;
  colRefs.value[level] = el;
}
function resetColsScroll(fromLevel) {
  nextTick(() => {
    for (let i = fromLevel + 1; i < colRefs.value.length; i++) {
      const el = colRefs.value[i];
      if (el) el.scrollTop = 0;
    }
  });
}

const treeData = computed(() => {
  const list = catsList.value || [];

  const byId = new Map();
  list.forEach((c) => byId.set(String(c.id), { ...c, children: [] }));

  const roots = [];
  list.forEach((c) => {
    const n = byId.get(String(c.id));
    if (!n) return;
    const parent =
      c.parent == null || String(c.parent) === "" ? null : String(c.parent);
    if (!parent) roots.push(n);
    else byId.get(String(parent))?.children.push(n);
  });

  const sortNode = (n) => {
    n.children.sort((a, b) =>
      String(a.name).localeCompare(String(b.name), "ru", {
        sensitivity: "base",
      })
    );
    n.children.forEach(sortNode);
  };

  roots.sort((a, b) =>
    String(a.name).localeCompare(String(b.name), "ru", { sensitivity: "base" })
  );
  roots.forEach(sortNode);

  const byCode = new Map();
  for (const n of byId.values()) byCode.set(String(n.code), n);

  return { roots, byId, byCode };
});

const topCats = computed(() => treeData.value.roots);
const hoverPath = ref([]);

function hydrateHoverPathFromActive() {
  const code = props.currentCategory;
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
    const parent =
      cur.parent == null || String(cur.parent) === ""
        ? null
        : String(cur.parent);
    if (!parent) break;
    cur = treeData.value.byId.get(String(parent));
    if (!cur) break;
  }
  hoverPath.value = path;
}

function desktopHover(level, node) {
  const next = hoverPath.value.slice(0, level);
  next[level] = node;
  hoverPath.value = next;
  resetColsScroll(level);
}

function colKey(level) {
  if (level === 0) return "col-0";
  const parent = hoverPath.value[level - 1];
  return `col-${level}-${parent?.id ?? "none"}`;
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

const inTree = (cc, code) => {
  cc = String(cc || "");
  code = String(code || "");
  return cc === code || cc.startsWith(code + ".");
};

function isCatActive(n) {
  if (!props.currentCategory) return false;
  return inTree(props.currentCategory, n.code);
}
function isCatPicked(n) {
  if (!props.currentCategory) return false;
  return String(props.currentCategory) === String(n.code);
}

function openCatPopover() {
  showCatPopover.value = true;
  openDd.value = false;

  hydrateHoverPathFromActive();
  nextTick(() => {
    colRefs.value.forEach((el) => el && (el.scrollTop = 0));
  });
}

function closeCatPopover() {
  showCatPopover.value = false;
}

const showMobileCats = ref(false);
const mobileCatsParent = ref(null);
const mobileCatsStack = ref([]);

const childrenByParent = computed(() => {
  const map = {};
  (catsList.value || []).forEach((c) => {
    const parentKey = c.parent ? String(c.parent) : "root";
    (map[parentKey] ||= []).push(c);
  });
  Object.keys(map).forEach((k) =>
    map[k].sort((a, b) =>
      String(a.name).localeCompare(String(b.name), "ru", {
        sensitivity: "base",
      })
    )
  );
  return map;
});

const catsById = computed(() => {
  const m = new Map();
  (catsList.value || []).forEach((c) => m.set(String(c.id), c));
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
  openDd.value = false;
  closeCatPopover();
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

function toggleCatPopover() {
  if (!catsList.value.length) return;

  if (isMobile.value) {
    openMobileCats();
    return;
  }
  showCatPopover.value ? closeCatPopover() : openCatPopover();
}

function pickCategory(nodeOrCat, { close = false } = {}) {
  const slug = String(nodeOrCat?.slug || nodeOrCat?.code || "");
  if (!slug) return;

  const qRaw = qTrim.value;

  router.push({
    path: props.catalogPath,
    query: {
      cat: slug,
      q: qRaw || undefined,
    },
  });

  closeCatPopover();
  if (isMobile.value && close) closeMobileCats();
}

/* close desktop popover on outside click */
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

/* ================= TOAST ================= */
const toastShow = ref(false);
const toastText = ref("");
const toastKind = ref("info");
let toastTimer = null;

function showToast(text, kind = "info", ms = 2400) {
  toastText.value = text;
  toastKind.value = kind;
  toastShow.value = true;
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => (toastShow.value = false), ms);
}

/* ================= BARCODE SCANNER ================= */
const canScan = computed(() => {
  return (
    typeof window !== "undefined" &&
    window.isSecureContext &&
    navigator?.mediaDevices?.getUserMedia
  );
});

const showScanner = ref(false); // ✅ важно: объявлено ДО любых watch где оно используется
const scanVideoRef = ref(null);

const scanState = ref("idle"); // idle | starting | scanning | checking | not_found | found | error
const scanMessage = ref("");
const scanCode = ref("");
const scanBad = ref(false);

const torchSupported = ref(false);
const torchOn = ref(false);
const facingMode = ref("environment"); // environment | user

let zxingReader = null;
let zxingControls = null;
let currentTrack = null;

let scanLock = false;
let lastHandledCode = "";
let lastHandledAt = 0;

const scanBadgeText = computed(() => {
  const s = scanState.value;
  if (s === "starting") return "Подключение камеры…";
  if (s === "scanning") return "Сканер активен";
  if (s === "checking") return "Проверка кода…";
  if (s === "not_found") return "Товар не найден";
  if (s === "found") return "Товар найден";
  if (s === "error") return "Ошибка";
  return "Сканер";
});

const scanTitleText = computed(() => {
  const s = scanState.value;
  if (s === "starting") return "Подготовка сканера";
  if (s === "scanning") return "Наведите штрих-код в рамку";
  if (s === "checking") return "Проверяем код";
  if (s === "not_found") return "Товар не найден";
  if (s === "found") return "Товар найден ✅";
  if (s === "error") return "Не удалось запустить сканер";
  return "Сканирование";
});

const scanBadgeClass = computed(() => {
  const s = scanState.value;
  return {
    starting: s === "starting",
    scanning: s === "scanning",
    checking: s === "checking",
    bad: s === "not_found" || s === "error",
    ok: s === "found",
  };
});

const scanPanelClass = computed(() => {
  const s = scanState.value;
  return {
    bad: s === "not_found" || s === "error",
    ok: s === "found",
  };
});

async function openScanner() {
  openDd.value = false;
  closeCatPopover();
  closeMobileCats();

  if (!canScan.value) {
    showToast(
      "Камера недоступна: требуется HTTPS и разрешение на доступ.",
      "err"
    );
    return;
  }

  showScanner.value = true;
  scanBad.value = false;
  scanCode.value = "";
  scanMessage.value = "Пожалуйста, разрешите доступ к камере.";
  scanState.value = "starting";

  await nextTick();

  try {
    await startZXing();
    scanState.value = "scanning";
    scanMessage.value = torchSupported.value
      ? "Держите штрих-код в рамке. Если код читается плохо — поднесите ближе или включите фонарь."
      : "Держите штрих-код в рамке. Если код читается плохо — поднесите ближе и держите код ровно.";
  } catch (e) {
    scanState.value = "error";
    scanBad.value = true;
    scanMessage.value =
      "Не удалось открыть камеру. Проверьте разрешения браузера и убедитесь, что сайт открыт по HTTPS.";
  }
}

function closeScanner() {
  showScanner.value = false;
  stopZXing();
  scanState.value = "idle";
  scanMessage.value = "";
  scanCode.value = "";
  scanBad.value = false;
  torchSupported.value = false;
  torchOn.value = false;
  currentTrack = null;
  scanLock = false;
}

function resumeScanningNow() {
  scanBad.value = false;
  scanState.value = "scanning";
  scanMessage.value = "Наведите штрих-код в рамку";
  scanLock = false;
}

async function manualSearchScanned() {
  const code = String(scanCode.value || "").trim();
  if (!code) return;
  await processCode(code, { manual: true });
}

async function startZXing() {
  stopZXing();

  const ZX = await import("@zxing/browser");
  const ZL = await import("@zxing/library");

  const hints = new Map();
  hints.set(ZL.DecodeHintType.TRY_HARDER, true);
  hints.set(ZL.DecodeHintType.POSSIBLE_FORMATS, [
    ZL.BarcodeFormat.EAN_13,
    ZL.BarcodeFormat.EAN_8,
    ZL.BarcodeFormat.UPC_A,
    ZL.BarcodeFormat.UPC_E,
    ZL.BarcodeFormat.CODE_128,
    ZL.BarcodeFormat.CODE_39,
    ZL.BarcodeFormat.ITF,
    ZL.BarcodeFormat.CODABAR,
  ]);

  zxingReader = new ZX.BrowserMultiFormatReader(hints);

  const videoEl = scanVideoRef.value;
  if (!videoEl) throw new Error("No video element");

  const constraints = {
    audio: false,
    video: {
      facingMode: { ideal: facingMode.value }, // ✅ если телефон — обычно задняя
      width: { ideal: 1280 },
      height: { ideal: 720 },
    },
  };

  zxingControls = await zxingReader.decodeFromConstraints(
    constraints,
    videoEl,
    async (result) => {
      if (!result) return;

      const text = String(result.getText?.() ?? result.text ?? "").trim();
      if (!text) return;

      // анти-дребезг + анти-спам
      const now = Date.now();
      if (scanLock) return;
      if (text === lastHandledCode && now - lastHandledAt < 1600) return;

      lastHandledCode = text;
      lastHandledAt = now;

      await processCode(text);
    }
  );

  // torch detection
  try {
    const stream = videoEl.srcObject;
    const track = stream?.getVideoTracks?.()?.[0] || null;
    currentTrack = track;

    const caps = track?.getCapabilities?.();
    torchSupported.value = !!caps?.torch;
    torchOn.value = false;
  } catch {
    torchSupported.value = false;
    torchOn.value = false;
  }
}

function stopZXing() {
  try {
    if (zxingControls && typeof zxingControls.stop === "function")
      zxingControls.stop();
  } catch {}
  zxingControls = null;

  try {
    if (zxingReader && typeof zxingReader.reset === "function")
      zxingReader.reset();
  } catch {}
  zxingReader = null;

  const v = scanVideoRef.value;
  if (v && v.srcObject) {
    try {
      const stream = v.srcObject;
      stream?.getTracks?.().forEach((t) => t.stop());
    } catch {}
    v.srcObject = null;
  }

  currentTrack = null;
}

async function toggleTorch() {
  const track = currentTrack;
  if (!track) return;

  try {
    await track.applyConstraints({
      advanced: [{ torch: !torchOn.value }],
    });
    torchOn.value = !torchOn.value;

    if (navigator.vibrate) navigator.vibrate(10);
  } catch {
    showToast("Фонарь недоступен на этом устройстве.", "err");
  }
}

async function switchCamera() {
  facingMode.value =
    facingMode.value === "environment" ? "user" : "environment";

  scanBad.value = false;
  scanCode.value = "";
  scanState.value = "starting";
  scanMessage.value = "Переключаем камеру…";
  scanLock = true;

  try {
    await startZXing();
    scanState.value = "scanning";
    scanMessage.value = "Наведите штрих-код в рамку.";
  } catch (e) {
    scanState.value = "error";
    scanBad.value = true;
    scanMessage.value = "Не удалось переключить камеру.";
  } finally {
    setTimeout(() => (scanLock = false), 600);
  }
}

async function processCode(codeRaw, { manual = false } = {}) {
  const code = String(codeRaw || "").trim();
  if (!code) return;

  scanCode.value = code;
  scanState.value = "checking";
  scanBad.value = false;
  scanLock = true;

  if (navigator.vibrate) navigator.vibrate(20);

  scanMessage.value = manual ? "Проверяем код…" : "Код считан. Ищем товар…";

  try {
    const p = await findProductByBarcode(code);

    if (p?.id) {
      scanState.value = "found";
      scanMessage.value = "Открываем карточку товара…";

      // закрываем камеру и переходим
      setTimeout(() => {
        closeScanner();
        router.push({
          name: "product",
          params: { slug: p.slug || String(p.id) },
        });
      }, 260);

      return;
    }

    // ❌ НЕ НАЙДЕНО — КАМЕРА ПРОДОЛЖАЕТ СКАНИРОВАТЬ
    scanState.value = "scanning"; // оставляем “Сканер активен”
    scanBad.value = true;
    scanMessage.value = torchSupported.value
      ? "Товар по этому штрих-коду не найден. Поднесите код ближе и держите его ровно. При необходимости включите фонарь."
      : "Товар по этому штрих-коду не найден. Поднесите код ближе и держите его ровно.";
    // рамку красной делаем ненадолго
    setTimeout(() => {
      if (!showScanner.value) return;
      scanBad.value = false;
    }, 900);

    // сообщение показываем 3 секунды, потом возвращаем обычную подсказку
    setTimeout(() => {
      if (!showScanner.value) return;
      if (scanState.value === "scanning") {
        scanMessage.value = "Наведите штрих-код в рамку.";
      }
    }, 3000);

    // снимаем лок, чтобы сканер продолжал ловить новые коды
    setTimeout(() => {
      scanLock = false;
    }, 700);
  } catch (e) {
    scanState.value = "error";
    scanBad.value = true;
    scanMessage.value =
      "Не удалось проверить товар. Проверьте подключение к интернету и повторите попытку.";
    setTimeout(() => {
      if (!showScanner.value) return;
      scanLock = false;
    }, 1200);
  }
}

async function findProductByBarcode(code) {
  const r = await fetch(
    `/api/admin/product/search_products.php?q=${encodeURIComponent(
      code
    )}&limit=1`,
    { headers: { Accept: "application/json" } }
  );
  const data = await r.json();
  const list = Array.isArray(data) ? data : data.products || data.items || [];
  return list?.[0] || null;
}

/* ================= BODY LOCK (mobile cats + scanner) ================= */
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

const bodyLocked = ref(false);
watch([showMobileCats, showScanner], ([m, s]) => {
  const should = !!(m || s);
  if (should && !bodyLocked.value) lockBody();
  if (!should && bodyLocked.value) unlockBody();
  bodyLocked.value = should;
});

/* cleanup */
onBeforeUnmount(() => {
  clearTimeout(t);
  if (ac) ac.abort();
  stopZXing();
  clearTimeout(toastTimer);
});
</script>

<style scoped>
/* ===== wrapper ===== */
.search-wrap {
  position: relative;
  width: min(760px, 100%);
  margin: 0 auto;
}

/* ===== input как в Catalog ===== */
.search-box {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 5px 10px;
  border-radius: 999px;
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  box-shadow: var(--shadow-sm);
  position: relative;
}

.search-icon {
  color: var(--text-muted);
  font-size: 14px;
  flex: 0 0 auto;
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
.search-box {
  gap: 8px;
}
.search-clear,
.search-scan {
  flex: 0 0 auto;
}

/* ===== scan button (красиво) ===== */
.search-scan {
  width: 34px;
  height: 34px;
  border-radius: 999px;
  border: 1px solid rgba(2, 6, 23, 0.08);
  background: linear-gradient(
    180deg,
    rgba(255, 255, 255, 1),
    rgba(247, 248, 255, 1)
  );
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.15s ease, box-shadow 0.15s ease,
    border-color 0.15s ease;
}
.search-scan:hover {
  transform: translateY(-1px);
  box-shadow: 0 12px 28px rgba(2, 6, 23, 0.12);
  border-color: rgba(4, 0, 255, 0.18);
}

/* ===== category button ===== */
.catpick-wrap {
  position: relative;
  display: inline-flex;
  align-items: center;
  flex: 0 0 auto;

  height: 34px;
  padding-left: 10px;
  margin-left: 2px;
  border-left: 1px solid var(--border-soft);
}

.catpick-btn {
  height: 34px;
  border-radius: 999px;

  border: 1px solid transparent;
  background: transparent;

  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;

  gap: 8px;
  padding: 0 10px;

  font-weight: 900;
  color: var(--text-main);

  transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
}

.catpick-btn:hover {
  background: rgba(4, 0, 255, 0.06);
  box-shadow: var(--shadow-sm);
  transform: translateY(-1px);
}

.catpick-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
  box-shadow: none;
  transform: none;
}

.catpick-btn.on {
  background: rgba(4, 0, 255, 0.08);
  border-color: rgba(4, 0, 255, 0.22);
  box-shadow: 0 10px 26px rgba(4, 0, 255, 0.1);
}

.catpick-text {
  font-size: 13px;
  letter-spacing: -0.01em;
  white-space: nowrap;
}

.catpick-ico {
  font-size: 13px;
  color: var(--accent);
}

@media (max-width: 520px) {
  .catpick-text {
    display: none;
  }
}

/* ===== desktop categories popover ===== */
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

.catpop::before {
  content: "";
  position: absolute;
  top: -8px;
  left: 84px;
  width: 16px;
  height: 16px;
  background: rgba(255, 255, 255, 0.92);
  border-left: 1px solid rgba(15, 23, 42, 0.1);
  border-top: 1px solid rgba(15, 23, 42, 0.1);
  transform: rotate(45deg);
}

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

.catpop-cols {
  display: flex;
  height: min(520px, 62vh);
  overflow-x: auto;
  overflow-y: hidden;
}

.catpop-col {
  height: 100%;
  overflow-y: auto;
  width: 280px;
  flex: 0 0 280px;
  padding: 10px 10px 30px 12px;
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

.catpop-item {
  width: 100%;
  text-align: left;
  border: 1px solid transparent;
  background: transparent;
  cursor: pointer;
  border-radius: 14px;
  padding: 10px 10px;
  display: flex;
  align-items: center;
  justify-content: space-between;
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
.catpop-item.active {
  background: rgba(4, 0, 255, 0.08);
  border-color: rgba(4, 0, 255, 0.18);
}
.catpop-item.picked {
  color: var(--accent);
  background: rgba(4, 0, 255, 0.1);
  border-color: rgba(4, 0, 255, 0.26);
}
.catpop-text {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.catpop-chev {
  opacity: 0.35;
  font-size: 12px;
}

/* ===== hint ===== */
.search-hint {
  margin-top: 10px;
  font-size: 12px;
  color: var(--text-muted);
  display: flex;
  align-items: center;
  gap: 8px;
}
.search-hint .dot {
  width: 7px;
  height: 7px;
  border-radius: 999px;
  background: var(--accent);
  opacity: 0.7;
}

/* ===== dropdown results ===== */
.dd {
  position: absolute;
  left: 0;
  right: 0;
  top: calc(100% + 10px);
  z-index: 50;

  background: rgba(255, 255, 255, 0.96);
  border: 1px solid var(--border-soft);
  border-radius: 18px;
  box-shadow: var(--shadow-lg);
  overflow: hidden;

  display: flex;
  flex-direction: column;
  max-height: min(520px, 62vh);

  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
}

.dd-list {
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  padding: 8px;
}

.dd-empty {
  padding: 14px 14px;
  font-size: 13px;
  font-weight: 800;
  color: var(--text-muted);
}

.dd-item {
  width: 100%;
  text-align: left;
  border: 1px solid transparent;
  background: transparent;
  cursor: pointer;

  display: flex;
  align-items: center;
  gap: 12px;

  padding: 10px 10px;
  border-radius: 14px;

  transition: background 0.12s ease, border-color 0.12s ease,
    transform 0.12s ease;
}
.dd-item:hover {
  background: rgba(4, 0, 255, 0.05);
  border-color: rgba(4, 0, 255, 0.1);
}
.dd-item.active {
  background: rgba(4, 0, 255, 0.08);
  border-color: rgba(4, 0, 255, 0.18);
}
.dd-item:active {
  transform: scale(0.997);
}

.dd-thumb-wrap {
  width: 46px;
  height: 46px;
  flex: 0 0 46px;
  border-radius: 14px;
  overflow: hidden;

  border: 1px solid var(--border-soft);
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;

  box-shadow: 0 8px 18px rgba(0, 0, 0, 0.06);
}

.dd-thumb {
  width: 100%;
  height: 100%;
  object-fit: contain;
  display: block;
}

.dd-thumb-ph {
  color: var(--text-muted);
  background: linear-gradient(180deg, #fff, #f7f9ff);
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
}

.dd-main {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
  flex: 1;
}

.dd-title {
  font-size: 13px;
  font-weight: 900;
  color: var(--text-main);
  line-height: 1.15;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.dd-sub {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
}

.dd-pill {
  font-size: 11px;
  font-weight: 900;
  padding: 5px 10px;
  border-radius: 999px;

  background: rgba(4, 0, 255, 0.08);
  border: 1px solid rgba(4, 0, 255, 0.16);
  color: var(--text-main);

  max-width: 180px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.dd-price {
  font-size: 12px;
  font-weight: 900;
  color: var(--accent);
}

.dd-code {
  font-size: 11px;
  font-weight: 850;
  color: var(--text-muted);
  background: #f3f4f6;
  border: 1px solid #e5e7eb;
  padding: 5px 10px;
  border-radius: 999px;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas,
    "Liberation Mono", "Courier New", monospace;
}

.dd-arrow {
  color: rgba(27, 30, 40, 0.35);
  font-size: 12px;
  flex: 0 0 auto;
  margin-left: 2px;
}

.dd-all {
  width: 100%;
  border: none;
  border-top: 1px solid var(--border-soft);
  background: rgba(255, 255, 255, 0.92);
  padding: 12px 14px;
  cursor: pointer;

  font-weight: 900;
  color: var(--accent);
  transition: background 0.12s ease;
}
.dd-all:hover {
  background: rgba(4, 0, 255, 0.05);
}

/* ===== mobile cats overlay ===== */
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
  width: 20px;
  height: 20px;
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

/* ===== toast ===== */
.toast {
  position: fixed;
  left: 50%;
  bottom: 18px;
  transform: translateX(-50%);
  z-index: 9999;
  padding: 10px 12px;
  border-radius: 999px;
  font-weight: 900;
  font-size: 12px;
  border: 1px solid var(--border-soft);
  background: rgba(255, 255, 255, 0.96);
  box-shadow: 0 18px 50px rgba(0, 0, 0, 0.18);
  max-width: min(92vw, 520px);
  text-align: center;
}
.toast.ok {
  color: #0f766e;
}
.toast.err {
  color: #b91c1c;
}
.toast.info {
  color: var(--text-main);
}

.toast-enter-active,
.toast-leave-active {
  transition: opacity 0.16s ease, transform 0.16s ease;
}
.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(8px);
}
/* torch button (over video) */
.scan-torch {
  position: absolute;
  top: 22px;
  right: 22px;
  z-index: 6;

  width: 44px;
  height: 44px;
  border-radius: 16px;

  border: 1px solid rgba(255, 255, 255, 0.22);
  background: rgba(2, 6, 23, 0.36);
  color: #fff;

  display: inline-flex;
  align-items: center;
  justify-content: center;

  cursor: pointer;
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  box-shadow: 0 16px 44px rgba(0, 0, 0, 0.3);

  transition: transform 0.12s ease, background 0.12s ease,
    border-color 0.12s ease;
}

.scan-torch:hover {
  transform: translateY(-1px);
  border-color: rgba(255, 255, 255, 0.32);
}

.scan-torch:active {
  transform: translateY(1px) scale(0.99);
}

.scan-torch.on {
  background: rgba(255, 255, 255, 0.92);
  color: rgba(2, 6, 23, 0.92);
  border-color: rgba(255, 255, 255, 0.55);
}

/* ===== scanner overlay ===== */
.scan-overlay {
  position: fixed;
  inset: 0;
  z-index: 5000;
  background: radial-gradient(
      900px 420px at 50% 0%,
      rgba(255, 255, 255, 0.1),
      transparent 60%
    ),
    rgba(15, 23, 42, 0.62);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 14px;
}

.scan-panel {
  width: min(700px, 100%);
  border-radius: 24px;
  border: 1px solid rgba(255, 255, 255, 0.18);
  background: rgba(255, 255, 255, 0.96);
  box-shadow: 0 34px 110px rgba(0, 0, 0, 0.32);
  overflow: hidden;
  transform: translateY(0);
  animation: scanPop 0.18s ease-out;
}
@keyframes scanPop {
  from {
    opacity: 0;
    transform: translateY(10px) scale(0.985);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}
.scan-panel.bad {
  box-shadow: 0 34px 110px rgba(185, 28, 28, 0.22);
}
.scan-panel.ok {
  box-shadow: 0 34px 110px rgba(16, 185, 129, 0.22);
}

.scan-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 12px 12px;
  border-bottom: 1px solid var(--border-soft);
  background: linear-gradient(
    180deg,
    rgba(255, 255, 255, 0.98),
    rgba(255, 255, 255, 0.9)
  );
}

.scan-title {
  font-weight: 950;
  font-size: 13px;
  letter-spacing: 0.02em;
}

.scan-close {
  width: 40px;
  height: 40px;
  border-radius: 14px;
  border: 1px solid var(--border-soft);
  background: #fff;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.scan-video-wrap {
  position: relative;
  padding: 12px;
}

.scan-video {
  width: 100%;
  height: min(66vh, 480px);
  border-radius: 20px;
  background: #0b1020;
  object-fit: cover;
  display: block;
  border: 1px solid rgba(2, 6, 23, 0.12);
  filter: contrast(1.05) saturate(1.05);
}

/* рамка */
.scan-frame {
  position: absolute;
  inset: 12px;
  pointer-events: none;
  display: flex;
  align-items: center;
  justify-content: center;
}

.scan-frame::before {
  content: "";
  width: min(78%, 470px);
  aspect-ratio: 3 / 2;
  border-radius: 20px;
  border: 1px dashed rgba(255, 255, 255, 0.6);
  box-shadow: 0 0 0 9999px rgba(2, 6, 23, 0.18) inset,
    0 0 0 1px rgba(255, 255, 255, 0.1), 0 18px 50px rgba(0, 0, 0, 0.25);
  transition: border-color 0.18s ease, box-shadow 0.18s ease;
}

.scan-frame.bad::before {
  border-color: rgba(239, 68, 68, 0.95);
  box-shadow: 0 0 0 9999px rgba(185, 28, 28, 0.18) inset,
    0 0 0 1px rgba(239, 68, 68, 0.35), 0 18px 50px rgba(185, 28, 28, 0.2);
}
.scan-frame.ok::before {
  border-color: rgba(34, 197, 94, 0.95);
  box-shadow: 0 0 0 9999px rgba(16, 185, 129, 0.18) inset,
    0 0 0 1px rgba(34, 197, 94, 0.35), 0 18px 50px rgba(16, 185, 129, 0.18);
}

/* уголки */
.scan-corner {
  position: absolute;
  width: 24px;
  height: 24px;
  border: 3px solid rgba(255, 255, 255, 0.95);
  border-radius: 7px;
  transition: border-color 0.18s ease;
}
.scan-frame.bad .scan-corner {
  border-color: rgba(239, 68, 68, 0.95);
}
.scan-frame.ok .scan-corner {
  border-color: rgba(34, 197, 94, 0.95);
}

.scan-corner.tl {
  transform: translate(calc(-1 * min(39%, 235px)), calc(-1 * min(26%, 150px)));
  border-right: 0;
  border-bottom: 0;
}
.scan-corner.tr {
  transform: translate(min(39%, 235px), calc(-1 * min(26%, 150px)));
  border-left: 0;
  border-bottom: 0;
}
.scan-corner.bl {
  transform: translate(calc(-1 * min(39%, 235px)), min(26%, 150px));
  border-right: 0;
  border-top: 0;
}
.scan-corner.br {
  transform: translate(min(39%, 235px), min(26%, 150px));
  border-left: 0;
  border-top: 0;
}

/* линия скана */
.scan-line {
  position: absolute;
  width: min(76%, 455px);
  height: 2px;
  border-radius: 999px;
  background: linear-gradient(
    90deg,
    transparent,
    rgba(255, 255, 255, 0.95),
    transparent
  );
  opacity: 0.95;
  filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.28));
  animation: scanLine 1.25s ease-in-out infinite;
}

@keyframes scanLine {
  0% {
    transform: translateY(calc(-1 * min(20%, 120px)));
    opacity: 0.25;
  }
  45% {
    opacity: 0.95;
  }
  100% {
    transform: translateY(min(20%, 120px));
    opacity: 0.25;
  }
}

/* UI card */
.scan-ui {
  position: absolute;
  left: 16px;
  right: 16px;
  top: 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  pointer-events: none;
}

.scan-badge {
  align-self: flex-start;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 10px;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.22);
  background: rgba(2, 6, 23, 0.34);
  color: #fff;
  font-weight: 950;
  font-size: 12px;
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
}
.scan-badge.checking {
  background: rgba(4, 0, 255, 0.26);
}
.scan-badge.ok {
  background: rgba(16, 185, 129, 0.22);
  border-color: rgba(34, 197, 94, 0.25);
}
.scan-badge.bad {
  background: rgba(127, 29, 29, 0.22);
  border-color: rgba(239, 68, 68, 0.25);
}

.scan-bdot {
  width: 8px;
  height: 8px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.92);
  opacity: 0.9;
}
.scan-badge.scanning .scan-bdot {
  animation: pulseDot 1s ease-in-out infinite;
}
.scan-badge.checking .scan-bdot {
  animation: pulseDot 0.7s ease-in-out infinite;
}

@keyframes pulseDot {
  0%,
  100% {
    transform: scale(0.9);
    opacity: 0.6;
  }
  50% {
    transform: scale(1.15);
    opacity: 1;
  }
}

.scan-msg {
  pointer-events: auto;
  border-radius: 18px;
  border: 1px solid rgba(255, 255, 255, 0.22);
  background: rgba(2, 6, 23, 0.35);
  color: #fff;
  padding: 12px 12px;
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  box-shadow: 0 18px 60px rgba(0, 0, 0, 0.28);
}
.scan-msg.bad {
  background: rgba(127, 29, 29, 0.3);
  border-color: rgba(239, 68, 68, 0.28);
}

.scan-msg-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 6px;
}
.scan-msg-title {
  font-weight: 950;
  font-size: 13px;
  letter-spacing: 0.01em;
}
.scan-code {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas,
    "Liberation Mono", "Courier New", monospace;
  font-size: 12px;
  font-weight: 900;
  padding: 5px 10px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.16);
  border: 1px solid rgba(255, 255, 255, 0.18);
  max-width: 58%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.scan-msg-sub {
  font-size: 12px;
  font-weight: 850;
  opacity: 0.96;
  line-height: 1.25;
}

.search-scan:active,
.search-clear:active,
.catpick-btn:active {
  transform: translateY(1px) scale(0.99);
}
/* switch camera (bottom-right) */
.scan-switch {
  position: absolute;
  right: 22px;
  bottom: 22px;
  z-index: 6;

  width: 46px;
  height: 46px;
  border-radius: 16px;

  border: 1px solid rgba(255,255,255,0.22);
  background: rgba(2, 6, 23, 0.36);
  color: #fff;

  display: inline-flex;
  align-items: center;
  justify-content: center;

  cursor: pointer;
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  box-shadow: 0 16px 44px rgba(0,0,0,0.30);

  transition: transform 0.12s ease, border-color 0.12s ease;
}

.scan-switch:hover {
  transform: translateY(-1px);
  border-color: rgba(255,255,255,0.32);
}

.scan-switch:active {
  transform: translateY(1px) scale(0.99);
}

/* flip animation on video */
.scan-video.flip {
  animation: camFlip 0.26s ease-in-out;
}

@keyframes camFlip {
  0%   { transform: scale(1) rotateY(0deg); filter: blur(0px); }
  45%  { transform: scale(0.985) rotateY(18deg); filter: blur(1px); }
  100% { transform: scale(1) rotateY(0deg); filter: blur(0px); }
}

</style>
