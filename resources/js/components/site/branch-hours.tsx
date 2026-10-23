import { useEffect, useState } from 'react';
import { hoursSummary, isOpenNow } from '@/lib/site';
import { cn } from '@/lib/utils';
import type { Branch } from '@/types/site';

/** Weekly hours plus a live open or closed badge, worked out in Manila time. */
export function BranchHours({
    branch,
    className,
}: {
    branch: Pick<Branch, 'hours'>;
    className?: string;
}) {
    // Read after mount: the server render cannot know the visitor's "now".
    const [open, setOpen] = useState<boolean | null>(null);

    useEffect(() => {
        const tick = () => setOpen(isOpenNow(branch));
        tick();
        const id = window.setInterval(tick, 60_000);

        return () => window.clearInterval(id);
    }, [branch]);

    return (
        <div className={className}>
            <p
                className={cn(
                    'inline-flex min-h-4 items-center gap-2 text-xs font-medium tracking-wide uppercase',
                    open ? 'text-royal-ink' : 'text-muted-foreground',
                )}
            >
                {open !== null && (
                    <>
                        <span
                            aria-hidden
                            className={cn(
                                'size-2 rounded-full',
                                open ? 'bg-aqua' : 'bg-muted-foreground/50',
                            )}
                        />
                        {open ? 'Open now' : 'Closed now'}
                    </>
                )}
            </p>
            <dl className="mt-2 divide-y divide-border border-y border-border text-sm">
                {hoursSummary(branch.hours).map((row) => (
                    <div
                        key={row.days}
                        className="flex justify-between gap-4 py-2"
                    >
                        <dt className="text-muted-foreground">{row.days}</dt>
                        <dd>{row.time}</dd>
                    </div>
                ))}
            </dl>
        </div>
    );
}
