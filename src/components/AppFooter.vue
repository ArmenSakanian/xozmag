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
/* ===== FOOTER (под твой :root) ===== */

.footer{
  background: var(--bg-panel);
  border-top: 1px solid var(--border-soft);
  box-shadow: 0 -1px 0 rgba(0,0,0,0.02);
  color: var(--text-main);
}

/* Внутренние отступы */
.footer-inner{
  max-width: 1200px;
  margin: 0 auto;
  padding: 26px 16px 18px;

  display: grid;
  grid-template-columns: 1.2fr 1fr 1fr;
  gap: 18px;
}

/* Колонки */
.footer-col{
  background: var(--bg-soft);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  padding: 16px 16px;

  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

/* Лого */
.logo{
  width: 120px;
  height: auto;
  border-radius: 12px;
  border: 1px solid var(--border-soft);
  box-shadow: var(--shadow-sm);
  margin-bottom: 12px;
}

.footer-desc{
  margin: 0;
  max-width: 320px;

  font-size: 13.5px;
  line-height: 1.6;
  color: var(--text-muted);
}

/* Заголовки */
.footer-title{
  margin: 0 0 10px;
  font-size: 16px;
  font-weight: 900;
  letter-spacing: -0.01em;
  color: var(--text-main);
}

/* Меню-ссылки */
.menu-link{
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;

  width: 100%;
  padding: 10px 12px;
  margin-bottom: 10px;

  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: 14px;
  box-shadow: var(--shadow-sm);

  color: var(--text-main);
  text-decoration: none;
  font-size: 14px;
  font-weight: 800;

  transition: transform .18s ease, filter .18s ease, border-color .18s ease;
}

.menu-link:last-child{ margin-bottom: 0; }

.menu-link:hover{
  transform: translateY(-1px);
  filter: brightness(1.02);
  border-color: rgba(4, 0, 231, 0.22);
}

.menu-link:active{
  transform: translateY(0);
}

/* Контакты */
.contact-line{
  margin: 0 0 12px;
  font-size: 13.5px;
  line-height: 1.6;
  color: var(--text-muted);
}

.contact-line:last-child{ margin-bottom: 0; }

.phone{
  display: inline-flex;
  align-items: center;
  justify-content: center;

  margin-top: 6px;
  padding: 8px 12px;

  background: rgba(4, 0, 231, 0.06);
  border: 1px solid rgba(4, 0, 231, 0.16);
  border-radius: 999px;

  color: var(--accent);
  text-decoration: none;
  font-size: 14px;
  font-weight: 900;

  transition: transform .18s ease, filter .18s ease;
}

.phone:hover{
  transform: translateY(-1px);
  filter: brightness(1.05);
}

.phone:active{
  transform: translateY(0);
}

/* Нижняя полоса */
.footer-bottom{
  text-align: center;
  padding: 14px 16px 18px;

  color: var(--text-light);
  font-size: 13px;

  border-top: 1px solid var(--border-soft);
  background: linear-gradient(to bottom, rgba(244,246,251,0), rgba(244,246,251,1));
}

/* ===== Responsive ===== */
@media (max-width: 980px){
  .footer-inner{
    grid-template-columns: 1fr;
    gap: 12px;
    padding: 18px 12px 14px;
  }
}

@media (max-width: 600px){
  .footer-col{
    padding: 14px 14px;
  }

  .logo{
    width: 110px;
  }

  .footer-title{
    font-size: 15px;
  }

  .menu-link{
    font-size: 13.5px;
    padding: 10px 12px;
  }

  .footer-desc,
  .contact-line{
    font-size: 13px;
  }

  .footer-bottom{
    font-size: 12.5px;
    padding: 12px 12px 16px;
  }
}
</style>

