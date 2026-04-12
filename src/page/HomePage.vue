<template>
  <section class="home-entry">
    <h1 class="sr-only">Все для дома рядом с метро Сходненская и Планерная</h1>

    <PhotoGallery />
    <HomeShowcaseSlider />

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

import HomeShowcaseSlider from "@/components/HomeShowcaseSlider.vue";
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

const SITE_NAME = "XOZMAG.RU";
const STORE_NAME = "Всё для дома";
const HOME_URL = "https://xozmag.ru/";
const OG_IMAGE = "https://xozmag.ru/android-chrome-512x512.png";
const STORE_ADDRESS = "Улица Героев Панфиловцев, дом 3";
const STORE_CITY = "Москва";
const STORE_PHONE = "+7 (925) 869-34-16";
const STORE_PHONE_RAW = "+79258693416";
const MAP_URL = "https://yandex.ru/maps/-/CLgkAIiy";
const TELEGRAM_URL = "https://t.me/magazin_xozmag_bot";

const title = computed(() => `Хозтовары и товары для дома у метро Сходненская и Планерная | ${SITE_NAME}`);

const description = computed(
  () =>
    "Магазин «Всё для дома» в Москве рядом с метро Сходненская и Планерная: хозтовары, сантехника, электрика, стройматериалы, крепеж, замки. Смотрите товары, уточняйте цену и наличие."
);

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

const ldStore = computed(() => ({
  "@context": "https://schema.org",
  "@type": "Store",
  name: STORE_NAME,
  url: HOME_URL,
  image: OG_IMAGE,
  description: description.value,
  telephone: STORE_PHONE_RAW,
  address: {
    "@type": "PostalAddress",
    streetAddress: STORE_ADDRESS,
    addressLocality: STORE_CITY,
    addressCountry: "RU",
  },
  areaServed: [
    { "@type": "City", name: "Москва" },
    { "@type": "Place", name: "Северное Тушино" },
    { "@type": "Place", name: "Сходненская" },
    { "@type": "Place", name: "Планерная" },
  ],
  hasMap: MAP_URL,
  sameAs: [MAP_URL, TELEGRAM_URL],
  geo: {
    "@type": "GeoCoordinates",
    latitude: 55.854563,
    longitude: 37.437056,
  },
  openingHoursSpecification: [
    {
      "@type": "OpeningHoursSpecification",
      dayOfWeek: ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],
      opens: "09:00",
      closes: "20:00",
    },
    {
      "@type": "OpeningHoursSpecification",
      dayOfWeek: ["Saturday", "Sunday"],
      opens: "10:00",
      closes: "19:00",
    },
  ],
}));

useHead(() => ({
  title: title.value,
  link: [{ rel: "canonical", href: HOME_URL }],
  meta: [
    { name: "description", content: description.value },
    { name: "robots", content: "index,follow" },
    { property: "og:title", content: title.value },
    { property: "og:description", content: description.value },
    { property: "og:type", content: "website" },
    { property: "og:url", content: HOME_URL },
    { property: "og:site_name", content: SITE_NAME },
    { property: "og:locale", content: "ru_RU" },
    { property: "og:image", content: OG_IMAGE },
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
  width: min(1180px, calc(100% - 24px));
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 22px;
}

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

@media (max-width: 767px) {
  .home-entry {
    width: min(100%, calc(100% - 16px));
    margin-top: 0;
    gap: 18px;
  }
}
</style>
