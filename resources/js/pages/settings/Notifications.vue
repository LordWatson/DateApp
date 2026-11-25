<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { edit, update } from '@/routes/notification-settings';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Notification settings',
                href: edit(),
            },
        ],
    },
});

const props = defineProps<{ emailNotifications: boolean }>();

const form = useForm({ email_notifications: props.emailNotifications });
</script>

<template>
    <Head title="Notification settings" />

    <h2 class="sr-only">Notification settings</h2>

    <form
        class="space-y-5 card-premium p-5"
        @submit.prevent="form.patch(update().url, { preserveScroll: true })"
    >
        <Heading
            variant="small"
            title="Notifications"
            description="Choose how we get in touch with you"
        />

        <label class="flex items-start gap-3" for="email_notifications">
            <input
                id="email_notifications"
                v-model="form.email_notifications"
                type="checkbox"
                class="mt-1 size-4 shrink-0 accent-primary"
            />
            <span class="space-y-1">
                <span class="block text-sm font-medium text-foreground"
                    >Email notifications</span
                >
                <span
                    class="block text-sm leading-relaxed text-muted-foreground"
                >
                    Emails about love notes, new moments, date night plans and
                    your partner's activity. With this off you still get all of
                    them as notifications in the app. Account emails, such as
                    password resets, email verification and partner invitations
                    you send, are always sent.
                </span>
            </span>
        </label>
        <InputError :message="form.errors.email_notifications" />

        <Button :disabled="form.processing">Save</Button>
    </form>
</template>
