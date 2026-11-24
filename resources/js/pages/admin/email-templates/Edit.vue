<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { ChevronLeft, ExternalLink } from '@lucide/vue';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps<{
    template: {
        id: number;
        key: string;
        label: string;
        subject: string;
        html_body: string;
        text_body: string | null;
        active: boolean;
    };
}>();

const form = useForm({
    subject: props.template.subject,
    html_body: props.template.html_body,
    text_body: props.template.text_body ?? '',
    active: props.template.active,
});

const save = () => {
    form.put(route('admin.email-templates.update', props.template.id));
};

const preview = () => {
    const win = window.open('', '_blank');

    if (win) {
        win.document.write(form.html_body);
        win.document.close();
    }
};
</script>

<template>
    <div>
        <div class="mb-6 flex items-center gap-4">
            <Link
                :href="route('admin.email-templates.index')"
                class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                ><ChevronLeft class="size-4" aria-hidden="true" />Email
                templates</Link
            >
            <h1 class="text-2xl font-semibold text-foreground">
                {{ template.label }}
            </h1>
        </div>

        <form @submit.prevent="save" class="space-y-6">
            <div class="space-y-4 card-premium rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-foreground">
                        Template settings
                    </h2>
                    <div class="flex items-center gap-2">
                        <input
                            id="active"
                            v-model="form.active"
                            type="checkbox"
                            class="size-4 rounded accent-primary"
                        />
                        <label for="active" class="text-sm text-foreground"
                            >Active</label
                        >
                    </div>
                </div>

                <div>
                    <label class="text-xs font-medium text-muted-foreground"
                        >Subject line</label
                    >
                    <input
                        v-model="form.subject"
                        required
                        class="mt-1 input-premium px-3 py-2 text-sm"
                    />
                </div>

                <div>
                    <div class="mb-1 flex items-center justify-between">
                        <label class="text-xs font-medium text-muted-foreground"
                            >HTML body</label
                        >
                        <button
                            type="button"
                            @click="preview"
                            class="inline-flex items-center gap-1 text-xs font-medium text-foreground hover:underline"
                        >
                            Preview
                            <ExternalLink class="size-3.5" aria-hidden="true" />
                        </button>
                    </div>
                    <textarea
                        v-model="form.html_body"
                        rows="20"
                        class="input-premium px-3 py-2 font-mono text-sm"
                    />
                </div>

                <div>
                    <label class="text-xs font-medium text-muted-foreground"
                        >Plain text body (optional)</label
                    >
                    <textarea
                        v-model="form.text_body"
                        rows="6"
                        class="mt-1 input-premium px-3 py-2 font-mono text-sm"
                    />
                </div>
            </div>

            <div class="flex gap-3">
                <Button size="sm" type="submit" :disabled="form.processing">
                    Save template
                </Button>
                <Button as-child variant="outline" size="sm"
                    ><Link :href="route('admin.email-templates.index')">
                        Cancel
                    </Link></Button
                >
            </div>
        </form>
    </div>
</template>
