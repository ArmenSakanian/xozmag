<template>
  <div class="admin-page">
    <h2 class="title">Добавление товара</h2>

    <div class="card form">
      <!-- === НАЗВАНИЕ === -->
      <label class="label">Название товара</label>
      <input
        v-model="name"
        class="input"
        placeholder="Например: Эмаль ПФ-115"
      />

      <!-- === АРТИКУЛ === -->
      <label class="label">Артикул</label>
      <input v-model="article" class="input" placeholder="PF115-01" />

      <!-- === КАТЕГОРИЯ === -->
      <label class="label">Категория</label>

      <input
        v-model="categorySearch"
        class="input"
        placeholder="Поиск категории..."
      />

      <select v-model="category_id" class="select">
        <option value="">Выберите категорию</option>
        <option v-for="c in filteredCategories" :key="c.id" :value="c.id">
          {{ c.full_name }}
        </option>
      </select>

      <label class="label">Цена продажи</label>
      <input v-model="price" type="number" class="input" />
      <!-- === БРЕНД === -->
      <label class="label">Бренд</label>
      <input v-model="brand" class="input" placeholder="Например: ORIO" />

      <!-- === ШТРИХКОД === -->
      <label class="label">Штрихкод</label>
      <input v-model="barcode" class="input" placeholder="4601234567890" />

      <!-- === ОПИСАНИЕ === -->
      <label class="label">Описание</label>
      <textarea v-model="description" rows="3" class="input"></textarea>

      <!-- === ХАРАКТЕРИСТИКИ === -->
      <h3 class="block-title">Характеристики</h3>

      <div class="flex-row">
        <input
          v-model="newAttrName"
          class="input"
          placeholder="Новый заголовок (Тип краски...)"
        />
        <button @click="createNewAttribute" class="small-btn">Создать</button>
      </div>

      <div class="flex-row">
        <select v-model="attr.attribute_id" class="select">
          <option value="">Выберите заголовок</option>
          <option v-for="a in allAttributes" :value="a.id">
            {{ a.name }}
          </option>
        </select>

        <input v-model="attr.value" class="input" placeholder="Значение..." />
        <button @click="addAttribute" class="small-btn">Добавить</button>
      </div>

      <ul class="attr-list">
        <li v-for="(a, i) in attributes" :key="i">
          <span>{{ getAttributeName(a.attribute_id) }}:</span>
          <b>{{ a.value }}</b>
          <button class="delete-btn" @click="removeAttribute(i)">✕</button>
        </li>
      </ul>

      <button @click="saveProduct" class="save-btn">Создать товар</button>
    </div>

    <!-- ================================ -->
    <!-- ==== СУЩЕСТВУЮЩИЕ ТОВАРЫ ======= -->
    <!-- ================================ -->

    <h3 class="block-title" style="margin-top: 40px">Существующие товары</h3>
    <!-- ================================ -->
<!-- ========= ИМПОРТ EXCEL ========= -->
<!-- ================================ -->

<h3 class="block-title" style="margin-top: 40px">Импорт товаров из Excel</h3>

<div class="card">
  <input
    type="file"
    class="input"
    accept=".xlsx,.xls,.csv"
    @change="onImportFileChange"
  />

  <button
    class="save-btn"
    style="margin-top: 10px"
    :disabled="!importFile || importing"
    @click="importExcel"
  >
    {{ importing ? "Импортируется..." : "Импортировать" }}
  </button>

  <div v-if="importResult" class="import-result">
    <p>
      Создано товаров: <b>{{ importResult.created }}</b><br />
      Пропущено: <b>{{ importResult.skipped }}</b>
    </p>

    <ul
      v-if="importResult.errors && importResult.errors.length"
      class="import-errors"
    >
      <li v-for="(e, i) in importResult.errors" :key="i">{{ e }}</li>
    </ul>
  </div>
</div>

    <div class="card product-list">
      <div class="product-row" v-for="p in products" :key="p.id">

        <div class="product-name">{{ p.name }}</div>

        <div class="product-cat">{{ p.category_name }}</div>

        <div class="product-small">
          <span>Арт: {{ p.article || "—" }}</span>
          <span>Цена: {{ p.price || "—" }}</span>
          <span>ШК: {{ p.barcode || "—" }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";

/* ==============================
   ПОЛЯ ТОВАРА
============================== */

const name = ref("");
const article = ref("");
const category_id = ref("");
const price = ref("");
const barcode = ref("");
const description = ref("");
const brand = ref("");

/* ==============================
   КАТЕГОРИИ
============================== */

const categories = ref([]);
const categorySearch = ref("");

/* ==============================
   ХАРАКТЕРИСТИКИ
============================== */

const allAttributes = ref([]);
const attributes = ref([]);
const newAttrName = ref("");

const attr = ref({
  attribute_id: "",
  value: "",
});


const importFile = ref(null);
const importing = ref(false);
const importResult = ref(null);

function onImportFileChange(e) {
  importFile.value = e.target.files[0] || null;
  importResult.value = null;
}

async function importExcel() {
  if (!importFile.value) return alert("Выберите файл");

  importing.value = true;
  importResult.value = null;

  const fd = new FormData();
  fd.append("file", importFile.value);

  const res = await fetch("/api/admin/product/import_excel.php", {
    method: "POST",
    body: fd,
  });

  const data = await res.json();
  importResult.value = data;

  if (data.success) {
    alert("Импорт завершён!");
    await loadProducts();
  } else {
    alert("Ошибка: " + (data.error || "Unknown"));
  }

  importing.value = false;
}


/* ==============================
   СПИСОК ПРОДУКТОВ
============================== */

const products = ref([]);

/* ==============================
   ЗАГРУЗКА ДАННЫХ ПРИ СТАРТЕ
============================== */

onMounted(async () => {
  // категории
  const resCat = await fetch("/api/admin/product/get_categories_flat.php");
  categories.value = await resCat.json();

  // заголовки характеристик
  const resAttr = await fetch("/api/admin/attribute/get_attributes.php");
  allAttributes.value = await resAttr.json();

  // товары
  await loadProducts();
});

/* ==============================
   ФИЛЬТР КАТЕГОРИЙ
============================== */

const filteredCategories = computed(() =>
  categories.value.filter((c) =>
    c.full_name.toLowerCase().includes(categorySearch.value.toLowerCase())
  )
);

/* ==============================
   ХАРАКТЕРИСТИКИ
============================== */

async function createNewAttribute() {
  if (!newAttrName.value.trim()) return alert("Введите название");

  const res = await fetch("/api/admin/attribute/create_attribute.php", {
    method: "POST",
    body: JSON.stringify({ name: newAttrName.value }),
  });

  const data = await res.json();

  if (data.error) return alert(data.error);

  allAttributes.value.push({ id: data.id, name: data.name });
  attr.value.attribute_id = data.id;

  newAttrName.value = "";
}

function addAttribute() {
  if (!attr.value.attribute_id || !attr.value.value.trim()) {
    return alert("Введите данные");
  }

  attributes.value.push({
    attribute_id: attr.value.attribute_id,
    value: attr.value.value,
  });

  attr.value = { attribute_id: "", value: "" };
}

function removeAttribute(i) {
  attributes.value.splice(i, 1);
}

function getAttributeName(id) {
  const f = allAttributes.value.find((a) => a.id === id);
  return f ? f.name : "";
}

/* ==============================
   СОХРАНЕНИЕ ТОВАРА
============================== */

async function saveProduct() {
  if (!name.value.trim()) return alert("Введите название");
  if (!category_id.value) return alert("Выберите категорию");

  const fd = new FormData();

  fd.append("name", name.value);
  fd.append("article", article.value);
  fd.append("category_id", category_id.value);
  fd.append("price", price.value);
  fd.append("barcode", barcode.value);
  fd.append("description", description.value);
  fd.append("attributes", JSON.stringify(attributes.value));
  fd.append("brand", brand.value);

  const res = await fetch("/api/admin/product/create_product.php", {
    method: "POST",
    body: fd,
  });

  const data = await res.json();

  if (data.success) {
    alert("Товар создан!");
    preview.value = null;
    photoFile.value = null;
    await loadProducts();
  } else {
    alert("Ошибка: " + data.error);
  }
}


/* ==============================
   ПОЛУЧЕНИЕ СПИСКА ТОВАРОВ
============================== */

async function loadProducts() {
  const res = await fetch("/api/admin/product/get_products.php");
  products.value = await res.json();
}
</script>

<style scoped>
/* ==== ТОЧЕЧНЫЕ СТИЛИ ТОЛЬКО ДЛЯ СПИСКА ТОВАРОВ ==== */

.product-list {
  margin-top: 10px;
}

.product-row {
  padding: 12px 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.product-row:last-child {
  border-bottom: none;
}

.product-name {
  font-size: 16px;
  font-weight: 600;
}

.product-cat {
  font-size: 14px;
  opacity: 0.8;
  margin-top: 2px;
}

.product-small {
  margin-top: 3px;
  display: flex;
  gap: 12px;
  font-size: 13px;
  opacity: 0.6;
}

.import-result {
  margin-top: 10px;
  font-size: 14px;
}

.import-errors {
  margin-top: 8px;
  padding-left: 18px;
  font-size: 13px;
  opacity: 0.9;
}

.import-errors li {
  margin-bottom: 3px;
}


</style>
