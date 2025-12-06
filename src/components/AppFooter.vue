<template>
  <footer class="footer">

    <div class="footer-inner">

      <!-- ЛОГОТИП + ОПИСАНИЕ -->
      <div class="footer-col footer-logo">
        <img src="@/assets/logo.webp" alt="Логотип" class="logo" />

        <p class="footer-desc">
          Магазин товаров для дома, ремонта и электрооборудования.<br>
          Более 5000 товаров в наличии.
        </p>
      </div>

      <!-- МЕНЮ -->
      <div class="footer-col footer-nav">
        <h3 class="footer-title">Меню</h3>

        <a href="/product" class="menu-link">Каталог</a>

        <a href="#about" class="menu-link"
           @click.prevent="scrollToSection('about')">
          О нас
        </a>

        <a href="#contact" class="menu-link"
           @click.prevent="scrollToSection('contact')">
          Контакты
        </a>

        <a href="#photo" class="menu-link"
           @click.prevent="scrollToSection('photo')">
          Фотографии
        </a>
      </div>

      <!-- КОНТАКТЫ -->
      <div class="footer-col footer-contact">
        <h3 class="footer-title">Контакты</h3>

        <p class="contact-line">
          Телефон:<br>
          <a href="tel:+79258693416" class="phone">
            +7 (925) 869-34-16
          </a>
        </p>

        <p class="contact-line">
          Адрес:<br>
          Улица Героев Панфиловцев, дом 3
        </p>
      </div>

    </div>

    <!-- НИЖНЯЯ ПОЛОСА -->
    <div class="footer-bottom">
      © 2025 xozmag.ru — Все права защищены
    </div>

  </footer>
</template>

<script setup>
function scrollToSection(id) {
  const el = document.getElementById(id);
  if (el) {
    el.scrollIntoView({ behavior: "smooth" });
  }
}
</script>

<style scoped>
.footer {
  background: var(--background-container);
  padding: 40px 20px 25px;
  color: #e7e7e7;
  border-top: 1px solid rgba(255,255,255,0.08);
}

.footer-inner {
  max-width: 1200px;
  margin: 0 auto;

  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 40px;
}

.logo {
  width: 140px;
  height: auto;
  border-radius: 10px;
  margin-bottom: 15px;
}

.footer-desc {
  font-size: 14px;
  color: #b8b8b8;
  line-height: 1.5;
  max-width: 250px;
  text-align: center;
}

/* Заголовки */
.footer-title {
  font-size: 18px;
  margin-bottom: 12px;
  font-weight: 700;
  color: #fff;
}

/* Ссылки */
.menu-link {
  display: block;
  margin-bottom: 10px;
  font-size: 15px;
  color: #d4d4d4;
  text-decoration: none;
  transition: 0.25s;
}

.menu-link:hover {
  color: var(--accent-color);
}

/* Контакты */
.contact-line {
  margin-bottom: 14px;
  font-size: 14px;
  color: #cfcfcf;
  text-align: center;
}

.phone {
  color: var(--accent-color);
  text-decoration: none;
  font-size: 16px;
  font-weight: 600;
}

.phone:hover {
  text-decoration: underline;
}

/* Нижняя линия */
.footer-bottom {
  text-align: center;
  margin-top: 35px;
  padding-top: 18px;
  font-size: 14px;
  color: #9a9a9a;
  border-top: 1px solid rgba(255,255,255,0.06);
}

.footer-logo, .footer-nav, .footer-contact {
    display: flex;
    flex-direction: column;
    align-items: center;
}


/* АДАПТИВ */
@media (max-width: 600px) {
  .footer {
    padding: 30px 15px 20px;
  }

  .footer-inner {
    gap: 30px;
  }

  .footer-title {
    font-size: 17px;
  }

  .menu-link {
    font-size: 14px;
  }

  .footer-desc {
    font-size: 13px;
  }
}
</style>
