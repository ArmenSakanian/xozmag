import { fileURLToPath, URL } from "node:url";
import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import vueDevTools from "vite-plugin-vue-devtools";

export default defineConfig({
  plugins: [
    vue(),
    vueDevTools(),
  ],

  resolve: {
    alias: {
      "@": fileURLToPath(new URL("./src", import.meta.url)),
    },
  },

  server: {
    host: true,
    port: 5173,

    proxy: {
      // ===== PHP API =====
      "/api": {
        target: "https://xozmag.ru",
        changeOrigin: true,
        secure: false,
      },

      // ===== IMAGES =====
      "/photo_product_vitrina": {
        target: "https://xozmag.ru",
        changeOrigin: true,
        secure: false,
      },
    },
  },
});
