<template>
  <main class="nf">
    <section class="card">
      <!-- regular icon -->
      <div class="icon" aria-hidden="true">
        <!-- вариант 1 -->
<Fa :icon="['fas','circle-xmark']" />

        <!-- вариант 2 (если не добавлял circle-xmark): -->
        <!-- <Fa :icon="['far', 'face-meh']" /> -->
      </div>

      <h1 class="title">Страница не найдена</h1>
      <p class="text">
        Похоже, вы перешли по несуществующему адресу.
        Проверьте ссылку или вернитесь на главную.
      </p>

      <div class="actions">
        <router-link class="btn primary" to="/">На главную</router-link>
        <button class="btn ghost" type="button" @click="goBack">Назад</button>
      </div>

      <div class="hint">
        <span class="code">404</span>
        <span class="muted">Not Found</span>
      </div>
    </section>
  </main>
</template>

<script setup lang="ts">
import { useRouter } from "vue-router";

const router = useRouter();
const goBack = () => {
  // если истории нет — вернем на главную
  if (window.history.length > 1) router.back();
  else router.push("/");
};
</script>

<style scoped>
.nf{
  min-height: calc(100vh - var(--site-header-h));
  background: var(--bg-main);
  display: grid;
  place-items: center;
  padding: 24px;
}

.card{
  width: 100%;
  max-width: 720px;
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);
  padding: 28px;
  text-align: center;
}

.icon{
  display: grid;
  place-items: center;
  margin: 6px auto 14px;
  color: var(--accent-danger);
  font-size: clamp(64px, 10vw, 120px);
  line-height: 1;
}

.title{
  color: var(--text-main);
  font-size: clamp(22px, 3.6vw, 32px);
  margin: 0 0 10px;
  letter-spacing: -0.02em;
}

.text{
  margin: 0 auto 18px;
  max-width: 52ch;
  color: var(--text-muted);
  font-size: 16px;
  line-height: 1.6;
}

.actions{
  display: flex;
  gap: 12px;
  justify-content: center;
  flex-wrap: wrap;
  margin-top: 10px;
}

.btn{
  border-radius: var(--radius-md);
  border: 1px solid var(--border-soft);
  padding: 10px 14px;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 140px;
  box-shadow: var(--shadow-sm);
  transition: transform .08s ease, box-shadow .15s ease;
}

.btn:active{
  transform: translateY(1px);
  box-shadow: none;
}

.primary{
  background: var(--accent);
  color: #fff;
  border-color: transparent;
}

.ghost{
  background: var(--bg-soft);
  color: var(--text-main);
}

.hint{
  margin-top: 18px;
  display: inline-flex;
  gap: 10px;
  align-items: center;
  padding: 8px 12px;
  border-radius: var(--radius-lg);
  background: var(--bg-soft);
  border: 1px solid var(--border-soft);
}

.code{
  font-weight: 800;
  color: var(--text-main);
}

.muted{
  color: var(--text-light);
}

/* ✅ Адаптация под мобилки */
@media (max-width: 480px){
  .card{ padding: 18px; }
  .text{ font-size: 15px; }
  .btn{ width: 100%; min-width: 0; }
  .actions{ gap: 10px; }
}
</style>
