<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import ConnectionChoices from '@/components/ConnectionChoices.vue';
import type { SharedHistory } from '@/components/ConnectionChoices.vue';
import PageHeader from '@/components/PageHeader.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';
import { applyChoices, index } from '@/routes/partner';

defineOptions({ layout: MobileLayout });

const props = defineProps<{
    formerPartner: { name: string };
    shared: SharedHistory;
}>();

const form = useForm({
    keep_plans: props.shared.keeping_plans,
    keep_notes: props.shared.keeping_notes,
});
</script>

<template>
    <Head title="What you keep" />

    <div class="space-y-8 px-4 pt-6 pb-28">
        <PageHeader
            eyebrow="Your connection"
            title="What you keep"
            :description="`You and ${formerPartner.name} are no longer connected.`"
            :back-href="index().url"
            back-label="Partner"
        />

        <form
            class="space-y-5 card-premium p-6"
            @submit.prevent="form.post(applyChoices().url)"
        >
            <ConnectionChoices
                v-model:keep-plans="form.keep_plans"
                v-model:keep-notes="form.keep_notes"
                :shared="shared"
                :name="formerPartner.name"
            />

            <p class="text-sm leading-relaxed text-muted-foreground">
                Your own moments and calendar events stay with you either way.
            </p>

            <PrimaryButton type="submit" full-width :loading="form.processing">
                Save my choices
            </PrimaryButton>
        </form>
    </div>
</template>
