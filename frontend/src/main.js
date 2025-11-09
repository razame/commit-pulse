import { createApp } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import App from './App.vue'
import './style.css'
import axios from 'axios'

import Dashboard from './pages/Dashboard.vue'
import Settings from './pages/Settings.vue'
import PublicProfile from './pages/PublicProfile.vue'
import Login from './pages/Login.vue'

// Configure axios to send cookies for session-based authentication
axios.defaults.withCredentials = true
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

const routes = [
  { path: '/', component: Login },
  { path: '/dashboard', component: Dashboard },
  { path: '/settings', component: Settings },
  { path: '/u/:username', component: PublicProfile },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

const app = createApp(App)
app.use(router)
app.mount('#app')

