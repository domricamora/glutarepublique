import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import {
    formatDuration,
    formatPrice,
    formatTreatmentPrice,
    isFlyer,
} from '@/lib/site';
import { book } from '@/routes';
import treatmentRoutes from '@/routes/treatments';
import type { Specialist, Treatment } from '@/types/site';

type Props = {
    treatment: Treatment;
    related: Treatment[];
    specialists: Specialist[];
};

export default function TreatmentShow({
    treatment: t,
    related,
    specialists,
}: Props) {
    const price = t.promo_price ?? t.price;

    return (
        <>
            <Head
                title={`${t.name}, ${price > 0 ? `from ${formatPrice(price)}` : 'price on consultation'}`}
            >
                <meta
                    name="description"
                    content={`${t.summary} ${formatDuration(t.duration_minutes)} session, ${price > 0 ? `from ${formatPrice(price)}` : 'price on consultation'}. Book online.`}
                />
                <script type="application/ld+json">
                    {JSON.stringify({
                        '@context': 'https://schema.org',
                        '@type': 'Service',
                        name: t.name,
                        description: t.summary,
                        category: t.category?.name,
                        offers:
                            price > 0
                                ? {
                                      '@type': 'Offer',
                                      price,
                                      priceCurrency: 'PHP',
                                  }
                                : undefined,
                    })}
                </script>
            </Head>

            <section className="grid lg:min-h-[calc(100dvh-5rem)] lg:grid-cols-2">
                <div className="relative bg-lilac">
                    {t.image &&
                        (isFlyer(t.image) ? (
                            // The clinic's own artwork carries prices and text, so it is shown
                            // whole, pinned under the header while the details scroll.
                            <figure className="flex flex-col items-center gap-3 p-6 sm:p-10 lg:sticky lg:top-20 lg:h-[calc(100dvh-5rem)] lg:justify-center">
                                <img
                                    src={t.image}
                                    alt={`${t.name}, from the clinic's promotional artwork`}
                                    className="max-h-[80dvh] w-full max-w-xl object-contain"
                                    fetchPriority="high"
                                />
                                <figcaption className="text-xs text-muted-foreground">
                                    Clinic artwork. Before and after photos are
                                    illustrative; results vary.
                                </figcaption>
                            </figure>
                        ) : (
                            <img
                                src={t.image}
                                alt=""
                                className="h-80 w-full object-cover sm:h-[28rem] lg:absolute lg:inset-0 lg:h-full"
                                fetchPriority="high"
                            />
                        ))}
                </div>
                <div className="px-4 py-12 sm:px-8 lg:px-16 lg:py-20">
                    <Link
                        href={treatmentRoutes.index().url}
                        className="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-rose-ink"
                    >
                        <ArrowLeft className="size-4" /> All treatments
                    </Link>
                    <p className="mt-8 text-sm text-rose-ink">
                        {t.category?.name}
                    </p>
                    <h1 className="mt-2 text-5xl leading-[1.05] sm:text-6xl">
                        {t.name}
                    </h1>
                    <p className="mt-5 max-w-lg text-lg text-muted-foreground">
                        {t.summary}
                    </p>

                    <dl className="numerals mt-10 grid grid-cols-3 border-y border-border">
                        <div className="py-5 pr-4">
                            <dt className="text-xs text-muted-foreground">
                                From
                            </dt>
                            <dd
                                className={
                                    price > 0
                                        ? 'mt-1 font-display text-3xl'
                                        : 'mt-2 text-sm'
                                }
                            >
                                {formatTreatmentPrice(price)}
                            </dd>
                            {t.promo_price && (
                                <dd className="text-xs text-muted-foreground">
                                    <s>{formatPrice(t.price)}</s> this month
                                </dd>
                            )}
                        </div>
                        <div className="border-l border-border py-5 pl-4">
                            <dt className="text-xs text-muted-foreground">
                                Session
                            </dt>
                            <dd className="mt-1 font-display text-3xl">
                                {formatDuration(t.duration_minutes)}
                            </dd>
                        </div>
                        <div className="border-l border-border py-5 pl-4">
                            <dt className="text-xs text-muted-foreground">
                                Usually
                            </dt>
                            <dd className="mt-2 text-sm">
                                {t.recommended_sessions}
                            </dd>
                        </div>
                    </dl>

                    <Link
                        href={book({ query: { treatment: t.id } }).url}
                        className="press mt-8 inline-flex h-13 items-center gap-2 bg-plum px-7 font-medium text-white hover:bg-violet"
                    >
                        Book {t.name} <ArrowRight className="size-4" />
                    </Link>

                    <p className="mt-10 max-w-xl leading-relaxed">
                        {t.description}
                    </p>

                    {t.price_list && t.price_list.length > 0 && (
                        <div className="mt-10 max-w-xl">
                            <h2 className="font-sans text-sm font-semibold">
                                Prices
                            </h2>
                            <ul className="numerals mt-3 divide-y divide-border border-y border-border">
                                {t.price_list.map((row) => (
                                    <li
                                        key={row.label}
                                        className="flex items-baseline justify-between gap-6 py-3 text-sm"
                                    >
                                        <span>{row.label}</span>
                                        <span className="shrink-0 text-right">
                                            {formatPrice(row.price)}
                                            {row.unit && (
                                                <span className="ml-1.5 text-xs text-muted-foreground">
                                                    {row.unit}
                                                </span>
                                            )}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                            <p className="mt-3 text-xs text-muted-foreground">
                                Prices are subject to change without prior
                                notice.
                            </p>
                        </div>
                    )}

                    <div className="mt-10 divide-y divide-border border-y border-border">
                        {[
                            ['Before your visit', t.preparation],
                            ['Aftercare', t.aftercare],
                            ['Is it right for me?', t.contraindications],
                        ]
                            .filter(([, text]) => text)
                            .map(([title, text]) => (
                                <div key={title} className="py-6">
                                    <h2 className="font-sans text-sm font-semibold">
                                        {title}
                                    </h2>
                                    <p className="mt-2 max-w-xl text-sm leading-relaxed text-muted-foreground">
                                        {text}
                                    </p>
                                </div>
                            ))}
                    </div>

                    <div className="mt-10">
                        <h2 className="font-sans text-sm font-semibold">
                            Your physicians
                        </h2>
                        <ul className="mt-4 flex flex-wrap gap-5">
                            {specialists.map((s) => (
                                <li
                                    key={s.id}
                                    className="flex items-center gap-3"
                                >
                                    {s.photo && (
                                        <img
                                            src={s.photo}
                                            alt=""
                                            className="size-12 rounded-full object-cover object-top"
                                        />
                                    )}
                                    <span className="text-sm">
                                        <span className="block font-medium">
                                            {s.name}
                                        </span>
                                        <span className="text-muted-foreground">
                                            {s.branches?.join(', ')}
                                        </span>
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>
            </section>

            {related.length > 0 && (
                <section className="gutter bg-mist py-16 lg:py-20">
                    <h2 className="text-3xl sm:text-4xl">Often paired with</h2>
                    <div className="mt-10 grid gap-8 sm:grid-cols-3">
                        {related.map((r) => (
                            <Link
                                key={r.id}
                                href={treatmentRoutes.show(r.slug).url}
                                className="group block"
                            >
                                <div className="aspect-[4/3] overflow-hidden bg-lilac">
                                    {r.image && (
                                        <img
                                            src={r.image}
                                            alt=""
                                            loading="lazy"
                                            className="h-full w-full object-cover transition-transform duration-300 ease-out group-hover:scale-[1.03]"
                                        />
                                    )}
                                </div>
                                <div className="numerals mt-4 flex items-baseline justify-between gap-4">
                                    <h3 className="text-2xl group-hover:text-rose-ink">
                                        {r.name}
                                    </h3>
                                    <span className="font-display text-xl">
                                        {formatTreatmentPrice(
                                            r.promo_price ?? r.price,
                                        )}
                                    </span>
                                </div>
                            </Link>
                        ))}
                    </div>
                </section>
            )}
        </>
    );
}
