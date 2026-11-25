<script setup lang="ts">
import { ChevronLeft, ChevronRight, X } from '@lucide/vue';
import { nextTick, onMounted, ref, watch } from 'vue';

/**
 * Photos shown whole (never cropped), one at a time; swipe, arrows or dots move between them.
 * With `zoomable`, a tap opens the photo full screen.
 */
const {
    photos,
    alt,
    maxHeightClass = 'max-h-[28rem]',
    zoomable = false,
    start = 0,
} = defineProps<{
    photos: string[];
    alt: string;
    /** Tall photos are scaled down to this, keeping their shape. */
    maxHeightClass?: string;
    zoomable?: boolean;
    start?: number;
}>();

const track = ref<HTMLElement | null>(null);
const current = ref(0);
const zoomed = ref(false);

function onScroll(): void {
    if (track.value) {
        current.value = Math.round(
            track.value.scrollLeft / track.value.clientWidth,
        );
    }
}

function goTo(index: number, behavior: ScrollBehavior = 'smooth'): void {
    track.value?.scrollTo({
        left: index * track.value.clientWidth,
        behavior,
    });
}

// The full-screen copy opens on the photo that was tapped.
onMounted(() => {
    if (start > 0) {
        goTo(start, 'instant');
    }
});

// Focus moves into the full-screen view so Escape closes it.
const closeButton = ref<HTMLElement | null>(null);

watch(zoomed, async (open) => {
    if (open) {
        await nextTick();
        closeButton.value?.focus();
    }
});

const arrowClass =
    'absolute top-1/2 hidden size-9 -translate-y-1/2 items-center justify-center rounded-full bg-black/45 text-white backdrop-blur-sm transition-colors hover:bg-black/65 focus-visible:ring-2 focus-visible:ring-white focus-visible:outline-none sm:flex';
</script>

<template>
    <div class="relative bg-muted">
        <div
            ref="track"
            class="flex snap-x snap-mandatory [scrollbar-width:none] items-center overflow-x-auto overscroll-x-contain [&::-webkit-scrollbar]:hidden"
            @scroll.passive="onScroll"
        >
            <div
                v-for="(photo, i) in photos"
                :key="photo"
                class="flex w-full shrink-0 snap-center justify-center"
            >
                <img
                    :src="photo"
                    :alt="
                        photos.length > 1
                            ? `${alt} (photo ${i + 1} of ${photos.length})`
                            : alt
                    "
                    :loading="i === 0 ? 'eager' : 'lazy'"
                    :class="[
                        'h-auto w-auto max-w-full object-contain',
                        maxHeightClass,
                        zoomable && 'cursor-zoom-in',
                    ]"
                    @click="zoomable && (zoomed = true)"
                />
            </div>
        </div>

        <template v-if="photos.length > 1">
            <span
                class="absolute top-3 right-3 rounded-full bg-black/45 px-2 py-0.5 text-[11px] font-semibold text-white tabular-nums backdrop-blur-sm"
                aria-hidden="true"
                >{{ current + 1 }} / {{ photos.length }}</span
            >
            <button
                v-if="current > 0"
                type="button"
                :class="[arrowClass, 'left-3']"
                aria-label="Previous photo"
                @click.stop="goTo(current - 1)"
            >
                <ChevronLeft class="size-5" aria-hidden="true" />
            </button>
            <button
                v-if="current < photos.length - 1"
                type="button"
                :class="[arrowClass, 'right-3']"
                aria-label="Next photo"
                @click.stop="goTo(current + 1)"
            >
                <ChevronRight class="size-5" aria-hidden="true" />
            </button>
            <div
                class="absolute inset-x-0 bottom-2 flex justify-center gap-1"
                @click.stop
            >
                <button
                    v-for="(photo, i) in photos"
                    :key="photo"
                    type="button"
                    class="flex size-6 items-center justify-center focus-visible:outline-none"
                    :aria-label="`Photo ${i + 1}`"
                    :aria-current="i === current"
                    @click="goTo(i)"
                >
                    <span
                        :class="[
                            'h-1.5 rounded-full shadow-sm transition-all duration-300',
                            i === current
                                ? 'w-4 bg-white'
                                : 'w-1.5 bg-white/60',
                        ]"
                    />
                </button>
            </div>
        </template>

        <Teleport v-if="zoomable" to="body">
            <div
                v-if="zoomed"
                class="fixed inset-0 z-50 flex items-center bg-black"
                role="dialog"
                aria-modal="true"
                :aria-label="alt"
                @keydown.esc="zoomed = false"
            >
                <PhotoCarousel
                    class="w-full bg-transparent!"
                    :photos="photos"
                    :alt="alt"
                    :start="current"
                    max-height-class="max-h-dvh"
                />
                <button
                    ref="closeButton"
                    type="button"
                    class="absolute top-4 left-4 flex size-10 items-center justify-center rounded-full bg-white/15 text-white backdrop-blur-sm transition-colors hover:bg-white/25 focus-visible:ring-2 focus-visible:ring-white focus-visible:outline-none"
                    aria-label="Close photo"
                    @click="zoomed = false"
                >
                    <X class="size-5" aria-hidden="true" />
                </button>
            </div>
        </Teleport>
    </div>
</template>
