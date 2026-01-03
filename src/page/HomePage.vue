<template>
  <section class="home-entry">
    <PhotoGallery />

    <!-- ✅ Категории на главной (только 1 уровень) -->
    <div v-if="homeCatsLoading" class="home-cats-loading">
      Загрузка категорий…
    </div>

    <HomeCatalogEntry v-else :show-head="true" :items="homeCats" :navigate-on-pick="true" />

    <Contact />
  </section>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useHead } from "@vueuse/head";

import PhotoGallery from "@/components/PhotoGallery.vue";
import HomeCatalogEntry from "@/components/HomeCatalogEntry.vue";
import Contact from "@/components/Contact.vue";

import { getCategoriesOnce } from "@/composables/useCategories";

const homeCats = ref<any[]>([]);
const homeCatsLoading = ref(true);

onMounted(async () => {
  homeCatsLoading.value = true;
  homeCats.value = await getCategoriesOnce();
  homeCatsLoading.value = false;
});

useHead({
  title: "Все для дома — Сходненская и Планерная | XOZMAG.RU",
  meta: [
    {
      name: "description",
      content:
        "Хозтовары, сантехника, электрика, стройматериалы, крепеж и замки — большой выбор товаров для дома и ремонта. Магазин рядом с метро Сходненская и Планерная.",
    },
    { property: "og:title", content: "Все для дома — Сходненская и Планерная" },
    {
      property: "og:description",
      content:
        "Хозтовары, сантехника, электрика, стройматериалы, крепеж и замки — большой выбор товаров для дома и ремонта.",
    },
    { property: "og:type", content: "website" },
    { property: "og:url", content: "https://xozmag.ru/" },
  ],
});
</script>


<style scoped>
.home-cats-loading {
  padding: 12px 12px;
  border: 1px dashed var(--border-soft);
  border-radius: var(--radius-lg);
  background: var(--bg-panel);
  color: var(--text-muted);
  font-weight: 900;
}

.home-entry {
  width: min(1120px, 100%);
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 18px;
}
</style>
