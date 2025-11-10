import { createApp } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import App from './App.vue'
import './style.css'
import axios from 'axios'

import Dashboard from './pages/Dashboard.vue'
import Settings from './pages/Settings.vue'
import PublicProfile from './pages/PublicProfile.vue'
import Login from './pages/Login.vue'

// Configure axios base URL and credentials
axios.defaults.baseURL = 'http://localhost:8000'
axios.defaults.withCredentials = true
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

// Track if CSRF cookie has been fetched
let csrfCookieFetched = false

// Helper function to get CSRF token from cookie
const getCsrfToken = () => {
  const name = 'XSRF-TOKEN'
  const cookies = document.cookie.split(';')
  for (let cookie of cookies) {
    const [key, value] = cookie.trim().split('=')
    if (key === name) {
      return decodeURIComponent(value)
    }
  }
  return null
}

// Helper function to ensure CSRF cookie is set
const ensureCsrfCookie = async () => {
  if (!csrfCookieFetched) {
    try {
      await axios.get('/sanctum/csrf-cookie')
      csrfCookieFetched = true
    } catch (err) {
      console.warn('Failed to fetch CSRF cookie:', err)
    }
  }
}

// Interceptor to automatically fetch CSRF cookie and add token to headers
axios.interceptors.request.use(async (config) => {
  // Only fetch CSRF cookie for state-changing requests
  if (['post', 'put', 'patch', 'delete'].includes(config.method?.toLowerCase())) {
    await ensureCsrfCookie()
    // Add CSRF token to headers
    const csrfToken = getCsrfToken()
    if (csrfToken) {
      config.headers['X-XSRF-TOKEN'] = csrfToken
    }
  }
  return config
}, (error) => {
  return Promise.reject(error)
})

// Fetch CSRF cookie on app initialization
ensureCsrfCookie()

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

