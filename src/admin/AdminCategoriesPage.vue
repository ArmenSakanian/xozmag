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
          <Fa :icon="['fas','rotate-right']" />
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
              {{ c.code }} - {{ c.name }}
            </option>
          </select>

          <div class="form-actions">
            <button
              class="btn primary"
              @click="createCategory"
              :disabled="creating || !newName.trim()"
            >
              <Fa :icon="['fas','plus']" />
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
                <Fa
                 
                  :class="{ open: opened[c.id] !== false }"
                 :icon="['fas','chevron-right']" />
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
                  {{ p.code }} - {{ p.name }}
                </option>
              </select>

              <button
                class="btn ghost"
                @click="changeParent(c.id)"
                :disabled="!!moving[c.id]"
                title="Переместить категорию"
              >
                <Fa :icon="['fas','arrow-right-arrow-left']" />
                Перенести
              </button>

              <button
                class="btn danger"
                @click="deleteCategory(c.id, c.name)"
                :disabled="!!deleting[c.id]"
                title="Удалить категорию"
              >
                <Fa :icon="['fas','trash']" />
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
        // если родитель не найден (грязные данные) - считаем корнем
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
.admin-page{
  max-width: 1200px;
  margin: 0 auto;
  padding: clamp(14px, 2.4vw, 24px);
  color: var(--text-main);
}

/* ===== Header ===== */
.page-head{
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 14px;
  margin-bottom: 14px;
}

.page-title{
  margin: 0;
  font-size: clamp(20px, 2.2vw, 28px);
  font-weight: 900;
  letter-spacing: -0.02em;
  color: var(--text-main);
}

.page-subtitle{
  margin-top: 6px;
  color: var(--text-muted);
  font-size: 13px;
  line-height: 1.35;
  max-width: 760px;
}

/* ===== Layout ===== */
.grid{
  display: grid;
  grid-template-columns: 380px 1fr;
  gap: 14px;
  align-items: start;
}
@media (max-width: 1020px){
  .grid{ grid-template-columns: 1fr; }
}

/* ===== Cards ===== */
.card{
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  padding: 14px;
}

.card-head{
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 12px;
}

.card-title{
  margin: 0;
  font-size: 15px;
  font-weight: 900;
  color: var(--text-main);
}

.pill{
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 34px;
  height: 24px;
  padding: 0 10px;
  border-radius: var(--radius-lg);
  background: color-mix(in srgb, var(--accent) 12%, var(--bg-panel));
  color: var(--accent);
  border: 1px solid color-mix(in srgb, var(--accent) 25%, var(--border-soft));
  font-weight: 900;
  font-size: 12px;
}

/* ===== List head ===== */
.list-head{
  align-items: flex-start;
  gap: 12px;
}

.list-head-left{
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.muted{
  font-size: 12px;
  color: var(--text-light);
}

.list-head-right{
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

/* ===== Form ===== */
.form{
  display: grid;
  gap: 10px;
}

.label{
  font-size: 12px;
  color: var(--text-muted);
  font-weight: 900;
}

.input,
.select{
  width: 100%;
  padding: 10px 12px;
  border-radius: var(--radius-md);
  border: 1px solid var(--border-soft);
  background: var(--bg-panel);
  color: var(--text-main);
  outline: none;
  transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
}

.input::placeholder{
  color: var(--text-light);
}

.input.small{
  padding: 9px 10px;
  border-radius: var(--radius-md);
  width: 220px;
  background: var(--bg-soft);
}

.select{
  background: var(--bg-panel);
}

.select.small{
  padding: 8px 10px;
  border-radius: var(--radius-md);
  min-width: 240px;
  background: var(--bg-soft);
}

.input:focus,
.select:focus{
  border-color: color-mix(in srgb, var(--accent) 55%, var(--border-soft));
  box-shadow: 0 0 0 4px color-mix(in srgb, var(--accent) 18%, transparent);
  background: var(--bg-panel);
}

/* ===== Buttons ===== */
.form-actions{
  display: flex;
  gap: 10px;
  margin-top: 6px;
  flex-wrap: wrap;
}

.btn{
  border: 1px solid transparent;
  border-radius: var(--radius-md);
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

.btn:disabled{
  opacity: 0.55;
  cursor: not-allowed;
}

/* primary */
.btn.primary{
  background: var(--accent);
  color: #fff;
  box-shadow: var(--shadow-sm);
  border-color: color-mix(in srgb, var(--accent) 55%, var(--border-soft));
}
.btn.primary:hover:not(:disabled){
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
  filter: brightness(1.03);
}

/* soft */
.btn.soft{
  background: var(--bg-soft);
  color: var(--text-main);
  border-color: var(--border-soft);
}
.btn.soft:hover:not(:disabled),
.btn.ghost:hover:not(:disabled){
  background: color-mix(in srgb, var(--accent) 6%, var(--bg-soft));
  border-color: color-mix(in srgb, var(--accent) 22%, var(--border-soft));
}

/* ghost */
.btn.ghost{
  background: transparent;
  color: var(--text-main);
  border: 1px solid var(--border-soft);
}

/* danger */
.btn.danger{
  background: color-mix(in srgb, var(--accent-danger) 10%, var(--bg-panel));
  color: var(--accent-danger);
  border: 1px solid color-mix(in srgb, var(--accent-danger) 28%, var(--border-soft));
}
.btn.danger:hover:not(:disabled){
  background: color-mix(in srgb, var(--accent-danger) 14%, var(--bg-panel));
}

.btn:focus-visible{
  outline: none;
  box-shadow: 0 0 0 4px color-mix(in srgb, var(--accent) 18%, transparent);
}

/* ===== Notices ===== */
.notice{
  margin-top: 8px;
  border-radius: var(--radius-md);
  padding: 10px 12px;
  font-weight: 900;
  font-size: 13px;
  border: 1px solid var(--border-soft);
}

.notice.error{
  background: color-mix(in srgb, var(--accent-danger) 10%, var(--bg-panel));
  border-color: color-mix(in srgb, var(--accent-danger) 25%, var(--border-soft));
  color: var(--accent-danger);
}

.notice.ok{
  background: color-mix(in srgb, var(--accent-2) 10%, var(--bg-panel));
  border-color: color-mix(in srgb, var(--accent-2) 22%, var(--border-soft));
  color: var(--accent-2);
}

/* ===== Empty ===== */
.empty{
  padding: 16px;
  border-radius: var(--radius-lg);
  background: var(--bg-soft);
  color: var(--text-muted);
  font-weight: 900;
  border: 1px solid var(--border-soft);
}

/* ===== Tree list ===== */
.tree{
  display: grid;
  gap: 8px;
}

/* строка */
.row{
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;

  padding: 10px 12px;
  border-radius: var(--radius-md);
  border: 1px solid var(--border-soft);
  background: var(--bg-panel);

  transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease, background .15s ease;
}

.row:hover{
  border-color: color-mix(in srgb, var(--accent) 22%, var(--border-soft));
  box-shadow: var(--shadow-sm);
}

.row.root{
  background: linear-gradient(
    90deg,
    color-mix(in srgb, var(--accent) 6%, var(--bg-panel)),
    var(--bg-panel)
  );
}

/* main */
.row-main{
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
  flex: 1 1 auto;
}

/* twisty */
.twisty{
  width: 30px;
  height: 30px;
  border-radius: var(--radius-md);
  border: 1px solid var(--border-soft);
  background: var(--bg-soft);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  flex: 0 0 auto;
  transition: background .15s ease, border-color .15s ease;
}

.twisty:hover{
  background: color-mix(in srgb, var(--accent) 6%, var(--bg-soft));
  border-color: color-mix(in srgb, var(--accent) 22%, var(--border-soft));
}

.twisty.placeholder{
  border-color: transparent;
  background: transparent;
}

.twisty svg {
  transition: transform .15s ease;
  color: var(--text-main);
  font-size: 13px;
}
.twisty svg.open{
  transform: rotate(90deg);
}

/* code pill */
.code{
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
  font-size: 12px;
  font-weight: 900;

  color: var(--text-main);
  background: var(--bg-soft);
  border: 1px solid var(--border-soft);

  padding: 4px 8px;
  border-radius: var(--radius-lg);
  white-space: nowrap;
}

/* name */
.name{
  font-weight: 900;
  color: var(--text-main);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 520px;
  min-width: 0;
}

/* actions */
.row-actions{
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 0 0 auto;
  flex-wrap: nowrap;
  justify-content: flex-end;
}

/* ===== Responsive ===== */
@media (max-width: 1020px){
  .page-head-right{ width: 100%; }
}

@media (max-width: 860px){
  .page-head{
    flex-direction: column;
    align-items: flex-start;
  }

  .page-subtitle{ max-width: 100%; }

  .list-head-right{
    width: 100%;
    justify-content: flex-start;
  }

  .input.small{ width: 100%; }

  .row{
    flex-direction: column;
    align-items: stretch;
    padding: 12px;
  }

  .row-main{ width: 100%; }

  .row-actions{
    width: 100%;
    justify-content: flex-start;
    flex-wrap: wrap;
    gap: 10px;
  }

  .row-actions .select.small,
  .row-actions .btn{
    flex: 1 1 220px;
    min-width: 0;
  }

  .select.small{
    min-width: 0;
    width: 100%;
  }

  .name{ max-width: 100%; }
}

@media (max-width: 520px){
  .admin-page{ padding: 12px; }

  .card{
    padding: 12px;
    border-radius: var(--radius-lg);
  }

  .page-head-right .btn,
  .form-actions .btn{
    width: 100%;
    justify-content: center;
  }

  .row-actions{ gap: 8px; }

  .row-actions .select.small,
  .row-actions .btn{
    flex: 1 1 100%;
    width: 100%;
  }

  .input,
  .select,
  .btn{
    min-height: 44px;
  }

  .twisty{
    width: 28px;
    height: 28px;
  }

  .name{
    white-space: normal;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
  }
}

@media (prefers-reduced-motion: reduce){
  .row,
  .btn,
  .twisty,
  .input,
  .select{
    transition: none !important;
  }
}
</style>

