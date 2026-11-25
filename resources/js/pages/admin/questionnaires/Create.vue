<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { ChevronLeft } from '@lucide/vue';
import QuestionnaireModeFields from '@/components/admin/QuestionnaireModeFields.vue';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
import admin from '@/routes/admin';

defineOptions({ layout: AdminLayout });

const form = useForm({
    title: '',
    description: '',
    emoji: '',
    status: 'draft',
    visibility: 'public',
    estimated_minutes: null as number | null,
    display_order: 0,
    is_solo: false,
    partner_optional: false,
    is_intimacy: false,
    is_seasonal: false,
    active_from: '',
    active_until: '',
});

const submit = () => {
    form.post(admin.questionnaires.store().url);
};
</script>

<template>
    <div class="max-w-2xl">
        <div class="mb-6 flex items-center gap-4">
            <Link
                :href="admin.questionnaires.index().url"
                class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                ><ChevronLeft
                    class="size-4"
                    aria-hidden="true"
                />Questionnaires</Link
            >
            <h1 class="text-2xl font-semibold text-foreground">
                New questionnaire
            </h1>
        </div>

        <form
            @submit.prevent="submit"
            class="space-y-5 card-premium rounded-2xl p-6"
        >
            <div class="grid grid-cols-4 gap-4">
                <div>
                    <label class="text-xs font-medium text-muted-foreground"
                        >Emoji</label
                    >
                    <input
                        v-model="form.emoji"
                        class="mt-1 input-premium px-3 py-2 text-sm"
                        placeholder="💕"
                    />
                </div>
                <div class="col-span-3">
                    <label class="text-xs font-medium text-muted-foreground"
                        >Title *</label
                    >
                    <input
                        v-model="form.title"
                        required
                        class="mt-1 input-premium px-3 py-2 text-sm"
                        placeholder="Questionnaire title"
                    />
                    <p
                        v-if="form.errors.title"
                        class="mt-1 text-xs text-destructive"
                    >
                        {{ form.errors.title }}
                    </p>
                </div>
            </div>

            <div>
                <label class="text-xs font-medium text-muted-foreground"
                    >Description</label
                >
                <textarea
                    v-model="form.description"
                    rows="3"
                    class="mt-1 input-premium px-3 py-2 text-sm"
                    placeholder="Brief description…"
                />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-medium text-muted-foreground"
                        >Status</label
                    >
                    <select
                        v-model="form.status"
                        class="mt-1 input-premium px-3 py-2 text-sm"
                    >
                        <option value="draft">Draft</option>
                        <option value="active">Active</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-medium text-muted-foreground"
                        >Visibility</label
                    >
                    <select
                        v-model="form.visibility"
                        class="mt-1 input-premium px-3 py-2 text-sm"
                    >
                        <option value="public">Public</option>
                        <option value="private">Private</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-medium text-muted-foreground"
                        >Estimated minutes</label
                    >
                    <input
                        v-model.number="form.estimated_minutes"
                        type="number"
                        min="1"
                        class="mt-1 input-premium px-3 py-2 text-sm"
                    />
                </div>
                <div>
                    <label class="text-xs font-medium text-muted-foreground"
                        >Display order</label
                    >
                    <input
                        v-model.number="form.display_order"
                        type="number"
                        min="0"
                        class="mt-1 input-premium px-3 py-2 text-sm"
                    />
                </div>
            </div>

            <QuestionnaireModeFields
                v-model:is-solo="form.is_solo"
                v-model:partner-optional="form.partner_optional"
                v-model:is-intimacy="form.is_intimacy"
            />

            <div class="flex items-center gap-3">
                <input
                    id="seasonal"
                    v-model="form.is_seasonal"
                    type="checkbox"
                    class="size-4 rounded accent-primary"
                />
                <label for="seasonal" class="text-sm text-foreground"
                    >Seasonal questionnaire</label
                >
            </div>

            <div v-if="form.is_seasonal" class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-medium text-muted-foreground"
                        >Active from</label
                    >
                    <input
                        v-model="form.active_from"
                        type="date"
                        class="mt-1 input-premium px-3 py-2 text-sm"
                    />
                </div>
                <div>
                    <label class="text-xs font-medium text-muted-foreground"
                        >Active until</label
                    >
                    <input
                        v-model="form.active_until"
                        type="date"
                        class="mt-1 input-premium px-3 py-2 text-sm"
                    />
                </div>
            </div>

            <p
                v-if="form.hasErrors && !form.errors.title"
                role="alert"
                class="text-xs text-destructive"
            >
                {{ Object.values(form.errors)[0] }}
            </p>

            <div class="flex gap-3 pt-2">
                <Button size="sm" type="submit" :disabled="form.processing">
                    Create questionnaire
                </Button>
                <Button as-child variant="outline" size="sm"
                    ><Link :href="admin.questionnaires.index().url">
                        Cancel
                    </Link></Button
                >
            </div>
        </form>
    </div>
</template>
