<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import { privacy } from '@/routes';
import { update } from '@/routes/ai-consent';
import type { Auth } from '@/types';

const page = usePage<{ auth: Auth }>();

/** true = agreed, false = declined, null = not asked yet. */
const consent = computed(() => page.props.auth.user.ai_consent ?? null);
const saving = ref(false);

function answer(agreed: boolean): void {
    router.put(
        update().url,
        { agreed },
        {
            preserveScroll: true,
            onStart: () => (saving.value = true),
            onFinish: () => (saving.value = false),
        },
    );
}
</script>

<template>
    <section class="space-y-4 card-premium p-5" aria-labelledby="ai-consent">
        <div class="space-y-1.5">
            <h2
                id="ai-consent"
                class="text-lg leading-tight font-semibold text-foreground"
            >
                Personalise with AI
            </h2>
            <p class="text-sm leading-relaxed text-muted-foreground">
                To write your date night plans and weekly reflections, Date
                Night can send your questionnaire answers, love notes and moment
                descriptions to an outside AI provider (Anthropic, or DeepSeek
                if the site is set up to use it). Nothing is sent unless you
                agree, and shared plans and reflections need both of you to
                agree. Without AI you get our standard suggestions. You can
                change this at any time in Settings.
                <Link
                    :href="privacy()"
                    class="font-medium text-primary underline underline-offset-4"
                    >Privacy Policy</Link
                >
            </p>
        </div>

        <p
            v-if="consent !== null"
            class="text-sm font-medium text-foreground"
            role="status"
        >
            {{ consent ? 'AI suggestions are on.' : 'AI suggestions are off.' }}
        </p>

        <div class="flex flex-wrap gap-3">
            <PrimaryButton
                v-if="consent !== true"
                size="sm"
                :loading="saving"
                @click="answer(true)"
            >
                Allow AI
            </PrimaryButton>
            <SecondaryButton
                v-if="consent !== false"
                size="sm"
                variant="outline"
                :loading="saving"
                @click="answer(false)"
            >
                {{ consent === true ? 'Turn off' : 'No thanks' }}
            </SecondaryButton>
        </div>
    </section>
</template>
