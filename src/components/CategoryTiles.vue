<template>
  <div class="tiles-wrapper">
    <div
      class="tile"
      v-for="cat in categories"
      :key="cat.id"
      @click="goToCategory(cat)"
    >
      <div class="tile-img-box">
        <img :src="cat.image || '/img/no-photo.png'" class="tile-img" />
      </div>

      <div class="tile-name">{{ cat.name }}</div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";

const router = useRouter();
const categories = ref([]);

// Загружаем категории 1 уровня
async function loadCategories() {
  const r = await fetch("/api/admin/product/get_categories_flat.php");
  let all = await r.json();

  categories.value = all
    .filter(c => {
      const level = c.level_code.split(".").filter(Boolean).length;
      return level === 1; // только 1 уровень
    })
    .map(c => ({
      id: c.id,
      name: c.name,
      code: c.level_code,
      image: c.image || null // фото категории (заполни в БД)
    }));
}

function goToCategory(cat) {
  router.push({
    name: "catalog",   // имя маршрута CatalogV2.vue
    query: { cat: cat.code }
  });
}

onMounted(loadCategories);
</script>

<style scoped>
.tiles-wrapper {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 18px;
  padding: 20px;
}

.tile {
  background: #1b1c20;
  border-radius: 14px;
  padding: 14px;
  cursor: pointer;
  border: 1px solid rgba(255, 255, 255, 0.05);
  transition: 0.25s;
}

.tile:hover {
  transform: translateY(-5px);
  border-color: var(--accent-color);
  box-shadow: 0 6px 18px rgba(255, 0, 80, 0.25);
}

.tile-img-box {
  width: 100%;
  height: 110px;
  background: #0f0f11;
  border-radius: 10px;
  overflow: hidden;
  display: flex;
  justify-content: center;
  align-items: center;
}

.tile-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.tile-name {
  text-align: center;
  margin-top: 10px;
  font-size: 15px;
  font-weight: 600;
  color: white;
}
</style>
