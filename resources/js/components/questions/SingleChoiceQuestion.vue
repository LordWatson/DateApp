<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import OptionCard from '@/components/OptionCard.vue';

interface Option {
    id: number;
    title: string;
    description?: string | null;
    emoji?: string | null;
    value: string;
}

interface Props {
    options: Option[];
    modelValue: string | null;
}

const CUSTOM_AMOUNT_PREFIX = 'custom_amount';

const props = defineProps<Props>();
const emit = defineEmits<{ 'update:modelValue': [value: string] }>();

const hasCustomAmountOption = computed(() =>
    props.options.some((option) => option.value === CUSTOM_AMOUNT_PREFIX),
);

function parseCustomAmount(value: string | null): string {
    if (!value || !value.startsWith(`${CUSTOM_AMOUNT_PREFIX}:`)) {
        return '';
    }

    return value.slice(CUSTOM_AMOUNT_PREFIX.length + 1);
}

function isCustomAmountSelected(value: string | null): boolean {
    return value === CUSTOM_AMOUNT_PREFIX
        || (value?.startsWith(`${CUSTOM_AMOUNT_PREFIX}:`) ?? false);
}

const customAmount = ref<string>(parseCustomAmount(props.modelValue));

watch(
    () => props.modelValue,
    (newVal) => {
        customAmount.value = parseCustomAmount(newVal);
    },
);

function isSelected(option: Option): boolean {
    if (option.value === CUSTOM_AMOUNT_PREFIX) {
        return isCustomAmountSelected(props.modelValue);
    }

    return props.modelValue === option.value;
}

function select(value: string | number): void {
    const stringValue = String(value);

    if (stringValue === CUSTOM_AMOUNT_PREFIX) {
        const trimmed = customAmount.value.trim();
        emit('update:modelValue', trimmed === '' ? CUSTOM_AMOUNT_PREFIX : `${CUSTOM_AMOUNT_PREFIX}:${trimmed}`);

        return;
    }

    emit('update:modelValue', stringValue);
}

function onCustomAmountInput(event: Event): void {
    const raw = (event.target as HTMLInputElement).value;
    const digits = raw.replace(/[^0-9]/g, '');

    customAmount.value = digits;

    emit(
        'update:modelValue',
        digits === '' ? CUSTOM_AMOUNT_PREFIX : `${CUSTOM_AMOUNT_PREFIX}:${digits}`,
    );
}
</script>

<template>
    <div class="space-y-3" role="radiogroup">
        <template v-for="option in options" :key="option.id">
            <OptionCard
                :label="option.title"
                :description="option.description ?? undefined"
                :emoji="option.emoji ?? undefined"
                :value="option.value"
                :selected="isSelected(option)"
                type="radio"
                @select="select"
            />

            <div
                v-if="hasCustomAmountOption && option.value === 'custom_amount' && isSelected(option)"
                class="rounded-2xl border-2 border-primary/40 bg-primary/5 p-4"
            >
                <label for="custom-budget-amount" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-primary">
                    Your budget
                </label>
                <div class="flex items-center gap-2">
                    <span class="text-lg font-semibold text-foreground" aria-hidden="true">£</span>
                    <input
                        id="custom-budget-amount"
                        :value="customAmount"
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        placeholder="e.g. 80"
                        class="flex-1 rounded-2xl border-2 border-border bg-background px-4 py-3 text-base font-semibold text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none"
                        @input="onCustomAmountInput"
                    />
                </div>
                <p class="mt-2 text-xs text-muted-foreground">
                    We'll tailor suggestions to roughly this amount for the whole date.
                </p>
            </div>
        </template>
    </div>
</template>
