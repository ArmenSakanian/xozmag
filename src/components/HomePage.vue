<template>
  <div class="catalog-page">

    <h1 class="title">Каталог товаров</h1>

    <div v-if="loading" class="loading">Загрузка...</div>

    <div v-if="error" class="error">{{ error }}</div>

    <div class="items">
      <div v-for="item in products" :key="item.uuid" class="item-card">
        <h3 class="item-title">{{ item.name }}</h3>

        <p class="item-description" v-if="item.description">
          {{ item.description }}
        </p>

        <div class="price-row">
          <span class="label">Цена:</span>
          <span class="value">{{ item.price }} ₽</span>
        </div>

        <div class="stock-row">
          <span class="label">Остаток:</span>
          <span class="value" :class="{ low: item.quantity <= 1 }">
            {{ item.quantity }}
          </span>
        </div>

        <div class="small-data">
          <div v-if="item.article">Артикул: {{ item.article }}</div>
          <div v-if="item.barcode">Штрихкод: {{ item.barcode }}</div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";

const products = ref([]);
const loading = ref(true);
const error = ref(null);

async function loadData() {
  try {
    const r = await fetch("/api/vitrina/evotor_products.php");
    const data = await r.json();

    if (!Array.isArray(data)) {
      throw new Error("Неверный формат данных");
    }

    products.value = data;
  } catch (e) {
    error.value = e.message;
  } finally {
    loading.value = false;
  }
}

onMounted(() => loadData());
</script>

<style scoped>
.catalog-page {
  padding: 20px;
  max-width: 900px;
  margin: auto;
}

.title {
  font-size: 28px;
  margin-bottom: 20px;
}

.items {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 15px;
}

.item-card {
  background: #1e1e1e;
  color: white;
  padding: 15px;
  border-radius: 10px;
  border: 1px solid #333;
}

.item-title {
  font-size: 20px;
  margin-bottom: 10px;
}

.price-row,
.stock-row {
  margin: 6px 0;
}

.label {
  color: #888;
}

.value {
  font-weight: bold;
  margin-left: 6px;
}

.value.low {
  color: #ff5555;
}

.small-data {
  margin-top: 10px;
  font-size: 12px;
  color: #bbb;
}

.loading,
.error {
  font-size: 18px;
  margin-top: 20px;
  text-align: center;
}
</style>
