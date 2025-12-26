<template>
  <section class="home-entry">
    <HomeSearch
      :show-category="true"
      :categories="categories"
      :current-category="currentCategory"
      :sync-route="false"
      catalog-path="/catalogv2"
    />

    <HomeCatalogEntry />
  </section>

  <Aboutus />
  <Contact />
  <StoreConditions />
  <PhotoGallery />
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useRoute } from "vue-router";

import HomeSearch from "@/components/HomeSearch.vue";
import HomeCatalogEntry from "@/components/HomeCatalogEntry.vue";
import Aboutus from "@/components/Aboutus.vue";
import Contact from "@/components/Contact.vue";
import StoreConditions from "@/components/StoreConditions.vue";
import PhotoGallery from "@/components/PhotoGallery.vue";

const route = useRoute();

/** категории как в CatalogV2 */
const categories = ref<any[]>([]);

const currentCategory = computed(() => {
  const v: any = route.query.cat;
  return v ? String(Array.isArray(v) ? v[0] : v) : null;
});

async function loadCategories() {
  try {
    const r = await fetch("/api/admin/product/get_categories_flat.php");
    const raw = await r.json();

    categories.value = (raw || []).map((c: any) => {
      const pid = c.parent_id;
      const parent =
        pid === null || pid === undefined || String(pid) === "0" || String(pid) === ""
          ? null
          : String(pid);

      return {
        id: c.id,
        name: c.name,
        code: c.code,
        parent,
        photo:
          c.photo_url_abs ||
          c.photo_url ||
          (c.photo_categories ? `/photo_categories_vitrina/${c.photo_categories}` : null),
      };
    });
  } catch {
    categories.value = [];
  }
}

onMounted(loadCategories);
</script>

<style scoped>
.home-entry {
  width: min(1120px, 100%);
  margin: 0 auto;
  padding: 8px 10px;
  display: flex;
  flex-direction: column;
  gap: 18px;
}

@media (max-width: 480px) {
  .home-entry {
    padding: 6px 6px;
    gap: 14px;
  }
}
</style>
