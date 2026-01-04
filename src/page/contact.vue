<template>
  <section>
    <div id="contact" class="contact-page">
      <h1 class="page-title">Контакты магазина</h1>

      <div class="contact-container">
        <!-- Левая часть: контакты -->
        <div class="contact-info">
          <h2 class="block-title">Связаться с нами</h2>

          <!-- Телефон -->
          <div class="info-block">
            <div class="ib-ico">
              <Fa :icon="['fas','phone']" />
            </div>
            <div class="ib-body">
              <p class="label">Телефон</p>
              <a :href="phoneHref" class="value-text link">{{ phone }}</a>
            </div>
          </div>

          <!-- Адрес -->
          <div class="info-block">
            <div class="ib-ico">
              <Fa :icon="['fas','location-dot']" />
            </div>
            <div class="ib-body">
              <p class="label">Адрес</p>
              <a :href="mapsLink" class="value-text link" target="_blank" rel="noopener">
                Москва, <br />
                Улица Героев Панфиловцев, дом 3
              </a>
            </div>
          </div>

          <!-- Время работы -->
          <div class="info-block">
            <div class="ib-ico">
              <Fa :icon="['fas','clock']" />
            </div>
            <div class="ib-body">
              <p class="label">Режим работы</p>
              <p class="value-text">
                Будни: <b>09:00 – 20:00</b><br />
                Выходные: <b>10:00 – 19:00</b>
              </p>
            </div>
          </div>
        </div>

        <!-- Правая часть: карта -->
        <div class="contact-map" ref="mapWrapRef">
          <!-- пока src не установлен - показываем спокойную заглушку (без кнопок) -->
          <div v-if="!mapLoaded" class="map-skeleton" aria-hidden="true"></div>

          <iframe
            class="map-iframe"
            :src="mapLoaded ? mapUrl : ''"
            loading="lazy"
            frameborder="0"
            title="Карта магазина"
            referrerpolicy="no-referrer-when-downgrade"
          ></iframe>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from "vue";
import { useHead } from "@vueuse/head";

/** ===== Contact data (у тебя уже есть) ===== */
const phone = "+7 (925) 869-34-16";
const phoneHref = "tel:+79258693416";

const mapsLink = "https://yandex.ru/maps/-/CLgkAIiy";

// iframe
const mapUrl =
  "https://yandex.ru/map-widget/v1/?um=constructor%3A620efd7c99bc91020f789a56c6f0e55bd29c1a6a5f3cb8a86aa31e52f6d1242e&source=constructor";

/** ===== Map lazy-load (у тебя уже есть) ===== */
const mapLoaded = ref(false);
const mapWrapRef = ref(null);
let io = null;

onMounted(() => {
  if ("IntersectionObserver" in window) {
    io = new IntersectionObserver(
      (entries) => {
        const e = entries[0];
        if (e && e.isIntersecting) {
          mapLoaded.value = true;
          io?.disconnect();
          io = null;
        }
      },
      { root: null, rootMargin: "300px 0px", threshold: 0.01 }
    );

    if (mapWrapRef.value) io.observe(mapWrapRef.value);
  } else {
    setTimeout(() => (mapLoaded.value = true), 800);
  }
});

onBeforeUnmount(() => {
  io?.disconnect();
  io = null;
});

/** ================= SEO (useHead) ================= */
const SITE_NAME = "XOZMAG.RU";
const HOME_URL = "https://xozmag.ru/";
const PAGE_PATH = "/contact";
const PAGE_URL = `${HOME_URL.replace(/\/$/, "")}${PAGE_PATH}`;

const storeName = "Всё для дома";
const ogImage = "https://xozmag.ru/android-chrome-512x512.png";

const title = computed(() => `Контакты - ${storeName} | ${SITE_NAME}`);
const description = computed(
  () =>
    "Контакты магазина «Всё для дома» в Москве (Северное Тушино): адрес, телефон, режим работы и карта проезда. Метро Сходненская / Планерная."
);

const addressStreet = "Улица Героев Панфиловцев, дом 3";
const addressCity = "Москва";

const ldContactPage = computed(() => ({
  "@context": "https://schema.org",
  "@type": "ContactPage",
  name: `Контакты - ${storeName}`,
  url: PAGE_URL,
  description: description.value,
  inLanguage: "ru-RU",
  isPartOf: { "@type": "WebSite", name: SITE_NAME, url: HOME_URL },
  primaryImageOfPage: { "@type": "ImageObject", url: ogImage },
}));

const ldBreadcrumbs = computed(() => ({
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  itemListElement: [
    { "@type": "ListItem", position: 1, name: "Главная", item: HOME_URL },
    { "@type": "ListItem", position: 2, name: "Контакты", item: PAGE_URL },
  ],
}));

const ldStore = computed(() => ({
  "@context": "https://schema.org",
  "@type": "Store",
  name: storeName,
  url: HOME_URL,
  image: ogImage,
  description: description.value,
  telephone: phoneHref.replace("tel:", ""),
  address: {
    "@type": "PostalAddress",
    streetAddress: addressStreet,
    addressLocality: addressCity,
    addressCountry: "RU",
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
  hasMap: mapsLink,
  sameAs: [mapsLink],
}));

useHead(() => ({
  title: title.value,
  link: [
    { rel: "canonical", href: PAGE_URL },
    { rel: "alternate", href: PAGE_URL, hreflang: "ru" },
    { rel: "alternate", href: PAGE_URL, hreflang: "x-default" },
  ],
  meta: [
    { name: "description", content: description.value },
    { name: "robots", content: "index,follow" },

    // Open Graph
    { property: "og:title", content: title.value },
    { property: "og:description", content: description.value },
    { property: "og:type", content: "website" },
    { property: "og:url", content: PAGE_URL },
    { property: "og:site_name", content: SITE_NAME },
    { property: "og:locale", content: "ru_RU" },
    { property: "og:image", content: ogImage },
    { property: "og:image:alt", content: `Контакты магазина ${storeName}` },

    // Twitter
    { name: "twitter:card", content: "summary_large_image" },
    { name: "twitter:title", content: title.value },
    { name: "twitter:description", content: description.value },
    { name: "twitter:image", content: ogImage },
  ],
  script: [
    { type: "application/ld+json", children: JSON.stringify(ldContactPage.value) },
    { type: "application/ld+json", children: JSON.stringify(ldBreadcrumbs.value) },
    { type: "application/ld+json", children: JSON.stringify(ldStore.value) },
  ],
}));
</script>



<style scoped>
/* ===== CONTACTS (под твой :root) ===== */

.contact-page{
  max-width: 1350px;
  margin: 0 auto;
  padding: 18px 14px 30px;
  color: var(--text-main);
}

.page-title{
  margin: 6px 0 14px;
  font-size: 28px;
  line-height: 1.15;
  letter-spacing: -0.02em;
  color: var(--text-main);
}

/* Основная сетка */
.contact-container{
  display: grid;
  grid-template-columns: 1fr 1.35fr;
  gap: 22px;
  align-items: start;
}

/* Блок контактов */
.contact-info{
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);
  padding: 22px 22px;
}

.block-title{
  margin: 0 0 16px;
  font-size: 20px;
  font-weight: 800;
  letter-spacing: -0.01em;
  color: var(--text-main);
}

/* Info blocks */
.info-block{
  display: grid;
  grid-template-columns: 42px 1fr;
  gap: 12px;

  padding: 14px 14px;
  margin-bottom: 12px;

  background: var(--bg-soft);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
}

.info-block:last-child{
  margin-bottom: 0;
}

.ib-ico{
  width: 42px;
  height: 42px;
  border-radius: 12px;
  display: grid;
  place-items: center;

  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  box-shadow: var(--shadow-sm);

  color: var(--accent);
  font-size: 18px;
}

.ib-body{ min-width: 0; }

/* подписи */
.label{
  margin: 0 0 6px;
  color: var(--text-muted);
  font-size: 12px;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

/* значения */
.value-text{
  margin: 0;
  display: inline-block;
  color: var(--text-main);
  font-size: 16.5px;
  line-height: 1.6;
  text-decoration: none;
  font-weight: 700;
}

.link{
  transition: color .18s ease;
}

.link:hover{
  color: var(--accent);
}



/* Правая часть: карта */
.contact-map{
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);
  overflow: hidden;
  position: relative;
}

/* Заглушка (спокойная, без неона) */
.map-skeleton{
  position: absolute;
  inset: 0;
  background:
    linear-gradient(90deg, rgba(0,0,0,0.035), rgba(0,0,0,0.02), rgba(0,0,0,0.035));
  background-size: 300% 100%;
  animation: shimmer 1.2s ease-in-out infinite;
}

@keyframes shimmer{
  0%{ background-position: 0% 0%; }
  100%{ background-position: 100% 0%; }
}

/* Карта */
.map-iframe{
  width: 100%;
  height: 420px;
  display: block;
  border: 0;
  background: var(--bg-panel);
}

/* ===== Responsive ===== */
@media (max-width: 1024px){
  .contact-container{
    grid-template-columns: 1fr;
  }

  .map-iframe{
    height: 380px;
  }
}

@media (max-width: 600px){
  .contact-page{
    padding: 14px 12px 26px;
  }

  .page-title{
    font-size: 24px;
    margin-bottom: 12px;
  }

  .contact-info{
    padding: 18px 16px;
  }

  .block-title{
    font-size: 18px;
  }

  .value-text{
    font-size: 16px;
  }

  .map-iframe{
    height: 320px;
  }
}
</style>
