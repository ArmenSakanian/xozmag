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
            <p class="label">Телефон</p>
            <a href="tel:+79258693416" class="value-text">+7 (925) 869-34-16</a>
          </div>

          <!-- Адрес -->
          <div class="info-block">
            <p class="label">Адрес</p>
            <a href="https://yandex.ru/maps/-/CLgkAIiy" class="value-text"
              >Москва, <br />
              Улица Героев Панфиловцев, дом 3</a
            >
          </div>

          <!-- Время работы -->
          <div class="info-block">
            <p class="label">Режим работы</p>
            <p class="value-text">
              Будни: <b>09:00 – 20:00</b><br />
              Выходные: <b>10:00 – 19:00</b>
            </p>
          </div>
        </div>

        <!-- Правая часть: карта -->
        <div class="contact-map">
<iframe src="https://yandex.ru/map-widget/v1/?z=12&ol=biz&oid=1728338739" width="560" height="400" frameborder="0"></iframe>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
// логика не нужна
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

.info-block{
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

  transition: color .18s ease, transform .18s ease, box-shadow .18s ease;
}

/* ссылка выглядит как аккуратная кнопка-тег */
a.value-text{
  padding: 8px 10px;
  border-radius: 10px;
  border: 1px solid transparent;
}

a.value-text:hover{
  color: var(--accent);
  border-color: rgba(4, 0, 231, 0.18);
  background: rgba(4, 0, 231, 0.06);
  transform: translateY(-1px);
}

a.value-text:active{
  transform: translateY(0);
}

/* Правая часть: карта */
.contact-map{
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);
  overflow: hidden;
}

/* Карта */
.contact-map iframe{
  width: 100%;
  height: 420px;
  display: block;
  border: 0;
  border-radius: 0; /* уже обрезается контейнером */
}

/* ===== Responsive ===== */
@media (max-width: 1024px){
  .contact-container{
    grid-template-columns: 1fr;
  }

  .contact-map iframe{
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

  .contact-map iframe{
    height: 320px;
  }
}
</style>
