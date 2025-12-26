<template>
  <div class="admin-panel">
    <header class="head">
      <div>
        <h1 class="title">Админ-панель</h1>
        <p class="subtitle">Быстрый доступ к разделам управления</p>
      </div>
    </header>

    <section class="tiles" aria-label="Разделы админ-панели">
      <a href="/admin/categories" class="tile">
        <span class="ico"><Fa :icon="['fas','folder-tree']" /></span>
        <span class="t">
          <span class="t-title">Категории</span>
          <span class="t-desc">Дерево категорий и подкатегорий</span>
        </span>
        <Fa class="arrow" :icon="['fas','chevron-right']" />
      </a>

      <a href="/admin/attributes" class="tile">
        <span class="ico"><Fa :icon="['fas','sliders']" /></span>
        <span class="t">
          <span class="t-title">Характеристики</span>
          <span class="t-desc">Атрибуты и значения</span>
        </span>
        <Fa class="arrow" :icon="['fas','chevron-right']" />
      </a>

      <a href="/admin/products" class="tile">
        <span class="ico"><Fa :icon="['fas','boxes-stacked']" /></span>
        <span class="t">
          <span class="t-title">Товары</span>
          <span class="t-desc">Таблица, редактирование, bulk</span>
        </span>
        <Fa class="arrow" :icon="['fas','chevron-right']" />
      </a>

      <a href="/admin/functions" class="tile">
        <span class="ico"><Fa :icon="['fas','gears']" /></span>
        <span class="t">
          <span class="t-title">Функции</span>
          <span class="t-desc">Сервисные инструменты</span>
        </span>
        <Fa class="arrow" :icon="['fas','chevron-right']" />
      </a>

      <a href="/admin/order" class="tile">
        <span class="ico"><Fa :icon="['fas','truck']" /></span>
        <span class="t">
          <span class="t-title">Заказ поставщику</span>
          <span class="t-desc">Сформировать и выгрузить заказ</span>
        </span>
        <Fa class="arrow" :icon="['fas','chevron-right']" />
      </a>

      <a href="/admin/barcode" class="tile">
        <span class="ico"><Fa :icon="['fas','barcode']" /></span>
        <span class="t">
          <span class="t-title">Штрихкод</span>
          <span class="t-desc">Создать/изменить размер штрихкода</span>
        </span>
        <Fa class="arrow" :icon="['fas','chevron-right']" />
      </a>
    </section>
  </div>
</template>

<style scoped>
.admin-panel{
  min-height: 100dvh;
  padding: clamp(16px, 3.2vw, 28px);
  background: var(--bg-main);
  color: var(--text-main);
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
  color: var(--text-main);
}

.subtitle{
  margin: 6px 0 0 0;
  color: var(--text-muted);
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
  border-radius: var(--radius-lg);
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  text-decoration: none;
  color: inherit;

  box-shadow: var(--shadow-sm);
  transition: transform .14s ease, box-shadow .14s ease, border-color .14s ease, background .14s ease;
}

.tile:hover{
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
  border-color: color-mix(in srgb, var(--text-main) 14%, var(--border-soft));
  background: color-mix(in srgb, var(--accent) 4%, var(--bg-panel));
}

.tile:active{ transform: translateY(-1px); }

.tile:focus-visible{
  outline: none;
  box-shadow:
    0 0 0 3px color-mix(in srgb, var(--accent) 18%, transparent),
    var(--shadow-md);
  border-color: color-mix(in srgb, var(--accent) 35%, var(--border-soft));
}

/* Иконка слева */
.ico{
  width: 42px;
  height: 42px;
  border-radius: var(--radius-md);
  flex: 0 0 auto;

  display: flex;
  align-items: center;
  justify-content: center;

  border: 1px solid var(--border-soft);
  background: var(--bg-soft);
  color: var(--accent);
}


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
  color: var(--text-main);
}

.t-desc{
  margin-top: 2px;
  font-size: 13px;
  color: var(--text-muted);
  line-height: 1.3;

  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Стрелка справа */
.arrow{
  margin-left: auto;
  color: var(--text-light);
  font-size: 12px;
}

/* Мобилка */
@media (max-width: 520px){
  .tiles{ grid-template-columns: 1fr; }
  .tile{ padding: 12px 12px; border-radius: var(--radius-lg); }
  .ico{ width: 40px; height: 40px; border-radius: var(--radius-md); }
}

/* reduce motion */
@media (prefers-reduced-motion: reduce){
  .tile{ transition: none; }
  .tile:hover{ transform: none; }
}
</style>
