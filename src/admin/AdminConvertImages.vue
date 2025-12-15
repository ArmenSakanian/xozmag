<template>
  <div class="convert-root">
    <div class="convert-box">
      <h1 class="title">Image Converter</h1>
      <p class="subtitle">photo_product_vitrina → WEBP</p>

      <button
        class="main-btn"
        :disabled="loading"
        @click="start"
      >
        {{ loading ? "Processing…" : "START CONVERSION" }}
      </button>

      <!-- CONVERT -->
      <div class="progress-section">
        <div class="progress-header">
          <span>Conversion</span>
          <span>{{ convert.current }} / {{ convert.total }}</span>
        </div>
        <div class="progress-track">
          <div
            class="progress-fill convert"
            :style="{ width: convertPercent + '%' }"
          ></div>
        </div>
      </div>

      <!-- DELETE -->
      <div class="progress-section">
        <div class="progress-header">
          <span>Cleanup</span>
          <span>{{ remove.current }} / {{ remove.total }}</span>
        </div>
        <div class="progress-track">
          <div
            class="progress-fill remove"
            :style="{ width: removePercent + '%' }"
          ></div>
        </div>
      </div>

      <!-- LOG -->
      <div class="log-box">
        <div
          v-for="(l, i) in logs"
          :key="i"
          class="log-line"
        >
          {{ l }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from "vue";
import NProgress from "nprogress";
import "nprogress/nprogress.css";

NProgress.configure({
  showSpinner: false,
  trickleSpeed: 120
});

const loading = ref(false);
const logs = ref([]);

const convert = ref({ current: 0, total: 0 });
const remove = ref({ current: 0, total: 0 });

const convertPercent = computed(() =>
  convert.value.total
    ? Math.round((convert.value.current / convert.value.total) * 100)
    : 0
);

const removePercent = computed(() =>
  remove.value.total
    ? Math.round((remove.value.current / remove.value.total) * 100)
    : 0
);

const sleep = (ms) => new Promise(r => setTimeout(r, ms));

const start = async () => {
  loading.value = true;
  logs.value = [];
  convert.value = { current: 0, total: 0 };
  remove.value = { current: 0, total: 0 };

  NProgress.start();

  let index = 0;

  while (true) {
    const res = await fetch(
      `/api/vitrina/convert_images_step.php?index=${index}`
    );
    const data = await res.json();

    if (data.done) {
      convert.value.total = data.total;
      remove.value.total = data.total;
      break;
    }

    convert.value.total = data.total;
    convert.value.current = data.index;
    remove.value.current = data.index;

    index = data.index;

    logs.value.unshift(
      `${data.status.toUpperCase().padEnd(10)} | ${data.file}`
    );

    NProgress.set(convertPercent.value / 100);
    await sleep(18);
  }

  NProgress.done();
  logs.value.unshift("✔ ALL FILES PROCESSED");
  loading.value = false;
};
</script>

<style scoped>
/* ===== ROOT ===== */
.convert-root {
  min-height: 100vh;
  background: radial-gradient(circle at top, #111 0%, #050505 60%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  color: #eaeaea;
  font-family: "Inter", system-ui, sans-serif;
}

/* ===== BOX ===== */
.convert-box {
  width: 720px;
  max-width: 100%;
  padding: 36px 42px;
  background: linear-gradient(180deg, #0e0e0e, #090909);
  border-radius: 22px;
  box-shadow:
    0 0 0 1px rgba(255,255,255,0.04),
    0 30px 80px rgba(0,0,0,0.8);
}

/* ===== TITLES ===== */
.title {
  text-align: center;
  font-size: 28px;
  font-weight: 800;
  letter-spacing: 1px;
}

.subtitle {
  text-align: center;
  opacity: 0.6;
  margin-bottom: 26px;
}

/* ===== BUTTON ===== */
.main-btn {
  width: 100%;
  padding: 16px;
  font-size: 15px;
  font-weight: 800;
  letter-spacing: 1px;
  border-radius: 14px;
  border: none;
  cursor: pointer;
  background: linear-gradient(135deg, #00ffe1, #0077ff);
  color: #000;
  margin-bottom: 28px;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.main-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 10px 30px rgba(0,170,255,0.35);
}

.main-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

/* ===== PROGRESS ===== */
.progress-section {
  margin-bottom: 22px;
}

.progress-header {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
  letter-spacing: 0.5px;
  margin-bottom: 6px;
  opacity: 0.9;
}

.progress-track {
  height: 14px;
  background: #151515;
  border-radius: 10px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  transition: width 0.18s ease;
}

.progress-fill.convert {
  background: linear-gradient(90deg, #00ffd5, #00aaff);
}

.progress-fill.remove {
  background: linear-gradient(90deg, #ff9800, #ff3d00);
}

/* ===== LOG ===== */
.log-box {
  margin-top: 20px;
  background: #070707;
  border-radius: 14px;
  padding: 14px;
  max-height: 220px;
  overflow-y: auto;
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
  font-size: 12px;
}

.log-line {
  padding: 3px 0;
  opacity: 0.85;
}

/* ===== MOBILE ADAPTATION ===== */
@media (max-width: 600px) {
  .convert-box {
    padding: 24px 20px;
    border-radius: 16px;
  }

  .title {
    font-size: 22px;
  }

  .subtitle {
    font-size: 13px;
  }

  .main-btn {
    font-size: 14px;
    padding: 14px;
  }

  .progress-header {
    font-size: 12px;
  }

  .log-box {
    max-height: 180px;
    font-size: 11px;
  }
}
</style>
