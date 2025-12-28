<template>
  <section class="home-entry">
    <PhotoGallery />

    <!-- SEO/инфо блок (после галереи) -->
    <header class="seo-card" aria-label="О магазине">
      <div class="seo-accent" aria-hidden="true"></div>

      <div class="seo-body">
        <h1 class="seo-title">Все Для Дома — Сходненская и Планерная</h1>

        <p class="seo-desc">
          Хозтовары, сантехника, электрика, стройматериалы, крепеж и замки —
          большой выбор товаров для дома и ремонта.
        </p>

        <div class="seo-chips" aria-hidden="true">
          <span class="chip">Хозтовары</span>
          <span class="chip">Сантехника</span>
          <span class="chip">Электрика</span>
          <span class="chip">Стройматериалы</span>
          <span class="chip">Крепеж</span>
          <span class="chip">Замки</span>
        </div>
      </div>
    </header>

    <Aboutus />
    <Contact />
  </section>
</template>

<script setup lang="ts">
import PhotoGallery from "@/components/PhotoGallery.vue";
import Aboutus from "@/components/Aboutus.vue";
import Contact from "@/components/Contact.vue";
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

/* ===== SEO Card ===== */
.seo-card {
  position: relative;
  display: flex;
  gap: 14px;
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}

.seo-accent {
  width: 6px;
  background: linear-gradient(180deg, var(--accent), var(--secondary-accent));
}

.seo-body {
  padding: 14px 14px 12px;
  flex: 1;
}

.seo-title {
  margin: 0;
  color: var(--text-main);
  font-size: 18px;
  line-height: 1.2;
  font-weight: 800;
  letter-spacing: 0.2px;
}

.seo-desc {
  margin: 8px 0 0;
  color: var(--text-muted);
  font-size: 14px;
  line-height: 1.45;
}

.seo-chips {
  margin-top: 12px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.chip {
  display: inline-flex;
  align-items: center;
  height: 26px;
  padding: 0 10px;
  border-radius: 999px;
  background: var(--bg-soft);
  border: 1px solid var(--border-soft);
  color: var(--text-main);
  font-size: 12.5px;
  line-height: 1;
  white-space: nowrap;
}

@media (max-width: 480px) {
  .home-entry {
    padding: 6px 6px;
    gap: 14px;
  }

  .seo-body {
    padding: 12px 12px 10px;
  }

  .seo-title {
    font-size: 16px;
  }

  .seo-desc {
    font-size: 13px;
  }

  .chip {
    height: 24px;
    font-size: 12px;
  }
}
</style>
