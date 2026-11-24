<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Bot,
    CalendarHeart,
    Camera,
    ChartNoAxesColumn,
    ChevronDown,
    ClipboardList,
    Dices,
    Heart,
    HeartHandshake,
    History,
    House,
    Lock,
    MoonStar,
    NotebookPen,
    Percent,
    Sparkles,
    Trophy,
} from '@lucide/vue';
import type { Component } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import FloatingHearts from '@/components/FloatingHearts.vue';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { home, login, register } from '@/routes';

type ModuleSection = {
    icon: Component;
    title: string;
    tagline: string;
    body: string;
};

const sections: ModuleSection[] = [
    /*    {
        icon: Sparkles,
        title: 'A gentle start',
        tagline: 'Onboarding made for two',
        body: 'Set up your profile in a couple of taps and invite your partner with a single link. No awkward forms, no corporate energy — just a warm welcome for the two of you.',
    },*/
    {
        icon: HeartHandshake,
        title: 'Link with your partner',
        tagline: 'Partner connection',
        body: 'One shared space, just for the two of you. Send an invite, get connected, and everything you do inside Date Night becomes a little conversation between you both.',
    },
    {
        icon: ClipboardList,
        title: 'Playful questionnaires',
        tagline: 'One question at a time',
        body: 'Beautifully designed questionnaires, one question per page, that ask what you actually want tonight — mood, energy, cravings, cheeky ideas. Auto-saves as you go, so you can pause and pick it up whenever life gets in the way.',
    },
    {
        icon: Percent,
        title: 'Your compatibility',
        tagline: 'See where you sparkle together',
        body: 'When you both finish the latest questionnaire, we crunch the numbers and show you a compatibility percentage, the preferences you both share, and the fun little differences that keep things interesting.',
    },
    /*    {
        icon: Bookmark,
        title: 'Saved profiles',
        tagline: 'Bottle your favourite moods',
        body: 'Loved last Friday? Save that answer set as “Lazy Sunday”, “Anniversary”, or whatever fits. Reuse it in one tap the next time you want that exact vibe.',
    },*/
    {
        icon: MoonStar,
        title: 'Date night plans',
        tagline: 'Tonight, sorted',
        body: 'We turn your answers into a personalised date night plan you can actually follow. Favourite the best ones, add them to your calendar, or export a pretty PDF to keep.',
    },
    /*    {
        icon: Mail,
        title: 'Love notes',
        tagline: 'Little messages, big feelings',
        body: 'Send short, sweet, or slightly cheeky notes to your partner throughout the day. Think passing notes in class — but grown up, and much better looking.',
    },*/
    {
        icon: Camera,
        title: 'Moments',
        tagline: 'Your shared little journal',
        body: 'Save the memories worth keeping — a photo, a line, a feeling. Moments become the story you get to reread together, later.',
    },
    {
        icon: History,
        title: 'Your timeline',
        tagline: 'Everything you two, in one feed',
        body: 'A calm, scrollable timeline of your date nights, love notes, moments and milestones. It’s your relationship’s highlight reel, always with you.',
    },
    {
        icon: CalendarHeart,
        title: 'Shared calendar',
        tagline: 'Plans that actually happen',
        body: 'Turn ideas into evenings. Add date nights, anniversaries and little rituals to a calendar you both share, so “we should do that” becomes “we did that”.',
    },
    {
        icon: Trophy,
        title: 'Achievements and streaks',
        tagline: 'Little wins, together',
        body: 'Keep your streak alive, unlock playful achievements, and celebrate showing up for each other. Because consistency is romantic, actually.',
    },
    {
        icon: ChartNoAxesColumn,
        title: 'Insights',
        tagline: 'Get to know your love language',
        body: 'Gentle, private analytics about your patterns as a couple — what you enjoy, when you connect most, what to lean into next. No dashboards from hell, just clarity.',
    },
    {
        icon: House,
        title: 'Relationship hub',
        tagline: 'One home for the two of you',
        body: 'A cosy home base bringing your favourite features into one calm, card-based screen. It’s where your relationship lives inside the app.',
    },
    {
        icon: Dices,
        title: 'Intimacy games',
        tagline: 'For the brave and the curious',
        body: 'A curated catalogue of intimacy games designed to spark conversation, connection and a little mischief. Playful, never pushy — you pick the pace.',
    },
    {
        icon: NotebookPen,
        title: 'Weekly reflection',
        tagline: 'A kind recap of your week',
        body: 'A supportive, AI-crafted weekly recap that celebrates what went well and gently suggests where to lean in next. Like a therapist, but nicer, and only takes a minute.',
    },
    /*    {
        icon: Bell,
        title: 'Notifications',
        tagline: 'Only the important nudges',
        body: 'Know when your partner finishes a questionnaire, when your compatibility is ready, or when a fresh challenge lands. No spam, no noise — just the good stuff.',
    },
    {
        icon: Search,
        title: 'Search',
        tagline: 'Find that one thing, fast',
        body: 'Search across your notes, moments, plans and answers in one place. Because the perfect memory shouldn’t take ten taps to find.',
    },*/
    /*    {
        icon: LayoutDashboard,
        title: 'Your dashboard',
        tagline: 'Everything at a glance',
        body: 'A warm, card-based home screen with your streak, your latest compatibility score, your favourites, and today’s random challenge — all ready when you open the app.',
    },*/
    {
        icon: Bot,
        title: 'Thoughtful AI',
        tagline: 'Powered by care, not creepiness',
        body: 'We use AI to write your date night plans and weekly reflections — always with safety, privacy and your relationship first. Never explicit, never pushy, always on your side.',
    },
];

const proofPoints = [
    { icon: Heart, label: 'For couples' },
    { icon: Lock, label: 'Private' },
    { icon: Sparkles, label: 'Playful' },
];

const focusRing =
    'focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none';
</script>

<template>
    <Head
        title="About Date Night — the app that helps you love more, plan less"
    />

    <div class="relative flex min-h-svh flex-col">
        <FloatingHearts />

        <header
            class="relative z-10 mx-auto flex w-full max-w-5xl items-center justify-between px-6 py-5"
        >
            <Link
                :href="home()"
                :class="['flex items-center gap-2.5 rounded-xl', focusRing]"
            >
                <div
                    class="flex h-8 w-8 items-center justify-center rounded-xl shadow-glow gradient-primary"
                >
                    <AppLogoIcon class="size-4 text-white" aria-hidden="true" />
                </div>
                <span
                    class="font-display text-lg font-semibold tracking-tight text-foreground"
                    >Date Night</span
                >
            </Link>

            <nav class="flex items-center gap-1">
                <Link
                    :href="login()"
                    :class="[
                        'inline-flex min-h-10 items-center rounded-full px-3 text-sm font-medium text-foreground transition-colors hover:text-primary',
                        focusRing,
                    ]"
                >
                    Sign in
                </Link>
            </nav>
        </header>

        <main
            class="relative z-10 mx-auto w-full max-w-md flex-1 px-6 pt-8 pb-16 sm:max-w-lg"
        >
            <div class="animate-page-fade-in space-y-12">
                <div class="text-center">
                    <p class="eyebrow">About</p>
                    <h1
                        class="mt-5 text-4xl leading-[1.08] font-medium tracking-tight text-balance text-foreground sm:text-5xl"
                    >
                        The little app that makes love a lot more
                        <span class="gradient-text italic">fun</span>
                    </h1>
                    <p
                        class="mx-auto mt-6 max-w-sm text-[15px] leading-relaxed text-pretty text-muted-foreground"
                    >
                        Date Night is a private, playful space for couples to
                        share what they want, dream up perfect evenings, and
                        keep the spark alive, one small, delightful moment at a
                        time.
                    </p>
                    <div class="mx-auto mt-10 hairline-gold max-w-xs" />
                </div>

                <section class="space-y-5">
                    <div class="space-y-1.5">
                        <p class="eyebrow">What&rsquo;s inside</p>
                        <h2
                            class="text-2xl leading-tight font-semibold text-foreground"
                        >
                            Everything for the two of you
                        </h2>
                    </div>

                    <div class="space-y-3">
                        <Collapsible
                            v-for="section in sections"
                            :key="section.title"
                            class="group card-premium"
                        >
                            <CollapsibleTrigger
                                :class="[
                                    'flex w-full items-center gap-4 rounded-3xl p-4 text-left',
                                    focusRing,
                                ]"
                            >
                                <span
                                    class="icon-tile h-12 w-12 text-primary"
                                    aria-hidden="true"
                                >
                                    <component
                                        :is="section.icon"
                                        class="size-5"
                                    />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span
                                        class="block font-display text-lg leading-tight font-semibold text-foreground"
                                    >
                                        {{ section.title }}
                                    </span>
                                    <span
                                        class="mt-0.5 block text-sm text-muted-foreground"
                                    >
                                        {{ section.tagline }}
                                    </span>
                                </span>
                                <ChevronDown
                                    class="size-5 shrink-0 text-muted-foreground transition-transform duration-200 group-data-[state=open]:rotate-180"
                                    aria-hidden="true"
                                />
                            </CollapsibleTrigger>
                            <CollapsibleContent
                                class="overflow-hidden data-[state=closed]:animate-collapsible-up data-[state=open]:animate-collapsible-down"
                            >
                                <p
                                    class="px-5 pb-5 text-sm leading-relaxed text-muted-foreground"
                                >
                                    {{ section.body }}
                                </p>
                            </CollapsibleContent>
                        </Collapsible>
                    </div>
                </section>

                <section class="card-feature p-6 sm:p-8">
                    <p class="eyebrow text-gold-soft">Begin tonight</p>
                    <h2
                        class="mt-2 text-3xl leading-tight font-medium text-balance"
                    >
                        Ready to make tonight unforgettable?
                    </h2>
                    <p class="mt-3 text-sm leading-relaxed text-white/80">
                        It&rsquo;s free to start, delightful to use, and only
                        takes a minute to set up together.
                    </p>

                    <div class="my-6 hairline-gold opacity-60" />

                    <Link
                        :href="register()"
                        class="group flex min-h-12 w-full items-center justify-between rounded-full bg-white pr-2 pl-6 text-[15px] font-semibold tracking-tight text-primary transition-all duration-200 hover:-translate-y-px focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-primary focus-visible:outline-none active:scale-[0.98]"
                    >
                        Create your account
                        <span
                            class="flex size-9 items-center justify-center rounded-full text-white transition-transform gradient-primary group-hover:translate-x-0.5"
                        >
                            <ArrowRight class="size-4" aria-hidden="true" />
                        </span>
                    </Link>
                    <Link
                        :href="login()"
                        class="mt-2 flex min-h-11 w-full items-center justify-center rounded-full text-sm font-medium text-white/90 transition-colors hover:bg-white/10 focus-visible:ring-2 focus-visible:ring-white focus-visible:outline-none"
                    >
                        I already have one
                    </Link>
                </section>

                <ul
                    class="flex items-center justify-center gap-6 text-sm text-muted-foreground"
                >
                    <li
                        v-for="point in proofPoints"
                        :key="point.label"
                        class="flex items-center gap-1.5"
                    >
                        <component
                            :is="point.icon"
                            class="size-4 text-gold"
                            aria-hidden="true"
                        />
                        {{ point.label }}
                    </li>
                </ul>
            </div>
        </main>
    </div>
</template>
