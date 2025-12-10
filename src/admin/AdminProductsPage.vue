<template>
  <div class="admin-products">
    <h1>Добавление товара</h1>

    <div class="form">

      <!-- НАЗВАНИЕ -->
      <label>Название товара</label>
      <input v-model="name" type="text" placeholder="Например: Эмаль ПФ-115" />

      <!-- АРТИКУЛ -->
      <label>Артикул</label>
      <input v-model="article" type="text" placeholder="Например: 115-ЭМ" />

      <!-- КАТЕГОРИЯ -->
      <label>Категория</label>

      <input 
        v-model="categorySearch" 
        type="text" 
        placeholder="Поиск категории..."
        class="search-input"
      />

      <select v-model="category_id">
        <option 
          v-for="c in filteredCategories" 
          :key="c.id"
          :value="c.id"
        >
          {{ c.full_name }}
        </option>
      </select>

      <!-- ЦЕНА -->
      <label>Цена продажи</label>
      <input v-model="price" type="number" step="0.01" />

      <!-- ШТРИХКОД -->
      <label>Штрихкод</label>
      <input v-model="barcode" type="text" placeholder="Например: 4601234567890" />

      <!-- ОПИСАНИЕ -->
      <label>Описание</label>
      <textarea v-model="description" rows="4"></textarea>

      <!-- КНОПКА -->
      <button @click="saveProduct" class="save-btn">
        Создать товар
      </button>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";

const name = ref("");
const article = ref("");
const category_id = ref(null);
const price = ref("");
const barcode = ref("");
const description = ref("");

const categories = ref([]);
const categorySearch = ref("");

// === ЗАГРУЗКА КАТЕГОРИЙ ===
onMounted(async () => {
  const res = await fetch("/api/admin/get_categories_flat.php");
  categories.value = await res.json();
});

// ФИЛЬТРАЦИЯ ПО ПОИСКУ
const filteredCategories = computed(() => {
  return categories.value.filter(c =>
    c.full_name.toLowerCase().includes(categorySearch.value.toLowerCase())
  );
});

// === СОХРАНЕНИЕ ТОВАРА ===
async function saveProduct() {
  const body = {
    name: name.value,
    article: article.value,
    category_id: category_id.value,
    price: price.value,
    barcode: barcode.value,
    description: description.value,
  };

  const res = await fetch("/api/admin/create_product.php", {
    method: "POST",
    body: JSON.stringify(body),
  });

  const data = await res.json();

  if (data.success) {
    alert("Товар создан!");
  } else {
    alert("Ошибка: " + data.error);
  }
}
</script>

<style>
.admin-products {
  padding: 20px;
  max-width: 500px;
}

.form {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.search-input {
  margin-bottom: -5px;
}

.save-btn {
  margin-top: 20px;
  padding: 12px;
  background: #2a7df4;
  color: white;
  border-radius: 8px;
  cursor: pointer;
  border: none;
}
</style>
