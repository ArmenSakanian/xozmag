<template>
  <section class="about-page" id="about">
    <div class="wrap">
      <!-- HERO: новая композиция -->
      <header class="stage" data-aos="fade-up">
        <div class="stage-accent" aria-hidden="true"></div>

        <div class="stage-grid">
          <!-- LEFT: основной текст -->
          <div class="stage-main" data-aos="fade-right" data-aos-delay="60">
            <div class="brandline">
              <div class="brand-dot" aria-hidden="true"></div>
              <div class="brand-text">
                <div class="brand-name">{{ storeName }}</div>
                <div class="brand-sub">
                  Москва • Северное Тушино • м. Сходненская / Планерная
                </div>
              </div>
            </div>

            <h1 class="page-title" data-aos="fade-up" data-aos-delay="110">
              О нас
            </h1>

            <p class="lead" data-aos="fade-up" data-aos-delay="140">
              Магазин «Всё для дома» — розничный магазин хозяйственных и
              строительных товаров. Работаем <b>более 10 лет</b>. В ассортименте —
              товары для ремонта, монтажа и повседневных бытовых задач.
            </p>

            <div class="hero-actions" data-aos="fade-up" data-aos-delay="170">
              <RouterLink class="btn btn-primary" to="/catalog">
                Открыть каталог
              </RouterLink>
              <a class="btn btn-ghost" href="#contact">Контакты</a>
            </div>

            <div class="hero-note" data-aos="fade-up" data-aos-delay="210">
              Адрес и режим работы указаны в разделе
              <a href="#contact">«Контакты»</a>.
            </div>
          </div>

          <!-- RIGHT: мета-колонка -->
          <aside class="stage-side" data-aos="fade-left" data-aos-delay="90">
            <div class="rating-card" aria-label="Рейтинг Яндекс">
              <div class="rating-top">
                <span class="rating-star"><Fa :icon="['fas', 'star']" /></span>
                <span class="rating-score">{{ yandexRating }}</span>
              </div>
              <div class="rating-meta">
                {{ yandexReviews }} отзывов • {{ yandexGrades }} оценок
              </div>
            </div>

            <div class="facts-stack">
              <div class="fact">
                <div class="fact-title">Доставка</div>
                <div class="fact-text">
                  Возможна по согласованию (зависит от товара и адреса).
                </div>
              </div>

              <div class="fact">
                <div class="fact-title">Оплата</div>
                <div class="fact-text">Наличные • карта • QR-код.</div>
              </div>

              <div class="fact">
                <div class="fact-title">Парковка</div>
                <div class="fact-text">
                  Есть парковка со шлагбаумом — по звонку откроем.
                </div>
              </div>
            </div>
          </aside>
        </div>
      </header>

      <!-- ASSORTMENT: не карточки, а редакционная лента -->
      <section class="section section-range" id="about-range" data-aos="fade-up">
        <div class="section-head">
          <h2 class="h2">Ассортимент</h2>
          <div class="section-hint">Основные направления</div>
        </div>

        <div class="range-list" role="list">
          <article class="range-row" role="listitem" data-aos="fade-up" data-aos-delay="40">
            <div class="range-kicker" aria-hidden="true"></div>
            <div class="range-title">Сантехника</div>
            <div class="range-text">
              Смесители, сифоны, подводки, арматура, фитинги и сопутствующие товары.
            </div>
          </article>

          <article class="range-row" role="listitem" data-aos="fade-up" data-aos-delay="80">
            <div class="range-kicker" aria-hidden="true"></div>
            <div class="range-title">Вентиляция</div>
            <div class="range-text">
              Воздуховоды, соединители, крепления, вентиляторы и комплектующие.
            </div>
          </article>

          <article class="range-row" role="listitem" data-aos="fade-up" data-aos-delay="120">
            <div class="range-kicker" aria-hidden="true"></div>
            <div class="range-title">Стройматериалы</div>
            <div class="range-text">
              Краски, колеры, шпатели, смеси и материалы для ремонта.
            </div>
          </article>

          <article class="range-row" role="listitem" data-aos="fade-up" data-aos-delay="160">
            <div class="range-kicker" aria-hidden="true"></div>
            <div class="range-title">Крепёж</div>
            <div class="range-text">
              Саморезы и крепёжные изделия для бытовых и ремонтных задач.
            </div>
          </article>

          <article class="range-row" role="listitem" data-aos="fade-up" data-aos-delay="200">
            <div class="range-kicker" aria-hidden="true"></div>
            <div class="range-title">Электрика</div>
            <div class="range-text">
              Лампы, светильники, розетки, выключатели и аксессуары.
            </div>
          </article>

          <article class="range-row" role="listitem" data-aos="fade-up" data-aos-delay="240">
            <div class="range-kicker" aria-hidden="true"></div>
            <div class="range-title">Хозтовары и бытовая химия</div>
            <div class="range-text">
              Товары для дома, уборки и повседневного использования.
            </div>
          </article>
        </div>

        <div class="callout" data-aos="fade-up" data-aos-delay="140">
          <div class="callout-left">
            <div class="callout-title">Нужна консультация по выбору</div>
            <div class="callout-text">
              Если вы не уверены в подборе или совместимости товара, свяжитесь с
              нами — поможем уточнить подходящий вариант.
            </div>
          </div>
          <div class="callout-mark" aria-hidden="true"></div>
        </div>
      </section>

      <!-- TERMS: без details/summary, всё видно сразу -->
      <section class="section section-terms" id="about-terms" data-aos="fade-up">
        <div class="section-head">
          <h2 class="h2">Условия обслуживания</h2>
          <div class="section-hint">Коротко и официально</div>
        </div>

        <div class="terms-grid">
          <div class="term" data-aos="fade-up" data-aos-delay="60">
            <div class="term-head">
              <div class="term-title">Доставка</div>
              <div class="term-tag">по согласованию</div>
            </div>
            <div class="term-body">
              Доставка <b>возможна</b>, но не всегда и не везде — зависит от товара и
              адреса. Рекомендуем уточнить по телефону: сообщим возможность и условия.
            </div>
          </div>

          <div class="term" data-aos="fade-up" data-aos-delay="120">
            <div class="term-head">
              <div class="term-title">Оплата</div>
              <div class="term-tag">наличные / карта / QR</div>
            </div>
            <div class="term-body">
              Принимаем оплату <b>наличными</b>, <b>картой</b> и по <b>QR-коду</b>.
            </div>
          </div>

          <div class="term" data-aos="fade-up" data-aos-delay="180">
            <div class="term-head">
              <div class="term-title">Возврат и обмен</div>
              <div class="term-tag">по закону</div>
            </div>
            <div class="term-body">
              Возврат и обмен осуществляются <b>в соответствии с законодательством</b>.
              Возможность возврата зависит от категории товара, состояния упаковки и
              комплектности. Есть товары, которые не подлежат возврату — уточняйте при покупке.
            </div>
          </div>

          <div class="term" data-aos="fade-up" data-aos-delay="240">
            <div class="term-head">
              <div class="term-title">Дополнительно</div>
              <div class="term-tag">удобства</div>
            </div>
            <div class="term-body">
              Посещение с <b>животными</b> допускается.
            </div>
          </div>
        </div>
      </section>

      <!-- REVIEWS: большой рейтинг + CTA -->
      <section class="section section-reviews" id="about-reviews" data-aos="fade-up">
        <div class="section-head">
          <h2 class="h2">Отзывы</h2>
          <div class="section-hint">Яндекс.Карты</div>
        </div>

        <div class="reviews-stage">
          <div class="reviews-left" data-aos="fade-right" data-aos-delay="90">
            <div class="reviews-big">
              <span class="reviews-star"><Fa :icon="['fas', 'star']" /></span>
              <span class="reviews-score">{{ yandexRating }}</span>
            </div>
            <div class="reviews-sub">
              {{ yandexReviews }} отзывов • {{ yandexGrades }} оценок
            </div>
          </div>

          <a
            class="reviews-cta"
            :href="yandexLink"
            target="_blank"
            rel="noopener"
            data-aos="fade-left"
            data-aos-delay="120"
          >
            Читать на Яндекс.Картах
          </a>
        </div>

        <p class="closing" data-aos="fade-up" data-aos-delay="140">
          Благодарим за доверие. Мы ценим обратную связь и поддерживаем качество
          обслуживания.
        </p>
      </section>
    </div>
  </section>
</template>

<script setup>
import { RouterLink } from "vue-router";

const storeName = "Всё для дома";

const yandexRating = "4.9";
const yandexReviews = "308";
const yandexGrades = "969";

const yandexLink =
  "https://yandex.ru/maps/213/moscow/?from=mapframe&ll=37.436739%2C55.854634&mode=poi&poi%5Bpoint%5D=37.437056%2C55.854563&poi%5Buri%5D=ymapsbm1%3A%2F%2Forg%3Foid%3D1728338739&source=mapframe&tab=reviews&utm_source=share&z=19";
</script>

<style scoped>
/* page */
.about-page {
  padding: 18px 14px 34px;
  color: var(--text-main);
  background: var(--bg-main);
}

.wrap {
  width: min(1120px, 100%);
  margin: 0 auto;
  display: grid;
  gap: 14px;
}

/* shared */
.section {
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);
  padding: 16px 16px;
}

.section-head {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 12px;
}

.h2 {
  margin: 0;
  font-size: 18px;
  letter-spacing: -0.01em;
  font-weight: 950;
}

.section-hint {
  color: var(--text-muted);
  font-size: 12.5px;
  font-weight: 850;
}

/* HERO / stage */
.stage {
  position: relative;
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);
  overflow: hidden;
}

.stage-accent {
  position: absolute;
  inset: 0 auto 0 0;
  width: 10px;
  background: linear-gradient(
    180deg,
    rgba(4, 0, 255, 0.95),
    rgba(4, 0, 255, 0.35)
  );
  opacity: 0.9;
}

.stage-grid {
  display: grid;
  grid-template-columns: 1.35fr 0.65fr;
  gap: 14px;
  padding: 16px 16px 14px;
  padding-left: 26px;
}

.stage-main {
  display: grid;
  gap: 12px;
}

.brandline {
  display: flex;
  align-items: center;
  gap: 10px;
  padding-bottom: 12px;
  border-bottom: 1px solid var(--border-soft);
}

.brand-dot {
  width: 10px;
  height: 10px;
  border-radius: var(--radius-lg);
  background: var(--secondary-accent);
  box-shadow: 0 0 0 4px rgba(252, 200, 34, 0.25);
}

.brand-name {
  font-weight: 950;
  letter-spacing: -0.02em;
  font-size: 16px;
}

.brand-sub {
  color: var(--text-muted);
  font-size: 12.5px;
  margin-top: 2px;
}

.page-title {
  margin: 0;
  font-size: clamp(28px, 2.4vw, 36px);
  line-height: 1.08;
  letter-spacing: -0.03em;
  font-weight: 950;
}

.lead {
  margin: 0;
  line-height: 1.75;
  font-size: 16px;
  color: var(--text-main);
}

.hero-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 10px 14px;
  border-radius: var(--radius-md);
  text-decoration: none;
  font-weight: 950;
  border: 1px solid var(--border-soft);
  background: var(--bg-panel);
  box-shadow: var(--shadow-sm);
  transition: transform 0.18s ease, filter 0.18s ease, box-shadow 0.18s ease;
  white-space: nowrap;
  outline: none;
}

.btn:hover {
  transform: translateY(-1px);
  filter: brightness(1.02);
  box-shadow: var(--shadow-md);
}
.btn:active {
  transform: translateY(0px);
  filter: brightness(0.99);
}
.btn:focus-visible {
  outline: 2px solid var(--accent);
  outline-offset: 2px;
}

.btn-primary {
  background: var(--secondary-accent);
  color: var(--text-main);
}

.btn-ghost {
  background: var(--bg-panel);
  color: var(--text-main);
}

.hero-note {
  margin-top: 2px;
  padding-top: 12px;
  border-top: 1px solid var(--border-soft);
  color: var(--text-muted);
  font-size: 12.5px;
  line-height: 1.55;
}
.hero-note a {
  color: var(--accent);
  text-decoration: none;
  font-weight: 900;
}
.hero-note a:hover {
  text-decoration: underline;
}

.stage-side {
  display: grid;
  gap: 10px;
  align-content: start;
}

.rating-card {
  background: var(--bg-soft);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  padding: 12px 12px;
}

.rating-top {
  display: flex;
  align-items: center;
  gap: 10px;
}

.rating-star {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 6px 10px;
  border-radius: var(--radius-lg);
  background: var(--secondary-accent);
  border: 1px solid rgba(0, 0, 0, 0.08);
  line-height: 1;
}

.rating-score {
  font-size: 26px;
  font-weight: 950;
  letter-spacing: -0.02em;
}

.rating-meta {
  margin-top: 6px;
  color: var(--text-muted);
  font-size: 13px;
  line-height: 1.4;
}

.facts-stack {
  display: grid;
  gap: 10px;
}

.fact {
  background: var(--bg-soft);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  padding: 12px 12px;
}

.fact-title {
  font-weight: 950;
  margin-bottom: 6px;
  letter-spacing: -0.01em;
}

.fact-text {
  color: var(--text-muted);
  line-height: 1.6;
  font-size: 13.5px;
}

/* ASSORTMENT: editorial list */
.section-range {
  padding-top: 14px;
}

.range-list {
  display: grid;
  gap: 0;
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  overflow: hidden;
  background: var(--bg-panel);
  box-shadow: var(--shadow-sm);
}

.range-row {
  display: grid;
  grid-template-columns: 10px 220px 1fr;
  gap: 12px;
  align-items: start;
  padding: 14px 14px;
  border-bottom: 1px solid var(--border-soft);
  background: var(--bg-panel);
}

.range-row:nth-child(even) {
  background: var(--bg-soft);
}

.range-row:last-child {
  border-bottom: none;
}

.range-kicker {
  width: 10px;
  height: 100%;
  border-radius: var(--radius-lg);
  background: rgba(4, 0, 255, 0.85);
}

.range-title {
  font-weight: 950;
  letter-spacing: -0.01em;
}

.range-text {
  color: var(--text-muted);
  line-height: 1.65;
  font-size: 14px;
}

/* callout: отдельная плашка */
.callout {
  margin-top: 12px;
  display: grid;
  grid-template-columns: 1fr 120px;
  gap: 12px;
  align-items: stretch;

  background: var(--bg-soft);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}

.callout-left {
  padding: 12px 12px;
}

.callout-title {
  font-weight: 950;
  margin-bottom: 6px;
  letter-spacing: -0.01em;
}

.callout-text {
  color: var(--text-muted);
  line-height: 1.65;
}

.callout-mark {
  background: linear-gradient(
    135deg,
    rgba(252, 200, 34, 0.85),
    rgba(252, 200, 34, 0.35)
  );
}

/* TERMS: 2x2 official blocks */
.section-terms {
  padding-top: 14px;
}

.terms-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.term {
  background: var(--bg-soft);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  padding: 12px 12px;
}

.term-head {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 10px;
  padding-bottom: 10px;
  border-bottom: 1px solid rgba(228, 231, 239, 0.85);
  margin-bottom: 10px;
}

.term-title {
  font-weight: 950;
  letter-spacing: -0.01em;
}

.term-tag {
  font-size: 12px;
  font-weight: 950;
  background: var(--secondary-accent);
  border: 1px solid rgba(0, 0, 0, 0.08);
  padding: 6px 10px;
  border-radius: var(--radius-lg);
  white-space: nowrap;
}

.term-body {
  color: var(--text-muted);
  line-height: 1.7;
  font-size: 14px;
}

/* REVIEWS: big rating */
.section-reviews {
  padding-top: 14px;
}

.reviews-stage {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  align-items: stretch;
}

.reviews-left {
  background: var(--bg-soft);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  padding: 14px 16px;
  display: grid;
  align-content: center;
  gap: 8px;
}

.reviews-big {
  display: flex;
  align-items: center;
  gap: 12px;
}

.reviews-star {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 8px 12px;
  border-radius: var(--radius-lg);
  background: var(--secondary-accent);
  border: 1px solid rgba(0, 0, 0, 0.08);
  line-height: 1;
}

.reviews-score {
  font-size: 34px;
  font-weight: 950;
  letter-spacing: -0.03em;
}

.reviews-sub {
  color: var(--text-muted);
  font-size: 13px;
  line-height: 1.4;
}

.reviews-cta {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  text-align: center;

  background: var(--secondary-accent);
  color: var(--text-main);
  text-decoration: none;
  font-weight: 950;
  padding: 14px 16px;

  border-radius: var(--radius-lg);
  border: 1px solid rgba(0, 0, 0, 0.08);
  box-shadow: var(--shadow-sm);
  transition: transform 0.18s ease, filter 0.18s ease, box-shadow 0.18s ease;
  outline: none;
}

.reviews-cta:hover {
  transform: translateY(-1px);
  filter: brightness(1.03);
  box-shadow: var(--shadow-md);
}
.reviews-cta:active {
  transform: translateY(0px);
  filter: brightness(0.99);
}
.reviews-cta:focus-visible {
  outline: 2px solid var(--accent);
  outline-offset: 2px;
}

.closing {
  margin: 12px 0 0;
  color: var(--text-muted);
  line-height: 1.7;
}

/* responsive */
@media (max-width: 980px) {
  .stage-grid {
    grid-template-columns: 1fr;
  }

  .reviews-stage {
    grid-template-columns: 1fr;
  }

  .terms-grid {
    grid-template-columns: 1fr;
  }

  .range-row {
    grid-template-columns: 10px 1fr;
    grid-template-areas:
      "k title"
      "k text";
  }

  .range-kicker {
    grid-area: k;
  }

  .range-title {
    grid-area: title;
  }

  .range-text {
    grid-area: text;
  }

  .callout {
    grid-template-columns: 1fr;
  }

  .callout-mark {
    min-height: 10px;
  }
}

@media (max-width: 600px) {
  .about-page {
    padding: 14px 12px 26px;
  }

  .section {
    padding: 14px 14px;
  }

  .stage-grid {
    padding: 14px 14px 12px;
    padding-left: 22px;
  }
}

/* motion safety */
@media (prefers-reduced-motion: reduce) {
  .btn,
  .reviews-cta {
    transition: none !important;
  }
}
</style>
