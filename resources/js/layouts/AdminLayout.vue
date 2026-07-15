<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const page = usePage()
const user = computed(() => page.props.auth?.user)
const flash = computed(() => page.props.flash as { success?: string; error?: string } | undefined)

const sidebarOpen = ref(true)
const toggleSidebar = () => {
  sidebarOpen.value = !sidebarOpen.value
}

const navItems = [
  { label: 'Dashboard', href: '/admin', icon: '📊', exact: true },
  { label: 'Users', href: '/admin/users', icon: '👥' },
  { label: 'Couples', href: '/admin/couples', icon: '💑' },
  { label: 'Questionnaires', href: '/admin/questionnaires', icon: '📝' },
  { label: 'Challenges', href: '/admin/challenges', icon: '🎯' },
  { label: 'Achievements', href: '/admin/achievements', icon: '🏆' },
  { label: 'Feature Flags', href: '/admin/feature-flags', icon: '🚩' },
  { label: 'Settings', href: '/admin/settings', icon: '⚙️' },
  { label: 'Email Templates', href: '/admin/email-templates', icon: '📧' },
  { label: 'AI Prompts', href: '/admin/ai-prompts', icon: '🤖' },
  { label: 'Media Library', href: '/admin/media', icon: '🖼️' },
  { label: 'Analytics', href: '/admin/analytics', icon: '📈' },
  { label: 'Audit Logs', href: '/admin/audit-logs', icon: '🔍' },
]

const isActive = (href: string, exact = false) =>
  exact ? page.url === href || page.url.startsWith(`${href}?`) : page.url.startsWith(href)
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex">
    <!-- Sidebar -->
    <aside
      :class="[
        'bg-white border-r border-gray-200 flex flex-col transition-all duration-300 ease-in-out',
        sidebarOpen ? 'w-64' : 'w-16'
      ]"
    >
      <!-- Logo -->
      <div class="h-16 flex items-center px-4 border-b border-gray-200 gap-3">
        <span class="text-2xl">💕</span>
        <span v-if="sidebarOpen" class="font-bold text-gray-900 text-lg truncate">Date Night Admin</span>
      </div>

      <!-- Nav -->
      <nav class="flex-1 py-4 overflow-y-auto">
        <Link
          v-for="item in navItems"
          :key="item.href"
          :href="item.href"
          :class="[
            'flex items-center gap-3 px-4 py-2.5 text-sm font-medium transition-colors rounded-lg mx-2 mb-1',
            isActive(item.href, item.exact)
              ? 'bg-pink-50 text-pink-600'
              : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
          ]"
        >
          <span class="text-lg flex-shrink-0">{{ item.icon }}</span>
          <span v-if="sidebarOpen" class="truncate">{{ item.label }}</span>
        </Link>
      </nav>

      <!-- User -->
      <div class="border-t border-gray-200 p-4">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-full bg-gradient-to-br from-pink-400 to-purple-500 flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
            {{ user?.name?.charAt(0) }}
          </div>
          <div v-if="sidebarOpen" class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 truncate">{{ user?.name }}</p>
            <Link href="/dashboard" class="text-xs text-gray-500 hover:text-pink-500">← Back to app</Link>
          </div>
        </div>
      </div>
    </aside>

    <!-- Main -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Header -->
      <header class="h-16 bg-white border-b border-gray-200 flex items-center px-6 gap-4">
        <button
          @click="toggleSidebar"
          class="p-2 rounded-lg hover:bg-gray-100 text-gray-500 transition-colors"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>

        <div class="flex-1" />

        <span class="text-xs font-medium px-2 py-1 rounded-full bg-pink-100 text-pink-700">Admin Portal</span>
      </header>

      <!-- Flash messages -->
      <div v-if="flash?.success || flash?.error" class="px-6 pt-4">
        <div
          v-if="flash?.success"
          class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm font-medium"
        >
          ✅ {{ flash.success }}
        </div>
        <div
          v-if="flash?.error"
          class="bg-red-50 border border-red-200 text-red-800 rounded-xl px-4 py-3 text-sm font-medium"
        >
          ❌ {{ flash.error }}
        </div>
      </div>

      <!-- Page content -->
      <main class="flex-1 p-6 overflow-auto">
        <slot />
      </main>
    </div>
  </div>
</template>
