import path from 'node:path';

export const port = Number(process.env.E2E_PORT ?? 8787);
export const baseURL = `http://127.0.0.1:${port}`;
export const databasePath = path.resolve('database', 'e2e.sqlite');

/**
 * Real environment variables win over `.env`, so the app under test never
 * reads the developer's database, cache, sessions, queue, mail or AI settings.
 */
export const serverEnv: Record<string, string> = {
    APP_ENV: 'local',
    APP_URL: baseURL,
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: databasePath,
    DB_URL: '',
    SESSION_DRIVER: 'database',
    SESSION_DOMAIN: '',
    SESSION_SECURE_COOKIE: 'false',
    CACHE_STORE: 'database',
    QUEUE_CONNECTION: 'sync',
    MAIL_MAILER: 'array',
    AI_PROVIDER: 'null',
    INERTIA_SSR_ENABLED: 'false',
    BCRYPT_ROUNDS: '4',
};

/** The couple created by `DemoCoupleSeeder`. */
export const users = {
    alex: { name: 'Alex', email: 'squatty.watson@gmail.com' },
    rimma: { name: 'Rimma', email: 'alexander.watson.work@gmail.com' },
} as const;

export type UserKey = keyof typeof users;

export const password = 'password';

export function authFile(user: UserKey): string {
    return path.resolve('tests', 'e2e', '.auth', `${user}.json`);
}
