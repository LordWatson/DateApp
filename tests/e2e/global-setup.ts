import { execSync } from 'node:child_process';
import { writeFileSync } from 'node:fs';
import { databasePath, serverEnv } from './env';

function artisan(command: string): string {
    return execSync(`php artisan ${command} --no-interaction`, {
        env: { ...process.env, ...serverEnv },
        encoding: 'utf8',
        stdio: 'pipe',
    });
}

export default function globalSetup(): void {
    // A cached config ignores the environment and would point at the real database.
    artisan('config:clear');

    const database = artisan(
        'config:show database.connections.sqlite.database',
    );

    if (!database.includes('e2e.sqlite')) {
        throw new Error(
            `Refusing to run: the app is not using the throwaway database.\n${database}`,
        );
    }

    writeFileSync(databasePath, '');
    artisan('migrate:fresh --seed --force');

    if (!/\bnull\b/.test(artisan('config:show ai.provider'))) {
        throw new Error(
            'Refusing to run: the AI provider is not the built-in null provider.',
        );
    }

    // Stale cached routes or events would hide new ones; compiled views and the app cache are left alone.
    artisan('route:clear');
    artisan('event:clear');
}
