<template>
  <div class="contact-page">

    <h1 class="page-title">Контакты</h1>

    <div class="contact-container">

      <!-- Левая часть: контакты -->
      <div class="contact-info">
        <h2>Наши контакты</h2>

        <div class="info-block">
          <p class="label">Телефон:</p>
          <a href="tel:+79258693416" class="phone">+7 (925) 869-34-16</a>
        </div>

        <div class="info-block">
          <p class="label">Режим работы:</p>
          <p class="work-time">
            Будни: <b>09:00 – 20:00</b><br>
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
</template>

<script setup>
// логика не нужна
</script>

<style scoped>
.contact-page {
  padding: 30px 20px;
  max-width: 1200px;
  margin: 0 auto;
  color: #e7e7e7;
}

.page-title {
  text-align: center;
  color: #fff;
  font-size: 32px;
  margin-bottom: 25px;
}

.contact-container {
  display: grid;
  grid-template-columns: 1fr 1.4fr;
  gap: 30px;
}

/* Левая колонка */
.contact-info {
  background: #1c1e22;
  padding: 25px 30px;
  border-radius: 14px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.3);
}

.contact-info h2 {
  margin-bottom: 15px;
  color: #fff;
}

.info-block {
  margin-bottom: 20px;
}

.label {
  color: #b8b8b8;
  margin-bottom: 4px;
}

.phone {
  color: #4da3ff;
  font-size: 20px;
  text-decoration: none;
  font-weight: 600;
}

.phone:hover {
  text-decoration: underline;
}

/* График работы */
.work-time {
  color: #d6d6d6;
  font-size: 16px;
  line-height: 1.5;
}

/* Карта */
.contact-map iframe {
  width: 100%;
  min-height: 420px;
  border: none;
  border-radius: 14px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.25);
}

/* Адаптив */
@media (max-width: 768px) {
  .contact-container {
    grid-template-columns: 1fr;
  }

  .contact-map iframe {
    min-height: 320px;
  }

  .phone {
    font-size: 18px;
  }
}
</style>
