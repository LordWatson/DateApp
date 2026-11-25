<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

interface Props {
    title: string;
    description?: string;
    confirmLabel?: string;
}

const { title, description, confirmLabel = 'Delete' } = defineProps<Props>();

defineEmits<{ confirm: [] }>();

const open = defineModel<boolean>('open', { required: true });
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent>
            <DialogHeader class="space-y-3">
                <DialogTitle class="font-display text-xl leading-tight">{{
                    title
                }}</DialogTitle>
                <DialogDescription v-if="description">{{
                    description
                }}</DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <Button variant="outline" @click="open = false">Cancel</Button>
                <Button
                    variant="destructive"
                    @click="
                        open = false;
                        $emit('confirm');
                    "
                    >{{ confirmLabel }}</Button
                >
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
