import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import type { FlashToast } from '@/types/ui';

type SessionFlash = { success?: string | null; error?: string | null };

export function initializeFlashToast(): void {
    // Inertia::flash('toast', ...) from the server.
    router.on('flash', (event) => {
        const flash = (event as CustomEvent).detail?.flash;
        const data = flash?.toast as FlashToast | undefined;

        if (!data) {
            return;
        }

        toast[data.type](data.message);
    });

    // ->with('success' | 'error', ...) redirects, shared as the `flash` prop.
    // "success" fires for visits the user made, not for back/forward, so a
    // message is never replayed from history.
    router.on('success', (event) => {
        const flash = event.detail.page.props.flash as SessionFlash | undefined;

        if (flash?.success) {
            toast.success(flash.success);
        }

        if (flash?.error) {
            toast.error(flash.error);
        }
    });
}
