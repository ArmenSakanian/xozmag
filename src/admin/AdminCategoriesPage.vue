<template>
  <div class="admin-page">
    <!-- ===== HEADER ===== -->
    <div class="page-head">
      <div class="page-head-left">
        <h1 class="page-title">Категории</h1>
        <div class="page-subtitle">
          Одинаковые названия разрешены только в разных ветках (у разных родителей).
        </div>
      </div>

      <div class="page-head-right">
        <button class="btn soft" @click="loadCategories" :disabled="loading">
          <i class="fa-solid fa-rotate-right"></i>
          Обновить
        </button>
      </div>
    </div>

    <div class="grid">
      <!-- ===== CREATE ===== -->
      <div class="card">
        <div class="card-head">
          <h2 class="card-title">Создать категорию</h2>
          <span class="pill" v-if="categories.length">{{ categories.length }}</span>
        </div>

        <div class="form">
          <label class="label">Название</label>
          <input
            v-model="newName"
            class="input"
            placeholder="Например: Эмаль"
            @keyup.enter="createCategory"
          />

          <label class="label">Родитель</label>
          <select v-model="newParent" class="select">
            <option :value="null">Без родителя (корень)</option>
            <option v-for="c in treeOrdered" :key="c.id" :value="c.id">
              {{ c.code }} — {{ c.name }}
            </option>
          </select>

          <div class="form-actions">
            <button
              class="btn primary"
              @click="createCategory"
              :disabled="creating || !newName.trim()"
            >
              <i class="fa-solid fa-plus"></i>
              Создать
            </button>

            <button class="btn ghost" @click="resetForm" :disabled="creating">
              Сброс
            </button>
          </div>

          <div v-if="formError" class="notice error">{{ formError }}</div>
          <div v-if="formOk" class="notice ok">{{ formOk }}</div>
        </div>
      </div>

      <!-- ===== LIST ===== -->
      <div class="card">
        <div class="card-head list-head">
          <div class="list-head-left">
            <h2 class="card-title">Существующие категории</h2>
            <div class="muted" v-if="!loading && treeOrdered.length">
              {{ treeOrdered.length }} строк
            </div>
          </div>

          <div class="list-head-right">
            <input v-model="q" class="input small" placeholder="Поиск…" />

            <button class="btn soft" @click="collapseAll" :disabled="loading">
              Свернуть
            </button>
            <button class="btn soft" @click="expandAll" :disabled="loading">
              Развернуть
            </button>
          </div>
        </div>

        <div v-if="loading" class="empty">Загрузка…</div>
        <div v-else-if="filtered.length === 0" class="empty">
          Ничего не найдено
        </div>

        <div v-else class="tree">
          <div
            v-for="c in filtered"
            :key="c.id"
            class="row"
            :class="{ root: c.level === 1 }"
          >
            <div
              class="row-main"
              :style="{ paddingLeft: (c.level - 1) * 22 + 'px' }"
            >
              <button
                v-if="c.hasChildren"
                class="twisty"
                @click.stop="toggle(c.id)"
                :title="opened[c.id] === false ? 'Развернуть' : 'Свернуть'"
              >
                <i
                  class="fa-solid fa-chevron-right"
                  :class="{ open: opened[c.id] !== false }"
                ></i>
              </button>
              <span v-else class="twisty placeholder" aria-hidden="true"></span>

              <span class="code">{{ c.code }}</span>
              <span class="name" :title="c.name">{{ c.name }}</span>
            </div>

            <div class="row-actions">
              <select v-model="moveTo[c.id]" class="select small">
                <option :value="null">Корень</option>
                <option
                  v-for="p in treeOrdered"
                  :key="p.id"
                  :value="p.id"
                  :disabled="p.id === c.id"
                >
                  {{ p.code }} — {{ p.name }}
                </option>
              </select>

              <button
                class="btn ghost"
                @click="changeParent(c.id)"
                :disabled="!!moving[c.id]"
                title="Переместить категорию"
              >
                <i class="fa-solid fa-arrow-right-arrow-left"></i>
                Перенести
              </button>

              <button
                class="btn danger"
                @click="deleteCategory(c.id, c.name)"
                :disabled="!!deleting[c.id]"
                title="Удалить категорию"
              >
                <i class="fa-solid fa-trash"></i>
                Удалить
              </button>
            </div>
          </div>
        </div>

        <div v-if="listError" class="notice error" style="margin-top: 12px">
          {{ listError }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";

const categories = ref([]);
const treeOrdered = ref([]);
const opened = ref({});
const moveTo = ref({});

const newName = ref("");
const newParent = ref(null);

const q = ref("");

const loading = ref(false);
const creating = ref(false);
const moving = ref({});
const deleting = ref({});

const formError = ref("");
const formOk = ref("");
const listError = ref("");

const byId = computed(() => {
  const m = {};
  categories.value.forEach((c) => (m[c.id] = c));
  return m;
});

const filtered = computed(() => {
  const query = q.value.trim().toLowerCase();
  if (!query) return treeOrdered.value;

  return treeOrdered.value.filter((c) => {
    const name = String(c.name || "").toLowerCase();
    const code = String(c.code || "").toLowerCase();
    return name.includes(query) || code.includes(query);
  });
});

function normalizeParent(v) {
  return v === "" || v === undefined ? null : v;
}

async function apiGet(url) {
  const r = await fetch(url);
  const data = await r.json().catch(() => ({}));
  if (!r.ok) throw new Error("Ошибка загрузки");
  if (data?.error) throw new Error(data.error);
  return data;
}

async function apiPost(url, body) {
  const r = await fetch(url, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(body),
  });
  const data = await r.json().catch(() => ({}));
  if (!r.ok) throw new Error("Ошибка запроса");
  if (data?.error) throw new Error(data.error);
  return data;
}

async function loadCategories() {
  loading.value = true;
  listError.value = "";
  formOk.value = "";
  try {
    const flat = await apiGet("/api/admin/categories/get_categories.php");
    categories.value = flat;

    // предзаполняем селект переноса текущим parent_id
    const nextMove = {};
    flat.forEach((c) => (nextMove[c.id] = c.parent_id ?? null));
    moveTo.value = nextMove;

    treeOrdered.value = buildTreeOrder(flat);
  } catch (e) {
    listError.value = e?.message || "Ошибка";
  } finally {
    loading.value = false;
  }
}

function resetForm() {
  newName.value = "";
  newParent.value = null;
  formError.value = "";
  formOk.value = "";
}

async function createCategory() {
  const name = newName.value.trim();
  if (!name) return;

  creating.value = true;
  formError.value = "";
  formOk.value = "";
  try {
    await apiPost("/api/admin/categories/create_category.php", {
      name,
      parent_id: normalizeParent(newParent.value),
    });
    formOk.value = "Категория создана";
    newName.value = "";
    await loadCategories();
  } catch (e) {
    formError.value = e?.message || "Ошибка";
  } finally {
    creating.value = false;
  }
}

async function changeParent(id) {
  listError.value = "";
  const current = byId.value[id]?.parent_id ?? null;
  const target = normalizeParent(moveTo.value[id]);

  if (target === current) return; // ничего не меняли

  moving.value[id] = true;
  try {
    await apiPost("/api/admin/categories/change_parent.php", {
      id,
      parent_id: target,
    });
    await loadCategories();
  } catch (e) {
    listError.value = e?.message || "Ошибка";
  } finally {
    moving.value[id] = false;
  }
}

async function deleteCategory(id, name) {
  listError.value = "";
  if (!confirm(`Удалить категорию «${name}»?`)) return;

  deleting.value[id] = true;
  try {
    await apiPost("/api/admin/categories/delete_category.php", { id });
    await loadCategories();
  } catch (e) {
    listError.value = e?.message || "Ошибка";
  } finally {
    deleting.value[id] = false;
  }
}

function buildTreeOrder(list) {
  const map = {};
  const roots = [];

  // создаём map
  list.forEach((c) => {
    map[c.id] = { ...c, children: [], hasChildren: false };
  });

  // собираем дерево
  list.forEach((c) => {
    if (c.parent_id !== null && c.parent_id !== undefined) {
      if (map[c.parent_id]) {
        map[c.parent_id].children.push(map[c.id]);
        map[c.parent_id].hasChildren = true;
      } else {
        // если родитель не найден (грязные данные) — считаем корнем
        roots.push(map[c.id]);
      }
    } else {
      roots.push(map[c.id]);
    }
  });

  // рекурсивно разворачиваем в плоский список
  const result = [];

  function walk(node) {
    result.push(node);
    if (opened.value[node.id] === false) return;

    node.children.sort((a, b) => a.sort - b.sort).forEach(walk);
  }

  roots.sort((a, b) => a.sort - b.sort).forEach(walk);

  return result;
}

function toggle(id) {
  opened.value[id] = opened.value[id] === false;
  treeOrdered.value = buildTreeOrder(categories.value);
}

function collapseAll() {
  const o = {};
  categories.value.forEach((c) => (o[c.id] = false));
  opened.value = o;
  treeOrdered.value = buildTreeOrder(categories.value);
}

function expandAll() {
  opened.value = {};
  treeOrdered.value = buildTreeOrder(categories.value);
}

onMounted(loadCategories);
</script>

<style scoped>
.admin-page {
  --bg: #f6f7fb;
  --card: #ffffff;
  --text: #0f172a;
  --muted: #64748b;
  --muted2: #94a3b8;
  --stroke: rgba(15, 23, 42, 0.10);
  --stroke2: rgba(15, 23, 42, 0.14);
  --shadow: 0 10px 26px rgba(15, 23, 42, 0.08);
  --shadow2: 0 14px 34px rgba(15, 23, 42, 0.10);
  --radius: 16px;
  --radius-sm: 12px;
  --accent: #2563eb;
  --accent-soft: rgba(37, 99, 235, 0.12);
  --danger: #dc2626;
  --danger-soft: rgba(220, 38, 38, 0.12);
  --ok: #16a34a;
  --ok-soft: rgba(22, 163, 74, 0.12);

  max-width: 1200px;
  margin: 0 auto;
  padding: clamp(14px, 2.4vw, 24px);
  color: var(--text);
}

/* ===== Header ===== */
.page-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 14px;
  margin-bottom: 14px;
}

.page-title {
  margin: 0;
  font-size: clamp(20px, 2.2vw, 28px);
  font-weight: 900;
  letter-spacing: -0.02em;
  color: var(--text);
}

.page-subtitle {
  margin-top: 6px;
  color: var(--muted);
  font-size: 13px;
  line-height: 1.35;
  max-width: 760px;
}

/* ===== Layout ===== */
.grid {
  display: grid;
  grid-template-columns: 380px 1fr;
  gap: 14px;
  align-items: start;
}

@media (max-width: 1020px) {
  .grid {
    grid-template-columns: 1fr;
  }
}

/* ===== Cards ===== */
.card {
  background: var(--card);
  border: 1px solid var(--stroke);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: 14px;
}

.card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 12px;
}

.card-title {
  margin: 0;
  font-size: 15px;
  font-weight: 900;
  color: var(--text);
}

.pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 34px;
  height: 24px;
  padding: 0 10px;
  border-radius: 999px;
  background: var(--accent-soft);
  color: #1d4ed8;
  font-weight: 900;
  font-size: 12px;
}

/* ===== List head ===== */
.list-head {
  align-items: flex-start;
  gap: 12px;
}

.list-head-left {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.muted {
  font-size: 12px;
  color: var(--muted2);
}

.list-head-right {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

/* ===== Form ===== */
.form {
  display: grid;
  gap: 10px;
}

.label {
  font-size: 12px;
  color: var(--muted);
  font-weight: 900;
}

.input,
.select {
  width: 100%;
  padding: 10px 12px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--stroke2);
  background: #fff;
  color: var(--text);
  outline: none;
  transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
}

.input::placeholder {
  color: rgba(100, 116, 139, 0.9);
}

.input.small {
  padding: 9px 10px;
  border-radius: var(--radius-sm);
  width: 220px;
}

.select.small {
  padding: 8px 10px;
  border-radius: var(--radius-sm);
  min-width: 240px;
}

.input:focus,
.select:focus {
  border-color: rgba(37, 99, 235, 0.55);
  box-shadow: 0 0 0 4px var(--accent-soft);
}

/* ===== Buttons ===== */
.form-actions {
  display: flex;
  gap: 10px;
  margin-top: 6px;
  flex-wrap: wrap;
}

.btn {
  border: 1px solid transparent;
  border-radius: var(--radius-sm);
  padding: 10px 12px;
  font-weight: 900;
  font-size: 13px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: transform .12s ease, box-shadow .12s ease, background .12s ease, border-color .12s ease;
  user-select: none;
  white-space: nowrap;
}

.btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.btn.primary {
  background: var(--accent);
  color: #fff;
  box-shadow: 0 10px 18px rgba(37, 99, 235, 0.22);
}

.btn.primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 14px 22px rgba(37, 99, 235, 0.26);
}

.btn.soft {
  background: rgba(15, 23, 42, 0.05);
  color: var(--text);
  border-color: rgba(15, 23, 42, 0.08);
}

.btn.soft:hover:not(:disabled),
.btn.ghost:hover:not(:disabled) {
  background: rgba(15, 23, 42, 0.08);
}

.btn.ghost {
  background: transparent;
  color: var(--text);
  border: 1px solid rgba(15, 23, 42, 0.14);
}

.btn.danger {
  background: var(--danger-soft);
  color: #b91c1c;
  border: 1px solid rgba(220, 38, 38, 0.22);
}

.btn.danger:hover:not(:disabled) {
  background: rgba(220, 38, 38, 0.16);
}

.btn:focus-visible {
  outline: none;
  box-shadow: 0 0 0 4px var(--accent-soft);
}

/* ===== Notices ===== */
.notice {
  margin-top: 8px;
  border-radius: var(--radius-sm);
  padding: 10px 12px;
  font-weight: 900;
  font-size: 13px;
}

.notice.error {
  background: rgba(220, 38, 38, 0.10);
  border: 1px solid rgba(220, 38, 38, 0.20);
  color: #991b1b;
}

.notice.ok {
  background: rgba(22, 163, 74, 0.10);
  border: 1px solid rgba(22, 163, 74, 0.18);
  color: #065f46;
}

/* ===== Empty ===== */
.empty {
  padding: 16px;
  border-radius: var(--radius);
  background: rgba(15, 23, 42, 0.04);
  color: var(--muted);
  font-weight: 900;
}
/* ===== Tree list ===== */
.tree {
  display: grid;
  gap: 8px;
}

/* ✅ ДЕСКТОП: строка в линию (слева контент, справа действия) */
.row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 12px;
  border-radius: 14px;
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: #fff;
  transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
}

.row:hover {
  border-color: rgba(37, 99, 235, 0.28);
  box-shadow: 0 10px 22px rgba(15, 23, 42, 0.07);
}

.row.root {
  background: linear-gradient(90deg, rgba(37, 99, 235, 0.06), rgba(255, 255, 255, 0));
}

/* main */
.row-main {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
  flex: 1 1 auto;
}

/* twisty */
.twisty {
  width: 30px;
  height: 30px;
  border-radius: 10px;
  border: 1px solid rgba(15, 23, 42, 0.12);
  background: rgba(15, 23, 42, 0.03);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  flex: 0 0 auto;
  transition: background .15s ease, border-color .15s ease;
}

.twisty:hover {
  background: rgba(15, 23, 42, 0.06);
  border-color: rgba(15, 23, 42, 0.16);
}

.twisty.placeholder {
  border-color: transparent;
  background: transparent;
}

.twisty i {
  transition: transform .15s ease;
  color: var(--text);
  font-size: 13px;
}

.twisty i.open {
  transform: rotate(90deg);
}

.code {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
  font-size: 12px;
  font-weight: 900;
  color: #1e293b;
  background: rgba(15, 23, 42, 0.06);
  padding: 4px 8px;
  border-radius: 999px;
  white-space: nowrap;
}

/* ✅ на десктопе — одна строка, аккуратный ellipsis */
.name {
  font-weight: 900;
  color: var(--text);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 520px;
  min-width: 0;
}

/* actions */
.row-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 0 0 auto;
  flex-wrap: nowrap;
  justify-content: flex-end;
}

/* чуть компактнее контролы в строке */
.select.small {
  min-width: 240px;
}

/* ===== Responsive details ===== */

/* планшет/малый ноут — шапка и кнопка обновить не сжимаются */
@media (max-width: 1020px) {
  .page-head-right {
    width: 100%;
  }
}

/* ✅ ТЕЛЕФОН/ПЛАНШЕТ: переносим list-head + row в колонку */
@media (max-width: 860px) {
  .page-head {
    flex-direction: column;
    align-items: flex-start;
  }

  .page-subtitle {
    max-width: 100%;
  }

  .list-head-right {
    width: 100%;
    justify-content: flex-start;
  }

  .input.small {
    width: 100%;
  }

  /* строка — в колонку */
  .row {
    flex-direction: column;
    align-items: stretch;
    padding: 12px;
  }

  .row-main {
    width: 100%;
  }

  /* ✅ actions в 2 колонки, чтобы не было каши */
  .row-actions {
    width: 100%;
    justify-content: flex-start;
    flex-wrap: wrap;
    gap: 10px;
  }

  .row-actions .select.small,
  .row-actions .btn {
    flex: 1 1 220px; /* 2 колонки на большинстве телефонов в landscape/планшете */
    min-width: 0;
  }

  .select.small {
    min-width: 0;
    width: 100%;
  }

  /* имя — можно переносить, без горизонтального скролла */
  .name {
    max-width: 100%;
  }
}

/* ✅ Очень маленькие экраны: всё в 1 колонку + удобные тапы */
@media (max-width: 520px) {
  .admin-page {
    padding: 12px;
  }

  .card {
    padding: 12px;
    border-radius: 14px;
  }

  /* кнопка "Обновить" и кнопки формы — на всю ширину */
  .page-head-right .btn,
  .form-actions .btn {
    width: 100%;
    justify-content: center;
  }

  /* actions — строго столбиком */
  .row-actions {
    gap: 8px;
  }

  .row-actions .select.small,
  .row-actions .btn {
    flex: 1 1 100%;
    width: 100%;
  }

  /* комфортная высота на мобиле */
  .input,
  .select,
  .btn {
    min-height: 44px;
  }

  .twisty {
    width: 28px;
    height: 28px;
    border-radius: 10px;
  }

  /* ✅ имя — максимум 2 строки */
  .name {
    white-space: normal;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
  }
}

/* Reduce motion */
@media (prefers-reduced-motion: reduce) {
  .row,
  .btn,
  .twisty,
  .input,
  .select {
    transition: none !important;
  }
}


</style>
