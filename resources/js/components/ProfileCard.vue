<script setup lang="ts">
interface Props {
    name: string;
    description?: string;
    emoji?: string;
    lastUsed?: string;
    isFavourite?: boolean;
}

defineProps<Props>();

defineEmits<{ select: []; favourite: []; delete: [] }>();
</script>

<template>
    <div
        class="flex card-hover cursor-pointer items-center gap-4 card-premium p-5"
        @click="$emit('select')"
    >
        <div
            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-2xl gradient-primary-soft"
        >
            {{ emoji ?? '💝' }}
        </div>

        <div class="min-w-0 flex-1">
            <p class="truncate font-semibold text-foreground">{{ name }}</p>
            <p
                v-if="description"
                class="truncate text-sm text-muted-foreground"
            >
                {{ description }}
            </p>
            <p v-if="lastUsed" class="mt-0.5 text-xs text-muted-foreground">
                Last used {{ lastUsed }}
            </p>
        </div>

        <div class="flex shrink-0 items-center gap-1">
            <button
                class="rounded-full p-2 transition-colors hover:bg-accent focus:ring-2 focus:ring-primary focus:outline-none"
                :aria-label="
                    isFavourite ? 'Remove from favourites' : 'Add to favourites'
                "
                @click.stop="$emit('favourite')"
            >
                <span
                    :class="
                        isFavourite ? 'text-primary' : 'text-muted-foreground'
                    "
                >
                    {{ isFavourite ? '♥' : '♡' }}
                </span>
            </button>
            <button
                class="rounded-full p-2 transition-colors hover:bg-destructive/10 hover:text-destructive focus:ring-2 focus:ring-destructive focus:outline-none"
                aria-label="Delete profile"
                @click.stop="$emit('delete')"
            >
                <svg
                    class="size-4 text-muted-foreground"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                    />
                </svg>
            </button>
        </div>
    </div>
</template>
