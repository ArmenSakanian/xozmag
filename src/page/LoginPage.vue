<template>
  <div class="login-wrap">
    <div class="login-box">
      <h2>Вход</h2>

      <div class="neo-field">
        <span class="neo-ico" aria-hidden="true">
          <!-- user -->
          <svg viewBox="0 0 24 24" fill="none">
            <path
              d="M20 21a8 8 0 0 0-16 0"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
            />
            <path
              d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
        </span>
        <input v-model="login" placeholder="Логин" autocomplete="username" />
      </div>

      <div class="neo-field">
        <span class="neo-ico" aria-hidden="true">
          <!-- lock -->
          <svg viewBox="0 0 24 24" fill="none">
            <path
              d="M17 11H7a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2Z"
              stroke="currentColor"
              stroke-width="2"
              stroke-linejoin="round"
            />
            <path
              d="M8 11V8a4 4 0 0 1 8 0v3"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
            />
          </svg>
        </span>
        <input
          v-model="password"
          placeholder="Пароль"
          type="password"
          autocomplete="current-password"
        />
      </div>

      <button @click="submit" class="button-main">Войти</button>

      <p v-if="error" class="login-error">{{ error }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { useRouter, useRoute } from "vue-router";

const router = useRouter();
const route = useRoute();

const login = ref("");
const password = ref("");
const error = ref("");

async function submit() {
  error.value = "";

  const form = new FormData();
  form.append("username", login.value);
  form.append("password", password.value);

  const res = await fetch("/api/auth/login.php", {
    method: "POST",
    body: form,
    credentials: "same-origin",
    headers: { Accept: "application/json" },
  });

  const data = await res.json().catch(() => null);

  if (data?.status === "success") {
    const redirect = route.query.redirect;
    if (typeof redirect === "string" && redirect.startsWith("/")) {
      router.push(redirect);
      return;
    }

    if (data?.user?.role === "admin") router.push("/admin");
    else router.push("/barcode");
  } else {
    error.value = data?.message || "Ошибка входа";
  }
}
</script>

<style scoped>
/* ✅ фон разделён диагональю: линия от (лево-верх) к (право-низ) */
.login-wrap {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 18px;
  position: relative;
  overflow: hidden;
  background: var(--bg-main);
}

.login-wrap::before {
  content: "";
  position: absolute;
  inset: 0;
  background: var(--accent);
  /* треугольник сверху/справа, граница — (0,0) -> (100,100) */
  clip-path: polygon(0 0, 100% 0, 100% 100%);
  opacity: 1;
}

.login-wrap::after {
  /* лёгкий софт-градиент чтобы разделение выглядело аккуратно */
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(
    135deg,
    rgba(255,255,255,0.10) 0%,
    rgba(255,255,255,0.00) 55%,
    rgba(0,0,0,0.08) 100%
  );
  pointer-events: none;
}

.login-box {
  position: relative;
  z-index: 2;

  width: min(420px, 100%);
  padding: 26px 22px 22px;
  border-radius: 22px;

  /* чуть белее, чтобы читалось на accent-фоне */
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);

  display: flex;
  flex-direction: column;
  gap: 14px;
}

.login-box h2 {
  margin: 0 0 2px;
  text-align: center;
  font-size: 22px;
  font-weight: 900;
  color: var(--text-main);
  letter-spacing: 0.2px;
}

.neo-field {
  display: flex;
  align-items: center;
  gap: 10px;

  padding: 12px 14px;
  border-radius: 999px;
  background: var(--bg-soft);
  border: 1px solid var(--border-soft);

  /* глубже inset для читаемости */
  box-shadow:
    inset 12px 12px 20px rgba(0, 0, 0, 0.10),
    inset -12px -12px 20px rgba(255, 255, 255, 0.92);

  transition: box-shadow 0.15s ease, transform 0.15s ease, border-color 0.15s ease;
}

.neo-ico {
  width: 22px;
  height: 22px;
  color: var(--text-muted);
  display: grid;
  place-items: center;
  flex: 0 0 22px;
}

.neo-ico svg {
  width: 20px;
  height: 20px;
}

.neo-field:focus-within {
  border-color: rgba(4, 0, 255, 0.35);
  box-shadow:
    inset 14px 14px 22px rgba(0, 0, 0, 0.11),
    inset -14px -14px 22px rgba(255, 255, 255, 0.95),
    0 0 0 4px rgba(4, 0, 255, 0.14);
}

.neo-field input {
  width: 100%;
  border: none;
  outline: none;
  background: transparent;

  color: var(--text-main);
  font-size: 14px;
  font-weight: 800;
}

.neo-field input::placeholder {
  color: var(--text-muted);
  font-weight: 800;
}

.button-main {
  margin-top: 4px;
  padding: 12px 14px;
  border-radius: 999px;
  border: none;
  cursor: pointer;

  background: linear-gradient(180deg, rgba(255, 255, 255, 0.18), transparent 45%), var(--accent);
  color: #fff;
  font-weight: 900;
  letter-spacing: 0.2px;

  box-shadow:
    0 18px 34px rgba(4, 0, 255, 0.28),
    0 10px 18px rgba(0, 0, 0, 0.10);

  transition: transform 0.15s ease, filter 0.15s ease, box-shadow 0.15s ease;
}

.button-main:hover {
  filter: brightness(1.05);
  transform: translateY(-1px);
}

.button-main:active {
  transform: translateY(0px);
  box-shadow:
    0 12px 24px rgba(4, 0, 255, 0.22),
    0 8px 14px rgba(0, 0, 0, 0.10);
}

.login-error {
  margin: 4px 0 0;
  padding: 10px 12px;
  border-radius: 14px;
  background: rgba(220, 38, 38, 0.10);
  border: 1px solid rgba(220, 38, 38, 0.28);
  color: var(--text-main);
  font-weight: 900;
  text-align: center;
}

@media (max-width: 520px) {
  .login-box {
    padding: 22px 16px 16px;
    border-radius: 18px;
  }
}
</style>
