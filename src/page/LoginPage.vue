<template>
  <div class="login-wrap">
    <div class="login-box">
      <h2>Вход</h2>

      <input v-model="login" placeholder="Логин" />
      <input v-model="password" placeholder="Пароль" type="password" />

      <button @click="submit" class="button-main">Войти</button>

      <p v-if="error" class="login-error">{{ error }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";

const router = useRouter();

const login = ref("");
const password = ref("");
const error = ref("");

async function submit() {
  error.value = "";

  const form = new FormData();
  form.append("username", login.value);
  form.append("password", password.value);

  const res = await fetch("/api/login.php", {
    method: "POST",
    body: form
  });

  const data = await res.json();

  if (data.status === "success") {
    localStorage.setItem("token", data.token);
    router.push("/barcode");     // ← после входа открываем barcode
  } else {
    error.value = data.message;
  }
}
</script>


<style scoped>
.login-wrap {
  /* общий стиль как в админке */
  --bg: #0b1220;
  --panel: #0f1a2b;
  --stroke: rgba(148, 163, 184, 0.18);
  --txt: rgba(255, 255, 255, 0.92);
  --muted: rgba(255, 255, 255, 0.62);
  --brand: #4f7cff;
  --brand2: #77a1ff;
  --bad: #ef4444;

  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 18px;

  background:
    radial-gradient(1200px 800px at 15% -10%, rgba(79,124,255,0.18), transparent 55%),
    radial-gradient(1200px 800px at 110% 20%, rgba(16,185,129,0.12), transparent 55%),
    linear-gradient(180deg, rgba(255,255,255,0.02), transparent 55%),
    var(--bg);
}

.login-box {
  width: min(420px, 100%);
  padding: 18px;
  border-radius: 22px;
  border: 1px solid var(--stroke);
  background: linear-gradient(180deg, rgba(255,255,255,0.03), transparent 50%), var(--panel);
  box-shadow: 0 26px 70px rgba(0,0,0,0.55);

  display: flex;
  flex-direction: column;
  gap: 12px;
}

.login-box h2 {
  margin: 0 0 4px;
  font-size: 22px;
  font-weight: 950;
  color: var(--txt);
  letter-spacing: 0.2px;
}

.login-box input {
  width: 100%;
  padding: 12px 12px;
  border-radius: 14px;
  border: 1px solid var(--stroke);
  background: linear-gradient(180deg, rgba(255,255,255,0.04), transparent 35%), rgba(0,0,0,0.22);
  color: var(--txt);
  outline: none;
  transition: 0.15s ease;
}

.login-box input::placeholder {
  color: rgba(255,255,255,0.35);
}

.login-box input:focus {
  border-color: rgba(79,124,255,0.65);
  box-shadow: 0 0 0 4px rgba(79,124,255,0.18);
  background: rgba(0,0,0,0.28);
}

.button-main {
  margin-top: 6px;
  padding: 12px 14px;
  border-radius: 14px;
  border: 1px solid rgba(79,124,255,0.75);
  background: linear-gradient(180deg, rgba(255,255,255,0.10), transparent 40%), var(--brand);
  color: #0b1220;
  font-weight: 950;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: 0.15s ease;
  box-shadow: 0 14px 28px rgba(79,124,255,0.28);
}

.button-main:hover {
  filter: brightness(1.06);
  border-color: rgba(119,161,255,0.9);
  transform: translateY(-1px);
}

.button-main:active {
  transform: translateY(0px);
}

.login-error {
  margin: 6px 0 0;
  padding: 10px 12px;
  border-radius: 16px;
  border: 1px solid rgba(239,68,68,0.42);
  background: rgba(239,68,68,0.14);
  color: rgba(255,255,255,0.92);
  font-weight: 900;
  line-height: 1.25;
}

/* mobile */
@media (max-width: 520px) {
  .login-wrap { padding: 12px; }
  .login-box { padding: 14px; border-radius: 18px; }
}
</style>

