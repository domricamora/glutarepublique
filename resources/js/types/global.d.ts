import type { Auth } from '@/types/auth';
import type { Clinic, Module } from '@/types/clinic';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            clinic: Clinic;
            // platform => full profile url, for the ones the clinic has set
            socials: Record<string, string>;
            hasMembership: boolean;
            // The branch selector (App\Support\BranchAccess); null when signed out.
            branchContext: {
                canSwitch: boolean;
                current: number | null;
                home: number | null;
                options: { id: number; name: string; city: string | null }[];
            } | null;
            modules: Record<Module, boolean>;
            // Fingerprint of the compiled assets, shown in the sidebar so a
            // screen that does not match the server can be identified at a glance.
            build: string;
            mediaUrl: string;
            flash: { success: string | null };
            auth: Auth;
            sidebarOpen: boolean;
            approvalsWaiting: number;
            notificationsUnread: number;
            [key: string]: unknown;
        };
    }
}
