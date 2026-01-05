<template>
  <footer class="footer" role="contentinfo">
    <div class="footer-wrap">
      <!-- Бренд -->
      <div class="brand">
        <img
          src="@/assets/logo.webp"
          class="logo"
          alt="Логотип xozmag.ru"
          width="118"
          height="118"
          decoding="async"
        />

        <div class="brand-text">
          <p class="brand-title">Все Для Дома</p>
          <p class="brand-desc">Более 5000 товаров в наличии.</p>
        </div>
      </div>

      <!-- Меню -->
      <nav class="nav" aria-label="Навигация по сайту">
        <p class="nav-title">Меню</p>

        <RouterLink class="nav-link" to="/catalog">Каталог</RouterLink>
        <RouterLink class="nav-link" to="/aboutus">О нас</RouterLink>
        <RouterLink class="nav-link" to="/contact">Контакты</RouterLink>
      </nav>

      <!-- Контакты -->
      <div class="contact">
        <p class="contact-title">Контакты</p>

        <a class="phone" href="tel:+79258693416">+7 (925) 869-34-16</a>

        <p class="address">
          Улица Героев Панфиловцев, дом 3
        </p>
      </div>
    </div>

    <!-- тонкая линия снизу (без текста) -->
    <div class="footer-line" aria-hidden="true"></div>
  </footer>
</template>

<script setup>
// ничего не нужно — без скроллов и лишней логики
</script>

<style scoped>
/* ===== FOOTER (минимал, профессионально, под твой :root) ===== */

.footer{
  background: var(--bg-panel);
  border-top: 1px solid var(--border-soft);
  color: var(--text-main);
}

.footer-wrap{
  max-width: 1200px;
  margin: 0 auto;
  padding: 22px 16px;

  display: grid;
  grid-template-columns: 1.4fr 1fr 1fr;
  gap: 22px;
  align-items: start;
}

/* Brand */
.brand{
  display: flex;
  gap: 14px;
  align-items: center;
  min-width: 0;
}

.logo{
  width: 74px;
  height: 74px;
  object-fit: contain;

  border-radius: var(--radius-lg);
  border: 1px solid var(--border-soft);
  background: var(--bg-panel);
  box-shadow: var(--shadow-sm);
  flex: 0 0 auto;
}

.brand-text{
  min-width: 0;
}

.brand-title{
  margin: 0;
  font-size: 16px;
  font-weight: 1000;
  letter-spacing: -0.01em;
}

.brand-desc{
  margin: 6px 0 0;
  font-size: 13.5px;
  line-height: 1.55;
  color: var(--text-muted);
}

/* Nav */
.nav{
  display: grid;
  align-content: start;
  gap: 10px;
}

.nav-title{
  margin: 0;
  font-size: 13px;
  font-weight: 1000;
  color: var(--text-main);
  letter-spacing: 0.01em;
}

.nav-link{
  width: fit-content;
  text-decoration: none;

  color: var(--text-muted);
  font-weight: 850;
  font-size: 14px;

  padding: 4px 0;
  border-bottom: 1px solid transparent;

  transition: color .18s ease, border-color .18s ease, transform .18s ease;
}

.nav-link:hover{
  color: var(--accent);
  border-bottom-color: rgba(4, 0, 231, 0.22);
  transform: translateY(-1px);
}

.nav-link.router-link-active{
  color: var(--text-main);
  border-bottom-color: var(--border-soft);
}

/* Contact */
.contact{
  display: grid;
  align-content: start;
  gap: 10px;
}

.contact-title{
  margin: 0;
  font-size: 13px;
  font-weight: 1000;
  color: var(--text-main);
  letter-spacing: 0.01em;
}

.phone{
  width: fit-content;
  display: inline-flex;
  text-decoration: none;

  color: var(--accent);
  font-weight: 1000;
  font-size: 14px;

  padding: 6px 0;
  border-bottom: 1px solid rgba(4, 0, 231, 0.18);

  transition: transform .18s ease, filter .18s ease, border-color .18s ease;
}

.phone:hover{
  transform: translateY(-1px);
  filter: brightness(1.03);
  border-bottom-color: rgba(4, 0, 231, 0.30);
}

.address{
  margin: 0;
  font-size: 13.5px;
  line-height: 1.55;
  color: var(--text-muted);
  max-width: 340px;
}

/* bottom line */
.footer-line{
  height: 10px;
  background: linear-gradient(to bottom, rgba(0,0,0,0), rgba(240,242,247,1));
  border-top: 1px solid var(--border-soft);
}

/* Responsive */
@media (max-width: 980px){
  .footer-wrap{
    grid-template-columns: 1fr;
    gap: 18px;
    padding: 18px 12px;
  }

  .logo{
    width: 68px;
    height: 68px;
  }
}
</style>