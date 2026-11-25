<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import LegalDocument from '@/components/LegalDocument.vue';
import { terms } from '@/routes';

defineProps<{
    operator: string;
    contactEmail: string;
    /** The AI provider the site is set to use right now; null when AI is switched off. */
    aiProvider: string | null;
}>();
</script>

<template>
    <LegalDocument title="Privacy Policy" updated="25 November 2026">
        <p>
            Date Night is a private app for couples. This policy explains what
            we store about you, who else can see it, and the choices you have.
            Date Night is run by
            <strong>{{ operator }}</strong> (&ldquo;we&rdquo;), who is
            responsible for your data. Questions and requests go to
            <a :href="`mailto:${contactEmail}`">{{ contactEmail }}</a
            >.
        </p>

        <h2>What we store</h2>
        <ul>
            <li>
                <strong>Your account:</strong> name, display name, email
                address, password (stored only as a hash), and, if you give
                them, gender, date of birth, an emoji avatar and your time zone.
                If you turn them on: two-factor and passkey details.
            </li>
            <li>
                <strong>What you put in:</strong> your questionnaire answers
                (some questionnaires are about intimacy and are sensitive), the
                date night plans made from them, love notes, moments (title,
                description, mood and photos), calendar events, saved answer
                profiles and weekly reflections.
            </li>
            <li>
                <strong>A rough location, only if you give one</strong> for a
                date out: the place name, town, region, country, coordinates and
                how far you are willing to travel.
            </li>
            <li>
                <strong>Your partner link:</strong> who you are connected to,
                and the email address of anyone you invite.
            </li>
            <li>
                <strong>How you use the app:</strong> streaks, challenges and
                achievements, notifications, an activity history, the times you
                accepted the terms and answered the AI question below, and your
                current sessions (including IP address and browser).
            </li>
            <li>
                <strong>Technical logs</strong> kept for a limited time to find
                faults. A failed AI request can leave the provider&rsquo;s reply
                in these logs.
            </li>
        </ul>
        <p>
            We do not sell your data, show adverts, or use analytics or tracking
            services.
        </p>

        <h2>What your partner can see</h2>
        <p>
            Date Night is built to be shared. While you are connected, your
            partner can see the date night plans you made together, your answers
            to a questionnaire once you have both finished it and your
            compatibility score, your moments and calendar events, the love
            notes you send them, your daily challenge and streak progress, and
            the shared weekly reflection, which mentions what each of you did
            that week. Only you can change or delete your own records. After a
            disconnect, plans made together stay in both histories, love notes
            stay with sender and recipient, and your moments and calendar events
            are no longer visible to the other person.
        </p>

        <h2>AI processing, only with your agreement</h2>
        <p>
            Date Night can use an AI service to write your date night plans and
            weekly reflections. To do that it sends the relevant content to a
            third-party AI provider: your questionnaire answers and rough
            location for a plan; for a weekly reflection, the first names of you
            and your partner, the text of recent love notes, moment titles and
            descriptions, calendar event titles and plan details. Your email
            address and password are never sent.
        </p>
        <ul>
            <li>
                <strong>Nothing is sent unless you agree.</strong> We ask once,
                and you can change your answer at any time under Settings,
                Profile. Until you agree, the app writes plans and reflections
                itself from fixed rules, without AI.
            </li>
            <li>
                When content from both partners would be sent (a plan for two,
                the shared weekly reflection), <strong>both</strong> of you must
                have agreed.
            </li>
            <li>
                The provider is Anthropic (Claude) by default. The site can
                instead be set to use DeepSeek.
                <template v-if="aiProvider">
                    Right now it uses <strong>{{ aiProvider }}</strong
                    >.
                </template>
                <template v-else>Right now AI is switched off.</template>
                These companies process the content on their own servers, which
                may be outside your country (Anthropic is based in the United
                States, DeepSeek in China), under their own terms.
            </li>
            <li>
                We keep a record of each AI request (which feature, when, how
                large, whether it worked) but not the text that was sent.
            </li>
        </ul>

        <h2>Other services we rely on</h2>
        <ul>
            <li>
                <strong>Email:</strong> verification, password reset, invitation
                and notification emails are delivered by Resend, an email
                delivery service, which receives the recipient&rsquo;s address
                and the content of the email.
            </li>
            <li>
                <strong>Place lookup:</strong> if you search for a place or use
                your device&rsquo;s location for a date out, your browser sends
                that search or position to OpenStreetMap&rsquo;s Nominatim
                service to turn it into a place name.
            </li>
            <li>
                <strong>Fonts:</strong> the app loads its fonts from Google
                Fonts, so your browser contacts Google when a page loads.
            </li>
            <li>
                <strong>Hosting:</strong> the app and its database run on
                servers rented by us from a hosting provider.
            </li>
        </ul>

        <h2>Cookies</h2>
        <p>
            We only set cookies the app needs to work: one that keeps you signed
            in (and a second if you tick &ldquo;Remember me&rdquo;), one that
            protects forms against forgery, and ones that remember your light or
            dark appearance choice. There are no advertising or tracking
            cookies, so there is no cookie banner.
        </p>

        <h2>Your choices</h2>
        <ul>
            <li>
                <strong>See and correct</strong> your details under Settings.
            </li>
            <li>
                <strong>Download your data:</strong> Settings, Profile,
                &ldquo;Download my data&rdquo; prepares one file with your
                records. It stays available for 24 hours.
            </li>
            <li>
                <strong>Delete your account:</strong> Settings, Profile. This
                removes your account and what you own: answers, moments and
                their photos, calendar events, and the love notes between you
                and your partner. Date night plans you made together stay in
                your partner&rsquo;s history.
            </li>
            <li>
                <strong>Turn AI on or off</strong> and
                <strong>turn email notifications off</strong> under Settings.
            </li>
            <li>
                Depending on where you live you may have further rights, such as
                objecting to processing or complaining to your data protection
                authority. Write to
                <a :href="`mailto:${contactEmail}`">{{ contactEmail }}</a>
                and we will help.
            </li>
        </ul>

        <h2>How long we keep it</h2>
        <p>
            We keep your data for as long as you have an account. Deleting your
            account removes it as described above; backups and logs are
            overwritten on their normal cycle.
        </p>

        <h2>Age</h2>
        <p>Date Night is for adults. Do not use it if you are under 18.</p>

        <h2>Changes</h2>
        <p>
            If this policy changes in a way that matters, we will tell you in
            the app or by email before the change applies. See also our
            <Link :href="terms()">Terms</Link>.
        </p>
    </LegalDocument>
</template>
