import type { CSSProperties } from 'react';
import type { Specialist } from '@/types/site';

const stagger = (i: number) => ({ '--i': i }) as CSSProperties;

/** The people a client can meet, shared by About and Doctors. */
export function TeamGrid({ specialists }: { specialists: Specialist[] }) {
    return (
        <div className="mt-12 grid gap-10 sm:grid-cols-2 lg:grid-cols-3">
            {specialists.map((s, i) => (
                <article
                    key={s.id}
                    data-reveal
                    style={stagger(i % 3)}
                    className="flex flex-col"
                >
                    {s.photo && (
                        <img
                            src={s.photo}
                            alt={s.name}
                            loading="lazy"
                            className="aspect-[4/5] w-full object-cover"
                        />
                    )}
                    <h3 className="mt-5 text-2xl">{s.name}</h3>
                    <p className="mt-1 text-sm text-rose-ink">{s.title}</p>
                    {s.credentials && (
                        <p className="mt-1 text-xs text-muted-foreground">
                            {s.credentials}
                        </p>
                    )}
                    <p className="mt-4 text-sm leading-relaxed text-muted-foreground">
                        {s.bio}
                    </p>
                    {s.branches && s.branches.length > 0 && (
                        <p className="mt-4 text-xs text-muted-foreground">
                            {s.branches.join(' · ')}
                        </p>
                    )}
                </article>
            ))}
        </div>
    );
}
