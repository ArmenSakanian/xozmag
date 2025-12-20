<template>
  <section>
    <div id="about" class="about-page">
      <h1 class="page-title">О нас</h1>

      <div class="about-container">


        <p>
          Добро пожаловать в наш магазин хозяйственных и строительных товаров!
          Мы работаем уже более 10 лет и за это время заслужили самый высокий
          рейтинг в нашем районе. Каждый день мы стараемся поддерживать доверие
          клиентов, предлагая только качественные товары и честный подход в
          обслуживании.
        </p>

        <p>
          В нашем ассортименте представлены товары по основным направлениям:
        </p>

        <!-- Улучшенный список -->
        <ul class="about-list">
          <li><span>Сантехника</span></li>
          <li><span>Крепёжные изделия</span></li>
          <li><span>Клей и сопутствующие материалы</span></li>
          <li><span>Электрика: лампы, аксессуары и др.</span></li>
          <li><span>Хозтовары</span></li>
          <li><span>Товары для ремонтных и бытовых задач</span></li>
        </ul>

        <p>
          Мы уделяем особое внимание качеству продукции и много лет сотрудничаем
          только с проверенными поставщиками. Это позволяет нам предлагать
          товары, которые служат долго и надёжно.
        </p>

        <p>
          Наши ценности — это честность в отношениях с покупателями,
          уважительное общение и ответственность за предлагаемый товар.
          При соблюдении условий возможен возврат приобретённой продукции.
        </p>

        <p>
          Спасибо, что выбираете нас! Мы всегда рады помочь вам
          в решении бытовых и ремонтных задач.
        </p>
                <!-- ⭐ Улучшенный блок рейтинга Яндекс -->
                 <div class="separator"></div>
        <div class="yandex-rating">
          <div class="yr-left">
            <div class="yr-score-line">
              <span class="yr-stars">★</span>
              <span class="yr-score">4.9</span>
            </div>
            <div class="yr-count">308 отзывов • 969 оценок</div>
          </div>

          <a
            href="https://yandex.ru/maps/213/moscow/?from=mapframe&ll=37.436739%2C55.854634&mode=poi&poi%5Bpoint%5D=37.437056%2C55.854563&poi%5Buri%5D=ymapsbm1%3A%2F%2Forg%3Foid%3D1728338739&source=mapframe&tab=reviews&utm_source=share&z=19"
            target="_blank"
            class="yr-btn"
          >
            Читать отзывы на Яндекс.Картах
          </a>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
// Логики нет
</script>

<style scoped>
/* ===== ABOUT PAGE (под твой :root) ===== */

.about-page{
  max-width: 900px;
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

/* Основная карточка */
.about-container{
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);
  padding: 22px 24px;

  line-height: 1.75;
  font-size: 16.5px;
}

.about-container p{
  margin: 0 0 14px;
  color: var(--text-main);
}

.about-container p:last-child{
  margin-bottom: 0;
}

/* Разделитель */
.separator{
  width: 100%;
  height: 1px;
  background: var(--border-soft);
  margin: 18px 0;
}

/* ===== Список направлений ===== */
.about-list{
  margin: 10px 0 16px;
  padding: 0;
  list-style: none;

  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px 14px;
}

.about-list li{
  display: flex;
  align-items: flex-start;
  gap: 10px;

  background: var(--bg-soft);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-md);
  padding: 10px 12px;
  box-shadow: var(--shadow-sm);

  color: var(--text-main);
}

.about-list li::before{
  content: "";
  width: 10px;
  height: 10px;
  border-radius: 999px;
  margin-top: 6px;
  background: var(--accent);
  box-shadow: 0 0 0 4px rgba(4, 0, 231, 0.10);
  flex: 0 0 auto;
}

.about-list li span{
  color: var(--text-main);
  font-weight: 600;
}

/* ===== Блок рейтинга ===== */
.yandex-rating{
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;

  background: var(--bg-soft);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  padding: 16px 18px;

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
  font-weight: 800;
  letter-spacing: -0.02em;
  color: var(--text-main);
}

.yr-count{
  font-size: 13px;
  color: var(--text-muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%;
}

/* Кнопка */
.yr-btn{
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;

  background: var(--accent);
  color: #fff;
  text-decoration: none;
  font-weight: 700;

  padding: 10px 14px;
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  border: 1px solid rgba(0, 0, 0, 0.08);

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

/* ===== Mobile ===== */
@media (max-width: 700px){
  .about-list{
    grid-template-columns: 1fr;
  }
}

@media (max-width: 600px){
  .about-page{
    padding: 14px 12px 26px;
  }

  .page-title{
    font-size: 24px;
    margin-bottom: 12px;
  }

  .about-container{
    padding: 18px 16px;
    font-size: 16px;
    border-radius: var(--radius-lg);
  }

  .yandex-rating{
    flex-direction: column;
    align-items: stretch;
    text-align: center;
  }

  .yr-score-line{
    justify-content: center;
  }

  .yr-btn{
    width: 100%;
  }
}
</style>

