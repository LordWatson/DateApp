<script setup lang="ts">
defineProps<{
    columns: Array<{ key: string; label: string; sortable?: boolean }>;
    empty?: string;
}>();
</script>

<template>
    <div class="overflow-hidden card-premium rounded-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-border bg-muted/50">
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            scope="col"
                            class="px-4 py-3 text-left eyebrow whitespace-nowrap"
                        >
                            {{ col.label }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <slot />
                    <tr
                        v-if="$slots.empty || empty"
                        class="hidden only:table-row"
                    >
                        <td
                            :colspan="columns.length"
                            class="px-4 py-12 text-center text-muted-foreground"
                        >
                            <slot name="empty">{{
                                empty ?? 'No records found.'
                            }}</slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
