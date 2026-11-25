<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { TriangleAlert } from '@lucide/vue';
import { useTemplateRef } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';

const passwordInput = useTemplateRef('passwordInput');
</script>

<template>
    <div class="space-y-5 card-premium border-destructive/30! p-5">
        <div class="flex items-start gap-4">
            <span
                class="icon-tile h-11 w-11 text-destructive"
                aria-hidden="true"
            >
                <TriangleAlert class="size-5" />
            </span>
            <Heading
                variant="small"
                title="Delete account"
                description="Permanently delete your account and everything in it"
            />
        </div>

        <p
            class="rounded-2xl bg-destructive/10 px-4 py-3 text-sm leading-relaxed text-destructive"
        >
            <span class="font-medium">This cannot be undone.</span>
            Your answers, moments, calendar events and love notes are deleted.
            If you only want to stop sharing, you can pause or end your
            connection on the Partner page and keep your account. If you have a
            partner, they are disconnected and told, they keep the date nights
            you planned together, and the love notes between you are removed for
            both of you.
        </p>

        <Dialog>
            <DialogTrigger as-child>
                <Button variant="destructive" data-test="delete-user-button"
                    >Delete account</Button
                >
            </DialogTrigger>
            <DialogContent>
                <Form
                    v-bind="ProfileController.destroy.form()"
                    reset-on-success
                    @error="() => passwordInput?.focus()"
                    :options="{
                        preserveScroll: true,
                    }"
                    class="space-y-6"
                    v-slot="{ errors, processing, reset, clearErrors }"
                >
                    <DialogHeader class="space-y-3">
                        <DialogTitle class="font-display text-xl leading-tight"
                            >Are you sure you want to delete your
                            account?</DialogTitle
                        >
                        <DialogDescription>
                            Everything in your account is permanently deleted,
                            including the love notes between you and your
                            partner. Your partner keeps the date nights you
                            planned together. Enter your password to confirm.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-2">
                        <Label for="password" class="sr-only">Password</Label>
                        <PasswordInput
                            id="password"
                            name="password"
                            ref="passwordInput"
                            placeholder="Password"
                        />
                        <InputError :message="errors.password" />
                    </div>

                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button
                                variant="outline"
                                @click="
                                    () => {
                                        clearErrors();
                                        reset();
                                    }
                                "
                            >
                                Cancel
                            </Button>
                        </DialogClose>

                        <Button
                            type="submit"
                            variant="destructive"
                            :disabled="processing"
                            data-test="confirm-delete-user-button"
                        >
                            Delete account
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</template>
