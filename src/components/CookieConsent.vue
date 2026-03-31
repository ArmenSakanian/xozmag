<template>
  <transition name="fadeUp">
    <div v-if="!accepted" class="ccWrap" role="dialog" aria-live="polite" aria-label="Уведомление о cookies">
      <div class="cc">
        <div class="ccText">
          <div class="ccTitle">Мы используем cookies</div>
          <div class="ccDesc">
            На сайте используется аналитика (Яндекс.Метрика). Продолжая пользоваться сайтом, вы соглашаетесь
            с обработкой технических данных и использованием cookies.
            <RouterLink class="ccLink" :to="policyPath">Подробнее</RouterLink>
          </div>
        </div>

        <div class="ccActions">
          <button class="btnGhost" type="button" @click="goPolicy">
            Политика
          </button>
          <button class="btn" type="button" @click="accept">
            Понятно
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { useRouter } from "vue-router";

const router = useRouter();

const STORAGE_KEY = "xozmag_cookie_consent_v1";
const accepted = ref(true);

const policyPath = "/privacy";

onMounted(() => {
  accepted.value = localStorage.getItem(STORAGE_KEY) === "accepted";
});

function accept() {
  localStorage.setItem(STORAGE_KEY, "accepted");
  accepted.value = true;
}

function goPolicy() {
  router.push(policyPath);
}
</script>

<style scoped>
.ccWrap {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  padding: 12px;
  padding-bottom: calc(12px + env(safe-area-inset-bottom));
  z-index: 9999;
}

.cc {
  width: min(980px, calc(100% - 0px));
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 12px;
  align-items: center;

  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);
  padding: 14px 14px;
}

.ccTitle {
  font-weight: 800;
  margin-bottom: 4px;
}

.ccDesc {
  color: var(--text-muted);
  line-height: 1.45;
  font-size: 14px;
}

.ccLink {
  color: var(--accent);
  text-decoration: none;
  font-weight: 800;
  margin-left: 6px;
}
.ccLink:hover {
  text-decoration: underline;
}

.ccActions {
  display: flex;
  gap: 10px;
}

.btn, .btnGhost {
  border-radius: var(--radius-md);
  padding: 10px 14px;
  font-weight: 800;
  cursor: pointer;
  border: 1px solid transparent;
  transition: transform .06s ease, opacity .15s ease, background .15s ease, border-color .15s ease;
  user-select: none;
  white-space: nowrap;
}

.btn {
  background: var(--accent);
  color: #fff;
  border-color: rgba(0,0,0,0.08);
}
.btn:hover { opacity: .92; }
.btn:active { transform: translateY(1px); }

.btnGhost {
  background: transparent;
  color: var(--text-main);
  border-color: var(--border-soft);
}
.btnGhost:hover { background: var(--bg-soft); }
.btnGhost:active { transform: translateY(1px); }

.fadeUp-enter-active, .fadeUp-leave-active {
  transition: opacity .18s ease, transform .18s ease;
}
.fadeUp-enter-from, .fadeUp-leave-to {
  opacity: 0;
  transform: translateY(10px);
}

@media (max-width: 760px) {
  .cc {
    grid-template-columns: 1fr;
  }
  .ccActions {
    width: 100%;
    justify-content: flex-end;
  }
}
</style>
