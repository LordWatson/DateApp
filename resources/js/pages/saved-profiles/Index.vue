<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Pencil, Trash2 } from '@lucide/vue';
import axios from 'axios';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import MobileLayout from '@/layouts/MobileLayout.vue';
import { dashboard } from '@/routes';
import questionnaires from '@/routes/questionnaires';
import savedProfiles from '@/routes/saved-profiles';

defineOptions({ layout: MobileLayout });

interface Profile {
    id: number;
    name: string;
    emoji: string | null;
    colour: string | null;
    answer_count: number;
}

const props = defineProps<{ profiles: Profile[] }>();

// Local copy: the endpoints answer with JSON, so the list is updated here.
const profiles = ref<Profile[]>([...props.profiles]);

const emojiOptions = ['❤️', '🎬', '🏨', '🌧️', '🎉', '💕', '✨', '🌹'];
const colourOptions = [
    '#d6336c',
    '#6d2452',
    '#10B981',
    '#F59E0B',
    '#3B82F6',
    '#F43F5E',
];

const editing = ref<Profile | null>(null);
const editOpen = ref(false);
const draft = ref({ name: '', emoji: '', colour: '' });
const nameError = ref('');
const saving = ref(false);

function startEdit(profile: Profile): void {
    editing.value = profile;
    draft.value = {
        name: profile.name,
        emoji: profile.emoji ?? '',
        colour: profile.colour ?? '',
    };
    nameError.value = '';
    editOpen.value = true;
}

async function saveEdit(): Promise<void> {
    const profile = editing.value;

    if (!profile || saving.value) {
        return;
    }

    saving.value = true;
    nameError.value = '';

    const payload = {
        name: draft.value.name.trim(),
        emoji: draft.value.emoji.trim() || null,
        colour: draft.value.colour || null,
    };

    try {
        await axios.put(savedProfiles.update(profile.id).url, payload);
        Object.assign(profile, payload);
        editOpen.value = false;
        toast.success('Profile updated.');
    } catch (error) {
        if (axios.isAxiosError(error) && error.response?.status === 422) {
            nameError.value =
                error.response.data?.errors?.name?.[0] ??
                error.response.data?.message ??
                'Please check the details and try again.';
        } else {
            toast.error('That profile could not be updated. Please try again.');
        }
    } finally {
        saving.value = false;
    }
}

const deleting = ref<Profile | null>(null);
const deleteOpen = ref(false);

function askDelete(profile: Profile): void {
    deleting.value = profile;
    deleteOpen.value = true;
}

async function confirmDelete(): Promise<void> {
    const profile = deleting.value;

    if (!profile) {
        return;
    }

    try {
        await axios.delete(savedProfiles.destroy(profile.id).url);
        profiles.value = profiles.value.filter((p) => p.id !== profile.id);
        toast.success('Profile deleted.');
    } catch {
        toast.error('That profile could not be deleted. Please try again.');
    }
}
</script>

<template>
    <Head title="Saved profiles" />

    <div class="space-y-8 px-4 pt-6 pb-28">
        <PageHeader
            title="Saved profiles"
            description="Answers you saved to reuse on a questionnaire. Only you can see them."
            :back-href="dashboard().url"
            back-label="Home"
        />

        <div v-if="profiles.length > 0" class="card-premium p-2">
            <div
                v-for="profile in profiles"
                :key="profile.id"
                class="flex items-center gap-3.5 p-3"
            >
                <span
                    class="icon-tile h-11 w-11 text-xl"
                    :style="
                        profile.colour
                            ? {
                                  boxShadow: `inset 0 0 0 1.5px ${profile.colour}`,
                              }
                            : undefined
                    "
                    aria-hidden="true"
                    >{{ profile.emoji || '✨' }}</span
                >
                <div class="min-w-0 flex-1">
                    <p class="truncate font-medium text-foreground">
                        {{ profile.name }}
                    </p>
                    <p class="text-xs text-muted-foreground tabular-nums">
                        {{ profile.answer_count }}
                        {{ profile.answer_count === 1 ? 'answer' : 'answers' }}
                    </p>
                </div>
                <button
                    type="button"
                    class="flex size-10 shrink-0 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
                    :aria-label="`Edit ${profile.name}`"
                    @click="startEdit(profile)"
                >
                    <Pencil class="size-4" aria-hidden="true" />
                </button>
                <button
                    type="button"
                    class="flex size-10 shrink-0 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-muted hover:text-destructive focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
                    :aria-label="`Delete ${profile.name}`"
                    @click="askDelete(profile)"
                >
                    <Trash2 class="size-4" aria-hidden="true" />
                </button>
            </div>
        </div>

        <div v-else class="card-premium">
            <EmptyState
                emoji="🔖"
                title="No saved profiles yet"
                description="Finish a questionnaire, then choose Save as profile on the summary to reuse your answers next time."
                action-label="Browse questionnaires"
                @action="router.visit(questionnaires.index().url)"
            />
        </div>

        <Dialog v-model:open="editOpen">
            <DialogContent>
                <DialogHeader class="space-y-3">
                    <DialogTitle class="font-display text-xl leading-tight"
                        >Edit profile</DialogTitle
                    >
                    <DialogDescription
                        >Change the name, emoji or colour. The saved answers
                        stay the same.</DialogDescription
                    >
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="saveEdit">
                    <div class="space-y-1.5">
                        <label
                            for="profile-name"
                            class="text-sm font-medium text-foreground"
                            >Name</label
                        >
                        <input
                            id="profile-name"
                            v-model="draft.name"
                            type="text"
                            maxlength="100"
                            required
                            class="input-premium"
                        />
                        <InputError :message="nameError" />
                    </div>

                    <div>
                        <p class="mb-2 eyebrow">Emoji</p>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="e in emojiOptions"
                                :key="e"
                                type="button"
                                :aria-pressed="draft.emoji === e"
                                :class="[
                                    'icon-tile h-11 w-11 text-xl transition-all focus-visible:outline-none',
                                    draft.emoji === e
                                        ? 'ring-2 ring-primary'
                                        : 'focus-visible:ring-2 focus-visible:ring-primary/50',
                                ]"
                                @click="draft.emoji = e"
                            >
                                {{ e }}
                            </button>
                        </div>
                    </div>

                    <div>
                        <p class="mb-2 eyebrow">Colour</p>
                        <div class="flex gap-3">
                            <button
                                v-for="c in colourOptions"
                                :key="c"
                                type="button"
                                :aria-pressed="draft.colour === c"
                                :aria-label="`Colour ${c}`"
                                :class="[
                                    'size-9 rounded-full ring-offset-2 ring-offset-card transition-all focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none',
                                    draft.colour === c
                                        ? 'ring-2 ring-foreground'
                                        : '',
                                ]"
                                :style="{ backgroundColor: c }"
                                @click="draft.colour = c"
                            />
                        </div>
                    </div>

                    <DialogFooter class="gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="editOpen = false"
                            >Cancel</Button
                        >
                        <PrimaryButton type="submit" :loading="saving"
                            >Save changes</PrimaryButton
                        >
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <ConfirmDialog
            v-model:open="deleteOpen"
            title="Delete this profile?"
            :description="`'${deleting?.name ?? ''}' and its saved answers will be removed. Your questionnaire answers are not affected.`"
            @confirm="confirmDelete"
        />
    </div>
</template>
