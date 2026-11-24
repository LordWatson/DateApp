<script setup lang="ts">
import { KeyRound, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import type { Passkey } from '@/types/auth';

const props = defineProps<{
    passkey: Passkey;
}>();

const emit = defineEmits<{
    remove: [id: number, onError: () => void];
}>();

const isDeleting = ref(false);

const handleDelete = () => {
    isDeleting.value = true;
    emit('remove', props.passkey.id, () => {
        isDeleting.value = false;
    });
};
</script>

<template>
    <div class="flex items-center justify-between gap-3 p-4">
        <div class="flex min-w-0 items-center gap-3.5">
            <span class="icon-tile h-11 w-11 text-primary" aria-hidden="true">
                <KeyRound class="size-5" />
            </span>
            <div class="min-w-0 space-y-1">
                <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1">
                    <p class="truncate font-medium text-foreground">
                        {{ passkey.name }}
                    </p>
                    <span v-if="passkey.authenticator" class="badge-soft">
                        {{ passkey.authenticator }}
                    </span>
                </div>
                <p class="text-xs text-muted-foreground">
                    Added {{ passkey.created_at_diff }}
                    <template v-if="passkey.last_used_at_diff">
                        <span class="mx-1 text-muted-foreground/50">/</span>
                        Last used {{ passkey.last_used_at_diff }}
                    </template>
                </p>
            </div>
        </div>

        <Dialog>
            <DialogTrigger as-child>
                <Button
                    variant="ghost"
                    size="icon-lg"
                    class="shrink-0 text-destructive hover:bg-destructive/10 hover:text-destructive"
                >
                    <Trash2 class="size-4" aria-hidden="true" />
                    <span class="sr-only">Remove</span>
                </Button>
            </DialogTrigger>

            <DialogContent>
                <DialogTitle class="font-display text-xl leading-tight"
                    >Remove passkey</DialogTitle
                >
                <DialogDescription>
                    Are you sure you want to remove the "{{ passkey.name }}"
                    passkey? You will no longer be able to use it to sign in.
                </DialogDescription>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="outline">Cancel</Button>
                    </DialogClose>
                    <Button
                        variant="destructive"
                        :disabled="isDeleting"
                        @click="handleDelete"
                    >
                        {{ isDeleting ? 'Removing...' : 'Remove passkey' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
