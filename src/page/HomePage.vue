<template>
  <section class="home-entry">
    <!-- SEO H1 (не виден, но помогает поиску) -->
    <h1 class="sr-only">Все для дома рядом с метро Сходненская и Планерная</h1>

    <PhotoGallery />

    <div v-if="homeCatsLoading" class="home-cats-loading">
      Загрузка категорий…
    </div>

    <HomeCatalogEntry
      v-else
      :show-head="true"
      :items="homeCats"
      :navigate-on-pick="true"
    />
  </section>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { useHead } from "@vueuse/head";

import PhotoGallery from "@/components/PhotoGallery.vue";
import HomeCatalogEntry from "@/components/HomeCatalogEntry.vue";
import { getCategoriesOnce } from "@/composables/useCategories";

const homeCats = ref<any[]>([]);
const homeCatsLoading = ref(true);

onMounted(async () => {
  homeCatsLoading.value = true;
  homeCats.value = await getCategoriesOnce();
  homeCatsLoading.value = false;
});

/* ================= SEO CONFIG ================= */
const SITE_NAME = "XOZMAG.RU";
const HOME_URL = "https://xozmag.ru/";
const OG_IMAGE = "https://xozmag.ru/android-chrome-512x512.png";

const title = computed(() => `Все для дома - Сходненская и Планерная | ${SITE_NAME}`);

const description = computed(
  () =>
    "Хозтовары, сантехника, электрика, стройматериалы, крепеж и замки - большой выбор товаров для дома и ремонта. Магазин рядом с метро Сходненская и Планерная."
);

// ✅ заполнишь - будет круто; не заполнишь - поля удалятся автоматически
const STORE_ADDRESS = "__АДРЕС_УЛИЦА_ДОМ__"; // например: "ул. Свободы, 1к2"
const STORE_CITY = "__ГОРОД__"; // например: "Москва"
const STORE_PHONE = "__ТЕЛЕФОН__"; // например: "+7 999 123-45-67" или оставь как есть
const STORE_HOURS = "__ЧАСЫ_SCHEMA__"; // например: "Mo-Su 10:00-21:00"
const MAP_URL = "__ССЫЛКА_НА_КАРТЫ__"; // например: ссылка на Яндекс/Google карты (если нет - оставь)

function isPlaceholder(v: unknown) {
  const s = String(v ?? "").trim();
  return !s || s.startsWith("__") && s.endsWith("__");
}

/* JSON-LD: WebSite (поиск по сайту) */
const ldWebSite = computed(() => ({
  "@context": "https://schema.org",
  "@type": "WebSite",
  name: SITE_NAME,
  url: HOME_URL,
  potentialAction: {
    "@type": "SearchAction",
    target: "https://xozmag.ru/catalog?q={search_term_string}",
    "query-input": "required name=search_term_string",
  },
}));

/* JSON-LD: LocalBusiness / Store */
const ldStore = computed(() => {
  const data: any = {
    "@context": "https://schema.org",
    "@type": "Store",
    name: "Все для дома - XOZMAG",
    url: HOME_URL,
    image: OG_IMAGE,
    description: description.value,
    areaServed: { "@type": "City", name: "Москва" }, // если у тебя не Москва - поменяй
  };

  // address
  if (!isPlaceholder(STORE_ADDRESS) && !isPlaceholder(STORE_CITY)) {
    data.address = {
      "@type": "PostalAddress",
      streetAddress: String(STORE_ADDRESS).trim(),
      addressLocality: String(STORE_CITY).trim(),
      addressCountry: "RU",
    };
  }

  // phone
  if (!isPlaceholder(STORE_PHONE)) {
    data.telephone = String(STORE_PHONE).trim();
  }

  // opening hours
  if (!isPlaceholder(STORE_HOURS)) {
    data.openingHours = [String(STORE_HOURS).trim()];
  }

  // maps / socials (sameAs)
  if (!isPlaceholder(MAP_URL)) {
    data.sameAs = [String(MAP_URL).trim()];
  }

  return data;
});

useHead(() => ({
  title: title.value,
  link: [{ rel: "canonical", href: HOME_URL }],
  meta: [
    { name: "description", content: description.value },
    { name: "robots", content: "index,follow" },

    // Open Graph
    { property: "og:title", content: title.value },
    { property: "og:description", content: description.value },
    { property: "og:type", content: "website" },
    { property: "og:url", content: HOME_URL },
    { property: "og:site_name", content: SITE_NAME },
    { property: "og:locale", content: "ru_RU" },
    { property: "og:image", content: OG_IMAGE },

    // Twitter
    { name: "twitter:card", content: "summary_large_image" },
    { name: "twitter:title", content: title.value },
    { name: "twitter:description", content: description.value },
    { name: "twitter:image", content: OG_IMAGE },
  ],
  script: [
    { type: "application/ld+json", children: JSON.stringify(ldWebSite.value) },
    { type: "application/ld+json", children: JSON.stringify(ldStore.value) },
  ],
}));
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

/* SEO-only заголовок: невидимый, но доступный */
.sr-only {
  position: absolute !important;
  width: 1px !important;
  height: 1px !important;
  padding: 0 !important;
  margin: -1px !important;
  overflow: hidden !important;
  clip: rect(0, 0, 0, 0) !important;
  white-space: nowrap !important;
  border: 0 !important;
}
</style>
