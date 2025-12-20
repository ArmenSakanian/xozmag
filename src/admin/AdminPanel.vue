<template>
  <div class="admin-panel">
    <header class="head">
      <div>
        <h1 class="title">Админ-панель</h1>
        <p class="subtitle">Быстрый доступ к разделам управления</p>
      </div>
    </header>

    <section class="tiles" aria-label="Разделы админ-панели">
      <router-link to="/admin/categories" class="tile">
        <span class="ico"><i class="fa-solid fa-folder-tree"></i></span>
        <span class="t">
          <span class="t-title">Категории</span>
          <span class="t-desc">Дерево категорий и подкатегорий</span>
        </span>
        <i class="fa-solid fa-chevron-right arrow"></i>
      </router-link>

      <router-link to="/admin/attributes" class="tile">
        <span class="ico"><i class="fa-solid fa-sliders"></i></span>
        <span class="t">
          <span class="t-title">Характеристики</span>
          <span class="t-desc">Атрибуты и значения</span>
        </span>
        <i class="fa-solid fa-chevron-right arrow"></i>
      </router-link>

      <router-link to="/admin/products" class="tile">
        <span class="ico"><i class="fa-solid fa-boxes-stacked"></i></span>
        <span class="t">
          <span class="t-title">Товары</span>
          <span class="t-desc">Таблица, редактирование, bulk</span>
        </span>
        <i class="fa-solid fa-chevron-right arrow"></i>
      </router-link>

      <router-link to="/admin/functions" class="tile">
        <span class="ico"><i class="fa-solid fa-gears"></i></span>
        <span class="t">
          <span class="t-title">Функции</span>
          <span class="t-desc">Сервисные инструменты</span>
        </span>
        <i class="fa-solid fa-chevron-right arrow"></i>
      </router-link>

      <router-link to="/admin/order" class="tile">
        <span class="ico"><i class="fa-solid fa-truck"></i></span>
        <span class="t">
          <span class="t-title">Заказ поставщику</span>
          <span class="t-desc">Сформировать и выгрузить заказ</span>
        </span>
        <i class="fa-solid fa-chevron-right arrow"></i>
      </router-link>
    </section>
  </div>
</template>

<style scoped>
/* Минималистичные токены (без неона) */
.admin-panel{
  --bg: #f6f7fb;
  --card: #ffffff;
  --text: #111827;
  --muted: #6b7280;
  --stroke: #e6e8ef;
  --shadow: 0 8px 24px rgba(17, 24, 39, .08);
  --shadow2: 0 12px 30px rgba(17, 24, 39, .10);
  --radius: 16px;
  --accent: #2563eb; /* спокойный синий */
  --accent-soft: rgba(37, 99, 235, .10);

  min-height: 100dvh;
  padding: clamp(16px, 3.2vw, 28px);
  background: var(--bg);
  color: var(--text);
}

/* Шапка */
.head{
  max-width: 1100px;
  margin: 0 auto 14px auto;
}

.title{
  margin: 0;
  font-size: clamp(22px, 2.2vw, 30px);
  letter-spacing: .2px;
}

.subtitle{
  margin: 6px 0 0 0;
  color: var(--muted);
  font-size: 14px;
  line-height: 1.35;
}

/* Сетка */
.tiles{
  max-width: 1100px;
  margin: 0 auto;
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
}

/* Карточка-ссылка */
.tile{
  display: flex;
  align-items: center;
  gap: 12px;

  padding: 14px 14px;
  border-radius: var(--radius);
  background: var(--card);
  border: 1px solid var(--stroke);
  text-decoration: none;
  color: inherit;

  box-shadow: var(--shadow);
  transition: transform .14s ease, box-shadow .14s ease, border-color .14s ease;
}

.tile:hover{
  transform: translateY(-2px);
  box-shadow: var(--shadow2);
  border-color: rgba(17, 24, 39, .14);
}

.tile:active{ transform: translateY(-1px); }

.tile:focus-visible{
  outline: none;
  box-shadow: 0 0 0 3px var(--accent-soft), var(--shadow2);
  border-color: rgba(37, 99, 235, .35);
}

/* Иконка слева */
.ico{
  width: 42px;
  height: 42px;
  border-radius: 12px;
  flex: 0 0 auto;

  display: flex;
  align-items: center;
  justify-content: center;

  border: 1px solid var(--stroke);
  background: #fafbff;
  color: #1f2937;
}

.ico i{ font-size: 18px; }

/* Текст */
.t{
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.t-title{
  font-weight: 800;
  font-size: 15px;
  letter-spacing: .2px;
}

.t-desc{
  margin-top: 2px;
  font-size: 13px;
  color: var(--muted);
  line-height: 1.3;

  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Стрелка справа */
.arrow{
  margin-left: auto;
  color: #9ca3af;
  font-size: 12px;
}

/* Мобилка */
@media (max-width: 520px){
  .tiles{ grid-template-columns: 1fr; }
  .tile{ padding: 12px 12px; border-radius: 14px; }
  .ico{ width: 40px; height: 40px; border-radius: 12px; }
}

/* Уважение к reduce motion */
@media (prefers-reduced-motion: reduce){
  .tile{ transition: none; }
  .tile:hover{ transform: none; }
}
</style>
