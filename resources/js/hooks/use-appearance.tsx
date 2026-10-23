/**
 * The platform is light only (owner's call): no dark mode, no switcher, and
 * the visitor's system setting is ignored. Kept as a hook so the components
 * that ask (the toaster, the two-factor QR code) still get an answer.
 */
export type ResolvedAppearance = 'light';
export type Appearance = ResolvedAppearance;

export type UseAppearanceReturn = {
    readonly appearance: Appearance;
    readonly resolvedAppearance: ResolvedAppearance;
    readonly updateAppearance: (mode: Appearance) => void;
};

export function initializeTheme(): void {
    if (typeof document === 'undefined') {
        return;
    }

    // Undo any dark choice saved before the switcher was removed.
    document.documentElement.classList.remove('dark');
    document.documentElement.style.colorScheme = 'light';

    try {
        localStorage.removeItem('appearance');
    } catch {
        // Private browsing can refuse storage; light is applied either way.
    }

    document.cookie = 'appearance=light;path=/;max-age=31536000;SameSite=Lax';
}

export function useAppearance(): UseAppearanceReturn {
    return {
        appearance: 'light',
        resolvedAppearance: 'light',
        updateAppearance: () => {},
    } as const;
}
