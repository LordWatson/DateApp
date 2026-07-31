<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ChevronDown } from '@lucide/vue';
import AnimatedBackground from '@/components/AnimatedBackground.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import FloatingHearts from '@/components/FloatingHearts.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { home, login, register } from '@/routes';

type ModuleSection = {
    emoji: string;
    title: string;
    tagline: string;
    body: string;
};

const sections: ModuleSection[] = [
/*    {
        emoji: '💫',
        title: 'A Gentle Start',
        tagline: 'Onboarding made for two',
        body: 'Set up your profile in a couple of taps and invite your partner with a single link. No awkward forms, no corporate energy — just a warm welcome for the two of you.',
    },*/
    {
        emoji: '💞',
        title: 'Link With Your Partner',
        tagline: 'Partner connection',
        body: 'One shared space, just for the two of you. Send an invite, get connected, and everything you do inside Date Night becomes a little conversation between you both.',
    },
    {
        emoji: '📝',
        title: 'Playful Questionnaires',
        tagline: 'One question at a time',
        body: 'Beautifully designed questionnaires, one question per page, that ask what you actually want tonight — mood, energy, cravings, cheeky ideas. Auto-saves as you go, so you can pause and pick it up whenever life gets in the way.',
    },
    {
        emoji: '💘',
        title: 'Your Compatibility',
        tagline: 'See where you sparkle together',
        body: 'When you both finish the latest questionnaire, we crunch the numbers and show you a compatibility percentage, the preferences you both share, and the fun little differences that keep things interesting.',
    },
/*    {
        emoji: '💾',
        title: 'Saved profiles',
        tagline: 'Bottle your favourite moods',
        body: 'Loved last Friday? Save that answer set as “Lazy Sunday”, “Anniversary”, or whatever fits. Reuse it in one tap the next time you want that exact vibe.',
    },*/
    {
        emoji: '🌙',
        title: 'Date Night Plans',
        tagline: 'Tonight, sorted',
        body: 'We turn your answers into a personalised date night plan you can actually follow. Favourite the best ones, add them to your calendar, or export a pretty PDF to keep.',
    },
/*    {
        emoji: '💌',
        title: 'Love notes',
        tagline: 'Little messages, big feelings',
        body: 'Send short, sweet, or slightly cheeky notes to your partner throughout the day. Think passing notes in class — but grown up, and much better looking.',
    },*/
    {
        emoji: '📸',
        title: 'Moments',
        tagline: 'Your shared little journal',
        body: 'Save the memories worth keeping — a photo, a line, a feeling. Moments become the story you get to reread together, later.',
    },
    {
        emoji: '🕰️',
        title: 'Your Timeline',
        tagline: 'Everything you two, in one feed',
        body: 'A calm, scrollable timeline of your date nights, love notes, moments and milestones. It’s your relationship’s highlight reel, always with you.',
    },
    {
        emoji: '📅',
        title: 'Shared Calendar',
        tagline: 'Plans that actually happen',
        body: 'Turn ideas into evenings. Add date nights, anniversaries and little rituals to a calendar you both share, so “we should do that” becomes “we did that”.',
    },
    {
        emoji: '🏆',
        title: 'Achievements & Streaks',
        tagline: 'Little wins, together',
        body: 'Keep your streak alive, unlock playful achievements, and celebrate showing up for each other. Because consistency is romantic, actually.',
    },
    {
        emoji: '📊',
        title: 'Insights',
        tagline: 'Get to know your love language',
        body: 'Gentle, private analytics about your patterns as a couple — what you enjoy, when you connect most, what to lean into next. No dashboards from hell, just clarity.',
    },
    {
        emoji: '🏡',
        title: 'Relationship Hub',
        tagline: 'One home for the two of you',
        body: 'A cosy home base bringing your favourite features into one calm, card-based screen. It’s where your relationship lives inside the app.',
    },
    {
        emoji: '🎲',
        title: 'Intimacy Games',
        tagline: 'For the brave and the curious',
        body: 'A curated catalogue of intimacy games designed to spark conversation, connection and a little mischief. Playful, never pushy — you pick the pace.',
    },
    {
        emoji: '🪞',
        title: 'Weekly Reflection',
        tagline: 'A kind recap of your week',
        body: 'A supportive, AI-crafted weekly recap that celebrates what went well and gently suggests where to lean in next. Like a therapist, but nicer, and only takes a minute.',
    },
/*    {
        emoji: '🔔',
        title: 'Notifications',
        tagline: 'Only the important nudges',
        body: 'Know when your partner finishes a questionnaire, when your compatibility is ready, or when a fresh challenge lands. No spam, no noise — just the good stuff.',
    },
    {
        emoji: '🔎',
        title: 'Search',
        tagline: 'Find that one thing, fast',
        body: 'Search across your notes, moments, plans and answers in one place. Because the perfect memory shouldn’t take ten taps to find.',
    },*/
/*    {
        emoji: '🏠',
        title: 'Your dashboard',
        tagline: 'Everything at a glance',
        body: 'A warm, card-based home screen with your streak, your latest compatibility score, your favourites, and today’s random challenge — all ready when you open the app.',
    },*/
    {
        emoji: '🤖',
        title: 'Thoughtful AI',
        tagline: 'Powered by care, not creepiness',
        body: 'We use AI to write your date night plans and weekly reflections — always with safety, privacy and your relationship first. Never explicit, never pushy, always on your side.',
    },
];
</script>

<template>
    <Head title="About Date Night — the app that helps you love more, plan less" />

    <div class="relative flex min-h-svh flex-col bg-background">
        <AnimatedBackground variant="hearts" />
        <FloatingHearts />

        <header
            class="relative z-10 flex items-center justify-between px-6 py-5"
        >
            <Link :href="home()" class="flex items-center gap-2">
                <div
                    class="flex h-9 w-9 items-center justify-center rounded-2xl shadow-md gradient-primary"
                >
                    <AppLogoIcon class="size-5 text-white" />
                </div>
                <span class="gradient-text font-semibold">Date Night</span>
            </Link>

            <nav class="flex items-center gap-3">
                <Link :href="login()">
                    <SecondaryButton size="sm" variant="outline"
                        >Sign in</SecondaryButton
                    >
                </Link>
            </nav>
        </header>

        <main
            class="relative z-10 mx-auto w-full max-w-md flex-1 px-6 pt-4 pb-12"
        >
            <div class="animate-page-fade-in space-y-8">
                <div class="space-y-4 text-center">
                    <div class="flex justify-center">
                        <div
                            class="animate-pulse-glow flex h-20 w-20 items-center justify-center rounded-3xl shadow-2xl gradient-primary"
                        >
                            <AppLogoIcon class="size-12 text-white" />
                        </div>
                    </div>

                    <h1
                        class="text-3xl leading-tight font-semibold text-foreground"
                    >
                        The little app that makes love
                        <span class="gradient-text">a lot more fun</span>
                    </h1>
                    <p class="leading-relaxed text-muted-foreground">
                        Date Night is a private, playful space for couples to
                        share what they want, dream up perfect evenings, and
                        keep the spark alive — one tiny, delightful moment at
                        a time.
                    </p>
                </div>

                <section class="space-y-3">
                    <h2
                        class="px-1 text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        What&rsquo;s inside
                    </h2>

                    <Collapsible
                        v-for="section in sections"
                        :key="section.title"
                        class="group rounded-3xl bg-card shadow-xl transition-transform duration-200 hover:scale-[1.01]"
                    >
                        <CollapsibleTrigger
                            class="flex w-full items-center gap-4 rounded-3xl px-5 py-4 text-left focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
                        >
                            <span
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-background text-2xl"
                                aria-hidden="true"
                            >
                                {{ section.emoji }}
                            </span>
                            <span class="flex-1">
                                <span
                                    class="block font-semibold text-foreground"
                                >
                                    {{ section.title }}
                                </span>
                                <span
                                    class="block text-sm text-muted-foreground"
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
                                class="px-5 pb-5 text-sm leading-relaxed text-foreground/80"
                            >
                                {{ section.body }}
                            </p>
                        </CollapsibleContent>
                    </Collapsible>
                </section>

                <section
                    class="space-y-4 rounded-3xl bg-card p-6 text-center shadow-xl"
                >
                    <h2 class="text-xl font-semibold text-foreground">
                        Ready to make tonight
                        <span class="gradient-text">unforgettable?</span>
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        It&rsquo;s free to start, delightful to use, and only
                        takes a minute to set up together.
                    </p>
                    <div class="flex flex-col gap-3">
                        <Link :href="register()" class="block">
                            <PrimaryButton size="lg" full-width
                                >Create your account</PrimaryButton
                            >
                        </Link>
                        <Link :href="login()" class="block">
                            <SecondaryButton size="lg" full-width
                                >I already have one</SecondaryButton
                            >
                        </Link>
                    </div>
                </section>

                <div
                    class="flex items-center justify-center gap-6 text-sm text-muted-foreground"
                >
                    <div class="flex items-center gap-1.5">
                        <span>💕</span>
                        <span>For couples</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span>🔒</span>
                        <span>Private</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span>✨</span>
                        <span>Fun</span>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
