<template>
  <div class="page" :style="{ '--appH': appH + 'px' }">
    <header ref="topRef" class="top">

      <!-- controls: LIST -->
      <div class="controls" v-if="viewMode === 'list'">
        <div class="search">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input
            v-model="q"
            class="input"
            placeholder="Поиск поставщика…"
            autocomplete="off"
            inputmode="search"
          />
          <button v-if="q" class="xbtn" @click="q = ''" title="Очистить">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <button class="btn" :disabled="loading" @click="loadList(true)">
          <i class="fa-solid fa-rotate"></i>
          Обновить
        </button>
      </div>

      <!-- controls: ITEMS -->
      <div class="controls" v-else>
        <div class="search">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input
            v-model="itemsQ"
            class="input"
            placeholder="Поиск товара…"
            autocomplete="off"
            inputmode="search"
          />
          <button
            v-if="itemsQ"
            class="xbtn"
            @click="itemsQ = ''"
            title="Очистить"
          >
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <div class="btns-right">
          <button class="btn" :disabled="itemsLoading" @click="reloadItems">
            <i class="fa-solid fa-rotate"></i>
            Обновить товары
          </button>

          <button
            class="btn primary"
            :disabled="exporting || itemsLoading"
            @click="exportExcel"
          >
            <i v-if="!exporting" class="fa-solid fa-file-excel"></i>
            <i v-else class="fa-solid fa-circle-notch fa-spin"></i>
            Выгрузить Excel
          </button>
        </div>
      </div>
    </header>

    <!-- ✅ ВАЖНО: контент отдельным скроллом -->
    <main class="content">
      <!-- ================= LIST VIEW ================= -->
      <section v-if="viewMode === 'list'">
        <section v-if="loading" class="state card">
          <div class="spinner"></div>
          <div class="state-text">Загружаю контрагентов…</div>
        </section>

        <section v-else-if="error" class="state card error">
          <div class="state-title">
            <i class="fa-solid fa-triangle-exclamation"></i>
            Ошибка
          </div>
          <div class="state-text">{{ error }}</div>
          <button class="btn" @click="loadList(true)">
            <i class="fa-solid fa-rotate"></i>
            Повторить
          </button>
        </section>

        <section v-else class="grid">
          <div class="card list-card">
            <div class="list-head">
              <div class="count">
                Поставщиков: <b>{{ filtered.length }}</b>
              </div>
              <div class="hint">
                Нажми <b>«Открыть»</b> — покажет товары на сайте. Сверху будет
                кнопка <b>«Выгрузить Excel»</b>.
              </div>
            </div>

            <div v-if="filtered.length === 0" class="empty">
              Ничего не найдено по запросу «{{ q }}»
            </div>

            <div v-else class="rows">
              <div v-for="c in filtered" :key="c.key" class="row">
                <div class="left">
                  <div class="name">
                    <span class="dot"></span>
                    {{ c.name }}
                  </div>

                  <div class="meta">
                    <span class="pill"
                      >групп(2ур): <b>{{ c.uuids?.length ?? 0 }}</b></span
                    >
                    <span class="pill"
                      >категорий(1ур):
                      <b>{{ c.categoryUuids?.length ?? 0 }}</b></span
                    >
                  </div>

                  <div v-if="c.categoryUuids?.length" class="cats">
                    <span
                      v-for="cu in c.categoryUuids"
                      :key="cu"
                      class="chip"
                      :title="categoryNameByUuid[cu] || cu"
                    >
                      {{ categoryNameByUuid[cu] || cu }}
                    </span>
                  </div>
                </div>

                <div class="right">
                  <button
                    class="btn primary"
                    :disabled="openingKey === c.key"
                    @click="openContragent(c)"
                  >
                    <i
                      v-if="openingKey !== c.key"
                      class="fa-solid fa-folder-open"
                    ></i>
                    <i v-else class="fa-solid fa-circle-notch fa-spin"></i>
                    Открыть
                  </button>
                </div>
              </div>
            </div>
          </div>
        </section>
      </section>

      <!-- ================= ITEMS VIEW ================= -->
      <section v-else class="grid">
        <div class="card items-card">
          <div class="items-head">
            <div class="items-stats">
              <div class="stat">
                Товаров: <b>{{ itemsFiltered.length }}</b>
              </div>
              <div class="stat muted" v-if="itemsMeta?.generatedAt">
                Обновлено: <b>{{ itemsMeta.generatedAt }}</b>
              </div>
            </div>

            <div class="items-hint">
              Название, Остаток, Штрихкод, Артикул, <b>Минимальный остаток</b>.
            </div>
          </div>

          <div v-if="itemsLoading" class="state inner">
            <div class="spinner"></div>
            <div class="state-text">Загружаю товары…</div>
          </div>

          <div v-else-if="itemsError" class="state inner error">
            <div class="state-title">
              <i class="fa-solid fa-triangle-exclamation"></i>
              Ошибка
            </div>
            <div class="state-text">{{ itemsError }}</div>
            <button class="btn" @click="reloadItems">
              <i class="fa-solid fa-rotate"></i>
              Повторить
            </button>
          </div>

          <div v-else-if="itemsFiltered.length === 0" class="empty big">
            Ничего не найдено по запросу «{{ itemsQ }}»
          </div>

          <div v-else class="items-grid">
            <div
              v-for="p in itemsFiltered"
              :key="p.uuid || p.barcode + '_' + p.name"
              class="p-card"
            >
              <div class="p-top">
                <div class="p-left">
                  <div class="thumb">
                    <img
                      :src="p.image || '/img/no-photo.png'"
                      alt=""
                      loading="lazy"
                    />
                  </div>

                  <div class="p-name">{{ p.name }}</div>
                </div>
                
              </div>

              <div class="p-meta">
                <div class="kv">
                                  <div class="badge" :class="{ danger: Number(p.quantity) <= 0 }">
                  Остаток: <b><span class="quantity">{{ p.quantity }}</span></b>
                </div>
                </div>
                <div class="kv">
                  <div class="k">Штрихкод</div>
                  <div class="v mono">{{ p.barcode }}</div>
                </div>

                <div class="kv">
                  <div class="k">Артикул</div>
                  <div class="v mono">{{ p.article || "—" }}</div>
                </div>

                <div class="kv">
                  <div class="k">Минимальный остаток</div>
                  <div class="v mono">{{ p.min_stock }}</div>
                </div>
              </div>

              <div class="p-actions">
                <button class="btn mini" @click="copyText(p.barcode)">
                  <i class="fa-regular fa-copy"></i>
                  Копировать штрихкод
                </button>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>

    <div v-if="toast" class="toast">
      <i class="fa-solid fa-check"></i>
      {{ toast }}
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from "vue";

const API_URL = "/api/admin/order/order.php";

// ===== list =====
const loading = ref(false);
const error = ref("");
const q = ref("");

const categories = ref([]);
const contragents = ref([]);

// ===== view =====
const viewMode = ref("list");
const selectedContragent = ref(null);
const openingKey = ref("");

// ===== items =====
const itemsLoading = ref(false);
const itemsError = ref("");
const items = ref([]);
const itemsQ = ref("");
const itemsMeta = ref(null);

// ===== export =====
const exporting = ref(false);

// ===== toast =====
const toast = ref("");
let toastTimer = null;

function showToast(msg) {
  toast.value = msg;
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => (toast.value = ""), 1600);
}

const categoryNameByUuid = computed(() => {
  const map = {};
  for (const c of categories.value || []) map[c.uuid] = c.name;
  return map;
});

const filtered = computed(() => {
  const list = contragents.value || [];
  const s = q.value.trim().toLowerCase();
  if (!s) return list;
  return list.filter((x) =>
    String(x.name || "")
      .toLowerCase()
      .includes(s)
  );
});

const itemsFiltered = computed(() => {
  const list = items.value || [];
  const s = itemsQ.value.trim().toLowerCase();
  if (!s) return list;
  return list.filter((p) => {
    const hay = [p?.name, p?.barcode, p?.article, p?.min_stock, p?.quantity]
      .filter(Boolean)
      .join(" ")
      .toLowerCase();
    return hay.includes(s);
  });
});

async function loadList(force = false) {
  loading.value = true;
  error.value = "";

  try {
    const params = new URLSearchParams({ mode: "list" });
    if (force) params.set("nocache", "1");

    const res = await fetch(`${API_URL}?${params.toString()}`, {
      method: "GET",
      headers: { Accept: "application/json" },
    });

    if (!res.ok) {
      const t = await res.text().catch(() => "");
      throw new Error(`HTTP ${res.status}. ${t}`.trim());
    }

    const data = await res.json();

    categories.value = Array.isArray(data?.categories) ? data.categories : [];
    contragents.value = Array.isArray(data?.contragents)
      ? data.contragents
      : [];
    contragents.value.sort((a, b) =>
      String(a.name || "").localeCompare(String(b.name || ""), "ru")
    );
  } catch (e) {
    error.value = e?.message || String(e);
  } finally {
    loading.value = false;
  }
}

async function openContragent(c) {
  if (!c?.key) return;
  openingKey.value = c.key;

  selectedContragent.value = c;
  viewMode.value = "items";
  itemsQ.value = "";
  items.value = [];
  itemsMeta.value = null;

  await loadItems(c.key, true);
  openingKey.value = "";
}

function backToList() {
  viewMode.value = "list";
  selectedContragent.value = null;
  items.value = [];
  itemsError.value = "";
  itemsLoading.value = false;
  itemsMeta.value = null;
}

async function loadItems(key, force = false) {
  itemsLoading.value = true;
  itemsError.value = "";

  try {
    const params = new URLSearchParams({ mode: "items", key });
    if (force) params.set("nocache", "1");

    const res = await fetch(`${API_URL}?${params.toString()}`, {
      method: "GET",
      headers: { Accept: "application/json" },
    });

    if (!res.ok) {
      const t = await res.text().catch(() => "");
      throw new Error(`HTTP ${res.status}. ${t}`.trim());
    }

    const data = await res.json();
    const list = Array.isArray(data?.items) ? data.items : [];

    list.sort((a, b) =>
      String(a.name || "").localeCompare(String(b.name || ""), "ru")
    );
    items.value = list;

    itemsMeta.value = {
      generatedAt: data?.generatedAt || "",
      count: data?.count ?? list.length,
      contragent: data?.contragent || null,
    };
  } catch (e) {
    itemsError.value = e?.message || String(e);
  } finally {
    itemsLoading.value = false;
  }
}

function reloadItems() {
  const key = selectedContragent.value?.key;
  if (!key) return;
  loadItems(key, true);
}

function exportExcel() {
  const key = selectedContragent.value?.key;
  if (!key) return;

  exporting.value = true;
  const params = new URLSearchParams({ mode: "export", key });
  window.location.href = `${API_URL}?${params.toString()}`;
  setTimeout(() => (exporting.value = false), 900);
}

async function copyText(text) {
  try {
    await navigator.clipboard.writeText(String(text || ""));
    showToast("Скопировано");
  } catch {
    showToast("Не получилось скопировать");
  }
}

/**
 * ✅ Мобильный фикс клавиатуры:
 * visualViewport.height уменьшается при открытии клавиатуры,
 * мы используем это как высоту приложения.
 */
const topRef = ref(null);
const appH = ref(0);

function updateAppHeight() {
  const h = window.visualViewport?.height ?? window.innerHeight;
  appH.value = Math.round(h);
  // чуть позже, чтобы браузер успел переложить layout
  nextTick(() => {});
}

onMounted(() => {
  loadList(false);
  updateAppHeight();

  const vv = window.visualViewport;
  if (vv) vv.addEventListener("resize", updateAppHeight);

  window.addEventListener("resize", updateAppHeight);
  window.addEventListener("orientationchange", updateAppHeight);
});

onBeforeUnmount(() => {
  const vv = window.visualViewport;
  if (vv) vv.removeEventListener("resize", updateAppHeight);

  window.removeEventListener("resize", updateAppHeight);
  window.removeEventListener("orientationchange", updateAppHeight);
});
</script>

<style scoped>
:global(:root) {
  --bg: #f4f6fb;
  --panel: #ffffff;
  --text: #1b1e28;
  --muted: #6b7280;
  --border: #e4e7ef;
  --accent: #0400ff;
  --shadow: 0 12px 30px rgba(15, 23, 42, 0.1);
}

.page {
  min-height: 100vh;
  overflow: visible;
  background: var(--bg);
  color: var(--text);
}

/* header остаётся сверху, контент скроллится отдельно */
.top {
  padding: 14px 14px 10px;
  max-width: 1200px;
  width: 100%;
  margin: 0 auto;
}

.content {
  overflow: visible;
  padding: 0 14px 14px;
  padding-bottom: calc(14px + env(safe-area-inset-bottom));
}


.backline {
  display: flex;
  gap: 12px;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
}

.crumbs {
  display: flex;
  gap: 8px;
  align-items: center;
  color: var(--muted);
  font-size: 12px;
}
.sep {
  opacity: 0.6;
}
.crumb.current {
  color: var(--text);
  font-weight: 800;
}


.controls {
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
  justify-content: space-between;
  margin-top: 12px;
}

.btns-right {
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
}

.search {
  position: relative;
  flex: 1;
  min-width: 260px;
  max-width: 560px;
}

.search i.fa-magnifying-glass {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--muted);
  font-size: 14px;
}

/* ✅ iOS: чтобы не было увеличения при фокусе — font-size >= 16px */
.input {
  width: 100%;
  height: 44px;
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 0 44px 0 38px;
  background: var(--panel);
  outline: none;
  box-shadow: 0 1px 0 rgba(0, 0, 0, 0.02);
  font-size: 16px;
}

.input:focus {
  border-color: rgba(4, 0, 255, 0.35);
  box-shadow: 0 0 0 4px rgba(4, 0, 255, 0.1);
}

.xbtn {
  position: absolute;
  right: 8px;
  top: 50%;
  transform: translateY(-50%);
  height: 32px;
  width: 36px;
  border-radius: 10px;
  border: 1px solid var(--border);
  background: #fff;
  cursor: pointer;
}

.grid {
  max-width: 1200px;
  margin: 14px auto 0;
  display: grid;
  grid-template-columns: 1fr;
  gap: 14px;
  align-items: start;
}

.card {
  background: var(--panel);
  border: 1px solid var(--border);
  border-radius: 16px;
  box-shadow: var(--shadow);
}

.list-card {
  padding: 12px;
}

.list-head {
  display: flex;
  gap: 10px;
  align-items: center;
  justify-content: space-between;
  padding: 8px 8px 12px;
  border-bottom: 1px dashed var(--border);
}

.count {
  font-size: 13px;
  color: var(--muted);
}
.hint {
  font-size: 12px;
  color: var(--muted);
  text-align: right;
  max-width: 560px;
}

.rows {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 10px 6px 6px;
}

.row {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 12px;
  border: 1px solid rgba(228, 231, 239, 0.9);
  border-radius: 14px;
  padding: 12px;
  background: linear-gradient(180deg, #fff, rgba(244, 246, 251, 0.35));
}

.left .name {
  font-weight: 800;
  font-size: 14px;
  display: flex;
  gap: 10px;
  align-items: center;
}
.dot {
  height: 10px;
  width: 10px;
  border-radius: 999px;
  background: var(--accent);
  box-shadow: 0 0 0 4px rgba(4, 0, 255, 0.12);
}

.meta {
  margin-top: 8px;
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
.pill {
  font-size: 12px;
  color: var(--muted);
  background: #fff;
  border: 1px solid var(--border);
  padding: 6px 10px;
  border-radius: 999px;
}

.cats {
  margin-top: 10px;
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
.chip {
  font-size: 12px;
  padding: 6px 10px;
  border-radius: 999px;
  border: 1px solid rgba(4, 0, 255, 0.2);
  background: rgba(4, 0, 255, 0.06);
  color: #1b1e28;
  max-width: 240px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.right {
  display: flex;
  gap: 10px;
  align-items: center;
}

.btn {
  height: 40px;
  border-radius: 14px;
  border: 1px solid var(--border);
  background: #fff;
  padding: 0 12px;
  cursor: pointer;
  display: inline-flex;
  gap: 10px;
  align-items: center;
  font-weight: 700;
  color: #0f172a;
}
.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn.primary {
  border-color: rgba(4, 0, 255, 0.25);
  background: linear-gradient(
    135deg,
    rgba(4, 0, 255, 1),
    rgba(4, 0, 255, 0.78)
  );
  color: #fff;
  box-shadow: 0 10px 22px rgba(4, 0, 255, 0.18);
}
.btn.ghost {
  background: rgba(255, 255, 255, 0.9);
}
.btn.mini {
  height: 36px;
  border-radius: 12px;
  font-size: 12px;
  padding: 0 10px;
}

.state {
  padding: 18px;
  display: grid;
  justify-items: center;
  gap: 10px;
}
.state.inner {
  margin: 10px auto 14px;
  padding: 14px;
  border: 1px dashed var(--border);
  border-radius: 14px;
  width: calc(100% - 24px);
}
.state.error {
  border-color: rgba(220, 38, 38, 0.25);
}
.state-title {
  font-weight: 900;
  display: flex;
  gap: 10px;
  align-items: center;
}
.state-text {
  color: var(--muted);
  font-size: 13px;
  text-align: center;
}

.spinner {
  width: 34px;
  height: 34px;
  border-radius: 999px;
  border: 3px solid rgba(4, 0, 255, 0.18);
  border-top-color: rgba(4, 0, 255, 1);
  animation: spin 0.85s linear infinite;
}
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.empty {
  padding: 18px 10px;
  color: var(--muted);
  text-align: center;
}
.empty.big {
  padding: 24px 10px;
}

/* items */
.items-card {
  padding: 12px;
}
.items-head {
  display: flex;
  gap: 12px;
  align-items: flex-start;
  justify-content: space-between;
  padding: 8px 8px 12px;
  border-bottom: 1px dashed var(--border);
}

.items-stats {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.stat {
  font-size: 13px;
}
.stat.muted {
  color: var(--muted);
  font-size: 12px;
}
.items-hint {
  font-size: 12px;
  color: var(--muted);
  text-align: right;
  max-width: 560px;
}

.items-grid {
  padding: 12px 6px 6px;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
}
@media (max-width: 1000px) {
  .items-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
@media (max-width: 640px) {
  .items-grid {
    grid-template-columns: 1fr;
  }
}

.p-card {
  border: 1px solid rgba(228, 231, 239, 0.9);
  border-radius: 14px;
  padding: 12px;
  background: linear-gradient(180deg, #fff, rgba(244, 246, 251, 0.35));
  display: grid;
  gap: 10px;
}

.p-top {
  display: flex;
  gap: 10px;
  align-items: flex-start;
  justify-content: space-between;
}
.p-left {
  display: flex;
  gap: 10px;
  align-items: flex-start;
  min-width: 0;
}

.thumb {
  width: 100px;
  height: 100px;
  border-radius: 12px;
  border: 1px solid var(--border);
  background: #fff;
  display: grid;
  place-items: center;
  overflow: hidden;
  flex: 0 0 auto;
}
.thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.thumb.empty {
  color: var(--muted);
  background: rgba(244, 246, 251, 0.7);
}

.p-name {
  font-weight: 900;
  font-size: 13px;
  line-height: 1.25;
  min-width: 0;
}

.badge {
  font-size: 12px;
  border: 1px solid var(--border);
  padding: 6px 10px;
  border-radius: 999px;
  background: #ff9900;
  white-space: nowrap;
      display: flex;
    align-items: center;
    justify-content: flex-start;
    gap: 5px;
}
.quantity {
  font-weight: bold;
  font-size: 14px;
}

.badge.danger {
  border-color: rgba(220, 38, 38, 0.35);
}

.p-meta {
  display: grid;
  gap: 8px;
}
.kv {
  display: grid;
  grid-template-columns: 150px 1fr;
  gap: 10px;
  align-items: center;
}


@media (max-width: 640px) {
  .kv {
    grid-template-columns: 120px 1fr;
  }
}

.k {
  color: var(--muted);
  font-size: 12px;
}
.v {
  font-size: 12px;
  font-weight: 800;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas,
    "Liberation Mono", "Courier New", monospace;
}

.p-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

/* ✅ на телефоне ряд поставщика вертикально, кнопка на всю ширину */
@media (max-width: 640px) {
  .controls {
    flex-direction: column;
    align-items: stretch;
  }
  .search {
    max-width: none;
  }
  .btns-right {
    justify-content: stretch;
  }
  .btns-right .btn {
    width: 100%;
    justify-content: center;
  }

  .row {
    grid-template-columns: 1fr;
  }
  .right .btn {
    width: 100%;
    justify-content: center;
  }
  .hint {
    text-align: left;
  }
}

.toast {
  position: fixed;
  right: 16px;
  bottom: 16px;
  background: rgba(15, 23, 42, 0.92);
  color: #fff;
  padding: 12px 14px;
  border-radius: 14px;
  display: flex;
  gap: 10px;
  align-items: center;
  box-shadow: 0 18px 40px rgba(0, 0, 0, 0.25);
  z-index: 9999;
  font-weight: 700;
  font-size: 13px;
}
</style>
