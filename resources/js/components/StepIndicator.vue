<script setup lang="ts">
interface Props {
    current: number;
    total: number;
}

defineProps<Props>();
</script>

<template>
    <div
        class="flex items-center justify-center gap-2"
        role="navigation"
        :aria-label="`Step ${current} of ${total}`"
    >
        <div
            v-for="step in total"
            :key="step"
            :class="[
                'rounded-full transition-all duration-300',
                step === current
                    ? 'h-2.5 w-6 gradient-primary'
                    : step < current
                      ? 'h-2.5 w-2.5 bg-primary/60'
                      : 'h-2.5 w-2.5 bg-muted',
            ]"
            :aria-current="step === current ? 'step' : undefined"
        />
    </div>
</template>
