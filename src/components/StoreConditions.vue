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
/* ===== Удобства магазина (под твой :root) ===== */

.info-wrapper{
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
.info-box{
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);
  padding: 22px 24px;
}

/* Сетка */
.info-grid{
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 14px;
}

/* Карточки */
.item{
  background: var(--bg-soft);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  padding: 14px 14px;
}

/* Текст */
.label{
  margin: 0 0 6px;
  color: var(--text-muted);
  font-size: 12px;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.value{
  margin: 0;
  font-size: 16px;
  font-weight: 800;
  line-height: 1.35;
  color: var(--text-main);
}

/* Да/нет — без новых переменных, только через твои */
.value.yes{
  color: var(--accent-2);
}

.value.no{
  color: var(--accent-danger);
}

/* Небольшой “бейдж” слева для yes/no */
.value.yes::before,
.value.no::before{
  content: "";
  display: inline-block;
  width: 9px;
  height: 9px;
  border-radius: 999px;
  margin-right: 8px;
  transform: translateY(-1px);
}

.value.yes::before{
  background: var(--accent-2);
  box-shadow: 0 0 0 4px rgba(22, 163, 74, 0.12);
}

.value.no::before{
  background: var(--accent-danger);
  box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.12);
}

/* ===== Responsive ===== */
@media (max-width: 600px){
  .info-wrapper{
    padding: 14px 12px 26px;
  }

  .page-title{
    font-size: 24px;
    margin-bottom: 12px;
  }

  .info-box{
    padding: 18px 16px;
  }

  /* 2 карточки в ряд */
  .info-grid{
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
  }

  .item{
    padding: 12px 12px;
    border-radius: 12px;
  }

  .label{
    font-size: 11px;
    margin-bottom: 4px;
  }

  .value{
    font-size: 14.5px;
  }
}
</style>

