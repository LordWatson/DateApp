<script setup lang="ts">
import { Check, Copy, Share2 } from '@lucide/vue';
import { ref } from 'vue';
import SecondaryButton from '@/components/SecondaryButton.vue';

const props = defineProps<{ url: string }>();

const copied = ref(false);
// Not every browser can open the system share sheet.
const canShare = typeof navigator !== 'undefined' && 'share' in navigator;

async function copy(): Promise<void> {
    try {
        await navigator.clipboard.writeText(props.url);
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    } catch {
        // Clipboard blocked: the link is in the field to copy by hand.
    }
}

async function share(): Promise<void> {
    try {
        await navigator.share({
            title: 'Join me on Date Night',
            text: 'Connect with me on Date Night:',
            url: props.url,
        });
    } catch {
        // Closing the share sheet is not an error.
    }
}
</script>

<template>
    <div class="space-y-3">
        <div>
            <label
                for="invite-link"
                class="mb-1.5 block text-[13px] font-medium text-foreground/80"
                >Your invitation link</label
            >
            <input
                id="invite-link"
                :value="url"
                type="text"
                readonly
                class="input-premium text-sm"
                @focus="($event.target as HTMLInputElement).select()"
            />
        </div>
        <div class="flex gap-2">
            <SecondaryButton variant="outline" full-width @click="copy">
                <component
                    :is="copied ? Check : Copy"
                    class="size-4"
                    aria-hidden="true"
                />
                <span role="status">{{ copied ? 'Copied' : 'Copy link' }}</span>
            </SecondaryButton>
            <SecondaryButton
                v-if="canShare"
                variant="outline"
                full-width
                @click="share"
            >
                <Share2 class="size-4" aria-hidden="true" />
                Share
            </SecondaryButton>
        </div>
        <p class="text-xs leading-relaxed text-muted-foreground">
            Anyone with this link can ask to connect with you, so send it only
            to your partner. Nothing is connected until they press Connect.
        </p>
    </div>
</template>
