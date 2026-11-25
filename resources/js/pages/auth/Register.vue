<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login, privacy, terms } from '@/routes';
import { store } from '@/routes/register';

defineProps<{
    passwordRules: string;
}>();

defineOptions({
    layout: {
        title: 'Create an account',
        description: 'Enter your details below to create your account',
    },
});
</script>

<template>
    <Head title="Register" />

    <div
        v-if="!$page.props.registrationOpen"
        class="space-y-4 text-center"
        role="status"
    >
        <p class="text-sm leading-relaxed text-muted-foreground">
            Registration is closed at the moment. If you already have an account
            you can still log in.
        </p>
        <TextLink :href="login()">Log in</TextLink>
    </div>

    <Form
        v-else
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-5">
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    type="text"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="name"
                    name="name"
                    placeholder="Full name"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    :tabindex="2"
                    autocomplete="email"
                    name="email"
                    placeholder="email@example.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Password</Label>
                <PasswordInput
                    id="password"
                    required
                    :tabindex="3"
                    autocomplete="new-password"
                    name="password"
                    placeholder="Password"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm password</Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="Confirm password"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <div class="grid gap-2">
                <Label
                    for="terms"
                    class="flex items-start gap-3 leading-snug font-normal text-muted-foreground"
                >
                    <Checkbox
                        id="terms"
                        name="terms"
                        required
                        :tabindex="5"
                        class="mt-0.5"
                    />
                    <span>
                        I agree to the
                        <TextLink :href="terms()">Terms</TextLink> and
                        <TextLink :href="privacy()">Privacy Policy</TextLink>
                    </span>
                </Label>
                <InputError :message="errors.terms" />
            </div>

            <Button
                type="submit"
                size="lg"
                class="mt-1 w-full"
                tabindex="6"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" />
                Create account
            </Button>
        </div>

        <div class="h-px bg-border" />

        <div class="text-center text-sm text-muted-foreground">
            Already have an account?
            <TextLink :href="login()" :tabindex="7">Log in</TextLink>
        </div>
    </Form>
</template>
