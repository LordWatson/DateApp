<script setup lang="ts">
export interface SharedHistory {
    plans: number;
    notes: number;
    keeping_plans: boolean;
    keeping_notes: boolean;
}

defineProps<{ shared: SharedHistory; name: string }>();

const keepPlans = defineModel<boolean>('keepPlans', { required: true });
const keepNotes = defineModel<boolean>('keepNotes', { required: true });
</script>

<template>
    <fieldset class="space-y-3">
        <legend class="text-sm font-medium text-foreground">
            What stays in your account
        </legend>

        <label class="flex items-start gap-3" for="keep-plans">
            <input
                id="keep-plans"
                v-model="keepPlans"
                type="checkbox"
                class="mt-1 size-4 shrink-0 accent-primary"
            />
            <span class="space-y-0.5">
                <span class="block text-sm font-medium text-foreground"
                    >Keep the date nights planned with {{ name }}</span
                >
                <span class="block text-sm text-muted-foreground tabular-nums">
                    {{ shared.plans }} date night{{
                        shared.plans === 1 ? '' : 's'
                    }}. Untick to take them out of your history.
                </span>
            </span>
        </label>

        <label class="flex items-start gap-3" for="keep-notes">
            <input
                id="keep-notes"
                v-model="keepNotes"
                type="checkbox"
                class="mt-1 size-4 shrink-0 accent-primary"
            />
            <span class="space-y-0.5">
                <span class="block text-sm font-medium text-foreground"
                    >Keep the love notes between you</span
                >
                <span class="block text-sm text-muted-foreground tabular-nums">
                    {{ shared.notes }} love note{{
                        shared.notes === 1 ? '' : 's'
                    }}. Untick to take them out of your account.
                </span>
            </span>
        </label>

        <p class="text-xs leading-relaxed text-muted-foreground">
            This only changes what you see. {{ name }} decides separately what
            stays in their account, and you can change your mind later.
        </p>
    </fieldset>
</template>
