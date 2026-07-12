export type User = {
    id: number;
    name: string;
    display_name?: string;
    email: string;
    avatar?: string;
    gender?: string;
    date_of_birth?: string;
    timezone?: string;
    partner_id?: number;
    onboarding_completed?: boolean;
    current_streak?: number;
    longest_streak?: number;
    monthly_completion_count?: number;
    email_notifications?: boolean;
    push_notifications?: boolean;
    dark_mode?: boolean;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

/* @chisel-passkeys */
export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};
/* @end-chisel-passkeys */

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
