<script setup lang="ts">
import { watch } from 'vue';

const isSolo = defineModel<boolean>('isSolo', { required: true });
const partnerOptional = defineModel<boolean>('partnerOptional', {
    required: true,
});
const isIntimacy = defineModel<boolean>('isIntimacy', { required: true });

watch(isSolo, (solo) => {
    if (!solo) {
        partnerOptional.value = false;
    }
});
</script>

<template>
    <fieldset class="space-y-3">
        <legend class="text-xs font-medium text-muted-foreground">Mode</legend>

        <div class="flex items-start gap-2">
            <input
                id="q-is-solo"
                v-model="isSolo"
                type="checkbox"
                class="mt-0.5 size-4 rounded accent-primary"
                aria-describedby="q-is-solo-help"
            />
            <div>
                <label for="q-is-solo" class="text-sm text-foreground"
                    >Solo questionnaire</label
                >
                <p id="q-is-solo-help" class="text-xs text-muted-foreground">
                    One person answers and gets a plan straight away, without
                    waiting for a partner.
                </p>
            </div>
        </div>

        <div class="flex items-start gap-2 pl-6">
            <input
                id="q-partner-optional"
                v-model="partnerOptional"
                type="checkbox"
                :disabled="!isSolo"
                class="mt-0.5 size-4 rounded accent-primary disabled:opacity-50"
                aria-describedby="q-partner-optional-help"
            />
            <div>
                <label
                    for="q-partner-optional"
                    :class="[
                        'text-sm',
                        isSolo ? 'text-foreground' : 'text-muted-foreground',
                    ]"
                    >Partner optional</label
                >
                <p
                    id="q-partner-optional-help"
                    class="text-xs text-muted-foreground"
                >
                    Lets the person choose to answer alone or with their
                    partner. Only applies to a solo questionnaire, so it is
                    switched off when "Solo questionnaire" is off.
                </p>
            </div>
        </div>

        <div class="flex items-start gap-2">
            <input
                id="q-is-intimacy"
                v-model="isIntimacy"
                type="checkbox"
                class="mt-0.5 size-4 rounded accent-primary"
                aria-describedby="q-is-intimacy-help"
            />
            <div>
                <label for="q-is-intimacy" class="text-sm text-foreground"
                    >Intimacy questionnaire</label
                >
                <p
                    id="q-is-intimacy-help"
                    class="text-xs text-muted-foreground"
                >
                    Shown in the intimacy area instead of the main questionnaire
                    list.
                </p>
            </div>
        </div>
    </fieldset>
</template>
