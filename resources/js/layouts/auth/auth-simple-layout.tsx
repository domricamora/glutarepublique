import { Link, usePage } from '@inertiajs/react';
import Wordmark from '@/components/site/wordmark';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

/**
 * Every sign-in screen: the clinic photograph on the left, the form on the
 * right. On a phone the photograph steps aside and the form takes the screen.
 */
export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { mediaUrl, clinic } = usePage().props;

    return (
        <div className="grid min-h-svh bg-background lg:grid-cols-[minmax(0,1.05fr)_minmax(0,1fr)]">
            <aside className="relative hidden overflow-hidden bg-ocean lg:sticky lg:top-0 lg:block lg:h-svh">
                <img
                    src={`${mediaUrl}/reception.jpg`}
                    alt=""
                    className="absolute inset-0 h-full w-full object-cover"
                />
                <div
                    aria-hidden
                    className="absolute inset-0 bg-[linear-gradient(to_top,rgb(47_75_97/0.88),rgb(47_75_97/0.15)_55%)]"
                />
                <div className="absolute inset-x-0 bottom-0 p-12 text-white">
                    <p className="max-w-md font-display text-4xl leading-tight">
                        The whole clinic, from the first booking to the payslip.
                    </p>
                    <p className="mt-4 max-w-md text-sm text-white/80">
                        {clinic.name} clinic desk.
                    </p>
                </div>
            </aside>

            <main className="flex flex-col px-6 py-8 sm:px-10">
                <Link
                    href={home()}
                    className="press self-start"
                    aria-label="Back to the clinic website"
                >
                    <Wordmark />
                </Link>
                <div className="mx-auto flex w-full max-w-sm flex-1 flex-col justify-center gap-8 py-10">
                    <div className="space-y-2">
                        <h1 className="font-display text-4xl">{title}</h1>
                        {description && (
                            <p className="text-muted-foreground">
                                {description}
                            </p>
                        )}
                    </div>
                    {children}
                </div>
                <p className="text-center text-xs text-muted-foreground lg:text-left">
                    Platform by{' '}
                    <a
                        href="https://recursive-frog.com"
                        target="_blank"
                        rel="noopener"
                        className="text-foreground hover:text-rose-ink"
                    >
                        Recursive Frog
                    </a>
                </p>
            </main>
        </div>
    );
}
