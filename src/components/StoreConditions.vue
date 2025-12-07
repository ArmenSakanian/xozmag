<template>
  <section>
    <div id="StoreConditions" class="info-wrapper">
              <h2 class="page-title">Удобства магазина</h2>
      <div class="info-box">
  
        <div class="info-grid">
          <div class="item">
            <p class="label">Парковка</p>
            <p class="value yes">Есть</p>
          </div>
  
          <div class="item">
            <p class="label">Парковка для инвалидов</p>
            <p class="value yes">Есть</p>
          </div>
  
          <div class="item">
            <p class="label">Wi-Fi</p>
            <p class="value yes">Есть</p>
          </div>
  
          <div class="item">
            <p class="label">Оплата картой</p>
            <p class="value yes">Есть</p>
          </div>
  
          <div class="item">
            <p class="label">Оплата наличными</p>
            <p class="value yes">Можно</p>
          </div>
  
          <div class="item">
            <p class="label">Оплата по QR-коду</p>
            <p class="value yes">Можно</p>
          </div>
  
          <div class="item">
            <p class="label">Бесплатная доставка</p>
            <p class="value no">Нет</p>
          </div>
  
          <div class="item">
            <p class="label">Доставка</p>
            <p class="value yes">Есть (в пределах района)</p>
          </div>
  
          <div class="item">
            <p class="label">Посещение с животными</p>
            <p class="value yes">Разрешено со всеми</p>
          </div>
  
          <div class="item">
            <p class="label">Кнопка вызова персонала</p>
            <p class="value no">Нет</p>
          </div>
  
          <div class="item">
            <p class="label">Лифт</p>
            <p class="value no">Нет</p>
          </div>
  
          <div class="item">
            <p class="label">Пандус</p>
            <p class="value no">Нет</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
// логики нет — компонент информационный
</script>

<style scoped>
/* Обёртка, чтобы компонент не был на всю ширину */
.info-wrapper {
  max-width: 900px;
  margin: 0 auto;
}

.info-box {
  background: var(--background-container);
  padding: 30px;
  border-radius: 18px;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
  color: #e7e7e7;
}

/* === ПК версия (как было) === */
.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 22px 26px;
}

/* Карточки */
.item {
  background: rgba(255, 255, 255, 0.05);
  padding: 16px 18px;
  border-radius: 14px;
  backdrop-filter: blur(6px);
  border: 1px solid rgba(255, 255, 255, 0.06);
}

.label {
  font-size: 14px;
  color: #b8b8b8;
  margin-bottom: 4px;
}

.value {
  font-size: 18px;
  font-weight: 600;
}

.value.yes {
  color: var(--accent-color);
}

.value.no {
  color: #ff4e4e;
}

/* === 🔥 Мобильная версия — 2 карточки в ряд === */
@media (max-width: 600px) {
  .info-box {
    padding: 20px;
  }

  .info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr); /* две карточки в строке */
    gap: 12px 12px;
  }

  .item {
    padding: 12px 12px;      /* компактнее */
    border-radius: 10px;
  }

  .label {
    font-size: 13px;
    margin-bottom: 2px;
  }

  .value {
    font-size: 15px;
  }
}

</style>
