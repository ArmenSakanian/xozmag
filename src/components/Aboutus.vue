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
.about-page {
  max-width: 900px;
  margin: 0 auto;
  color: #e7e7e7;
}

.about-container {
  background: #1c1e22;
  padding: 25px 30px;
  border-radius: 14px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
  line-height: 1.65;
  font-size: 17px;
}

.about-container p {
  margin-bottom: 18px;
}

/* Разделитель */
.separator {
  width: 100%;
  height: 1px;
  background: rgba(255,255,255,0.07);
  margin: 18px 0;
}

/* === Улучшенный список === */
.about-list {
  margin: 12px 0 20px;
  padding-left: 0;
  list-style: none;
}

.about-list li {
  margin: 6px 0;
  color: #c7c7c7;
  display: flex;
  align-items: center;
}

.about-list li::before {
  content: "•";
  margin-right: 10px;
  color: var(--accent-color);
  font-size: 22px;
  line-height: 0;
}

/* === ⭐ Улучшенный блок рейтинга Яндекс === */
.yandex-rating {
  display: flex;
  justify-content: space-between;
  align-items: center;

  background: #24272c;
  padding: 18px 22px;
  border-radius: 12px;
  margin-bottom: 25px;

  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: 0 3px 12px rgba(0, 0, 0, 0.25);
}

.yr-left {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.yr-score-line {
  display: flex;
  align-items: center;
  gap: 6px;
}

.yr-stars {
  font-size: 26px;
  color: #ffc107;
}

.yr-score {
  font-size: 26px;
  font-weight: 700;
  color: #fff;
}

.yr-count {
  font-size: 14px;
  color: #b5b5b5;
}

/* Кнопка */
.yr-btn {
  background: var(--accent-color);
  color: #000;
  padding: 10px 18px;
  border-radius: 10px;
  font-weight: 600;
  text-decoration: none;
  transition: 0.25s;
  white-space: nowrap;
}

.yr-btn:hover {
  background: #ffb13b;
  transform: translateY(-2px);
}

/* === Мобильная адаптация === */
@media (max-width: 600px) {
  .about-container {
    padding: 20px;
    font-size: 16px;
  }

  .yandex-rating {
    flex-direction: column;
    text-align: center;
    gap: 12px;
  }

  .yr-btn {
    width: 100%;
    text-align: center;
  }
}
</style>
