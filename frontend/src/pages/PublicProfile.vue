<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div v-if="loading" class="text-center">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>

      <div v-else-if="user && stats" class="bg-white rounded-2xl shadow-xl p-8">
        <div class="text-center mb-8">
          <img
            v-if="user.avatar_url"
            :src="user.avatar_url"
            :alt="user.name"
            class="w-24 h-24 rounded-full mx-auto mb-4"
          />
          <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ user.name }}'s Week in Code</h1>
          <p class="text-gray-600">
            Week of {{ formatDate(stats.week_start) }} - {{ formatDate(stats.week_end) }}
          </p>
        </div>

        <div class="grid grid-cols-3 gap-6 mb-8">
          <div class="text-center">
            <p class="text-4xl font-bold text-primary-600">{{ stats.commits_count }}</p>
            <p class="text-sm text-gray-600 mt-1">Commits</p>
          </div>
          <div class="text-center">
            <p class="text-4xl font-bold text-green-600">{{ stats.total_additions.toLocaleString() }}</p>
            <p class="text-sm text-gray-600 mt-1">Lines Added</p>
          </div>
          <div class="text-center">
            <p class="text-4xl font-bold text-red-600">{{ stats.total_deletions.toLocaleString() }}</p>
            <p class="text-sm text-gray-600 mt-1">Lines Removed</p>
          </div>
        </div>

        <div class="border-t pt-6">
          <div class="grid grid-cols-2 gap-6">
            <div>
              <p class="text-sm text-gray-600 mb-1">Top Repository</p>
              <p class="text-lg font-semibold">{{ stats.top_repo || 'N/A' }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-600 mb-1">Top Language</p>
              <p class="text-lg font-semibold">{{ stats.top_language || 'N/A' }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'

const route = useRoute()
const loading = ref(true)
const user = ref(null)
const stats = ref(null)

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
  })
}

onMounted(async () => {
  try {
    const username = route.params.username
    // Use relative URL to go through Vite proxy
    const response = await axios.get(`/api/public/${username}`)
    user.value = response.data.user
    stats.value = response.data.stats
  } catch (err) {
    console.error('Failed to load profile:', err)
  } finally {
    loading.value = false
  }
})
</script>

