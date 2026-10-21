import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import type { CSSProperties } from 'react';
import BeforeAfter from '@/components/site/before-after';
import { useReveal } from '@/lib/site';
import { cn } from '@/lib/utils';
import { book } from '@/routes';
import treatmentRoutes from '@/routes/treatments';
import type { CompareCase } from '@/types/site';

const stagger = (i: number) => ({ '--i': i }) as CSSProperties;

// Before and after pairs cropped from the clinic's own published posts
// (glutarepubliq.com). Slug links to the treatment where the menu names it.
const gallery = [
    { file: 'aqua-lips.jpg', title: 'Aqua Lips', slug: 'aqua-lips' },
    { file: 'misty-brows.jpg', title: 'Misty Brows', slug: 'misty-brows' },
    {
        file: 'hiko-nose-lift.jpg',
        title: 'Hiko Nose Lift',
        slug: 'hiko-nose-lift',
    },
    // A wide pair: spans two columns on desktop so the row stays full.
    {
        file: 'chin-filler.jpg',
        title: 'Chin filler',
        slug: 'fillers',
        wide: true,
    },
    { file: 'melasma.jpg', title: 'Melasma treatment', slug: null },
];

/**
 * Comparison gallery: the clinic's own published client photos, then stock
 * portraits with a simulated "before", labelled as an illustration and never
 * as a result (plan.md §18, §83).
 */
export default function BeforeAfterPage({ cases }: { cases: CompareCase[] }) {
    const { mediaUrl } = usePage().props;
    useReveal();

    return (
        <>
            <Head title="Before and after">
                <meta
                    name="description"
                    content="Before and after photos from Gluta RepubliQ clients, illustrated comparisons for common treatments, and how clinical photographs are handled with written consent."
                />
            </Head>

            <section className="on-dark gutter bg-plum pt-16 pb-14 text-white lg:pt-24">
                <div className="mx-auto max-w-7xl">
                    <p className="eyebrow">Before and after</p>
                    <h1 className="mt-4 max-w-4xl text-5xl leading-[1.05] sm:text-7xl">
                        What changes, and{' '}
                        <em className="text-champagne">what to expect.</em>
                    </h1>
                    <p className="mt-6 max-w-2xl text-lg text-lilac">
                        Client photos from our own gallery, then illustrated
                        comparisons for common treatments. Your clinician will
                        show you more relevant cases at your consultation.
                    </p>
                </div>
            </section>

            <section className="gutter pt-14">
                <h2 data-reveal className="text-3xl sm:text-4xl">
                    From our clinics
                </h2>
                <div className="mt-8 grid gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
                    {gallery.map((item, i) => (
                        <figure
                            key={item.file}
                            data-reveal
                            style={stagger(i % 3)}
                            className={cn('wide' in item && 'lg:col-span-2')}
                        >
                            <img
                                src={`${mediaUrl}/results/${item.file}`}
                                alt={`${item.title}, before and after`}
                                loading="lazy"
                                className={cn(
                                    'aspect-[4/3] w-full bg-mist object-contain',
                                    'wide' in item && 'lg:aspect-[8/3]',
                                )}
                            />
                            <div className="mt-2 flex justify-between text-xs text-muted-foreground">
                                <span>Before</span>
                                <span>After</span>
                            </div>
                            <figcaption className="mt-2 text-lg">
                                {item.slug ? (
                                    <Link
                                        href={
                                            treatmentRoutes.show(item.slug).url
                                        }
                                        className="hover:text-rose-ink"
                                    >
                                        {item.title}
                                    </Link>
                                ) : (
                                    item.title
                                )}
                            </figcaption>
                        </figure>
                    ))}
                </div>
                <p className="mt-8 text-sm text-muted-foreground">
                    Photos published by Gluta RepubliQ. Individual results vary
                    with your skin, history and aftercare.
                </p>
            </section>

            <section className="gutter py-14">
                <div className="grid gap-6 border-y border-border py-6 sm:grid-cols-3">
                    <p className="text-sm text-muted-foreground">
                        Every comparison is labelled illustrative.
                    </p>
                    <p className="text-sm text-muted-foreground">
                        Real photographs stay in your clinical record only.
                    </p>
                    <p className="text-sm text-muted-foreground">
                        Results vary with your skin, history and aftercare.
                    </p>
                </div>
            </section>

            <section className="gutter pb-16">
                <div className="grid gap-12 lg:grid-cols-2">
                    {cases.map((item, i) => (
                        <article
                            key={item.id}
                            data-reveal
                            style={stagger(i % 2)}
                        >
                            {item.image && (
                                <BeforeAfter
                                    src={item.image}
                                    alt={`Portrait used to illustrate ${item.name.toLowerCase()} in this demo`}
                                />
                            )}
                            <h2 className="mt-4 text-2xl">
                                <Link
                                    href={treatmentRoutes.show(item.slug).url}
                                    className="hover:text-rose-ink"
                                >
                                    {item.name}
                                </Link>
                            </h2>
                            {item.category && (
                                <p className="mt-1 text-xs tracking-wide text-muted-foreground uppercase">
                                    {item.category}
                                </p>
                            )}
                            <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                                {item.summary}
                            </p>
                        </article>
                    ))}
                </div>
            </section>

            <section className="gutter bg-mist py-20 lg:py-24">
                <div className="grid gap-12 lg:grid-cols-12">
                    <div className="lg:col-span-5">
                        <h2 data-reveal className="text-3xl sm:text-4xl">
                            How we handle photographs
                        </h2>
                    </div>
                    <ul className="divide-y divide-border border-y border-border lg:col-span-7">
                        {[
                            [
                                'Consent first',
                                'Clinical photographs are taken only with your written consent, and you can decline at any point without it affecting your care.',
                            ],
                            [
                                'Kept with your record',
                                'Images sit in your clinical record, not in a marketing folder, and access is logged.',
                            ],
                            [
                                'Never published without approval',
                                'Nothing is published on this website from a real patient without a separate, specific consent that you can withdraw later.',
                            ],
                            [
                                'Judged with your clinician',
                                'Progress is best reviewed side by side at your follow up, in the same light and at the same angle.',
                            ],
                        ].map(([title, body]) => (
                            <li key={title} className="py-6">
                                <h3 className="text-xl">{title}</h3>
                                <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                                    {body}
                                </p>
                            </li>
                        ))}
                    </ul>
                </div>
            </section>

            <section className="gutter py-16">
                <div className="flex flex-wrap items-end justify-between gap-6 border-t border-border pt-10">
                    <h2 className="max-w-xl text-3xl sm:text-4xl">
                        Ask to see relevant cases at your consultation.
                    </h2>
                    <Link
                        href={book().url}
                        className="press inline-flex items-center gap-2 bg-plum px-6 py-3 text-sm font-medium text-white hover:bg-plum-deep"
                    >
                        Book a consultation <ArrowRight className="size-4" />
                    </Link>
                </div>
            </section>
        </>
    );
}
