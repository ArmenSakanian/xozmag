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
            <a href="tel:+79258693416" class="value-link">+7 (925) 869-34-16</a>
          </div>
  
          <!-- Адрес -->
          <div class="info-block">
            <p class="label">Адрес</p>
            <p class="value-text">
              Москва, <br />
              Улица Героев Панфиловцев, дом 3
            </p>
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
          <iframe
            src="https://yandex.ru/map-widget/v1/?um=constructor%3A620efd7c99bc91020f789a56c6f0e55bd29c1a6a5f3cb8a86aa31e52f6d1242e&amp;source=constructor"
            frameborder="0"
            allowfullscreen
            loading="lazy"
          ></iframe>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
// логика не нужна
</script>

<style scoped>
.contact-page {
  max-width: 1350px;
  margin: 0 auto;
  color: #dcdcdc;
}

/* Заголовок страницы */
.page-title {
  text-align: center;
  color: #ffffff;
  font-size: 38px;
  font-weight: 700;
  letter-spacing: 0.5px;
  margin-bottom: 40px;
  text-shadow: 0 0 12px rgba(0, 0, 0, 0.4);
}

/* Основная сетка */
.contact-container {
  display: grid;
  grid-template-columns: 1fr 1.5fr;
  gap: 40px;
  align-items: start;
}

/* Блок контактов */
.contact-info {
  background: linear-gradient(145deg, #1f2227, #1a1c20);
  padding: 35px;
  border-radius: 18px;
  box-shadow: 0 10px 35px rgba(0, 0, 0, 0.45);
  border: 1px solid rgba(255, 255, 255, 0.06);
  backdrop-filter: blur(6px);
}

.block-title {
  margin-bottom: 25px;
  color: #fff;
  font-size: 26px;
  font-weight: 700;
}

.info-block {
  margin-bottom: 28px;
}

.label {
  color: #9fa5b5;
  text-transform: uppercase;
  font-size: 13px;
  letter-spacing: 1px;
  margin-bottom: 6px;
}

.value-link {
  color: #4da3ff;
  font-size: 22px;
  font-weight: 600;
  text-decoration: none;
  transition: 0.25s ease;
}

.value-link:hover {
  color: #7dc4ff;
  text-shadow: 0 0 10px rgba(77, 163, 255, 0.5);
}

.value-text {
  color: #e0e0e0;
  font-size: 18px;
  line-height: 1.55;
}

.contact-map {
  height: 100%;
}

/* Карта */
.contact-map iframe {
  width: 100%;
  height: 100%;
  min-height: 100%;
  border: none;
  border-radius: 18px;
  box-shadow: 0 10px 35px rgba(0, 0, 0, 0.4);
  transition: 0.3s ease;
}

.contact-map iframe:hover {
  transform: scale(1.01);
  box-shadow: 0 15px 45px rgba(0, 0, 0, 0.55);
}

/* Адаптив под планшеты */
@media (max-width: 1024px) {
  .contact-container {
    grid-template-columns: 1fr;
  }

  .contact-map iframe {
    min-height: 400px;
  }
}

/* Адаптив под телефоны */
@media (max-width: 600px) {
  .page-title {
    font-size: 30px;
  }

  .contact-info {
    padding: 25px;
  }

  .value-link {
    font-size: 20px;
  }

  .value-text {
    font-size: 16px;
  }

  .contact-map iframe {
    min-height: 320px;
  }
}
</style>
