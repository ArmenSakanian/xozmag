<template>
  <section class="about-page" id="about">
    <div class="wrap">
      <!-- HERO -->
      <header class="hero" data-aos="fade-up">
        <div class="hero-top">
          <div class="brand" data-aos="fade-right" data-aos-delay="50">
            <div class="brand-dot"></div>
            <div class="brand-text">
              <div class="brand-name">{{ storeName }}</div>
              <div class="brand-sub">
                Москва • Северное Тушино • м. Сходненская / Планерная
              </div>
            </div>
          </div>

          <div class="rating-pill" aria-label="Рейтинг Яндекс" data-aos="fade-left" data-aos-delay="80">
            <span class="rp-star">★</span>
            <span class="rp-score">{{ yandexRating }}</span>
            <span class="rp-meta">{{ yandexReviews }} отзывов</span>
          </div>
        </div>

        <h1 class="page-title" data-aos="fade-up" data-aos-delay="120">О нас</h1>

        <p class="lead" data-aos="fade-up" data-aos-delay="160">
          Магазин «Всё для дома» — розничный магазин хозяйственных и строительных товаров.
          Работаем <b>более 10 лет</b>. В ассортименте — товары для ремонта, монтажа и повседневных бытовых задач.
        </p>

        <div class="hero-actions" data-aos="fade-up" data-aos-delay="210">
          <RouterLink class="btn primary" to="/catalogv2">Открыть каталог</RouterLink>
          <a class="btn ghost" href="#contact">Контакты</a>
        </div>

        <div class="facts">
          <div class="fact" data-aos="fade-up" data-aos-delay="80">
            <div class="fact-title">Доставка</div>
            <div class="fact-text">
              Возможна по согласованию (зависит от товара и адреса).
            </div>
          </div>
          <div class="fact" data-aos="fade-up" data-aos-delay="140">
            <div class="fact-title">Оплата</div>
            <div class="fact-text">
              Наличные • карта • QR-код.
            </div>
          </div>
          <div class="fact" data-aos="fade-up" data-aos-delay="200">
            <div class="fact-title">Парковка</div>
            <div class="fact-text">
              Есть парковка со шлагбаумом — по звонку откроем.
            </div>
          </div>
        </div>

        <div class="hero-note" data-aos="fade-up" data-aos-delay="240">
          Адрес и режим работы указаны в разделе <a href="#contact">«Контакты»</a>.
        </div>
      </header>

      <!-- ASSORTMENT -->
      <section class="section" id="about-range" data-aos="fade-up">
        <div class="section-head">
          <h2 class="h2">Ассортимент</h2>
          <div class="section-hint">Основные направления</div>
        </div>

        <div class="range-grid">
          <div class="range-card" data-aos="fade-up" data-aos-delay="40">
            <div class="rc-title">Сантехника</div>
            <div class="rc-text">
              Смесители, сифоны, подводки, арматура, фитинги и сопутствующие товары.
            </div>
          </div>

          <div class="range-card" data-aos="fade-up" data-aos-delay="80">
            <div class="rc-title">Вентиляция</div>
            <div class="rc-text">
              Воздуховоды, соединители, крепления, вентиляторы и комплектующие.
            </div>
          </div>

          <div class="range-card" data-aos="fade-up" data-aos-delay="120">
            <div class="rc-title">Стройматериалы</div>
            <div class="rc-text">
              Краски, колеры, шпатели, смеси и материалы для ремонта.
            </div>
          </div>

          <div class="range-card" data-aos="fade-up" data-aos-delay="160">
            <div class="rc-title">Крепёж</div>
            <div class="rc-text">
              Саморезы и крепёжные изделия для бытовых и ремонтных задач.
            </div>
          </div>

          <div class="range-card" data-aos="fade-up" data-aos-delay="200">
            <div class="rc-title">Электрика</div>
            <div class="rc-text">
              Лампы, светильники, розетки, выключатели и аксессуары.
            </div>
          </div>

          <div class="range-card" data-aos="fade-up" data-aos-delay="240">
            <div class="rc-title">Хозтовары и бытовая химия</div>
            <div class="rc-text">
              Товары для дома, уборки и повседневного использования.
            </div>
          </div>
        </div>

        <div class="callout" data-aos="zoom-in" data-aos-delay="140">
          <div class="callout-title">Нужна консультация по выбору</div>
          <div class="callout-text">
            Если вы не уверены в подборе или совместимости товара, свяжитесь с нами — поможем уточнить подходящий вариант.
          </div>
        </div>
      </section>

      <!-- TERMS / FAQ -->
      <section class="section" id="about-terms" data-aos="fade-up">
        <div class="section-head">
          <h2 class="h2">Условия обслуживания</h2>
          <div class="section-hint">Коротко и официально</div>
        </div>

        <div class="faq">
          <details class="faq-item" open data-aos="fade-up" data-aos-delay="60">
            <summary>
              <span class="faq-q">Доставка</span>
              <span class="faq-badge">по согласованию</span>
            </summary>
            <div class="faq-a">
              Доставка <b>возможна</b>, но не всегда и не везде — зависит от товара и адреса.
              Рекомендуем уточнить по телефону: сообщим возможность и условия.
            </div>
          </details>

          <details class="faq-item" data-aos="fade-up" data-aos-delay="120">
            <summary>
              <span class="faq-q">Оплата</span>
              <span class="faq-badge">наличные / карта / QR</span>
            </summary>
            <div class="faq-a">
              Принимаем оплату <b>наличными</b>, <b>картой</b> и по <b>QR-коду</b>.
            </div>
          </details>

          <details class="faq-item" data-aos="fade-up" data-aos-delay="180">
            <summary>
              <span class="faq-q">Возврат и обмен</span>
              <span class="faq-badge">по закону</span>
            </summary>
            <div class="faq-a">
              Возврат и обмен осуществляются <b>в соответствии с законодательством</b>.
              Возможность возврата зависит от категории товара, состояния упаковки и комплектности.
              Есть товары, которые не подлежат возврату — уточняйте при покупке.
            </div>
          </details>

          <details class="faq-item" data-aos="fade-up" data-aos-delay="240">
            <summary>
              <span class="faq-q">Дополнительно</span>
              <span class="faq-badge">удобства</span>
            </summary>
            <div class="faq-a">
              Посещение с <b>животными</b> допускается. Wi-Fi доступен при необходимости (по запросу).
            </div>
          </details>
        </div>
      </section>

      <!-- REVIEWS -->
      <section class="section" id="about-reviews" data-aos="fade-up">
        <div class="section-head">
          <h2 class="h2">Отзывы</h2>
          <div class="section-hint">Яндекс.Карты</div>
        </div>

        <div class="yandex-rating" data-aos="fade-up" data-aos-delay="90">
          <div class="yr-left">
            <div class="yr-score-line">
              <span class="yr-stars">★</span>
              <span class="yr-score">{{ yandexRating }}</span>
            </div>
            <div class="yr-count">{{ yandexReviews }} отзывов • {{ yandexGrades }} оценок</div>
          </div>

          <a :href="yandexLink" target="_blank" rel="noopener" class="yr-btn">
            Читать на Яндекс.Картах
          </a>
        </div>

        <p class="closing" data-aos="fade-up" data-aos-delay="140">
          Благодарим за доверие. Мы ценим обратную связь и поддерживаем качество обслуживания.
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
.about-page{
  padding: 18px 14px 34px;
  color: var(--text-main);
}

.wrap{
  width: min(1120px, 100%);
  margin: 0 auto;
  display: grid;
  gap: 14px;
}

/* HERO */
.hero{
  background:
    radial-gradient(900px 360px at 15% 0%, rgba(4,0,255,0.10), transparent 60%),
    radial-gradient(700px 320px at 85% 20%, rgba(22,163,74,0.10), transparent 60%),
    var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);
  padding: 18px 18px 16px;
}

.hero-top{
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  flex-wrap: wrap;
}

.brand{
  display: flex;
  align-items: center;
  gap: 10px;
}

.brand-dot{
  width: 12px;
  height: 12px;
  border-radius: 999px;
  background: var(--accent);
  box-shadow: 0 0 0 5px rgba(4,0,255,0.10);
}

.brand-name{
  font-weight: 950;
  letter-spacing: -0.02em;
  font-size: 16px;
}

.brand-sub{
  color: var(--text-muted);
  font-size: 12.5px;
  margin-top: 1px;
}

.rating-pill{
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 10px;
  border-radius: 999px;
  background: rgba(22,163,74,0.10);
  border: 1px solid rgba(22,163,74,0.22);
  box-shadow: var(--shadow-sm);
  font-weight: 900;
}

.rp-star{ color: var(--accent-2); }
.rp-score{ font-size: 14px; }
.rp-meta{ color: var(--text-muted); font-size: 12.5px; font-weight: 800; }

.page-title{
  margin: 12px 0 8px;
  font-size: 30px;
  line-height: 1.1;
  letter-spacing: -0.03em;
}

.lead{
  margin: 0;
  color: var(--text-main);
  line-height: 1.7;
  font-size: 16.5px;
}

.hero-actions{
  display: flex;
  gap: 10px;
  margin-top: 14px;
  flex-wrap: wrap;
}

.btn{
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 10px 14px;
  border-radius: var(--radius-md);
  text-decoration: none;
  font-weight: 950;
  border: 1px solid var(--border-soft);
  box-shadow: var(--shadow-sm);
  transition: transform .18s ease, filter .18s ease, background .18s ease;
  white-space: nowrap;
}

.btn:hover{ transform: translateY(-1px); }
.btn:active{ transform: translateY(0px); }

.btn.primary{
  background: var(--accent);
  color: #fff;
  border-color: rgba(0,0,0,0.08);
}

.btn.ghost{
  background: var(--bg-panel);
  color: var(--text-main);
}

.facts{
  margin-top: 14px;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
}

.fact{
  background: var(--bg-soft);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  padding: 10px 12px;
}

.fact-title{
  font-weight: 950;
  margin-bottom: 4px;
}

.fact-text{
  color: var(--text-muted);
  line-height: 1.55;
  font-size: 13.5px;
}

.hero-note{
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid var(--border-soft);
  color: var(--text-muted);
  font-size: 12.5px;
  line-height: 1.55;
}

.hero-note a{
  color: var(--accent);
  text-decoration: none;
  font-weight: 900;
}

/* SECTION */
.section{
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);
  padding: 16px 16px;
}

.section-head{
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 10px;
}

.h2{
  margin: 0;
  font-size: 18px;
  letter-spacing: -0.01em;
  font-weight: 950;
}

.section-hint{
  color: var(--text-muted);
  font-size: 12.5px;
  font-weight: 800;
}

/* range grid */
.range-grid{
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.range-card{
  background: var(--bg-soft);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  padding: 12px 12px;
  position: relative;
  overflow: hidden;
}

.range-card::before{
  content:"";
  position:absolute;
  left:0; top:0; bottom:0;
  width: 4px;
  background: var(--accent);
  opacity: .9;
}

.rc-title{
  font-weight: 950;
  margin-bottom: 6px;
  padding-left: 6px;
}

.rc-text{
  color: var(--text-muted);
  line-height: 1.6;
  font-size: 14px;
  padding-left: 6px;
}

.callout{
  margin-top: 12px;
  background: rgba(4,0,255,0.06);
  border: 1px solid rgba(4,0,255,0.14);
  border-radius: var(--radius-lg);
  padding: 12px 12px;
}

.callout-title{
  font-weight: 950;
  margin-bottom: 4px;
}

.callout-text{
  color: var(--text-muted);
  line-height: 1.6;
}

/* FAQ */
.faq{
  display: grid;
  gap: 10px;
}

.faq-item{
  background: var(--bg-soft);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  padding: 0;
  overflow: hidden;
}

.faq-item summary{
  cursor: pointer;
  list-style: none;
  padding: 12px 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  font-weight: 950;
}

.faq-item summary::-webkit-details-marker{ display:none; }

.faq-badge{
  font-size: 12px;
  font-weight: 950;
  color: var(--text-main);
  background: rgba(22,163,74,0.10);
  border: 1px solid rgba(22,163,74,0.22);
  padding: 6px 10px;
  border-radius: 999px;
  white-space: nowrap;
}

.faq-a{
  padding: 0 12px 12px;
  color: var(--text-muted);
  line-height: 1.65;
  font-size: 14px;
}

/* Yandex block */
.yandex-rating{
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 14px;

  background: var(--bg-soft);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  padding: 14px 16px;
  box-shadow: var(--shadow-sm);
}

.yr-left{
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}

.yr-score-line{
  display: flex;
  align-items: center;
  gap: 10px;
}

.yr-stars{
  font-size: 18px;
  line-height: 1;
  color: var(--accent-2);
  background: rgba(22, 163, 74, 0.12);
  border: 1px solid rgba(22, 163, 74, 0.22);
  padding: 6px 10px;
  border-radius: 999px;
}

.yr-score{
  font-size: 22px;
  font-weight: 950;
  letter-spacing: -0.02em;
}

.yr-count{
  font-size: 13px;
  color: var(--text-muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%;
}

.yr-btn{
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: var(--accent);
  color: #fff;
  text-decoration: none;
  font-weight: 950;
  padding: 10px 14px;
  border-radius: var(--radius-md);
  border: 1px solid rgba(0,0,0,0.08);
  box-shadow: var(--shadow-sm);
  transition: transform .18s ease, filter .18s ease;
  white-space: nowrap;
}

.yr-btn:hover{
  transform: translateY(-1px);
  filter: brightness(1.06);
}

.yr-btn:active{
  transform: translateY(0px);
  filter: brightness(0.98);
}

.closing{
  margin: 12px 0 0;
  color: var(--text-muted);
  line-height: 1.65;
}

/* responsive */
@media (max-width: 820px){
  .facts{ grid-template-columns: 1fr; }
  .range-grid{ grid-template-columns: 1fr; }
}

@media (max-width: 600px){
  .about-page{ padding: 14px 12px 26px; }
  .page-title{ font-size: 26px; }
  .hero{ padding: 16px 14px; }
  .section{ padding: 14px 14px; }

  .yandex-rating{
    flex-direction: column;
    align-items: stretch;
    text-align: center;
  }
  .yr-score-line{ justify-content: center; }
  .yr-btn{ width: 100%; }
}
</style>
