<template>
  <div class="page">

    <h2>Создать подкатегорию</h2>

    <div class="card">

      <!-- 1. Выбор категории -->
      <label class="label">Основная категория</label>
      <select v-model="rootId" class="select" @change="loadLevels">
        <option disabled value="">Выберите</option>
        <option v-for="c in rootCats" :key="c.id" :value="c.id">
          {{ c.level_code }} — {{ c.name }}
        </option>
      </select>

      <!-- 2. Название -->
      <label class="label">Название подкатегории</label>
      <input v-model="name" class="input" placeholder="Введите название..." />

      <!-- 3. Выбор уровня — С ПОНЯТНЫМ ОПИСАНИЕМ -->
      <label class="label">Куда создать?</label>
      <select v-model="chosenCode" class="select">

        <optgroup label="Уровень 2 (дочерние выбранной категории)">
          <option 
            v-for="c in levels.level2" 
            :key="c"
            :value="c"
          >
            {{ c }} — подкатегория категории "{{ getName(rootId) }}"
          </option>
        </optgroup>

        <optgroup label="Уровень 3 (внутри подкатегорий)">
          <option 
            v-for="c in levels.level3" 
            :key="c"
            :value="c"
          >
            {{ c }} — внутри подкатегории "{{ parentName(c) }}"
          </option>
        </optgroup>

      </select>

      <button class="btn" @click="create">Создать</button>

    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";

const categories = ref([]);
const rootId = ref("");
const name = ref("");
const chosenCode = ref("");

const levels = ref({ level2: [], level3: [] });

// Загрузка всех категорий
async function loadCats() {
  const res = await fetch("/api/admin/get_categories.php");
  categories.value = await res.json();
}
onMounted(loadCats);

const rootCats = computed(() => categories.value.filter(c => c.level === 1));

// Получение понятного имени категории по id
function getName(id) {
  const c = categories.value.find(x => x.id == id);
  return c ? c.name : "";
}

// Имя родителя по level_code
function parentName(level_code) {
  if (!level_code) return "";

  const parts = level_code.split(".");
  parts.pop();
  const parentCode = parts.join(".");

  const parent = categories.value.find(c => c.level_code == parentCode);
  return parent ? parent.name : parentCode;
}

// загрузка доступных уровней
async function loadLevels() {
  if (!rootId.value) return;

  const res = await fetch("/api/admin/get_available_levels.php?root_id=" + rootId.value);
  levels.value = await res.json();
}

// создание
async function create() {
  if (!name.value || !chosenCode.value) {
    alert("Введите данные");
    return;
  }

  const res = await fetch("/api/admin/create_subcategory.php", {
    method: "POST",
    body: JSON.stringify({
      name: name.value,
      level_code: chosenCode.value
    })
  });

  const data = await res.json();

  if (data.success) {
    alert("Создано!");
    name.value = "";
    chosenCode.value = "";
    await loadCats();
    await loadLevels();
  } else {
    alert("Ошибка: " + data.error);
  }
}
</script>

<style scoped>
.page { padding: 20px; max-width: 500px; margin: auto; color: white; }
.card {
  background: rgba(38,40,44,0.6);
  padding: 24px;
  border-radius: 14px;
  backdrop-filter: blur(6px);
}
.label { font-size: 14px; margin-bottom: 4px; display: block; }
.select, .input {
  width: 100%;
  padding: 10px;
  border-radius: 8px;
  background: #1d1f22;
  border: 1px solid #333;
  margin-bottom: 16px;
  color: white;
}
.btn {
  width: 100%;
  padding: 12px;
  font-size: 16px;
  background: #4c9fff;
  border: none;
  color: white;
  border-radius: 8px;
  cursor: pointer;
}
</style>
