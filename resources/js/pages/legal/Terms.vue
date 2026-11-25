<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import LegalDocument from '@/components/LegalDocument.vue';
import { privacy } from '@/routes';

defineProps<{
    operator: string;
    contactEmail: string;
}>();
</script>

<template>
    <LegalDocument title="Terms of Use" updated="25 November 2026">
        <p>
            These terms are the agreement between you and
            <strong>{{ operator }}</strong> (&ldquo;we&rdquo;), who runs Date
            Night. By creating an account you agree to them. You can reach us at
            <a :href="`mailto:${contactEmail}`">{{ contactEmail }}</a
            >.
        </p>

        <h2>Who can use Date Night</h2>
        <p>
            You must be at least 18. Some content is about intimacy between
            adults. One account is for one person; keep your password to
            yourself and tell us if you think someone else has used your
            account.
        </p>

        <h2>What Date Night is</h2>
        <p>
            Date Night helps couples plan time together: questionnaires, date
            night plans, love notes, moments, a shared calendar, challenges and
            a weekly reflection. Plans and reflections are suggestions for fun.
            Some are written by an AI service and can be wrong or unsuitable for
            you; use your own judgement, especially about places, food,
            allergies and safety. Date Night is not relationship counselling,
            therapy or medical advice.
        </p>

        <h2>Your content</h2>
        <p>
            What you write and upload stays yours. You give us permission to
            store it and show it to you and to your connected partner so the app
            can work. How we handle it, and when it may be sent to an AI
            provider, is set out in the
            <Link :href="privacy()">Privacy Policy</Link>.
        </p>

        <h2>Sharing with a partner</h2>
        <p>
            Connecting with a partner shares much of what you add with them, as
            the Privacy Policy describes. Only connect with someone you trust,
            and only invite someone who wants to be invited. Either of you can
            disconnect at any time.
        </p>

        <h2>Using it fairly</h2>
        <ul>
            <li>
                Do not use Date Night to harass, threaten or pressure anyone.
            </li>
            <li>
                Do not upload anything illegal, or photos of other people
                without their agreement.
            </li>
            <li>
                Do not try to break into the service, overload it, or get at
                other people&rsquo;s data.
            </li>
        </ul>
        <p>We may suspend or close an account that breaks these rules.</p>

        <h2>Leaving</h2>
        <p>
            You can delete your account at any time under Settings, Profile. You
            can download your data first.
        </p>

        <h2>Availability and liability</h2>
        <p>
            We try to keep Date Night running and your data safe, but the
            service is provided as it is, without a promise that it will always
            be available or free of faults. As far as the law allows, we are not
            liable for losses that come from using the suggestions the app makes
            or from the service being unavailable. Nothing in these terms limits
            rights you have by law as a consumer.
        </p>

        <h2>Changes</h2>
        <p>
            We may update these terms. If a change matters, we will tell you in
            the app or by email before it applies. If you keep using Date Night
            after that, you accept the new terms.
        </p>
    </LegalDocument>
</template>
