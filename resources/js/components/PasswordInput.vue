<script setup lang="ts">
import { Eye, EyeOff } from '@lucide/vue';
import { ref, useTemplateRef } from 'vue';
import type { HTMLAttributes } from 'vue';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();

const showPassword = ref(false);
const inputRef = useTemplateRef('inputRef');

defineExpose({
    $el: inputRef,
    focus: () => inputRef.value?.$el?.focus(),
});
</script>

<template>
    <div class="relative">
        <Input
            ref="inputRef"
            :type="showPassword ? 'text' : 'password'"
            :class="cn('pr-11', props.class)"
            v-bind="$attrs"
        />
        <button
            type="button"
            @click="showPassword = !showPassword"
            :class="
                cn(
                    'absolute inset-y-0 right-0 flex items-center rounded-r-xl px-3.5 text-muted-foreground transition-colors hover:text-foreground focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none',
                )
            "
            :aria-label="showPassword ? 'Hide password' : 'Show password'"
            :tabindex="-1"
        >
            <EyeOff v-if="showPassword" class="size-4" aria-hidden="true" />
            <Eye v-else class="size-4" aria-hidden="true" />
        </button>
    </div>
</template>
