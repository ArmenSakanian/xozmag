<template>
  <div class="page">

    <h2 class="title">Создать подкатегорию</h2>

    <div class="card">

      <label class="label">Основная категория</label>
      <select v-model="rootId" class="select" @change="loadLevels">
        <option disabled value="">Выберите</option>
        <option v-for="c in rootCats" :key="c.id" :value="c.id">
          {{ c.level_code }} — {{ c.name }}
        </option>
      </select>

      <label class="label">Название подкатегории</label>
      <input v-model="name" class="input" placeholder="Введите название..." />

      <label class="label">Куда создать?</label>
      <select v-model="chosenCode" class="select">

        <optgroup label="Уровень 2">
          <option 
            v-for="c in levels.level2" 
            :key="c"
            :value="c"
          >
            {{ c }} — подкатегория "{{ getName(rootId) }}"
          </option>
        </optgroup>

        <optgroup label="Уровень 3">
          <option 
            v-for="c in levels.level3" 
            :key="c"
            :value="c"
          >
            {{ c }} — внутри "{{ parentName(c) }}"
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

async function loadCats() {
  const res = await fetch("/api/admin/categories/get_categories.php");
  categories.value = await res.json();
}
onMounted(loadCats);

const rootCats = computed(() => categories.value.filter(c => c.level === 1));

function getName(id) {
  const c = categories.value.find(x => x.id == id);
  return c ? c.name : "";
}

function parentName(level_code) {
  const parts = level_code.split(".");
  parts.pop();
  const parentCode = parts.join(".");
  const parent = categories.value.find(c => c.level_code == parentCode);
  return parent ? parent.name : parentCode;
}

async function loadLevels() {
  if (!rootId.value) return;
  const res = await fetch("/api/admin/subcategory/get_available_levels.php?root_id=" + rootId.value);
  levels.value = await res.json();
}

async function create() {
  if (!name.value || !chosenCode.value) {
    alert("Введите данные");
    return;
  }

  const res = await fetch("/api/admin/subcategory/create_subcategory.php", {
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
