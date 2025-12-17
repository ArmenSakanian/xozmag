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
  max-width: 1200px;
  margin: 0 auto;
  padding: 22px;
}

.page-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 14px;
  margin-bottom: 14px;
}

.page-title {
  margin: 0;
  font-size: 26px;
  font-weight: 800;
  letter-spacing: -0.02em;
  color: #0f172a;
}

.page-subtitle {
  margin-top: 6px;
  color: #64748b;
  font-size: 13px;
  line-height: 1.35;
  max-width: 760px;
}

.grid {
  display: grid;
  grid-template-columns: 380px 1fr;
  gap: 14px;
  align-items: start;
}

@media (max-width: 980px) {
  .grid {
    grid-template-columns: 1fr;
  }
}

.card {
  background: #ffffff;
  border: 1px solid rgba(15, 23, 42, 0.08);
  border-radius: 16px;
  box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06);
  padding: 14px;
}

.card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 12px;
}

.list-head {
  align-items: flex-start;
}

.list-head-left {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.list-head-right {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.card-title {
  margin: 0;
  font-size: 15px;
  font-weight: 800;
  color: #0f172a;
}

.muted {
  font-size: 12px;
  color: #94a3b8;
}

.pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 34px;
  height: 24px;
  padding: 0 10px;
  border-radius: 999px;
  background: rgba(59, 130, 246, 0.12);
  color: #1d4ed8;
  font-weight: 700;
  font-size: 12px;
}

.form {
  display: grid;
  gap: 10px;
}

.label {
  font-size: 12px;
  color: #64748b;
  font-weight: 700;
}

.input,
.select {
  width: 100%;
  padding: 10px 12px;
  border-radius: 12px;
  border: 1px solid rgba(15, 23, 42, 0.14);
  background: #fff;
  color: #0f172a;
  outline: none;
  transition: 0.15s;
}

.input.small {
  padding: 9px 10px;
  border-radius: 12px;
  width: 220px;
}

.select.small {
  padding: 8px 10px;
  border-radius: 12px;
  min-width: 240px;
}

.input:focus,
.select:focus {
  border-color: rgba(59, 130, 246, 0.6);
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
}

.form-actions {
  display: flex;
  gap: 10px;
  margin-top: 6px;
}

.btn {
  border: none;
  border-radius: 12px;
  padding: 10px 12px;
  font-weight: 800;
  font-size: 13px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: 0.15s;
  user-select: none;
}

.btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.btn.primary {
  background: #2563eb;
  color: #fff;
  box-shadow: 0 10px 18px rgba(37, 99, 235, 0.22);
}

.btn.primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 14px 24px rgba(37, 99, 235, 0.26);
}

.btn.soft {
  background: rgba(15, 23, 42, 0.06);
  color: #0f172a;
}

.btn.soft:hover:not(:disabled),
.btn.ghost:hover:not(:disabled) {
  background: rgba(15, 23, 42, 0.09);
}

.btn.ghost {
  background: transparent;
  color: #0f172a;
  border: 1px solid rgba(15, 23, 42, 0.14);
}

.btn.danger {
  background: rgba(239, 68, 68, 0.1);
  color: #b91c1c;
  border: 1px solid rgba(239, 68, 68, 0.22);
}

.btn.danger:hover:not(:disabled) {
  background: rgba(239, 68, 68, 0.14);
}

.notice {
  margin-top: 8px;
  border-radius: 12px;
  padding: 10px 12px;
  font-weight: 700;
  font-size: 13px;
}

.notice.error {
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.22);
  color: #991b1b;
}

.notice.ok {
  background: rgba(16, 185, 129, 0.1);
  border: 1px solid rgba(16, 185, 129, 0.2);
  color: #065f46;
}

.empty {
  padding: 18px;
  border-radius: 14px;
  background: rgba(15, 23, 42, 0.04);
  color: #64748b;
  font-weight: 700;
}

.tree {
  display: grid;
  gap: 8px;
}

.row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 10px;
  border-radius: 14px;
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: #fff;
  transition: 0.15s;
}

.row:hover {
  border-color: rgba(59, 130, 246, 0.35);
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
}

.row.root {
  background: linear-gradient(
    90deg,
    rgba(59, 130, 246, 0.08),
    rgba(255, 255, 255, 0)
  );
}

.row-main {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}

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
  transition: 0.15s;
}

.twisty:hover {
  background: rgba(15, 23, 42, 0.06);
}

.twisty.placeholder {
  border-color: transparent;
  background: transparent;
}

.twisty i {
  transition: 0.15s;
  color: #0f172a;
}

.twisty i.open {
  transform: rotate(90deg);
}

.code {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas,
    "Liberation Mono", "Courier New", monospace;
  font-size: 12px;
  font-weight: 800;
  color: #1e293b;
  background: rgba(15, 23, 42, 0.06);
  padding: 4px 8px;
  border-radius: 999px;
  white-space: nowrap;
}

.name {
  font-weight: 800;
  color: #0f172a;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 520px;
}

.row-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 0 0 auto;
  flex-wrap: wrap;
  justify-content: flex-end;
}

@media (max-width: 980px) {
  .input.small {
    width: 100%;
  }
  .select.small {
    min-width: 100%;
  }
  .name {
    max-width: 100%;
  }
  .row {
    align-items: flex-start;
  }
  .row-actions {
    width: 100%;
    justify-content: flex-start;
  }
}
</style>
