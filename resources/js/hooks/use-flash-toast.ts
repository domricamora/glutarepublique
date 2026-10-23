import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import { isSitePage } from '@/lib/site';
import type { FlashToast } from '@/types/ui';

export function useFlashToast(): void {
    useEffect(() => {
        const offFlash = router.on('flash', (event) => {
            const flash = (event as CustomEvent).detail?.flash;
            const data = flash?.toast as FlashToast | undefined;

            if (!data) {
                return;
            }

            toast[data.type](data.message);
        });

        // Controllers answer with ->with('success', ...), shared as flash.success.
        // The public site prints it in its own banner, so only the office toasts it.
        const offSuccess = router.on('success', (event) => {
            const page = event.detail.page;
            const message = (
                page.props.flash as { success?: string | null } | undefined
            )?.success;

            // The kiosk shows its own large confirmation instead.
            if (
                message &&
                !isSitePage(page.component) &&
                !page.component.startsWith('kiosk/')
            ) {
                toast.success(message);
            }
        });

        return () => {
            offFlash();
            offSuccess();
        };
    }, []);
}
