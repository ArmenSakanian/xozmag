<template>
  <section>
    <div id="about" class="about-page">
      <h1 class="about-title">О нас</h1>
  
      <div class="about-container">
        <p>
          Добро пожаловать в наш магазин хозяйственных и строительных товаров! Мы
          работаем уже более 10 лет и за это время заслужили самый высокий рейтинг
          в нашем районе. Каждый день мы стараемся поддерживать доверие клиентов,
          предлагая только качественные товары и честный подход в обслуживании.
        </p>
  
        <p>В нашем ассортименте представлены товары по основным направлениям:</p>
  
        <ul class="about-list">
          <li>сантехника</li>
          <li>крепёжные изделия</li>
          <li>клей и сопутствующие материалы</li>
          <li>электрика (лампы, аксессуары, бытовая электрика)</li>
          <li>хозтовары</li>
          <li>товары для мелких ремонтных и бытовых задач</li>
        </ul>
  
        <p>
          Мы уделяем особое внимание качеству продукции и много лет сотрудничаем
          только с проверенными поставщиками. Это позволяет нам предлагать товары,
          которые служат долго и надёжно.
        </p>
  
        <p>
          Наши ценности — это честность в отношениях с покупателями, уважительное
          общение и ответственность за предлагаемый товар. При соблюдении условий
          возможен возврат приобретённой продукции.
        </p>
  
        <p>
          Спасибо, что выбираете нас! Мы всегда рады помочь вам в решении бытовых
          и ремонтных задач.
        </p>
      </div>
    </div>
  </section>
</template>

<script setup>
// компонент простой — логики не нужно
</script>

<style scoped>
.about-page {
  max-width: 900px;
  margin: 0 auto;
  color: #e7e7e7;
}

.about-title {
  font-size: 32px;
  font-weight: 700;
  color: #ffffff;
  text-align: center;
  margin-bottom: 25px;
}

.about-container {
  background: #1c1e22;
  padding: 25px 30px;
  border-radius: 14px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
  line-height: 1.6;
  font-size: 17px;
}

.about-container p {
  margin-bottom: 18px;
}

.about-list {
  margin: 10px 0 20px 0;
  padding-left: 20px;
}

.about-list li {
  margin: 6px 0;
  color: #b5b5b5;
}

/* Мобильная адаптация */
@media (max-width: 600px) {
  .about-container {
    padding: 20px;
    font-size: 16px;
  }

  .about-title {
    font-size: 26px;
  }
}
</style>
