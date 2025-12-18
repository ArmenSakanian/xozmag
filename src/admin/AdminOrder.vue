<template>
  <div class="page">
    <header class="top">
      <div class="title-wrap">
        <h1 class="title">Выгрузка товаров по контрагентам (Эвотор)</h1>
        <p class="sub">
          Контрагент = группа <b>2 уровня</b>. Одинаковые названия (в разных
          группах 1 уровня) — <b>объединяются</b>. Выгрузка включает товары во
          всех вложенных подгруппах.
        </p>
      </div>

      <div class="controls">
        <div class="search">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input
            v-model="q"
            class="input"
            placeholder="Поиск контрагента…"
            autocomplete="off"
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
    </header>

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
            Контрагентов: <b>{{ filtered.length }}</b>
          </div>

          <div class="hint">
            Нажми «Выгрузить» — скачает <b>Excel</b> с колонками:
            <b>Наименование</b>, <b>Штрихкод</b>, <b>Артикул</b>,
            <b>Остаток</b>.
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
                <span class="pill">
                  групп(2ур): <b>{{ c.uuids?.length ?? 0 }}</b>
                </span>
                <span class="pill">
                  категорий(1ур): <b>{{ c.categoryUuids?.length ?? 0 }}</b>
                </span>
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
                :disabled="downloadingKey === c.key"
                @click="downloadContragent(c)"
              >
                <i
                  v-if="downloadingKey !== c.key"
                  class="fa-solid fa-file-arrow-down"
                ></i>
                <i v-else class="fa-solid fa-circle-notch fa-spin"></i>
                Выгрузить
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <div v-if="toast" class="toast">
      <i class="fa-solid fa-check"></i>
      {{ toast }}
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";

const API_URL = "/api/admin/order/order.php";

const loading = ref(false);
const error = ref("");
const q = ref("");

const categories = ref([]);
const contragents = ref([]);

const downloadingKey = ref("");
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

async function loadList(force = false) {
  loading.value = true;
  error.value = "";

  try {
    const params = new URLSearchParams({ mode: "list" });
    if (force) params.set("nocache", "1"); // только по кнопке "Обновить"

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

function downloadContragent(c) {
  if (!c?.key) return;

  downloadingKey.value = c.key;

  const params = new URLSearchParams({ mode: "export", key: c.key });
  window.location.href = `${API_URL}?${params.toString()}`;

  setTimeout(() => {
    downloadingKey.value = "";
    showToast("Файл отправлен на скачивание");
  }, 900);
}

onMounted(() => loadList(false));
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
  background: var(--bg);
  padding: 18px;
  color: var(--text);
  font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
}
.top {
  display: grid;
  grid-template-columns: 1fr;
  gap: 14px;
  margin: 0 auto 14px;
  max-width: 1200px;
}
.title-wrap {
  background: linear-gradient(
    135deg,
    rgba(4, 0, 255, 0.1),
    rgba(255, 255, 255, 0)
  );
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 14px 16px;
  box-shadow: var(--shadow);
}
.title {
  margin: 0;
  font-size: 20px;
  font-weight: 800;
  letter-spacing: 0.2px;
}
.sub {
  margin: 6px 0 0;
  color: var(--muted);
  font-size: 13px;
  line-height: 1.35;
}

.controls {
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
  justify-content: space-between;
}
.search {
  position: relative;
  flex: 1;
  min-width: 280px;
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
.input {
  width: 100%;
  height: 42px;
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 0 44px 0 38px;
  background: var(--panel);
  outline: none;
  box-shadow: 0 1px 0 rgba(0, 0, 0, 0.02);
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
  height: 30px;
  width: 34px;
  border-radius: 10px;
  border: 1px solid var(--border);
  background: #fff;
  cursor: pointer;
}

.grid {
  max-width: 1200px;
  margin: 0 auto;
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

.state {
  max-width: 1200px;
  margin: 0 auto;
  padding: 18px;
  display: grid;
  justify-items: center;
  gap: 10px;
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
