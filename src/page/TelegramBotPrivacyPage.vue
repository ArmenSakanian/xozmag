<template>
  <main class="page">
    <div class="wrap">
      <header class="head">
        <h1 class="h1">Политика конфиденциальности Telegram-бота</h1>
        <p class="sub">Последнее обновление: <b>31.03.2026</b></p>
      </header>

      <section class="card">
        <h2 class="h2">1. Для чего нужна эта страница</h2>
        <p class="p">
          Настоящая страница описывает, какие данные могут обрабатываться при использовании Telegram-бота магазина
          <b>«Всё для дома»</b>.
        </p>
        <p class="p">
          Политика относится только к Telegram-боту и не заменяет общую политику конфиденциальности сайта.
        </p>
      </section>

      <section class="card">
        <h2 class="h2">2. Какие данные могут обрабатываться</h2>
        <ul class="list">
          <li class="li">Telegram ID пользователя</li>
          <li class="li">username, имя и фамилия, если они доступны в Telegram-профиле</li>
          <li class="li">время первого обращения и время последней активности</li>
          <li class="li">сведения о принятии политики конфиденциальности</li>
          <li class="li">история действий внутри бота: выбор разделов, поисковые запросы, открытие карточек товаров</li>
        </ul>
      </section>

      <section class="card">
        <h2 class="h2">3. Для чего используются данные</h2>
        <ul class="list">
          <li class="li">для работы бота и ответа на действия пользователя</li>
          <li class="li">для поиска товаров по названию, штрих-коду и артикулу</li>
          <li class="li">для ведения журнала использования бота и контроля работы сервиса</li>
          <li class="li">для улучшения сценариев работы Telegram-бота</li>
        </ul>
      </section>

      <section class="card">
        <h2 class="h2">4. Что не требуется от пользователя</h2>
        <p class="p">
          Для использования бота не требуется вводить паспортные данные, банковские карты или иные специальные сведения.
          Бот предназначен для поиска товаров и получения справочной информации.
        </p>
      </section>

      <section class="card">
        <h2 class="h2">5. Передача данных</h2>
        <p class="p">
          Данные, используемые ботом, не предназначены для публичного размещения и используются только в рамках работы
          магазина и Telegram-бота. Передача данных возможна только в случаях, когда это требуется по закону или для
          технической работы Telegram и сайта.
        </p>
      </section>

      <section class="card">
        <h2 class="h2">6. Контакты</h2>
        <p class="p">
          По вопросам, связанным с работой Telegram-бота, можно обратиться  по телефону
          <b>+7 (999) 547-01-77</b>.
        </p>
      </section>
    </div>
  </main>
</template>

<script setup>
import { useHead } from '@vueuse/head'

useHead({
  title: 'Политика конфиденциальности Telegram-бота',
  meta: [
    { name: 'robots', content: 'noindex,nofollow,noarchive' },
    { name: 'description', content: 'Политика конфиденциальности Telegram-бота магазина Всё для дома.' },
  ],
})
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
</style>
