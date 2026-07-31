<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

defineProps<{
  dau: Array<{ date: string; count: number }>
  mau: Array<{ month: string; count: number }>
  completions: Array<{ date: string; count: number }>
  dropoff: Array<{ display_order: number; title: string; count: number }>
  avg_compatibility: number
  love_note_activity: Array<{ date: string; count: number }>
  moment_activity: Array<{ date: string; count: number }>
}>()
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Analytics</h1>
      <p class="text-gray-500 text-sm mt-1">Application usage and engagement metrics.</p>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <p class="text-xs text-gray-500 mb-1">Avg Compatibility</p>
        <p class="text-3xl font-bold text-gray-600">{{ avg_compatibility }}%</p>
      </div>
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <p class="text-xs text-gray-500 mb-1">New Users (30d)</p>
        <p class="text-3xl font-bold text-blue-500">{{ dau.reduce((s, d) => s + d.count, 0) }}</p>
      </div>
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <p class="text-xs text-gray-500 mb-1">Completions (30d)</p>
        <p class="text-3xl font-bold text-green-500">{{ completions.reduce((s, d) => s + d.count, 0) }}</p>
      </div>
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <p class="text-xs text-gray-500 mb-1">Love Notes (30d)</p>
        <p class="text-3xl font-bold text-rose-500">{{ love_note_activity.reduce((s, d) => s + d.count, 0) }}</p>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Daily Users Chart (simple bar) -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h2 class="font-semibold text-gray-900 mb-4">Daily New Users (30 days)</h2>
        <div class="flex items-end gap-1 h-32">
          <div
            v-for="day in dau"
            :key="day.date"
            :style="{ height: dau.length ? `${(day.count / Math.max(...dau.map(d => d.count), 1)) * 100}%` : '0%' }"
            class="flex-1 bg-pink-400 rounded-t-sm min-h-[2px] transition-all"
            :title="`${day.date}: ${day.count}`"
          />
        </div>
        <p v-if="!dau.length" class="text-sm text-gray-400 text-center py-8">No data yet.</p>
      </div>

      <!-- Completions Chart -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h2 class="font-semibold text-gray-900 mb-4">Questionnaire Completions (30 days)</h2>
        <div class="flex items-end gap-1 h-32">
          <div
            v-for="day in completions"
            :key="day.date"
            :style="{ height: completions.length ? `${(day.count / Math.max(...completions.map(d => d.count), 1)) * 100}%` : '0%' }"
            class="flex-1 bg-purple-400 rounded-t-sm min-h-[2px] transition-all"
            :title="`${day.date}: ${day.count}`"
          />
        </div>
        <p v-if="!completions.length" class="text-sm text-gray-400 text-center py-8">No data yet.</p>
      </div>

      <!-- Drop-off by Question -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h2 class="font-semibold text-gray-900 mb-4">Answers by Question</h2>
        <div class="space-y-2">
          <div
            v-for="q in dropoff.slice(0, 10)"
            :key="q.display_order"
            class="flex items-center gap-3"
          >
            <span class="text-xs text-gray-400 w-6 flex-shrink-0">{{ q.display_order }}</span>
            <div class="flex-1 min-w-0">
              <p class="text-xs text-gray-700 truncate">{{ q.title }}</p>
              <div class="h-1.5 bg-gray-100 rounded-full mt-1">
                <div
                  :style="{ width: dropoff.length ? `${(q.count / Math.max(...dropoff.map(d => d.count), 1)) * 100}%` : '0%' }"
                  class="h-full bg-pink-400 rounded-full"
                />
              </div>
            </div>
            <span class="text-xs text-gray-500 flex-shrink-0">{{ q.count }}</span>
          </div>
          <p v-if="!dropoff.length" class="text-sm text-gray-400 text-center py-4">No data yet.</p>
        </div>
      </div>

      <!-- Monthly Active Users -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h2 class="font-semibold text-gray-900 mb-4">Monthly Registrations (12 months)</h2>
        <div class="flex items-end gap-1 h-32">
          <div
            v-for="month in mau"
            :key="month.month"
            :style="{ height: mau.length ? `${(month.count / Math.max(...mau.map(d => d.count), 1)) * 100}%` : '0%' }"
            class="flex-1 bg-indigo-400 rounded-t-sm min-h-[2px] transition-all"
            :title="`${month.month}: ${month.count}`"
          />
        </div>
        <p v-if="!mau.length" class="text-sm text-gray-400 text-center py-8">No data yet.</p>
      </div>
    </div>
  </div>
</template>
