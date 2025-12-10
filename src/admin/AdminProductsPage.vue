<template>
  <div class="admin-products">
    <h1>Добавление товара</h1>

    <div class="form">

      <!-- === НАЗВАНИЕ === -->
      <label>Название товара</label>
      <input v-model="name" type="text" placeholder="Например: Эмаль ПФ-115" />

      <!-- === АРТИКУЛ === -->
      <label>Артикул</label>
      <input v-model="article" type="text" placeholder="Например: PF115-01" />

      <!-- === КАТЕГОРИЯ === -->
      <label>Категория</label>

      <input 
        v-model="categorySearch" 
        type="text" 
        placeholder="Поиск категории..."
        class="search-input"
      />

      <select v-model="category_id">
        <option value="">Выберите категорию</option>
        <option 
          v-for="c in filteredCategories" 
          :key="c.id"
          :value="c.id"
        >
          {{ c.full_name }}
        </option>
      </select>

      <!-- === ЦЕНА === -->
      <label>Цена продажи</label>
      <input v-model="price" type="number" step="0.01" />

      <!-- === ШТРИХКОД === -->
      <label>Штрихкод</label>
      <input v-model="barcode" type="text" placeholder="4601234567890" />

      <!-- === ОПИСАНИЕ === -->
      <label>Описание</label>
      <textarea v-model="description" rows="3"></textarea>

      <!-- ===================================================== -->
      <!-- === ХАРАКТЕРИСТИКИ ================================== -->
      <!-- ===================================================== -->

      <h3 class="block-title">Характеристики</h3>

      <!-- === создание нового заголовка характеристики === -->
      <div class="new-attr-title">
        <input 
          v-model="newAttrName" 
          placeholder="Новый заголовок (Тип краски, Объём...)"
        />
        <button @click="createNewAttribute" class="small-btn">Создать</button>
      </div>

      <!-- === добавление значения характеристики === -->
      <div class="attr-add">

        <select v-model="attr.attribute_id">
          <option value="">Выберите заголовок</option>
          <option v-for="a in allAttributes" :value="a.id">
            {{ a.name }}
          </option>
        </select>

        <input 
          v-model="attr.value"
          placeholder="Значение (Масляная, 6 кг...)"
        />

        <button @click="addAttribute" class="small-btn">Добавить</button>
      </div>

      <!-- === список характеристик === -->
      <ul class="attr-list">
        <li v-for="(a, index) in attributes" :key="index">
          <span>{{ getAttributeName(a.attribute_id) }}:</span>
          <b>{{ a.value }}</b>
          <button @click="removeAttribute(index)" class="delete-btn">✕</button>
        </li>
      </ul>

      <!-- === КНОПКА СОХРАНЕНИЯ === -->
      <button @click="saveProduct" class="save-btn">
        Создать товар
      </button>

    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";

// === ПОЛЯ ТОВАРА ===
const name = ref("");
const article = ref("");
const category_id = ref("");
const price = ref("");
const barcode = ref("");
const description = ref("");

// === КАТЕГОРИИ ===
const categories = ref([]);
const categorySearch = ref("");

onMounted(async () => {
  // категории
  const resCat = await fetch("/api/admin/get_categories_flat.php");
  categories.value = await resCat.json();

  // заголовки характеристик
  const resAttr = await fetch("/api/admin/get_attributes.php");
  allAttributes.value = await resAttr.json();
});

// фильтрация категорий
const filteredCategories = computed(() =>
  categories.value.filter(c =>
    c.full_name.toLowerCase().includes(categorySearch.value.toLowerCase())
  )
);

// === ХАРАКТЕРИСТИКИ ===
const allAttributes = ref([]);   // заголовки характеристик
const attributes = ref([]);      // характеристики текущего товара

const newAttrName = ref("");     // поле создания нового заголовка

const attr = ref({
  attribute_id: "",
  value: ""
});

// создать заголовок характеристики
async function createNewAttribute() {
  if (!newAttrName.value.trim()) {
    alert("Введите название характеристики");
    return;
  }

  const res = await fetch("/api/admin/create_attribute.php", {
    method: "POST",
    body: JSON.stringify({ name: newAttrName.value })
  });

  const data = await res.json();

  if (data.error) {
    alert(data.error);
    return;
  }

  // добавляем в список
  allAttributes.value.push({ id: data.id, name: data.name });

  // выбор нового атрибута
  attr.value.attribute_id = data.id;

  newAttrName.value = "";
}

// добавление значения
function addAttribute() {
  if (!attr.value.attribute_id || !attr.value.value.trim()) {
    alert("Выберите характеристику и введите значение");
    return;
  }

  attributes.value.push({
    attribute_id: attr.value.attribute_id,
    value: attr.value.value
  });

  attr.value = { attribute_id: "", value: "" };
}

// удалить характеристику
function removeAttribute(index) {
  attributes.value.splice(index, 1);
}

// получить название заголовка
function getAttributeName(id) {
  const f = allAttributes.value.find(a => a.id === id);
  return f ? f.name : "";
}

// === СОХРАНЕНИЕ ТОВАРА ===
async function saveProduct() {
  if (!name.value.trim()) return alert("Введите название");
  if (!category_id.value) return alert("Выберите категорию");

  const body = {
    name: name.value,
    article: article.value,
    category_id: category_id.value,
    price: price.value,
    barcode: barcode.value,
    description: description.value,
    attributes: attributes.value
  };

  const res = await fetch("/api/admin/create_product.php", {
    method: "POST",
    body: JSON.stringify(body)
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
  max-width: 600px;
}

.form {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.search-input {
  margin-bottom: -5px;
}

.block-title {
  margin-top: 25px;
  font-size: 18px;
}

.new-attr-title,
.attr-add {
  display: flex;
  gap: 10px;
  align-items: center;
}

.new-attr-title input,
.attr-add input {
  flex: 1;
}

.small-btn {
  padding: 8px 12px;
  background: #3a7bea;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}

.attr-list {
  padding-left: 10px;
  margin-top: 5px;
}

.attr-list li {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 4px 0;
}

.delete-btn {
  background: #ff4d4d;
  color: white;
  border: none;
  border-radius: 4px;
  padding: 2px 6px;
  cursor: pointer;
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
