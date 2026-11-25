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

interface EmailCategory {
    value: string;
    label: string;
    description: string;
    enabled: boolean;
}

const props = defineProps<{
    emailNotifications: boolean;
    emailCategories: EmailCategory[];
    reminderFrequency: string;
}>();

const form = useForm({
    email_notifications: props.emailNotifications,
    emails: Object.fromEntries(
        props.emailCategories.map((category) => [
            category.value,
            category.enabled,
        ]),
    ) as Record<string, boolean>,
    reminder_frequency: props.reminderFrequency,
});

const frequencies = [
    { value: 'weekly', label: 'Once a week' },
    { value: 'monthly', label: 'Once a month' },
    { value: 'off', label: 'Never' },
];
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
                    Turn every notification email on or off. With this off you
                    still get all of them as notifications in the app. Account
                    emails, such as password resets, email verification and
                    partner invitations you send, are always sent.
                </span>
            </span>
        </label>
        <InputError :message="form.errors.email_notifications" />

        <fieldset
            class="space-y-3 border-t border-border pt-4"
            :disabled="!form.email_notifications"
        >
            <legend class="text-sm font-medium text-foreground">
                Which emails
            </legend>
            <label
                v-for="category in emailCategories"
                :key="category.value"
                class="flex items-start gap-3"
                :class="!form.email_notifications && 'opacity-50'"
                :for="`email-${category.value}`"
            >
                <input
                    :id="`email-${category.value}`"
                    v-model="form.emails[category.value]"
                    type="checkbox"
                    class="mt-1 size-4 shrink-0 accent-primary"
                />
                <span class="space-y-0.5">
                    <span class="block text-sm font-medium text-foreground">{{
                        category.label
                    }}</span>
                    <span
                        class="block text-sm leading-relaxed text-muted-foreground"
                        >{{ category.description }}</span
                    >
                </span>
            </label>
            <InputError :message="form.errors.emails" />
        </fieldset>

        <div class="space-y-1.5 border-t border-border pt-4">
            <label
                class="block text-sm font-medium text-foreground"
                for="reminder_frequency"
                >Remind me to plan a date night</label
            >
            <p class="text-sm leading-relaxed text-muted-foreground">
                A notification in the app when you have not answered a
                questionnaire for a while.
            </p>
            <select
                id="reminder_frequency"
                v-model="form.reminder_frequency"
                class="input-premium"
            >
                <option
                    v-for="frequency in frequencies"
                    :key="frequency.value"
                    :value="frequency.value"
                >
                    {{ frequency.label }}
                </option>
            </select>
            <InputError :message="form.errors.reminder_frequency" />
        </div>

        <Button :disabled="form.processing">Save</Button>
    </form>
</template>
