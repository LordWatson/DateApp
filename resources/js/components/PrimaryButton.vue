<script setup lang="ts">
interface Props {
    type?: 'button' | 'submit' | 'reset';
    disabled?: boolean;
    loading?: boolean;
    size?: 'sm' | 'md' | 'lg';
    fullWidth?: boolean;
}

const {
    type = 'button',
    disabled = false,
    loading = false,
    size = 'md',
    fullWidth = false,
} = defineProps<Props>();
</script>

<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        :class="[
            'inline-flex items-center justify-center gap-2 rounded-full font-semibold tracking-tight transition-all duration-200',
            'text-white shadow-glow gradient-primary',
            'hover:-translate-y-px hover:brightness-105',
            'active:translate-y-0 active:scale-[0.97]',
            'disabled:transform-none disabled:cursor-not-allowed disabled:opacity-50',
            'focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none',
            size === 'sm' && 'min-h-10 px-5 text-sm',
            size === 'md' && 'min-h-12 px-7 text-[15px]',
            size === 'lg' && 'min-h-14 px-9 text-base',
            fullWidth && 'w-full',
        ]"
    >
        <svg
            v-if="loading"
            class="size-4 animate-spin"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
        >
            <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
            />
            <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
            />
        </svg>
        <slot />
    </button>
</template>
