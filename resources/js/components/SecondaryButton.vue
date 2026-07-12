<script setup lang="ts">
interface Props {
    type?: 'button' | 'submit' | 'reset';
    disabled?: boolean;
    loading?: boolean;
    size?: 'sm' | 'md' | 'lg';
    fullWidth?: boolean;
    variant?: 'filled' | 'outline' | 'ghost';
}

const {
    type = 'button',
    disabled = false,
    loading = false,
    size = 'md',
    fullWidth = false,
    variant = 'filled',
} = defineProps<Props>();
</script>

<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        :class="[
            'inline-flex items-center justify-center gap-2 rounded-3xl font-semibold transition-all duration-200',
            'hover:-translate-y-0.5 active:scale-95',
            'disabled:transform-none disabled:cursor-not-allowed disabled:opacity-50',
            'focus:ring-2 focus:ring-secondary focus:ring-offset-2 focus:outline-none',
            variant === 'filled' &&
                'bg-secondary text-white shadow-md hover:opacity-90 hover:shadow-lg',
            variant === 'outline' &&
                'border-2 border-primary text-primary hover:bg-primary hover:text-white',
            variant === 'ghost' && 'text-primary hover:bg-accent',
            size === 'sm' && 'min-h-10 px-5 text-sm',
            size === 'md' && 'min-h-14 px-8 text-base',
            size === 'lg' && 'min-h-16 px-10 text-lg',
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
