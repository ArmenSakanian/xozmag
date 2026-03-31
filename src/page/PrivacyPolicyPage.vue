<template>
  <main class="page">
    <div class="wrap">
      <header class="head">
        <h1 class="h1">Политика конфиденциальности и cookies</h1>
        <p class="sub">Последнее обновление: <b>11.01.2026</b></p>
      </header>

      <section class="card">
        <h2 class="h2">1. Общие положения</h2>
        <p class="p">
          Настоящая политика описывает обработку технических данных при использовании сайта
          <b>xozmag.ru</b> (далее — «Сайт»), включая использование cookies.
        </p>
        <p class="p">
          Продолжая использовать Сайт, вы соглашаетесь с использованием cookies и обработкой технических данных
          в соответствии с этой политикой. Если вы не согласны — вы можете отключить cookies в настройках браузера.
        </p>

        <div class="contacts">
          <div class="cRow">
            <span class="cKey">Контакт:</span>
            <span class="cVal">+7 925 869 34 16</span>
          </div>
        </div>
      </section>

      <section class="card">
        <h2 class="h2">2. Какие данные обрабатываются</h2>
        <p class="p">
          На Сайте обрабатываются <b>технические данные</b>, которые собираются аналитическим сервисом.
        </p>

        <h3 class="h3">2.1. Технические данные</h3>
        <ul class="list">
          <li class="li">
            IP-адрес, тип устройства, браузер, язык, страницы входа/выхода, время посещения, источники переходов,
            действия на страницах.
          </li>
          <li class="li">
            Данные, собираемые с помощью cookies и похожих технологий (см. раздел 4).
          </li>
        </ul>
      </section>

      <section class="card">
        <h2 class="h2">3. Аналитика: Яндекс.Метрика</h2>
        <p class="p">
          Для анализа посещаемости и улучшения работы Сайта используется <b>Яндекс.Метрика</b>.
          Она может собирать технические данные о вашем устройстве и действиях на сайте, а также
          использовать cookies и/или локальные идентификаторы для различения посещений и построения статистики.
        </p>

        <div class="grid2">
          <div class="box">
            <div class="boxTitle">Цели</div>
            <ul class="list">
              <li class="li">анализ посещаемости и поведения пользователей</li>
              <li class="li">улучшение интерфейса и качества работы Сайта</li>
              <li class="li">поиск ошибок и проблем на страницах</li>
            </ul>
          </div>

          <div class="box">
            <div class="boxTitle">Кто обрабатывает</div>
            <p class="p">
              Данные аналитики обрабатываются сервисом Яндекса. На стороне Яндекса могут устанавливаться
              сторонние cookies домена <b>yandex.ru</b>, а также cookies на домене Сайта, связанные с Метрикой.
            </p>
          </div>
        </div>
      </section>

      <section class="card">
        <h2 class="h2">4. Cookies и похожие технологии</h2>
        <p class="p">
          Cookies — это небольшие файлы, которые сохраняются в браузере. На Сайте используются cookies:
        </p>

        <div class="table">
          <div class="tRow tHead">
            <div class="tCell">Категория</div>
            <div class="tCell">Примеры</div>
            <div class="tCell">Зачем</div>
          </div>

          <div class="tRow">
            <div class="tCell"><b>Аналитика</b></div>
            <div class="tCell">cookies/идентификаторы Яндекс.Метрики</div>
            <div class="tCell">статистика посещений, улучшение сайта</div>
          </div>

          <div class="tRow">
            <div class="tCell"><b>Технические</b></div>
            <div class="tCell">обязательные cookies/сессионные данные (если применимо)</div>
            <div class="tCell">корректная работа сайта и функционала</div>
          </div>
        </div>

        <h3 class="h3">Как отключить cookies</h3>
        <p class="p">
          Вы можете удалить или запретить cookies в настройках вашего браузера. В этом случае часть функций Сайта
          может работать некорректно.
        </p>
      </section>

      <section class="card">
        <h2 class="h2">5. Передача данных третьим лицам</h2>
        <p class="p">
          Мы не продаём персональные данные. Передача технических данных возможна только в случаях:
        </p>
        <ul class="list">
          <li class="li">обработка аналитики сервисом Яндекс.Метрика</li>
          <li class="li">по требованию закона</li>
        </ul>
      </section>

      <section class="card">
        <h2 class="h2">6. Изображения и фотографии товаров</h2>
        <p class="p">
          Фотографии и изображения товаров на Сайте носят <b>иллюстративный</b> характер и могут отличаться от
          фактического вида товара: оттенок может зависеть от настроек экрана, производитель может менять
          упаковку/маркировку, возможны отличия между партиями.
        </p>
        <p class="p">
          Мы стараемся поддерживать информацию актуальной, но рекомендуем уточнять характеристики и комплектацию
          перед покупкой.
        </p>
      </section>

      <footer class="foot">
        <RouterLink class="link" to="/">На главную</RouterLink>
      </footer>
    </div>
  </main>
</template>

<script setup>
// Ничего менять не нужно
</script>

<style scoped>
.page {
  min-height: calc(100vh - var(--site-header-h));
  background: var(--bg-main);
  color: var(--text-main);
  padding: 18px 0 40px;
}

.wrap {
  width: min(980px, calc(100% - 24px));
  margin: 0 auto;
}

.head {
  margin: 8px 0 16px;
}

.h1 {
  font-size: 28px;
  line-height: 1.2;
  margin: 0 0 8px;
}

.sub {
  margin: 0;
  color: var(--text-muted);
  font-size: 14px;
}

.card {
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  padding: 18px;
  margin: 12px 0;
}

.h2 {
  margin: 0 0 10px;
  font-size: 18px;
}

.h3 {
  margin: 12px 0 8px;
  font-size: 15px;
}

.p {
  margin: 8px 0;
  color: var(--text-main);
  line-height: 1.55;
}

.list {
  margin: 8px 0;
  padding-left: 18px;
}

.li {
  margin: 6px 0;
  color: var(--text-main);
  line-height: 1.5;
}

.grid2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-top: 12px;
}

.box {
  background: var(--bg-soft);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-md);
  padding: 12px;
}

.boxTitle {
  font-weight: 800;
  margin-bottom: 8px;
}

.table {
  margin-top: 12px;
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-md);
  overflow: hidden;
}

.tRow {
  display: grid;
  grid-template-columns: 160px 1fr 1fr;
  border-top: 1px solid var(--border-soft);
}

.tRow:first-child {
  border-top: none;
}

.tCell {
  padding: 10px 12px;
  font-size: 14px;
  border-right: 1px solid var(--border-soft);
}

.tCell:last-child {
  border-right: none;
}

.tHead .tCell {
  background: var(--bg-soft);
  font-weight: 800;
}

.contacts {
  margin-top: 12px;
  background: var(--bg-soft);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-md);
  padding: 12px;
}

.cRow {
  display: flex;
  gap: 10px;
  align-items: center;
}

.cKey {
  color: var(--text-muted);
}

.cVal {
  color: var(--text-main);
  font-weight: 800;
}

.foot {
  margin-top: 14px;
  display: flex;
  justify-content: flex-start;
}

.link {
  color: var(--accent);
  text-decoration: none;
  font-weight: 800;
}

.link:hover {
  text-decoration: underline;
}

@media (max-width: 860px) {
  .grid2 {
    grid-template-columns: 1fr;
  }
  .tRow {
    grid-template-columns: 120px 1fr;
  }
  .tRow .tCell:nth-child(3) {
    grid-column: 1 / -1;
    border-right: none;
    border-top: 1px solid var(--border-soft);
  }
}
</style>
