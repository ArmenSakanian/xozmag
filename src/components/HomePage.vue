<template>
  <div class="vitrina-page">
    <h1>Витрина товаров</h1>

    <!-- Выбор магазина -->
    <select v-model="selectedStore" @change="loadProducts" class="store-select">
      <option disabled value="">Выберите магазин</option>
      <option v-for="s in stores" :key="s.uuid" :value="s.uuid">
        {{ s.name }}
      </option>
    </select>

    <!-- Список товаров -->
    <div class="products">
      <div class="product-card" v-for="p in products" :key="p.uuid">
        <h3>{{ p.name }}</h3>
        <p>Артикул: {{ p.code || '—' }}</p>
        <p>Цена: {{ p.price / 100 }} ₽</p>
        <p>Остаток: {{ getStock(p.uuid) }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";

const stores = ref([]);
const products = ref([]);
const stocks = ref([]);
const selectedStore = ref("");

async function loadStores() {
  const r = await fetch("/api/vitrina/get_stores.php");
  stores.value = await r.json();
}

async function loadProducts() {
  if (!selectedStore.value) return;

  const prodReq = await fetch("/api/vitrina/get_products.php?store=" + selectedStore.value);
  products.value = await prodReq.json();

  const stockReq = await fetch("/api/vitrina/get_stocks.php?store=" + selectedStore.value);
  stocks.value = await stockReq.json();
}

function getStock(productUuid) {
  const row = stocks.value.find(s => s.productUuid === productUuid);
  return row ? row.quantity : 0;
}

onMounted(() => {
  loadStores();
});
</script>

<style scoped>
.vitrina-page {
  padding: 20px;
}

.store-select {
  margin-bottom: 20px;
  padding: 10px;
  font-size: 16px;
}

.products {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 15px;
}

.product-card {
  background: #202020;
  padding: 15px;
  border-radius: 10px;
  border: 1px solid #333;
}

.product-card h3 {
  margin-bottom: 6px;
  color: #fff;
}
</style>
