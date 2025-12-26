import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'

import './assets/main.css'
import './assets/admin.css'
import './assets/swiper-custom.css'

import './fontawesome'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

const app = createApp(App)

app.component('Fa', FontAwesomeIcon)

app.use(createPinia())
app.use(router)

app.mount('#app')
