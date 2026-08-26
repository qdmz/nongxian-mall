import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'

// Vant 组件通过 unplugin-vue-components 自动按需引入；
// 函数式组件（showToast/showDialog 等）的样式统一引入完整样式兜底
import 'vant/lib/index.css'
import './styles/index.css'

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.mount('#app')
