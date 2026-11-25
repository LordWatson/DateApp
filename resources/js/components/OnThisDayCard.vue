<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import SectionHeader from '@/components/SectionHeader.vue';

export interface OnThisDayItem {
    type: 'moment' | 'plan';
    id: number;
    title: string;
    emoji: string;
    years_ago: number;
    url: string;
}

defineProps<{ items: OnThisDayItem[] }>();

function yearsAgo(years: number): string {
    return years === 1 ? '1 year ago today' : `${years} years ago today`;
}
</script>

<template>
    <section v-if="items.length > 0" class="space-y-3">
        <SectionHeader title="On this day" />

        <div class="card-premium p-2">
            <Link
                v-for="item in items"
                :key="`${item.type}-${item.id}`"
                :href="item.url"
                class="list-row"
            >
                <span class="icon-tile h-11 w-11 text-xl" aria-hidden="true">{{
                    item.emoji
                }}</span>
                <div class="min-w-0 flex-1">
                    <p class="truncate font-medium text-foreground">
                        {{ item.title }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ yearsAgo(item.years_ago) }} ·
                        {{ item.type === 'moment' ? 'Moment' : 'Date night' }}
                    </p>
                </div>
                <ChevronRight
                    class="size-4 shrink-0 text-muted-foreground"
                    aria-hidden="true"
                />
            </Link>
        </div>
    </section>
</template>
