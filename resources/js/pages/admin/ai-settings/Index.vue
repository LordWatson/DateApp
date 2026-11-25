<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { CircleAlert, CircleCheck } from '@lucide/vue';
import axios from 'axios';
import { ref, watch } from 'vue';
import AdminTable from '@/components/admin/AdminTable.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
import admin from '@/routes/admin';

defineOptions({ layout: AdminLayout });

interface Settings {
    provider: string;
    model: string;
    temperature: number;
    max_tokens: number;
    timeout: number;
    retry_attempts: number;
}

interface Analytics {
    total: number;
    successful: number;
    fallbacks: number;
    total_tokens: number;
    avg_duration_ms: number;
    by_feature: Array<{
        feature: string;
        generations: number;
        tokens: number | null;
    }>;
}

const props = defineProps<{
    settings: Settings;
    analytics: Analytics;
    providers: string[];
    useCases: string[];
}>();

const providerLabels: Record<string, string> = {
    anthropic: 'Anthropic (Claude)',
    deepseek: 'DeepSeek',
    null: 'None (canned responses, no network)',
};

const form = useForm({ ...props.settings });

const save = () => {
    form.put(admin.aiSettings.update().url, { preserveScroll: true });
};

// Test connection
interface TestResult {
    ok: boolean;
    provider?: string;
    model?: string | null;
    error?: string;
    error_code?: string | null;
    error_message?: string | null;
}

const testing = ref(false);
const testResult = ref<TestResult | null>(null);

function requestError(error: unknown): string {
    if (axios.isAxiosError(error)) {
        if (error.response?.status === 429) {
            return 'Too many AI requests. Try again later.';
        }

        return error.response?.data?.message ?? error.message;
    }

    return 'The request failed.';
}

const testConnection = async () => {
    testing.value = true;
    testResult.value = null;

    try {
        const { data } = await axios.post<TestResult>(
            admin.aiSettings.testConnection().url,
        );
        testResult.value = data;
    } catch (error) {
        testResult.value = { ok: false, error: requestError(error) };
    } finally {
        testing.value = false;
    }
};

// Prompt preview
interface PromptPreview {
    use_case: string;
    system_prompt: string | null;
    user_prompt_template: string | null;
    version: number | null;
}

const useCase = ref(props.useCases[0] ?? '');
const prompt = ref<PromptPreview | null>(null);
const promptError = ref('');

const loadPrompt = async () => {
    promptError.value = '';

    try {
        const { data } = await axios.get<PromptPreview>(
            admin.aiSettings.previewPrompt().url,
            { params: { use_case: useCase.value } },
        );
        prompt.value = data;
    } catch (error) {
        prompt.value = null;
        promptError.value = requestError(error);
    }
};

// Live preview
interface LiveResult {
    ok: boolean;
    data?: unknown;
    model?: string | null;
    usage?: Record<string, number> | null;
    error?: string;
    error_code?: string | null;
    error_message?: string | null;
}

const sampleContext = ref('{}');
const contextError = ref('');
const generating = ref(false);
const liveResult = ref<LiveResult | null>(null);

const runLivePreview = async () => {
    contextError.value = '';
    let parsed: unknown;

    try {
        parsed = JSON.parse(sampleContext.value || '{}');
    } catch {
        contextError.value = 'The sample context is not valid JSON.';

        return;
    }

    generating.value = true;
    liveResult.value = null;

    try {
        const { data } = await axios.post<LiveResult>(
            admin.aiSettings.previewResponse().url,
            // The seeded templates read their data from {{context}}.
            { use_case: useCase.value, context: { context: parsed } },
        );
        liveResult.value = data;
    } catch (error) {
        liveResult.value = { ok: false, error: requestError(error) };
    } finally {
        generating.value = false;
    }
};

watch(
    useCase,
    () => {
        liveResult.value = null;
        void loadPrompt();
    },
    { immediate: true },
);

const successRate = (a: Analytics): string =>
    a.total > 0 ? `${Math.round((a.successful / a.total) * 100)}%` : '–';
</script>

<template>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-foreground">AI settings</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Provider, model and limits for every AI call. Values saved here
                override the environment file.
            </p>
        </div>

        <!-- Usage -->
        <div class="mb-8 grid grid-cols-2 gap-4 md:grid-cols-5">
            <div class="card-premium rounded-2xl p-5">
                <p class="mb-1 eyebrow">Calls</p>
                <p
                    class="font-display text-3xl font-semibold text-foreground tabular-nums"
                >
                    {{ analytics.total }}
                </p>
            </div>
            <div class="card-premium rounded-2xl p-5">
                <p class="mb-1 eyebrow">Success rate</p>
                <p
                    class="font-display text-3xl font-semibold text-foreground tabular-nums"
                >
                    {{ successRate(analytics) }}
                </p>
            </div>
            <div class="card-premium rounded-2xl p-5">
                <p class="mb-1 eyebrow">Fallbacks</p>
                <p
                    class="font-display text-3xl font-semibold text-foreground tabular-nums"
                >
                    {{ analytics.fallbacks }}
                </p>
            </div>
            <div class="card-premium rounded-2xl p-5">
                <p class="mb-1 eyebrow">Tokens</p>
                <p
                    class="font-display text-3xl font-semibold text-foreground tabular-nums"
                >
                    {{ analytics.total_tokens.toLocaleString() }}
                </p>
            </div>
            <div class="card-premium rounded-2xl p-5">
                <p class="mb-1 eyebrow">Avg duration</p>
                <p
                    class="font-display text-3xl font-semibold text-foreground tabular-nums"
                >
                    {{ (analytics.avg_duration_ms / 1000).toFixed(1) }}s
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Settings form -->
            <form
                class="space-y-4 card-premium rounded-2xl p-6"
                @submit.prevent="save"
            >
                <h2 class="text-lg font-semibold text-foreground">Settings</h2>

                <div>
                    <label
                        for="ai-provider"
                        class="mb-1 block text-sm font-medium text-foreground"
                        >Provider</label
                    >
                    <select
                        id="ai-provider"
                        v-model="form.provider"
                        class="input-premium px-3 py-2 text-sm"
                    >
                        <option v-for="p in providers" :key="p" :value="p">
                            {{ providerLabels[p] ?? p }}
                        </option>
                    </select>
                    <InputError :message="form.errors.provider" />
                </div>

                <div>
                    <label
                        for="ai-model"
                        class="mb-1 block text-sm font-medium text-foreground"
                        >Model</label
                    >
                    <input
                        id="ai-model"
                        v-model="form.model"
                        type="text"
                        maxlength="120"
                        aria-describedby="ai-model-help"
                        class="input-premium px-3 py-2 font-mono text-sm"
                    />
                    <p
                        id="ai-model-help"
                        class="mt-1 text-xs text-muted-foreground"
                    >
                        Applies to the selected provider. Change the provider
                        and the model together: a model name the provider does
                        not know makes every call fail.
                    </p>
                    <InputError :message="form.errors.model" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label
                            for="ai-temperature"
                            class="mb-1 block text-sm font-medium text-foreground"
                            >Temperature</label
                        >
                        <input
                            id="ai-temperature"
                            v-model.number="form.temperature"
                            type="number"
                            min="0"
                            max="2"
                            step="0.1"
                            aria-describedby="ai-temperature-help"
                            class="input-premium px-3 py-2 text-sm"
                        />
                        <InputError :message="form.errors.temperature" />
                    </div>
                    <div>
                        <label
                            for="ai-max-tokens"
                            class="mb-1 block text-sm font-medium text-foreground"
                            >Max tokens</label
                        >
                        <input
                            id="ai-max-tokens"
                            v-model.number="form.max_tokens"
                            type="number"
                            min="1"
                            max="8000"
                            class="input-premium px-3 py-2 text-sm"
                        />
                        <InputError :message="form.errors.max_tokens" />
                    </div>
                    <div>
                        <label
                            for="ai-timeout"
                            class="mb-1 block text-sm font-medium text-foreground"
                            >Timeout (seconds)</label
                        >
                        <input
                            id="ai-timeout"
                            v-model.number="form.timeout"
                            type="number"
                            min="1"
                            max="300"
                            class="input-premium px-3 py-2 text-sm"
                        />
                        <InputError :message="form.errors.timeout" />
                    </div>
                    <div>
                        <label
                            for="ai-retries"
                            class="mb-1 block text-sm font-medium text-foreground"
                            >Retry attempts</label
                        >
                        <input
                            id="ai-retries"
                            v-model.number="form.retry_attempts"
                            type="number"
                            min="0"
                            max="10"
                            class="input-premium px-3 py-2 text-sm"
                        />
                        <InputError :message="form.errors.retry_attempts" />
                    </div>
                </div>
                <p
                    id="ai-temperature-help"
                    class="text-xs text-muted-foreground"
                >
                    Temperature only applies to DeepSeek. Claude ignores it.
                    With Claude, max tokens is a minimum rather than a cap.
                </p>

                <div class="flex flex-wrap items-center gap-3 pt-2">
                    <Button type="submit" size="sm" :disabled="form.processing">
                        {{ form.processing ? 'Saving…' : 'Save settings' }}
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        :disabled="testing"
                        @click="testConnection"
                    >
                        {{ testing ? 'Testing…' : 'Test connection' }}
                    </Button>
                </div>
                <p class="text-xs text-muted-foreground">
                    The test uses the saved settings, so save first.
                </p>

                <div
                    v-if="testResult"
                    :role="testResult.ok ? 'status' : 'alert'"
                    :class="[
                        'flex items-start gap-2 rounded-xl border px-4 py-3 text-sm text-foreground',
                        testResult.ok
                            ? 'border-success/25 bg-success/10'
                            : 'border-destructive/25 bg-destructive/10',
                    ]"
                >
                    <component
                        :is="testResult.ok ? CircleCheck : CircleAlert"
                        :class="[
                            'mt-0.5 size-4 shrink-0',
                            testResult.ok ? 'text-success' : 'text-destructive',
                        ]"
                        aria-hidden="true"
                    />
                    <div class="min-w-0 space-y-0.5">
                        <p class="font-medium">
                            {{
                                testResult.ok
                                    ? 'Connection ok'
                                    : 'Connection failed'
                            }}
                        </p>
                        <p
                            v-if="testResult.provider"
                            class="text-xs text-muted-foreground"
                        >
                            Provider {{ testResult.provider }}
                            <template v-if="testResult.model">
                                · model {{ testResult.model }}</template
                            >
                        </p>
                        <p
                            v-if="
                                testResult.error ||
                                testResult.error_code ||
                                testResult.error_message
                            "
                            class="text-xs break-words"
                        >
                            {{
                                [
                                    testResult.error_code,
                                    testResult.error_message ??
                                        testResult.error,
                                ]
                                    .filter(Boolean)
                                    .join(': ')
                            }}
                        </p>
                    </div>
                </div>
            </form>

            <!-- Prompt preview -->
            <div class="space-y-4 card-premium rounded-2xl p-6">
                <h2 class="text-lg font-semibold text-foreground">
                    Prompt preview
                </h2>

                <div>
                    <label
                        for="ai-use-case"
                        class="mb-1 block text-sm font-medium text-foreground"
                        >Use case</label
                    >
                    <select
                        id="ai-use-case"
                        v-model="useCase"
                        class="input-premium px-3 py-2 font-mono text-sm"
                    >
                        <option v-for="u in useCases" :key="u" :value="u">
                            {{ u }}
                        </option>
                    </select>
                </div>

                <InputError :message="promptError" />

                <template v-if="prompt">
                    <p
                        v-if="prompt.version === null"
                        class="text-sm text-muted-foreground"
                    >
                        No active template for this use case. Calls for it fall
                        back to the non-AI result.
                    </p>
                    <template v-else>
                        <div>
                            <p class="mb-1 eyebrow">
                                System prompt · version {{ prompt.version }}
                            </p>
                            <pre
                                class="max-h-48 overflow-auto rounded-xl bg-muted p-3 text-xs whitespace-pre-wrap text-foreground"
                                >{{ prompt.system_prompt }}</pre>
                        </div>
                        <div>
                            <p class="mb-1 eyebrow">User prompt template</p>
                            <pre
                                class="max-h-48 overflow-auto rounded-xl bg-muted p-3 text-xs whitespace-pre-wrap text-foreground"
                                >{{ prompt.user_prompt_template }}</pre>
                        </div>
                    </template>
                </template>

                <div class="border-t border-border pt-4">
                    <h3 class="text-base font-semibold text-foreground">
                        Live preview
                    </h3>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Optional. Sends this use case to the provider with the
                        sample context below. It is a real call: it costs tokens
                        and is counted in the usage figures.
                    </p>
                    <label for="ai-context" class="sr-only"
                        >Sample context (JSON)</label
                    >
                    <textarea
                        id="ai-context"
                        v-model="sampleContext"
                        rows="4"
                        spellcheck="false"
                        class="mt-3 input-premium px-3 py-2 font-mono text-xs"
                    />
                    <InputError :message="contextError" />
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        class="mt-3"
                        :disabled="generating"
                        @click="runLivePreview"
                    >
                        {{ generating ? 'Generating…' : 'Run live preview' }}
                    </Button>

                    <div v-if="liveResult" class="mt-3 space-y-1">
                        <p
                            :role="liveResult.ok ? 'status' : 'alert'"
                            :class="[
                                'text-sm font-medium',
                                liveResult.ok
                                    ? 'text-success'
                                    : 'text-destructive',
                            ]"
                        >
                            {{
                                liveResult.ok
                                    ? `Response from ${liveResult.model ?? 'the provider'}`
                                    : [
                                          'Failed',
                                          liveResult.error_code,
                                          liveResult.error_message ??
                                              liveResult.error,
                                      ]
                                          .filter(Boolean)
                                          .join(': ')
                            }}
                        </p>
                        <pre
                            v-if="liveResult.ok"
                            class="max-h-72 overflow-auto rounded-xl bg-muted p-3 text-xs whitespace-pre-wrap text-foreground"
                            >{{
                                JSON.stringify(liveResult.data, null, 2)
                            }}</pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- Per feature -->
        <h2 class="mt-8 mb-3 text-lg font-semibold text-foreground">
            Usage by feature
        </h2>
        <AdminTable
            :columns="[
                { key: 'feature', label: 'Feature' },
                { key: 'generations', label: 'Calls' },
                { key: 'tokens', label: 'Tokens' },
            ]"
            empty="No AI calls recorded yet."
        >
            <tr v-for="row in analytics.by_feature" :key="row.feature">
                <td class="px-4 py-3 font-mono text-xs text-foreground">
                    {{ row.feature }}
                </td>
                <td class="px-4 py-3 text-foreground tabular-nums">
                    {{ row.generations }}
                </td>
                <td class="px-4 py-3 text-foreground tabular-nums">
                    {{ Number(row.tokens ?? 0).toLocaleString() }}
                </td>
            </tr>
        </AdminTable>
    </div>
</template>
