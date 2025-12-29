import { createApp, nextTick } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'

import './assets/main.css'
import './assets/admin.css'

import 'swiper/css'
import 'swiper/css/navigation'
import './assets/swiper-custom.css'

import './fontawesome'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

// ✅ @vueuse/head
import { createHead } from '@vueuse/head'

// ✅ AOS
import AOS from 'aos'
import 'aos/dist/aos.css'

const app = createApp(App)

app.component('Fa', FontAwesomeIcon)

app.use(createPinia())

// ✅ подключаем head
const head = createHead()
app.use(head)

app.use(router)

/* ✅ ждём пока router подгрузит async-страницы */
router.isReady().then(async () => {
  app.mount('#app')

  // ✅ init AOS (можешь менять параметры)
  AOS.init({
    duration: 450,
    easing: 'ease-out',
    once: true,
    offset: 90,
  })

  // ✅ важно для SPA: после смены маршрута обновляем AOS
  router.afterEach(async () => {
    await nextTick()
    AOS.refreshHard()
  })
})
