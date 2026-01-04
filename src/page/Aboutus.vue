<template>
  <main class="page">
    <div class="wrap">
      <!-- HERO: слева текст, справа фото -->
      <section class="hero">
        <div class="heroLeft">
          <div class="crumb">
            <span class="dot" aria-hidden="true"></span>
            <span class="crumbText">
              Москва • Северное Тушино • м. Сходненская / Планерная
            </span>
          </div>

          <h1 class="h1">О нас</h1>

          <p class="lead">
            Магазин «Всё для дома» - розничный магазин хозяйственных и строительных
            товаров. Работаем <b>более 10 лет</b>. В ассортименте - товары для ремонта,
            монтажа и повседневных бытовых задач.
          </p>

          <div class="heroActions">
            <RouterLink class="btn primary" to="/catalog">Открыть каталог</RouterLink>

            <RouterLink class="btn ghost" :to="{ path: '/', query: { scroll: 'contact' } }">
              Контакты
            </RouterLink>

            <a class="btn link" :href="yandexLink" target="_blank" rel="noopener">
              Читать отзывы
            </a>
          </div>

          <div class="ratingLine" aria-label="Рейтинг Яндекс">
            <span class="star" aria-hidden="true">★</span>
            <span class="ratingScore">{{ yandexRating }}</span>
            <span class="ratingMeta">{{ yandexReviews }} отзывов • {{ yandexGrades }} оценок</span>
          </div>

          <!-- ✅ miniFacts: теперь под текстом, компактнее -->
          <div class="miniFacts">
            <div class="miniFact">
              <div class="miniFactTitle">Оплата</div>
              <div class="miniFactText">Наличные • карта • QR-код</div>
            </div>
            <div class="miniFact">
              <div class="miniFactTitle">Доставка</div>
              <div class="miniFactText">По согласованию</div>
            </div>
            <div class="miniFact">
              <div class="miniFactTitle">Парковка</div>
              <div class="miniFactText">Шлагбаум - откроем по звонку</div>
            </div>
          </div>
        </div>

        <aside class="heroRight" aria-label="Фотография магазина">
          <div class="photoCard">
            <img class="photo" :src="heroImg" alt="Фото магазина" loading="lazy" />
          </div>
        </aside>
      </section>

      <!-- KEY INFO -->
      <section class="section">
        <div class="sectionHead">
          <h2 class="h2">Ключевое</h2>
          <div class="hint">коротко, по делу</div>
        </div>

        <div class="keyGrid">
          <article class="keyCard">
            <div class="keyTop">
              <div class="keyIcon" aria-hidden="true">  <Fa :icon="['fas','check']" />
</div>
              <div class="keyTitle">Быстро подобрать</div>
            </div>
            <div class="keyText">
              Подскажем совместимость, аналоги и что лучше под задачу - без “продажного” давления.
            </div>
          </article>

          <article class="keyCard">
            <div class="keyTop">
              <div class="keyIcon" aria-hidden="true"><Fa :icon="['fas','basketshopping']" /></div>
              <div class="keyTitle">Товары для всего</div>
            </div>
            <div class="keyText">
              Для дома, сантехники, монтажа, электрики и мелких ремонтных работ - из реального спроса.
            </div>
          </article>

          <article class="keyCard">
            <div class="keyTop">
              <div class="keyIcon" aria-hidden="true">⌁</div>
              <div class="keyTitle">Рядом с метро</div>
            </div>
            <div class="keyText">
              Удобно забежать по дороге - Сходненская / Планерная, Северное Тушино.
            </div>
          </article>
        </div>
      </section>

      <!-- ASSORTMENT -->
      <section class="section">
        <div class="sectionHead">
          <h2 class="h2">Ассортимент</h2>
          <div class="hint">основные направления</div>
        </div>

        <div class="pillGrid">
          <div class="pill">
            <div class="pillTitle">Сантехника</div>
            <div class="pillText">Смесители, сифоны, подводки, арматура, фитинги</div>
          </div>

          <div class="pill">
            <div class="pillTitle">Вентиляция</div>
            <div class="pillText">Воздуховоды, соединители, крепления, вентиляторы</div>
          </div>

          <div class="pill">
            <div class="pillTitle">Стройматериалы</div>
            <div class="pillText">Краски, колеры, шпатели, смеси и материалы</div>
          </div>

          <div class="pill">
            <div class="pillTitle">Крепёж</div>
            <div class="pillText">Саморезы и крепёжные изделия для бытовых задач</div>
          </div>

          <div class="pill">
            <div class="pillTitle">Электрика</div>
            <div class="pillText">Лампы, светильники, розетки, выключатели</div>
          </div>

          <div class="pill">
            <div class="pillTitle">Хозтовары и бытовая химия</div>
            <div class="pillText">Товары для уборки и повседневного использования</div>
          </div>
        </div>

        <div class="ctaStrip">
          <div class="ctaText">
            <div class="ctaTitle">Нужна консультация по выбору?</div>
            <div class="ctaSub">
              Если не уверены в подборе или совместимости - позвоните, поможем.
            </div>
          </div>

          <!-- ✅ звонок -->
          <a class="btn primary" href="tel:+79258693416">Связаться</a>
        </div>
      </section>
    </div>
  </main>
</template>

<script setup>
import { computed } from "vue";
import { useHead } from "@vueuse/head";

/** ===== Page data ===== */
const SITE_NAME = "XOZMAG.RU";
const HOME_URL = "https://xozmag.ru/";
const PAGE_PATH = "/aboutus";
const PAGE_URL = `${HOME_URL.replace(/\/$/, "")}${PAGE_PATH}`;

const storeName = "Всё для дома";

// путь как ты просил
const heroImg = "/img/photo-shop/Slide1.webp";
const OG_IMAGE = computed(() => new URL(heroImg, HOME_URL).toString());

const yandexRating = "4.9";
const yandexReviews = "308";
const yandexGrades = "969";

const yandexLink =
  "https://yandex.ru/maps/213/moscow/?from=mapframe&ll=37.436739%2C55.854634&mode=poi&poi%5Bpoint%5D=37.437056%2C55.854563&poi%5Buri%5D=ymapsbm1%3A%2F%2Forg%3Foid%3D1728338739&source=mapframe&tab=reviews&utm_source=share&z=19";

/** ===== Optional placeholders (если не хочешь - оставь как есть) ===== */
const STORE_ADDRESS = "__АДРЕС_УЛИЦА_ДОМ__"; // "ул. ... , д. ..."
const STORE_CITY = "__ГОРОД__"; // "Москва"
const STORE_PHONE = "__ТЕЛЕФОН__"; // "+7..."
const STORE_HOURS = "__ЧАСЫ_SCHEMA__"; // "Mo-Su 10:00-21:00"
const MAP_URL = "__ССЫЛКА_НА_КАРТЫ__"; // можно оставить yandexLink или ссылку на Яндекс/Гугл карты

function isPlaceholder(v) {
  const s = String(v ?? "").trim();
  return !s || (s.startsWith("__") && s.endsWith("__"));
}

/** ===== SEO texts ===== */
const title = computed(() => `О нас - ${storeName} | ${SITE_NAME}`);
const description = computed(
  () =>
    "Магазин «Всё для дома» в Москве (Северное Тушино, м. Сходненская / Планерная). Более 10 лет: сантехника, электрика, стройматериалы, крепёж, хозтовары. Оплата наличные/карта/QR, доставка по согласованию."
);

/** ===== JSON-LD ===== */

// 1) WebPage
const ldWebPage = computed(() => ({
  "@context": "https://schema.org",
  "@type": "AboutPage",
  name: `О нас - ${storeName}`,
  url: PAGE_URL,
  description: description.value,
  inLanguage: "ru-RU",
  isPartOf: {
    "@type": "WebSite",
    name: SITE_NAME,
    url: HOME_URL,
  },
  primaryImageOfPage: {
    "@type": "ImageObject",
    url: OG_IMAGE.value,
  },
}));

// 2) BreadcrumbList (Главная → О нас)
const ldBreadcrumbs = computed(() => ({
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  itemListElement: [
    {
      "@type": "ListItem",
      position: 1,
      name: "Главная",
      item: HOME_URL,
    },
    {
      "@type": "ListItem",
      position: 2,
      name: "О нас",
      item: PAGE_URL,
    },
  ],
}));

// 3) Store / LocalBusiness (аккуратно, без обязательных полей)
const ldStore = computed(() => {
  const data = {
    "@context": "https://schema.org",
    "@type": "Store",
    name: storeName,
    url: HOME_URL,
    image: OG_IMAGE.value,
    description: description.value,
    areaServed: { "@type": "City", name: "Москва" }, // поменяй, если не Москва
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

  // maps / socials
  const sameAs = [];
  if (!isPlaceholder(MAP_URL)) sameAs.push(String(MAP_URL).trim());
  // можно сразу использовать Яндекс-отзывы как sameAs
  if (yandexLink) sameAs.push(yandexLink);
  if (sameAs.length) data.sameAs = sameAs;

  // rating (если уверен, что актуально)
  const rv = Number(String(yandexRating).replace(",", "."));
  const rc = Number(String(yandexGrades).replace(/\s/g, ""));
  if (Number.isFinite(rv) && rv > 0 && Number.isFinite(rc) && rc > 0) {
    data.aggregateRating = {
      "@type": "AggregateRating",
      ratingValue: rv,
      bestRating: 5,
      ratingCount: rc,
    };
  }

  return data;
});

/** ===== Head ===== */
useHead(() => ({
  title: title.value,
  link: [
    { rel: "canonical", href: PAGE_URL },

    // nice-to-have (не обязательно)
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
    { property: "og:image", content: OG_IMAGE.value },
    { property: "og:image:alt", content: `Магазин ${storeName}` },

    // Twitter
    { name: "twitter:card", content: "summary_large_image" },
    { name: "twitter:title", content: title.value },
    { name: "twitter:description", content: description.value },
    { name: "twitter:image", content: OG_IMAGE.value },
  ],
  script: [
    { type: "application/ld+json", children: JSON.stringify(ldWebPage.value) },
    { type: "application/ld+json", children: JSON.stringify(ldBreadcrumbs.value) },
    { type: "application/ld+json", children: JSON.stringify(ldStore.value) },
  ],
}));
</script>


<style scoped>
.page {
  background: #f4f6fb;
  color: #1b1e28;
  padding: 18px 14px 34px;
}

.wrap {
  width: min(1120px, 100%);
  margin: 0 auto;
  display: grid;
  gap: 14px;
}

/* HERO */
.hero {
  background: #ffffff;
  border: 1px solid #e4e7ef;
  border-radius: 16px;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
  padding: 18px;
  display: grid;
  grid-template-columns: 1.15fr 0.85fr;
  gap: 16px;
}

.heroLeft {
  min-width: 0;
  display: grid;
  gap: 12px;
  align-content: start;
}

.crumb {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  color: #6b7280;
  font-weight: 800;
  font-size: 12.5px;
}

.dot {
  width: 10px;
  height: 10px;
  border-radius: 999px;
  background: #fcc822;
  box-shadow: 0 0 0 5px rgba(252, 200, 34, 0.24);
}

.h1 {
  margin: 0;
  font-size: clamp(28px, 2.6vw, 38px);
  line-height: 1.08;
  letter-spacing: -0.03em;
  font-weight: 950;
}

.lead {
  margin: 0;
  font-size: 16px;
  line-height: 1.75;
}

.heroActions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  white-space: nowrap;

  padding: 10px 14px;
  border-radius: 10px;
  border: 1px solid #e4e7ef;
  background: #ffffff;
  color: #1b1e28;
  font-weight: 900;

  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  transition: transform 160ms ease, box-shadow 160ms ease, filter 160ms ease;
}

.btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
  filter: brightness(1.01);
}

.btn:active {
  transform: translateY(0px);
  filter: brightness(0.99);
}

.btn.primary {
  background: #fcc822;
  border-color: rgba(0, 0, 0, 0.08);
}

.btn.ghost {
  background: #ffffff;
}

.btn.link {
  background: #f0f2f7;
}

.ratingLine {
  margin-top: 2px;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  background: #f0f2f7;
  border: 1px solid #e4e7ef;
  border-radius: 999px;
  padding: 9px 12px;
  width: fit-content;
}

.star {
  color: #fcc822;
}

.ratingScore {
  font-weight: 950;
}

.ratingMeta {
  color: #6b7280;
  font-weight: 800;
  font-size: 12px;
}

/* ✅ miniFacts under text, smaller */
.miniFacts {
  margin-top: 2px;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 8px;
}

.miniFact {
  background: #f0f2f7;
  border: 1px solid #e4e7ef;
  border-radius: 14px;
  padding: 10px 10px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  min-width: 0;
}

.miniFactTitle {
  font-weight: 950;
  margin-bottom: 4px;
  letter-spacing: -0.01em;
  font-size: 13px;
}

.miniFactText {
  color: #6b7280;
  font-weight: 800;
  font-size: 12.5px;
  line-height: 1.45;
}

/* right */
.heroRight {
  display: grid;
  gap: 10px;
  align-content: start;
}

.photoCard {
  border: 1px solid #e4e7ef;
  border-radius: 16px;
  overflow: hidden;
  background: #ffffff;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
  aspect-ratio: 4 / 3;
}

.photo {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: cover;
}

/* sections */
.section {
  background: #ffffff;
  border: 1px solid #e4e7ef;
  border-radius: 16px;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
  padding: 16px;
}

.sectionHead {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 12px;
}

.h2 {
  margin: 0;
  font-size: 18px;
  font-weight: 950;
  letter-spacing: -0.01em;
}

.hint {
  color: #6b7280;
  font-weight: 800;
  font-size: 12.5px;
}

/* key cards */
.keyGrid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
}

.keyCard {
  background: #f0f2f7;
  border: 1px solid #e4e7ef;
  border-radius: 16px;
  padding: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.keyTop {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
}

.keyIcon {
  width: 34px;
  height: 34px;
  border-radius: 12px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #ffffff;
  border: 1px solid #e4e7ef;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  font-weight: 950;
}

.keyTitle {
  font-weight: 950;
  letter-spacing: -0.01em;
}

.keyText {
  color: #6b7280;
  font-weight: 800;
  font-size: 13.5px;
  line-height: 1.65;
}

/* assortment pills */
.pillGrid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}

.pill {
  background: #ffffff;
  border: 1px solid #e4e7ef;
  border-radius: 16px;
  padding: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  position: relative;
  overflow: hidden;
}

.pill::before {
  content: "";
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 6px;
  background: #0400ff;
  opacity: 0.9;
}

.pillTitle {
  font-weight: 950;
  letter-spacing: -0.01em;
  padding-left: 10px;
}

.pillText {
  margin-top: 6px;
  color: #6b7280;
  font-weight: 800;
  font-size: 13.5px;
  line-height: 1.6;
  padding-left: 10px;
}

.ctaStrip {
  margin-top: 12px;
  background: #f0f2f7;
  border: 1px solid #e4e7ef;
  border-radius: 16px;
  padding: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);

  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.ctaTitle {
  font-weight: 950;
  letter-spacing: -0.01em;
}

.ctaSub {
  margin-top: 4px;
  color: #6b7280;
  font-weight: 800;
  font-size: 13.5px;
  line-height: 1.55;
}

/* responsive */
@media (max-width: 980px) {
  .hero {
    grid-template-columns: 1fr;
  }

  .keyGrid {
    grid-template-columns: 1fr;
  }

  .pillGrid {
    grid-template-columns: 1fr;
  }

  .miniFacts {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 600px) {
  .page {
    padding: 14px 12px 26px;
  }

  .hero {
    padding: 14px;
  }

  .section {
    padding: 14px;
  }
}
</style>