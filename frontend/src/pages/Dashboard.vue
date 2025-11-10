<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex justify-between items-center">
          <h1 class="text-2xl font-bold text-gray-900">CommitPulse</h1>
          <div class="flex items-center gap-4">
            <router-link to="/settings" class="text-gray-600 hover:text-gray-900">
              Settings
            </router-link>
            <button @click="logout" class="text-gray-600 hover:text-gray-900">
              Logout
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div v-if="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
        <p class="mt-4 text-gray-600">Loading your stats...</p>
      </div>

      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4">
        <p class="text-red-800">{{ error }}</p>
      </div>

      <div v-else-if="stats && (stats.week_start || stats.total_commits !== undefined)">
        <!-- Hero Section -->
        <div class="mb-8">
          <h2 class="text-3xl font-bold text-gray-900 mb-2">Your Weekly Pulse</h2>
          <p class="text-gray-600" v-if="stats.week_start">
            Week of {{ formatDate(stats.week_start) }} - {{ formatDate(stats.week_end) }}
          </p>
          <p class="text-gray-600" v-else>
            Loading week information...
          </p>
        </div>

        <!-- No Data Message -->
        <div v-if="stats.total_commits === 0" class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-8">
          <div class="flex items-start gap-4">
            <div class="text-2xl">📭</div>
            <div class="flex-1">
              <h3 class="text-lg font-semibold text-yellow-900 mb-2">No commits found for this week</h3>
              <p class="text-yellow-800 mb-4">
                Click "Sync Now" below to fetch your commits from GitHub. The sync will fetch commits from the last 7 days.
              </p>
              <p class="text-sm text-yellow-700 mb-2">
                <strong>Note:</strong> The sync job runs in the background. If you're using queues, make sure to run <code class="bg-yellow-100 px-1 rounded">php artisan queue:work</code> in your backend directory.
              </p>
              <p class="text-sm text-yellow-700">
                Alternatively, set <code class="bg-yellow-100 px-1 rounded">QUEUE_CONNECTION=sync</code> in your <code class="bg-yellow-100 px-1 rounded">.env</code> file to run jobs immediately.
              </p>
            </div>
          </div>
        </div>

        <!-- Stats Grid -->
        <div v-if="stats.total_commits > 0" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <StatCard
            title="Total Commits"
            :value="stats.total_commits"
            icon="📊"
            color="bg-blue-500"
            fontSize="text-3xl"
          />
          <StatCard
            title="Lines Added"
            :value="(stats.total_additions || 0).toLocaleString()"
            icon="+"
            color="bg-green-500"
            textColor="text-white"
            fontSize="text-5xl"
          />
          <StatCard
            title="Lines Removed"
            :value="(stats.total_deletions || 0).toLocaleString()"
            icon="-"
            color="bg-green-500"
            textColor="text-white"
            fontSize="text-5xl"
          />
        </div>

        <!-- Charts Row -->
        <div v-if="stats.total_commits > 0" class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Commits by Day</h3>
            <WeeklyChart :data="commitsByDayData" />
          </div>

          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Top Languages</h3>
            <LanguagesChart :data="languagesData" />
          </div>
        </div>

        <!-- Commits List -->
        <div v-if="stats.total_commits > 0 && stats.commits && stats.commits.length > 0" class="bg-white rounded-lg shadow p-6 mb-8">
          <h3 class="text-lg font-semibold mb-4">All Commits</h3>
          <div class="space-y-3 max-h-96 overflow-y-auto">
            <div
              v-for="commit in stats.commits"
              :key="commit.id"
              class="flex items-start justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
            >
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-2">
                  <span class="text-sm font-medium text-gray-900">{{ formatCommitDate(commit.date) }}</span>
                  <span v-if="commit.repository" class="text-xs text-gray-500 bg-gray-200 px-2 py-1 rounded">
                    {{ commit.repository }}
                  </span>
                </div>
                <p class="text-sm text-gray-700 mb-2 line-clamp-2">{{ commit.message }}</p>
                <div class="flex items-center gap-4 text-xs text-gray-500">
                  <span class="flex items-center gap-1">
                    <span class="text-green-600 font-semibold">+{{ commit.additions }}</span>
                    <span>additions</span>
                  </span>
                  <span class="flex items-center gap-1">
                    <span class="text-red-600 font-semibold">-{{ commit.deletions }}</span>
                    <span>deletions</span>
                  </span>
                  <span class="text-gray-600">
                    {{ commit.total_changes }} total changes
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Top Repos -->
        <div v-if="stats.total_commits > 0 && stats.top_repos && stats.top_repos.length > 0" class="bg-white rounded-lg shadow p-6 mb-8">
          <h3 class="text-lg font-semibold mb-4">Most Active Repositories</h3>
          <div class="space-y-3">
            <div
              v-for="(repo, index) in stats.top_repos"
              :key="index"
              class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
            >
              <div class="flex items-center gap-3">
                <span class="text-2xl">#{{ index + 1 }}</span>
                <div>
                  <p class="font-medium text-gray-900">{{ repo.repo }}</p>
                </div>
              </div>
              <span class="text-lg font-semibold text-primary-600">{{ repo.count }} commits</span>
            </div>
          </div>
        </div>

        <!-- Sync Button -->
        <div class="flex justify-center">
          <button
            @click="syncData"
            :disabled="syncing"
            class="px-6 py-3 bg-primary-600 text-white rounded-lg font-semibold hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ syncing ? 'Syncing...' : 'Sync Now' }}
          </button>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import WeeklyChart from '../components/WeeklyChart.vue'
import LanguagesChart from '../components/LanguagesChart.vue'
import StatCard from '../components/StatCard.vue'

const router = useRouter()
const loading = ref(true)
const error = ref(null)
const stats = ref({
  week_start: '',
  week_end: '',
  total_commits: 0,
  total_additions: 0,
  total_deletions: 0,
  commits_by_day: {},
  top_repos: [],
  top_languages: {},
  commits: [],
  last_synced_at: null,
})
const syncing = ref(false)

const commitsByDayData = computed(() => {
  if (!stats.value.commits_by_day) return { labels: [], datasets: [] }
  
  const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
  const data = days.map(day => stats.value.commits_by_day[day] || 0)
  
  return {
    labels: days,
    datasets: [{
      label: 'Commits',
      data: data,
      backgroundColor: 'rgba(14, 165, 233, 0.5)',
      borderColor: 'rgb(14, 165, 233)',
      borderWidth: 2,
    }],
  }
})

const languagesData = computed(() => {
  if (!stats.value.top_languages) return { labels: [], datasets: [] }
  
  const labels = Object.keys(stats.value.top_languages)
  const data = Object.values(stats.value.top_languages)
  
  return {
    labels,
    datasets: [{
      data,
      backgroundColor: [
        'rgba(14, 165, 233, 0.8)',
        'rgba(34, 197, 94, 0.8)',
        'rgba(251, 146, 60, 0.8)',
        'rgba(168, 85, 247, 0.8)',
        'rgba(236, 72, 153, 0.8)',
      ],
    }],
  }
})

const fetchStats = async () => {
  try {
    loading.value = true
    error.value = null
    
    // Direct API call to backend
    // Session-based authentication via cookies (configured in main.js)
    const response = await axios.get('/api/stats/current-week')
    
    // Debug: Log the response
    console.log('Stats API Response:', response.data)
    
    // Ensure stats object has required properties
    stats.value = {
      week_start: response.data.week_start || '',
      week_end: response.data.week_end || '',
      total_commits: response.data.total_commits || 0,
      total_additions: response.data.total_additions || 0,
      total_deletions: response.data.total_deletions || 0,
      commits_by_day: response.data.commits_by_day || {},
      top_repos: response.data.top_repos || [],
      top_languages: response.data.top_languages || {},
      commits: response.data.commits || [],
      last_synced_at: response.data.last_synced_at || null,
    }
    
    console.log('Stats value set:', stats.value)
  } catch (err) {
    if (err.response) {
      // Server responded with error status
      if (err.response.status === 401) {
        error.value = 'Authentication failed. Please log in again.'
        localStorage.removeItem('auth_token')
        router.push('/')
      } else if (err.response.status === 500) {
        error.value = 'Server error. Please try again later.'
      } else {
        error.value = `Failed to load stats: ${err.response.data?.message || err.response.statusText || 'Unknown error'}`
      }
    } else if (err.request) {
      // Request made but no response received
      error.value = 'Unable to connect to server. Please check your connection.'
    } else {
      // Error setting up the request
      error.value = 'Failed to load stats. Please try again.'
    }
    console.error('Stats loading error:', err)
  } finally {
    loading.value = false
  }
}

const syncData = async () => {
  try {
    syncing.value = true
    error.value = null
    
    // Get CSRF token from cookie
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
    
    const csrfToken = getCsrfToken()
    
    // CSRF cookie is automatically fetched by axios interceptor
    // Direct API call to backend with CSRF token in headers
    // Session-based authentication via cookies (configured in main.js)
    const response = await axios.post('/api/sync', {}, {
      headers: {
        'X-XSRF-TOKEN': csrfToken
      }
    })
    
    // Show success message
    if (response.data?.message) {
      // You could show a toast notification here
      console.log(response.data.message)
    }
    
    // Refresh stats after a longer delay to allow worker to process
    setTimeout(() => {
      fetchStats()
    }, 5000) // Increased delay to give worker time
  } catch (err) {
    if (err.response) {
      error.value = err.response.data?.message || 'Sync failed. Please try again.'
    } else {
      error.value = 'Sync failed. Please make sure the worker is running. See SETUP.md for instructions.'
    }
    console.error(err)
  } finally {
    syncing.value = false
  }
}

const logout = async () => {
  try {
    // Call backend logout endpoint to clear session
    await axios.post('/auth/logout')
  } catch (err) {
    console.error('Logout error:', err)
  }
  router.push('/')
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
  })
}

const formatCommitDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  const today = new Date()
  const yesterday = new Date(today)
  yesterday.setDate(yesterday.getDate() - 1)
  
  // Check if it's today
  if (date.toDateString() === today.toDateString()) {
    return 'Today'
  }
  // Check if it's yesterday
  if (date.toDateString() === yesterday.toDateString()) {
    return 'Yesterday'
  }
  // Otherwise return formatted date
  return date.toLocaleDateString('en-US', {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
  })
}

onMounted(() => {
  fetchStats()
})
</script>

