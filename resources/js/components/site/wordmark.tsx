import { usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type { Clinic } from '@/types';

/**
 * The clinic's logo: the splash mark, the name in Conthrax (the logo's own
 * lettering, a licensed webfont) and the descriptor from the clinic's posters.
 * `name={null}` shows the mark alone.
 */
export default function Wordmark({
    name,
    descriptor,
    className,
    markClassName,
    nameClassName,
    variant = 'dark',
}: {
    name?: string | null;
    descriptor?: string;
    className?: string;
    markClassName?: string;
    nameClassName?: string;
    variant?: 'dark' | 'light';
}) {
    const { clinic } = usePage<{ clinic: Clinic }>().props;
    const light = variant === 'light';

    // Absolute urls from the server: a relative path breaks on nested pages
    // such as /treatments/{slug}.
    const logoSrc = light ? clinic.logo_light : clinic.logo;
    const logoFallback = light
        ? clinic.logo_light.replace(/\.webp$/, '.png')
        : clinic.logo_fallback;

    const mark = (
        <picture className={cn('block shrink-0', markClassName ?? 'size-11')}>
            <source srcSet={logoSrc} type="image/webp" />
            <img
                src={logoFallback}
                alt={name === null ? clinic.name : ''}
                width={256}
                height={256}
                decoding="async"
                className="h-full w-full object-contain"
            />
        </picture>
    );

    if (name === null) {
        return (
            <span className={cn('inline-flex items-center', className)}>
                {mark}
            </span>
        );
    }

    return (
        <span className={cn('inline-flex items-center gap-3', className)}>
            {mark}
            <span className="flex min-w-0 flex-col">
                <span
                    className={cn(
                        'font-brand text-[1.2rem] leading-none font-semibold whitespace-nowrap',
                        light ? 'text-white' : 'text-ocean',
                        nameClassName,
                    )}
                >
                    {name ?? clinic.short_name}
                </span>
                <span
                    className={cn(
                        'mt-1.5 font-sans text-[0.6rem] font-semibold tracking-[0.22em] whitespace-nowrap uppercase',
                        light ? 'text-soft-aqua' : 'text-royal-ink',
                    )}
                >
                    {descriptor ?? clinic.descriptor}
                </span>
            </span>
        </span>
    );
}
