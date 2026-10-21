import { usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type { Clinic } from '@/types';

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

    const split =
        name === undefined
            ? clinic.name.startsWith(clinic.short_name)
                ? {
                      main: clinic.short_name,
                      sub:
                          clinic.name.slice(clinic.short_name.length).trim() ||
                          'AESTHETICS',
                  }
                : { main: clinic.short_name || clinic.name, sub: 'AESTHETICS' }
            : {
                  main: name ?? clinic.short_name,
                  sub: descriptor ?? 'AESTHETICS',
              };

    // Absolute urls from the server: a relative path breaks on nested pages
    // such as /treatments/{slug}.
    const logoSrc = variant === 'light' ? clinic.logo_light : clinic.logo;
    const logoFallback =
        variant === 'light'
            ? clinic.logo_light.replace(/\.webp$/, '.png')
            : clinic.logo_fallback;

    const mark = (
        <picture className={cn('block shrink-0', markClassName ?? 'size-11')}>
            <source srcSet={logoSrc} type="image/webp" />
            <img
                src={logoFallback}
                alt={split.main ? '' : clinic.name}
                width={256}
                height={256}
                decoding="async"
                className="h-full w-full object-contain"
            />
        </picture>
    );

    if (!split.main) {
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
                        'font-brand text-[1.45rem] leading-none font-semibold tracking-tight',
                        variant === 'light' ? 'text-white' : 'text-ocean',
                        nameClassName,
                    )}
                >
                    {split.main}
                </span>
                <span
                    className={cn(
                        'mt-1 font-sans text-[0.625rem] font-bold tracking-[0.22em] uppercase',
                        variant === 'light'
                            ? 'text-soft-aqua'
                            : 'text-royal-ink',
                    )}
                >
                    {split.sub}
                </span>
            </span>
        </span>
    );
}
