<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
import admin from '@/routes/admin';

defineOptions({ layout: AdminLayout });

interface Setting {
    id: number;
    key: string;
    label: string;
    description: string | null;
    value: string | null;
    type: string;
    group: string;
}

const props = defineProps<{
    settings: Record<string, Setting[]>;
    inUse: string[];
}>();

// Build local editable copy
const localValues = ref<Record<number, string | null>>({});

Object.values(props.settings)
    .flat()
    .forEach((s) => {
        localValues.value[s.id] = s.value;
    });

const saving = ref(false);

const saveAll = () => {
    saving.value = true;
    const payload = Object.entries(localValues.value).map(([id, value]) => ({
        id: Number(id),
        value,
    }));

    router.post(
        admin.settings.bulkUpdate().url,
        { settings: payload },
        {
            onFinish: () => {
                saving.value = false;
            },
        },
    );
};
</script>

<template>
    <div>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-foreground">
                    System settings
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Application-wide settings. A setting marked "Not in use" is
                    saved but nothing in the app reads it yet. AI settings have
                    their own page.
                </p>
            </div>
            <Button size="sm" @click="saveAll" :disabled="saving">
                {{ saving ? 'Saving…' : 'Save all' }}
            </Button>
        </div>

        <div
            v-for="(groupSettings, groupName) in settings"
            :key="groupName"
            class="mb-8"
        >
            <h2 class="mb-3 font-sans eyebrow">{{ groupName }}</h2>
            <div class="divide-y divide-border card-premium rounded-2xl">
                <div
                    v-for="setting in groupSettings"
                    :key="setting.id"
                    class="flex items-center gap-6 px-6 py-4"
                >
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-foreground">
                            {{ setting.label }}
                            <span
                                v-if="!inUse.includes(setting.key)"
                                class="ml-2 badge-soft"
                                >Not in use</span
                            >
                        </p>
                        <p
                            v-if="setting.description"
                            class="mt-0.5 text-xs text-muted-foreground"
                        >
                            {{ setting.description }}
                        </p>
                    </div>
                    <div class="w-64 flex-shrink-0">
                        <input
                            v-if="setting.type === 'color'"
                            v-model="localValues[setting.id]"
                            type="color"
                            class="h-10 w-full cursor-pointer rounded-xl border border-input bg-card"
                        />
                        <input
                            v-else-if="setting.type === 'boolean'"
                            v-model="localValues[setting.id]"
                            type="checkbox"
                            class="size-4 rounded accent-primary"
                        />
                        <input
                            v-else
                            v-model="localValues[setting.id]"
                            :type="
                                setting.type === 'integer' ? 'number' : 'text'
                            "
                            class="input-premium px-3 py-2 text-sm"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
