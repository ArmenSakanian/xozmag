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
      "/api": {
        target: "http://localhost:8000",
        changeOrigin: true,
        secure: false,
      },

      "/photo_product_vitrina": {
        target: "http://localhost:8000",
        changeOrigin: true,
        secure: false,
      },

      "/photo_categories_vitrina": {
        target: "http://localhost:8000",
        changeOrigin: true,
        secure: false,
      },

      "/slider_photo": {
        target: "http://localhost:8000",
        changeOrigin: true,
        secure: false,
      },

      "/photo_product_barcode": {
        target: "http://localhost:8000",
        changeOrigin: true,
        secure: false,
      },

      "/home_showcase_cards": {
        target: "http://localhost:8000",
        changeOrigin: true,
        secure: false,
      },
    },
  },
});