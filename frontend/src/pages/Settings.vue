<template>
  <div class="min-h-screen bg-gray-50">
    <header class="bg-white shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex justify-between items-center">
          <h1 class="text-2xl font-bold text-gray-900">CommitPulse</h1>
          <router-link to="/dashboard" class="text-gray-600 hover:text-gray-900">
            Back to Dashboard
          </router-link>
        </div>
      </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <h2 class="text-2xl font-bold mb-6">Settings</h2>
      
      <div class="bg-white rounded-lg shadow p-6 space-y-6">
        <div>
          <h3 class="text-lg font-semibold mb-4">Public Profile</h3>
          <p class="text-gray-600 mb-4">
            Share your weekly coding stats with others.
          </p>
          <div class="bg-gray-50 p-4 rounded-lg">
            <p class="text-sm text-gray-600 mb-2">Your public profile URL:</p>
            <code class="text-primary-600">http://localhost:3000/u/your-username</code>
          </div>
        </div>

        <div>
          <h3 class="text-lg font-semibold mb-4">Email Preferences</h3>
          <label class="flex items-center gap-3">
            <input type="checkbox" class="rounded" />
            <span>Receive weekly digest emails</span>
          </label>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
</script>

