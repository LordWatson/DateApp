<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { RefreshCw, Sparkles } from '@lucide/vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import PageHeader from '@/components/PageHeader.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import SectionHeader from '@/components/SectionHeader.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';
import { index } from '@/routes/insights';
import { edit as editProfile } from '@/routes/profile';
import { regenerate as regenerateRoute } from '@/routes/weekly-reflection';

defineOptions({ layout: MobileLayout });

const regenerating = ref(false);

// Someone without a partner is not looking back at a week "together".
const title = usePage().props.auth.user.partner_id
    ? 'This week together'
    : 'Your week';

interface Reflection {
    id: number;
    week_start: string | null;
    week_end: string | null;
    headline: string;
    summary: string;
    highlights: string[];
    gentle_suggestion: string | null;
    encouragement: string | null;
    metrics: Record<string, unknown>;
    fallback_used: boolean;
    generated_at: string | null;
}

defineProps<{
    reflection: Reflection;
    history: Reflection[];
}>();

const formatRange = (start: string | null, end: string | null): string => {
    if (!start || !end) {
        return '';
    }

    const s = new Date(start);
    const e = new Date(end);
    const fmt: Intl.DateTimeFormatOptions = { month: 'short', day: 'numeric' };

    return `${s.toLocaleDateString(undefined, fmt)} – ${e.toLocaleDateString(undefined, fmt)}`;
};

const regenerate = (): void => {
    if (regenerating.value) {
        return;
    }

    router.post(
        regenerateRoute().url,
        {},
        {
            preserveScroll: true,
            // Regenerating costs an AI call, so the server limits it. Say so instead of showing an error page.
            onHttpException: (response) => {
                if (response.status !== 429) {
                    return;
                }

                toast.error(
                    'You have refreshed this a lot. Please try again in a little while.',
                );

                return false;
            },
            onStart: () => {
                regenerating.value = true;
            },
            onFinish: () => {
                regenerating.value = false;
            },
        },
    );
};
</script>

<template>
    <Head :title="title" />

    <div class="space-y-8 px-4 pt-6 pb-28">
        <PageHeader
            :back-href="index().url"
            back-label="Insights"
            :title="title"
            description="A gentle look back at your week"
        />

        <!-- Main reflection card -->
        <article
            class="relative overflow-hidden card-premium p-6"
            :aria-busy="regenerating"
        >
            <!-- Loading overlay while AI generates a new reflection -->
            <transition
                enter-active-class="transition-opacity duration-300"
                leave-active-class="transition-opacity duration-300"
                enter-from-class="opacity-0"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="regenerating"
                    class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-4 rounded-3xl bg-card/90 backdrop-blur-sm"
                    role="status"
                    aria-live="polite"
                >
                    <div
                        class="icon-tile h-14 w-14 animate-pulse text-3xl"
                        aria-hidden="true"
                    >
                        💞
                    </div>
                    <div class="space-y-1 px-6 text-center">
                        <p
                            class="font-display text-lg font-semibold text-foreground"
                        >
                            Crafting your reflection…
                        </p>
                        <p class="text-sm text-muted-foreground">
                            This can take a few moments while we look back at
                            your week.
                        </p>
                    </div>
                    <div class="flex gap-1" aria-hidden="true">
                        <span
                            class="size-1.5 animate-bounce rounded-full bg-primary [animation-delay:-0.3s]"
                        />
                        <span
                            class="size-1.5 animate-bounce rounded-full bg-primary [animation-delay:-0.15s]"
                        />
                        <span
                            class="size-1.5 animate-bounce rounded-full bg-primary"
                        />
                    </div>
                    <span class="sr-only"
                        >Generating a new weekly reflection, please wait.</span
                    >
                </div>
            </transition>

            <div class="space-y-5">
                <div class="space-y-1.5">
                    <p class="eyebrow">
                        {{
                            formatRange(
                                reflection.week_start,
                                reflection.week_end,
                            )
                        }}
                    </p>
                    <h2
                        class="text-2xl leading-tight font-semibold text-foreground"
                    >
                        {{ reflection.headline }}
                    </h2>
                </div>

                <p class="text-[15px] leading-relaxed text-foreground">
                    {{ reflection.summary }}
                </p>

                <div v-if="reflection.highlights.length > 0" class="space-y-3">
                    <p class="eyebrow">Little wins</p>
                    <ul class="space-y-2.5">
                        <li
                            v-for="(item, idx) in reflection.highlights"
                            :key="idx"
                            class="flex items-start gap-3"
                        >
                            <Sparkles
                                class="mt-0.5 size-4 shrink-0 text-gold"
                                aria-hidden="true"
                            />
                            <span class="text-sm text-foreground">{{
                                item
                            }}</span>
                        </li>
                    </ul>
                </div>

                <div
                    v-if="reflection.gentle_suggestion"
                    class="rounded-2xl bg-muted p-4"
                >
                    <p class="eyebrow">A gentle idea</p>
                    <p class="mt-1.5 text-sm text-foreground">
                        {{ reflection.gentle_suggestion }}
                    </p>
                </div>

                <template v-if="reflection.encouragement">
                    <div class="hairline-gold" />
                    <p
                        class="text-center font-display text-lg leading-snug text-foreground italic"
                    >
                        “{{ reflection.encouragement }}”
                    </p>
                </template>

                <p
                    v-if="reflection.fallback_used"
                    class="text-center text-xs text-muted-foreground"
                >
                    Written without AI. AI reflections need AI turned on in
                    <Link
                        :href="editProfile()"
                        class="font-medium text-primary underline underline-offset-4"
                        >Settings</Link
                    >{{
                        $page.props.auth.user.partner_id
                            ? ' by both of you'
                            : ''
                    }}.
                </p>
            </div>
        </article>

        <!-- Actions -->
        <SecondaryButton
            variant="outline"
            full-width
            :loading="regenerating"
            :aria-busy="regenerating"
            @click="regenerate"
        >
            <RefreshCw v-if="!regenerating" class="size-4" aria-hidden="true" />
            {{ regenerating ? 'Refreshing…' : 'Refresh reflection' }}
        </SecondaryButton>

        <!-- History -->
        <section v-if="history.length > 0" class="space-y-3">
            <SectionHeader title="Previous weeks" />

            <article
                v-for="entry in history"
                :key="entry.id"
                class="space-y-1.5 card-premium p-5"
            >
                <p class="eyebrow">
                    {{ formatRange(entry.week_start, entry.week_end) }}
                </p>
                <h3 class="text-lg leading-tight font-semibold text-foreground">
                    {{ entry.headline }}
                </h3>
                <p class="text-sm leading-relaxed text-muted-foreground">
                    {{ entry.summary }}
                </p>
            </article>
        </section>
    </div>
</template>
