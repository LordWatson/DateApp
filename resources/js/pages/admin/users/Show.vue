<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { ChevronLeft } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
import admin from '@/routes/admin';

defineOptions({ layout: AdminLayout });

const props = defineProps<{
    user: {
        id: number;
        name: string;
        email: string;
        display_name: string | null;
        gender: string | null;
        date_of_birth: string | null;
        timezone: string;
        is_suspended: boolean;
        suspended_at: string | null;
        suspension_reason: string | null;
        onboarding_completed: boolean;
        current_streak: number;
        longest_streak: number;
        monthly_completion_count: number;
        created_at: string;
        role: { id: number; label: string } | null;
        partner: { id: number; name: string; email: string } | null;
        responses: Array<{
            id: number;
            questionnaire: { title: string };
            completed_at: string | null;
            created_at: string;
        }>;
        achievements: Array<{
            id: number;
            name: string;
            emoji: string;
            pivot: { unlocked_at: string };
        }>;
    };
}>();

const editForm = useForm({
    name: props.user.name,
    email: props.user.email,
    role_id: props.user.role?.id ?? null,
});

const editing = ref(false);

const save = () => {
    editForm.put(admin.users.update(props.user.id).url, {
        onSuccess: () => {
            editing.value = false;
        },
    });
};
</script>

<template>
    <div>
        <div class="mb-6 flex items-center gap-4">
            <Link
                :href="admin.users.index().url"
                class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                ><ChevronLeft class="size-4" aria-hidden="true" />Users</Link
            >
            <h1 class="text-2xl font-semibold text-foreground">
                {{ user.name }}
            </h1>
            <span
                :class="[
                    'badge-soft',
                    user.is_suspended
                        ? 'bg-destructive/10 text-destructive'
                        : 'bg-success/10 text-success',
                ]"
            >
                {{ user.is_suspended ? 'Suspended' : 'Active' }}
            </span>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Profile Card -->
            <div class="space-y-4 lg:col-span-1">
                <div class="card-premium rounded-2xl p-6">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-foreground">
                            Profile
                        </h2>
                        <button
                            @click="editing = !editing"
                            class="text-xs font-medium text-foreground hover:underline"
                        >
                            {{ editing ? 'Cancel' : 'Edit' }}
                        </button>
                    </div>

                    <div v-if="!editing" class="space-y-3 text-sm">
                        <div>
                            <p class="text-xs text-muted-foreground">Name</p>
                            <p class="font-medium text-foreground">
                                {{ user.name }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Email</p>
                            <p class="font-medium text-foreground">
                                {{ user.email }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Role</p>
                            <p class="font-medium text-foreground">
                                {{ user.role?.label ?? 'User' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Joined</p>
                            <p class="font-medium text-foreground">
                                {{
                                    new Date(
                                        user.created_at,
                                    ).toLocaleDateString()
                                }}
                            </p>
                        </div>
                        <div v-if="user.partner">
                            <p class="text-xs text-muted-foreground">Partner</p>
                            <Link
                                :href="admin.users.show(user.partner.id).url"
                                class="font-medium text-foreground hover:underline"
                            >
                                {{ user.partner.name }}
                            </Link>
                        </div>
                    </div>

                    <form v-else @submit.prevent="save" class="space-y-3">
                        <div>
                            <label class="text-xs text-muted-foreground"
                                >Name</label
                            >
                            <input
                                v-model="editForm.name"
                                class="mt-1 input-premium px-3 py-2 text-sm"
                            />
                        </div>
                        <div>
                            <label class="text-xs text-muted-foreground"
                                >Email</label
                            >
                            <input
                                v-model="editForm.email"
                                type="email"
                                class="mt-1 input-premium px-3 py-2 text-sm"
                            />
                        </div>
                        <Button size="sm" type="submit" class="w-full">
                            Save changes
                        </Button>
                    </form>
                </div>

                <!-- Stats -->
                <div class="card-premium rounded-2xl p-6">
                    <h2 class="mb-4 text-lg font-semibold text-foreground">
                        Stats
                    </h2>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-xl bg-accent p-3 text-center">
                            <p
                                class="font-display text-2xl font-semibold text-foreground tabular-nums"
                            >
                                {{ user.current_streak }}
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Current streak
                            </p>
                        </div>
                        <div class="rounded-xl bg-accent p-3 text-center">
                            <p
                                class="font-display text-2xl font-semibold text-foreground tabular-nums"
                            >
                                {{ user.longest_streak }}
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Longest streak
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Suspension info -->
                <div
                    v-if="user.is_suspended"
                    class="rounded-2xl border border-destructive/20 bg-destructive/10 p-6"
                >
                    <h2 class="mb-2 font-semibold text-destructive">
                        Suspended
                    </h2>
                    <p class="text-xs text-destructive">
                        {{ user.suspension_reason ?? 'No reason provided.' }}
                    </p>
                    <p class="mt-1 text-xs text-destructive">
                        {{
                            user.suspended_at
                                ? new Date(
                                      user.suspended_at,
                                  ).toLocaleDateString()
                                : ''
                        }}
                    </p>
                </div>
            </div>

            <!-- Right column -->
            <div class="space-y-6 lg:col-span-2">
                <!-- Questionnaire Responses -->
                <div class="card-premium rounded-2xl p-6">
                    <h2 class="mb-4 text-lg font-semibold text-foreground">
                        Questionnaire responses
                    </h2>
                    <div class="space-y-2">
                        <div
                            v-for="response in user.responses"
                            :key="response.id"
                            class="flex items-center justify-between border-b border-border py-2 last:border-0"
                        >
                            <p class="text-sm font-medium text-foreground">
                                {{ response.questionnaire?.title }}
                            </p>
                            <span
                                :class="[
                                    'badge-soft',
                                    response.completed_at
                                        ? 'bg-success/10 text-success'
                                        : 'bg-warning/10 text-warning',
                                ]"
                            >
                                {{
                                    response.completed_at
                                        ? 'Completed'
                                        : 'In progress'
                                }}
                            </span>
                        </div>
                        <p
                            v-if="!user.responses.length"
                            class="py-4 text-center text-sm text-muted-foreground"
                        >
                            No responses yet.
                        </p>
                    </div>
                </div>

                <!-- Achievements -->
                <div class="card-premium rounded-2xl p-6">
                    <h2 class="mb-4 text-lg font-semibold text-foreground">
                        Achievements
                    </h2>
                    <div class="flex flex-wrap gap-2">
                        <div
                            v-for="achievement in user.achievements"
                            :key="achievement.id"
                            class="flex items-center gap-2 rounded-xl bg-warning/10 px-3 py-2"
                        >
                            <span class="text-lg">{{ achievement.emoji }}</span>
                            <span class="text-xs font-medium text-foreground">{{
                                achievement.name
                            }}</span>
                        </div>
                        <p
                            v-if="!user.achievements.length"
                            class="text-sm text-muted-foreground"
                        >
                            No achievements yet.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
