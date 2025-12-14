<template>
  <div class="admin-page">
    <h1 class="page-title">Категории</h1>

    <!-- ===== СОЗДАНИЕ ===== -->
    <div class="card">
      <h2 class="card-title">Создать категорию</h2>

      <div class="form-row">
        <input
          v-model="newName"
          class="input"
          placeholder="Название категории"
        />

        <select v-model="newParent" class="select">
          <option :value="null">Без родителя (корень)</option>
          <option v-for="c in categories" :key="c.id" :value="c.id">
            {{ c.code }} — {{ c.name }}
          </option>
        </select>

        <button class="btn primary" @click="createCategory">Создать</button>
      </div>
    </div>

    <!-- ===== СПИСОК ===== -->
    <div class="card">
      <h2 class="card-title">Существующие категории</h2>

      <div v-if="categories.length === 0" class="empty">Категорий пока нет</div>
      <div
        v-for="c in treeOrdered"
        :key="c.id"
        class="category-row"
        :style="{ paddingLeft: (c.level - 1) * 24 + 'px' }"
      >
<div class="cat-main">
<i
  v-if="c.hasChildren"
  class="fa-solid fa-chevron-right arrow"
  :class="{ open: opened[c.id] !== false }"
  @click.stop="toggle(c.id)"
></i>


<i
  v-else
  class="fa-solid fa-chevron-right arrow empty"
></i>


  <span class="cat-code">{{ c.code }}</span>
  <span class="cat-name">{{ c.name }}</span>
</div>


        <div class="cat-actions">
          <select v-model="moveTo[c.id]" class="select small">
            <option :value="null">Корень</option>
            <option
              v-for="p in categories"
              :key="p.id"
              :value="p.id"
              :disabled="p.id === c.id"
            >
              {{ p.code }} — {{ p.name }}
            </option>
          </select>

          <button class="btn ghost" @click="changeParent(c.id)">
            Перенести
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";

const categories = ref([]);
const treeOrdered = ref([]);
const opened = ref({});

const newName = ref("");
const newParent = ref(null);
const moveTo = ref({});

async function loadCategories() {
  const r = await fetch("/api/admin/categories/get_categories.php");
  const flat = await r.json();

  categories.value = flat;
  treeOrdered.value = buildTreeOrder(flat);
}

async function createCategory() {
  if (!newName.value.trim()) return;

  await fetch("/api/admin/categories/create_category.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      name: newName.value,
      parent_id: newParent.value,
    }),
  });

  newName.value = "";
  newParent.value = null;

  loadCategories();
}

async function changeParent(id) {
  await fetch("/api/admin/categories/change_parent.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      id,
      parent_id: moveTo.value[id],
    }),
  });

  loadCategories();
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
    if (c.parent_id) {
      map[c.parent_id]?.children.push(map[c.id]);
      map[c.parent_id].hasChildren = true;
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


onMounted(loadCategories);
</script>

<style scoped>
.admin-page {
  padding: 24px;
  max-width: 1100px;
  margin: 0 auto;
}

.page-title {
  font-size: 28px;
  margin-bottom: 20px;
}

.card {
  background: #1e1f23;
  border-radius: 16px;
  padding: 20px;
  margin-bottom: 20px;
}

.card-title {
  font-size: 20px;
  margin-bottom: 16px;
}

.form-row {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.input,
.select {
  background: #2a2b30;
  border: 1px solid #3a3b40;
  border-radius: 10px;
  padding: 10px 12px;
  color: #fff;
  min-width: 220px;
}

.select.small {
  min-width: 200px;
}

.input::placeholder {
  color: #888;
}

.btn {
  border-radius: 10px;
  padding: 10px 16px;
  cursor: pointer;
  border: none;
  font-weight: 500;
}

.btn.primary {
  background: #4f7cff;
  color: #fff;
}

.btn.primary:hover {
  background: #3f6cf0;
}

.btn.ghost {
  background: transparent;
  border: 1px solid #3a3b40;
  color: #fff;
}

.btn.ghost:hover {
  background: #2a2b30;
}

.category-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 0;
  border-bottom: 1px dashed #333;
}

.cat-main {
  display: flex;
  gap: 12px;
  align-items: center;
}

.cat-code {
  background: #333;
  border-radius: 8px;
  padding: 4px 8px;
  font-size: 13px;
  color: #aaa;
}

.cat-name {
  font-size: 15px;
}

.cat-actions {
  display: flex;
  gap: 8px;
  align-items: center;
}

.empty {
  color: #777;
  font-style: italic;
}

.arrow {
  width: 20px;
  display: inline-flex;
  justify-content: center;
  align-items: center;
  cursor: pointer;
  color: #aaa;
  transition: transform 0.25s ease, color 0.2s ease;
}

.arrow.open {
  transform: rotate(90deg);
  color: #fff;
}

.arrow.empty {
  visibility: hidden;
}



</style>
