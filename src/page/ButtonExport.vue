<template>
  <div class="exp-page">
    <button class="exp-btn" @click="download">
      Скачать Excel всех товаров
    </button>
  </div>
</template>

<script setup>
function download() {
  window.location.href = "/api/export_products.php";
}
</script>

<style scoped>
.exp-page {
  display: flex;
  justify-content: center;
  align-items: center;
  height: calc(100vh - 100px);
}

.exp-btn {
  background: var(--accent-color);
  color: white;
  padding: 18px 28px;
  border-radius: 12px;
  border: none;
  font-size: 20px;
  cursor: pointer;
  font-weight: 600;
  transition: 0.25s;
}

.exp-btn:hover {
  transform: scale(1.05);
  background: white;
  color: black;
}
</style>
а