<template>
  <div class="admin-panel">
    <header class="head">
      <div>
        <h1 class="title">Админ-панель</h1>
        <p class="subtitle">Быстрый доступ к разделам управления</p>
      </div>
    </header>

    <section class="tiles" aria-label="Разделы админ-панели">
      <a href="/admin/telegram?tab=support" class="tile priority">
        <span class="ico"><Fa :icon="['fas','shield-halved']" /></span>
        <span class="t">
          <span class="t-title">Telegram-бот</span>
          <span class="t-desc">Чаты, техподдержка и ответы пользователям</span>
        </span>
        <Fa class="arrow" :icon="['fas','chevron-right']" />
      </a>

      <a href="/admin/products" class="tile">
        <span class="ico"><Fa :icon="['fas','boxes-stacked']" /></span>
        <span class="t">
          <span class="t-title">Товары</span>
          <span class="t-desc">Таблица, редактирование и bulk</span>
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

      <a href="/admin/functions" class="tile">
        <span class="ico"><Fa :icon="['fas','gears']" /></span>
        <span class="t">
          <span class="t-title">Функции</span>
          <span class="t-desc">Сервисные инструменты</span>
        </span>
        <Fa class="arrow" :icon="['fas','chevron-right']" />
      </a>

      <a href="/admin/barcode" class="tile">
        <span class="ico"><Fa :icon="['fas','barcode']" /></span>
        <span class="t">
          <span class="t-title">Штрихкод</span>
          <span class="t-desc">Создать и изменить размер штрихкода</span>
        </span>
        <Fa class="arrow" :icon="['fas','chevron-right']" />
      </a>

      <a href="/admin/photogallery" class="tile">
        <span class="ico"><Fa :icon="['fa','image']" /></span>
        <span class="t">
          <span class="t-title">Слайдер</span>
          <span class="t-desc">Добавить и удалить изображения слайдера</span>
        </span>
        <Fa class="arrow" :icon="['fas','chevron-right']" />
      </a>
    </section>
  </div>
</template>

<style scoped>
.admin-panel {
  min-height: 100dvh;
  padding: clamp(14px, 3vw, 28px);
  background: var(--bg-main);
  color: var(--text-main);
}

.head {
  max-width: 1100px;
  margin: 0 auto 14px;
}

.title {
  margin: 0;
  font-size: clamp(22px, 2.2vw, 30px);
  letter-spacing: 0.2px;
  color: var(--text-main);
}

.subtitle {
  margin: 6px 0 0;
  color: var(--text-muted);
  font-size: 14px;
  line-height: 1.35;
}

.tiles {
  max-width: 1100px;
  margin: 0 auto;
  display: grid;
  gap: 10px;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.tile {
  display: flex;
  align-items: center;
  gap: 10px;
  min-height: 72px;
  padding: 12px;
  border-radius: var(--radius-lg);
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  text-decoration: none;
  color: inherit;
  box-shadow: var(--shadow-sm);
  transition: transform .14s ease, box-shadow .14s ease, border-color .14s ease, background .14s ease;
}

.tile.priority {
  border-color: color-mix(in srgb, var(--accent) 28%, var(--border-soft));
  background: color-mix(in srgb, var(--accent) 4%, var(--bg-panel));
}

.tile:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
  border-color: color-mix(in srgb, var(--text-main) 14%, var(--border-soft));
}

.tile:active {
  transform: translateY(-1px);
}

.tile:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 18%, transparent), var(--shadow-md);
  border-color: color-mix(in srgb, var(--accent) 35%, var(--border-soft));
}

.ico {
  width: 38px;
  height: 38px;
  border-radius: var(--radius-md);
  flex: 0 0 auto;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--border-soft);
  background: var(--bg-soft);
  color: var(--accent);
}

.t {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.t-title {
  font-weight: 800;
  font-size: 15px;
  letter-spacing: 0.2px;
  color: var(--text-main);
}

.t-desc {
  margin-top: 2px;
  font-size: 12px;
  color: var(--text-muted);
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.arrow {
  margin-left: auto;
  color: var(--text-light);
  font-size: 12px;
}

@media (max-width: 720px) {
  .tiles {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .tile {
    align-items: flex-start;
    min-height: 78px;
  }

  .arrow {
    display: none;
  }
}

@media (max-width: 460px) {
  .admin-panel {
    padding: 12px;
  }

  .tiles {
    gap: 8px;
  }

  .tile {
    min-height: 66px;
    padding: 10px;
    border-radius: 14px;
  }

  .ico {
    width: 34px;
    height: 34px;
  }

  .t-title {
    font-size: 14px;
  }

  .t-desc {
    display: none;
  }
}

@media (prefers-reduced-motion: reduce) {
  .tile {
    transition: none;
  }

  .tile:hover {
    transform: none;
  }
}
</style>
