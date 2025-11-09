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

      <div v-else>
        <!-- Hero Section -->
        <div class="mb-8">
          <h2 class="text-3xl font-bold text-gray-900 mb-2">Your Weekly Pulse</h2>
          <p class="text-gray-600">
            Week of {{ formatDate(stats.week_start) }} - {{ formatDate(stats.week_end) }}
          </p>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <StatCard
            title="Total Commits"
            :value="stats.total_commits"
            icon="📊"
            color="bg-blue-500"
          />
          <StatCard
            title="Lines Added"
            :value="stats.total_additions.toLocaleString()"
            icon="➕"
            color="bg-green-500"
          />
          <StatCard
            title="Lines Removed"
            :value="stats.total_deletions.toLocaleString()"
            icon="➖"
            color="bg-red-500"
          />
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Commits by Day</h3>
            <WeeklyChart :data="commitsByDayData" />
          </div>

          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Top Languages</h3>
            <LanguagesChart :data="languagesData" />
          </div>
        </div>

        <!-- Top Repos -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
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
const stats = ref({})
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
    
    const token = localStorage.getItem('auth_token')
    const response = await axios.get('http://localhost:8000/api/stats/current-week', {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
    
    stats.value = response.data
  } catch (err) {
    error.value = 'Failed to load stats. Please try again.'
    console.error(err)
  } finally {
    loading.value = false
  }
}

const syncData = async () => {
  try {
    syncing.value = true
    const token = localStorage.getItem('auth_token')
    await axios.post('http://localhost:8000/api/sync', {}, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
    
    // Refresh stats after sync
    setTimeout(() => {
      fetchStats()
    }, 2000)
  } catch (err) {
    error.value = 'Sync failed. Please try again.'
    console.error(err)
  } finally {
    syncing.value = false
  }
}

const logout = () => {
  localStorage.removeItem('auth_token')
  router.push('/')
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
  })
}

onMounted(() => {
  fetchStats()
})
</script>

