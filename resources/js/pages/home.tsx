import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowRight, ArrowUpRight, Gift, MapPin, Sparkles } from 'lucide-react';
import type { CSSProperties, FormEvent, RefObject } from 'react';
import { useEffect, useRef, useState } from 'react';
import BeforeAfter from '@/components/site/before-after';
import Glitter from '@/components/site/glitter';
import HeroVideo from '@/components/site/hero-video';
import { formatPrice, useReveal } from '@/lib/site';
import { cn } from '@/lib/utils';
import { about, beforeAfter, book, promotions } from '@/routes';
import leads from '@/routes/leads';
import treatmentRoutes from '@/routes/treatments';
import type { Branch, Faq, Treatment } from '@/types/site';

type Offer = {
    id: number;
    title: string;
    slug: string;
    summary: string;
    badge: string | null;
};

type Props = {
    featured: Treatment[];
    bookable: Treatment[];
    branches: Branch[];
    faqs?: Faq[];
    offers?: Offer[];
};

const stagger = (i: number) => ({ '--i': i }) as CSSProperties;

// Staff records carry no photos yet, so the home roster keeps its own
// portraits. The full team, with credentials, lives on the About page.
const doctors = [
    {
        name: 'Dr. Camille Rivera',
        role: 'Medical Director, Dermatology',
        photo: 'doctor-maya.jpg',
    },
    {
        name: 'Dr. Adrian Santos',
        role: 'Aesthetic Physician',
        photo: 'asian-doctor-male.jpg',
    },
    {
        name: 'Dr. Rhea Kapoor',
        role: 'Cosmetic Dermatologist',
        photo: 'doctor-sofia.jpg',
    },
];

export default function Home({
    featured,
    bookable,
    branches,
    faqs = [],
    offers = [],
}: Props) {
    const { mediaUrl, clinic } = usePage().props;
    const media = (file: string) => `${mediaUrl}/${file}`;
    const hero = useRef<HTMLElement>(null);
    useReveal();
    useSkinLens(hero);

    // Three signature treatments for the numbered row: a facial, a drip and a
    // laser protocol, so the row does not show the same service twice.
    const signature = [featured[0], featured[1], featured[3] ?? featured[2]]
        .filter(Boolean)
        .slice(0, 3);

    const strip = [
        {
            file: 'clinic/cebu-facial.jpg',
            alt: 'Facial treatment at the Cebu clinic',
            h: 'lg:h-[19rem]',
        },
        {
            file: 'clinic/cebu-carbon-laser.jpg',
            alt: 'Carbon laser treatment at the Cebu clinic',
            h: 'lg:h-[16rem]',
        },
        { video: true, h: 'lg:h-[22rem]' },
        {
            file: 'clinic/cebu-iv-drip.jpg',
            alt: 'Gluta drip being set up at the Cebu clinic',
            h: 'lg:h-[16rem]',
        },
        {
            file: 'clinic/cebu-reception.jpg',
            alt: 'Reception at Robinsons Galleria Cebu',
            h: 'lg:h-[19rem]',
        },
    ];

    return (
        <>
            <Head title="Glutathione, laser and skin clinic in Cebu and Laguna">
                <meta
                    name="description"
                    content="Gluta RepubliQ Aesthetics: gluta drips, 9D HIFU, Fotona laser, Hydrafacial, fillers and hair removal at clinics in San Pedro, Sta. Rosa, Bacoor and Cebu City."
                />
            </Head>

            {/* Hero: blurred skin with a clear square drifting across it. */}
            <section
                ref={hero}
                className="lens-hero relative isolate h-[min(calc(100dvh-4.5rem),54rem)] min-h-[36rem] overflow-hidden bg-ice"
            >
                <img
                    src={media('skin-closeup.jpg')}
                    alt=""
                    aria-hidden="true"
                    className="lens-hero-blur absolute inset-0 -z-20 h-full w-full object-cover"
                />
                <div
                    aria-hidden="true"
                    className="absolute inset-0 -z-10 bg-gradient-to-br from-ice/70 via-soft-aqua/25 to-ocean/55"
                />
                <div
                    aria-hidden="true"
                    className="absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_bottom_left,rgb(245_249_251/0.9),rgb(245_249_251/0.55)_30%,transparent_60%)]"
                />
                <div aria-hidden="true" className="skin-lens">
                    <img src={media('skin-closeup.jpg')} alt="" />
                </div>
                <Glitter />

                <p className="absolute top-8 left-[max(3rem,calc((100%-80rem)/2))] hidden max-w-[12rem] text-sm leading-snug text-ocean lg:block">
                    Doctor-led consultation before every plan
                </p>
                <p className="absolute top-8 right-[max(3rem,calc((100%-80rem)/2))] hidden max-w-[12rem] text-right text-sm leading-snug text-white lg:block">
                    Treatments chosen for Filipino skin
                </p>

                <div className="gutter relative flex h-full flex-col justify-end gap-8 pb-24 sm:pb-10 lg:flex-row lg:items-end lg:justify-between lg:pb-14">
                    <div className="max-w-sm">
                        <p className="text-2xl leading-tight font-medium text-ocean sm:text-3xl">
                            Advanced care.
                        </p>
                        <p className="mt-3 text-base leading-relaxed text-ocean/85">
                            Doctor-led glutathione, laser and facial treatments,
                            planned around your skin.
                        </p>
                        <div className="mt-6 flex flex-wrap gap-3">
                            <Link href={book().url} className="btn btn-solid">
                                Book consultation
                            </Link>
                            <Link
                                href={treatmentRoutes.index().url}
                                className="btn btn-outline bg-white/50"
                            >
                                Explore treatments
                            </Link>
                        </div>
                    </div>
                    <div className="order-first lg:order-none lg:text-right">
                        <p className="mb-3 font-display text-xl font-light text-white italic [text-shadow:0_1px_20px_rgb(34_50_63/0.4)] sm:text-2xl">
                            {clinic.tagline.split(/(?<=\.)\s+/).map((line) => (
                                <span
                                    key={line}
                                    className="block sm:inline sm:not-last:mr-1.5"
                                >
                                    {line}
                                </span>
                            ))}
                        </p>
                        <h1 className="lens-hero-title font-display text-[clamp(4rem,12vw,11rem)] leading-[0.86] font-light tracking-[-0.03em] text-white uppercase lg:text-right">
                            <span className="sr-only">Advanced care. </span>
                            Radiant
                            <br />
                            you
                        </h1>
                    </div>
                </div>
            </section>

            {/* Photo strip, with the clinic film in the middle. */}
            <section className="bg-pearl pt-3">
                <div className="grid grid-cols-2 items-end gap-2 sm:grid-cols-3 sm:gap-3 lg:grid-cols-5">
                    {strip.map((tile, i) => (
                        <div
                            key={i}
                            data-reveal
                            style={stagger(i)}
                            className={cn(
                                'relative h-56 overflow-hidden bg-ice sm:h-64',
                                tile.h,
                                // Phones show the film plus the two closest
                                // photos; tablets show three.
                                i === 0 && 'hidden sm:block',
                                i === 4 && 'hidden lg:block',
                                tile.video &&
                                    'order-first col-span-2 sm:order-none sm:col-span-1',
                            )}
                        >
                            {tile.video ? (
                                <HeroVideo
                                    inset={false}
                                    className="absolute inset-0"
                                />
                            ) : (
                                <img
                                    src={media(tile.file!)}
                                    alt={tile.alt}
                                    className="h-full w-full object-cover"
                                    loading={i < 3 ? 'eager' : 'lazy'}
                                />
                            )}
                        </div>
                    ))}
                </div>
            </section>

            {/* Signature treatments: numbered cards, one carrying a figure. */}
            {signature.length > 0 && (
                <section className="bg-ice/45 py-20 lg:py-28">
                    <div className="gutter">
                        <div className="flex flex-wrap items-end justify-between gap-6">
                            <h2 className="max-w-xl text-ocean">
                                Signature treatments
                            </h2>
                            <Link
                                href={treatmentRoutes.index().url}
                                className="group inline-flex items-center gap-2 text-sm font-medium text-royal-ink hover:text-ocean"
                            >
                                All treatments and prices
                                <ArrowRight className="size-4 transition-transform duration-200 ease-out group-hover:translate-x-0.5" />
                            </Link>
                        </div>

                        <div className="mt-12 grid gap-4 md:grid-cols-3">
                            {signature.map((t, i) => (
                                <Link
                                    key={t.id}
                                    href={treatmentRoutes.show(t.slug).url}
                                    data-reveal
                                    style={stagger(i)}
                                    className="group flex flex-col border border-white bg-white/70 p-7 transition-colors duration-200 ease-out hover:border-royal/30 hover:bg-white md:min-h-[26rem]"
                                >
                                    <div className="flex gap-4">
                                        <span className="font-display text-3xl font-light text-royal">
                                            {i + 1}
                                        </span>
                                        <div>
                                            <h3 className="text-ocean">
                                                {t.name}
                                            </h3>
                                            <p className="mt-2 text-sm leading-relaxed text-charcoal/70">
                                                {t.summary}
                                            </p>
                                        </div>
                                    </div>

                                    {i === 1 ? (
                                        <div className="mt-auto pt-10 text-center">
                                            <p className="font-display text-6xl font-light tracking-tight text-ocean lg:text-7xl">
                                                {formatPrice(
                                                    t.promo_price ?? t.price,
                                                )}
                                            </p>
                                            <p className="mt-3 text-sm text-charcoal/65">
                                                per session,{' '}
                                                {t.duration_minutes} minutes
                                                with a nurse on hand
                                            </p>
                                        </div>
                                    ) : (
                                        <div className="relative mt-auto aspect-[4/3] overflow-hidden bg-ice">
                                            {t.image && (
                                                <img
                                                    src={t.image}
                                                    alt=""
                                                    loading="lazy"
                                                    className="h-full w-full object-cover transition-transform duration-300 ease-out group-hover:scale-[1.03]"
                                                />
                                            )}
                                        </div>
                                    )}
                                </Link>
                            ))}
                        </div>
                    </div>
                </section>
            )}

            {/* About: photo grid beside the clinic story. */}
            <section className="bg-white py-20 lg:py-28">
                <div className="gutter">
                    <div className="grid items-center gap-12 lg:grid-cols-12">
                        <div className="grid grid-cols-2 gap-3 lg:col-span-6">
                            {[
                                ['clinic/cebu-reception-desk.jpg', 'Gluta RepubliQ reception, Cebu'],
                                [
                                    'clinic/cebu-drip-lounge.jpg',
                                    'Clients in the drip lounge',
                                ],
                                [
                                    'clinic/cebu-laser-hair-removal.jpg',
                                    'Laser hair removal in progress',
                                ],
                                ['clinic/cebu-lounge.jpg', 'Clinic lounge'],
                            ].map(([file, alt], i) => (
                                <img
                                    key={file}
                                    src={media(file)}
                                    alt={alt}
                                    loading="lazy"
                                    data-reveal
                                    style={stagger(i)}
                                    className="aspect-[5/4] w-full object-cover"
                                />
                            ))}
                        </div>
                        <div className="max-w-xl space-y-5 text-base leading-relaxed text-charcoal/80 lg:col-span-5 lg:col-start-8">
                            <h2 className="text-ocean">
                                Where science meets{' '}
                                <span className="text-royal-ink italic">
                                    aesthetics
                                </span>
                            </h2>
                            <p>
                                Gluta RepubliQ brings licensed doctors, nurses
                                and aestheticians together in four clinics, so
                                good skincare is close to home and fairly
                                priced.
                            </p>
                            <p>
                                Your first visit is a consultation. We look at
                                your skin, ask about your goals and agree a plan
                                before any treatment starts.
                            </p>
                            <Link
                                href={about().url}
                                className="group inline-flex items-center gap-2 pt-2 text-sm font-medium text-royal-ink hover:text-ocean"
                            >
                                Our story
                                <ArrowRight className="size-4 transition-transform duration-200 ease-out group-hover:translate-x-0.5" />
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            {/* Results: label, comparison, three plain facts. */}
            <section className="bg-ice/45 py-20 lg:py-28">
                <div className="gutter">
                    <h2 className="text-center text-ocean">
                        Real care, visible change
                    </h2>
                    <div className="mt-12 grid items-center gap-10 lg:grid-cols-12">
                        <div className="lg:col-span-3">
                            <h3 className="text-ocean">
                                See a treatment plan side by side
                            </h3>
                            <p className="mt-3 text-sm leading-relaxed text-charcoal/70">
                                Drag the handle to compare. Results differ from
                                person to person.
                            </p>
                            <Link
                                href={beforeAfter().url}
                                className="btn btn-outline mt-6"
                            >
                                More results
                            </Link>
                        </div>
                        <div className="lg:col-span-6">
                            <BeforeAfter
                                src={media('skin-closeup.jpg')}
                                alt="Illustrative skin comparison"
                            />
                        </div>
                        <ul className="grid grid-cols-3 gap-3 lg:col-span-3 lg:grid-cols-1">
                            {[
                                [String(branches.length || 4), 'clinics'],
                                ['6 days', 'open every week'],
                                ['1 to 1', 'doctor consultation'],
                            ].map(([figure, label]) => (
                                <li
                                    key={label}
                                    className="border border-white bg-white/70 px-4 py-5 text-center"
                                >
                                    <p className="font-display text-3xl font-light text-ocean">
                                        {figure}
                                    </p>
                                    <p className="mt-1 text-xs text-charcoal/70">
                                        {label}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>
            </section>

            {/* Specialists: portrait cards. */}
            <section
                id="doctors"
                className="scroll-mt-20 bg-white py-20 lg:py-28"
            >
                <div className="gutter">
                    <h2 className="text-center text-ocean">Our specialists</h2>
                    <div className="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {doctors.map((doc, i) => (
                            <article
                                key={doc.name}
                                data-reveal
                                style={stagger(i)}
                                className="group border border-ice bg-gradient-to-b from-ice/70 to-white text-center"
                            >
                                <div className="aspect-[4/5] overflow-hidden">
                                    <img
                                        src={media(doc.photo)}
                                        alt={doc.name}
                                        loading="lazy"
                                        className="h-full w-full object-cover object-top transition-transform duration-300 ease-out group-hover:scale-[1.02]"
                                    />
                                </div>
                                <div className="px-6 py-7">
                                    <h3 className="text-ocean">{doc.name}</h3>
                                    <p className="mt-1 text-sm text-royal-ink">
                                        {doc.role}
                                    </p>
                                    <Link
                                        href={about().url}
                                        className="btn btn-royal mt-5"
                                    >
                                        View profile
                                    </Link>
                                </div>
                            </article>
                        ))}
                    </div>
                </div>
            </section>

            {/* Offers: one filled card, one outlined. */}
            {offers.length > 0 && (
                <section className="bg-pearl py-20 lg:py-24">
                    <div className="gutter">
                        <h2 className="text-center text-ocean">
                            Special offers
                        </h2>
                        <div className="mt-10 grid gap-4 md:grid-cols-2">
                            {offers.map((offer, i) => {
                                const Icon = i === 0 ? Gift : Sparkles;
                                const filled = i === 0;

                                return (
                                    <div
                                        key={offer.id}
                                        className={cn(
                                            'flex items-center gap-6 p-7 sm:p-9',
                                            filled
                                                ? 'on-dark bg-ocean text-white'
                                                : 'border border-ice bg-white',
                                        )}
                                    >
                                        <span
                                            className={cn(
                                                'hidden size-24 shrink-0 place-items-center sm:grid',
                                                filled
                                                    ? 'bg-white/10 text-soft-aqua'
                                                    : 'border border-ice text-royal-ink',
                                            )}
                                        >
                                            <Icon
                                                className="size-9"
                                                strokeWidth={1.25}
                                            />
                                        </span>
                                        <div>
                                            {offer.badge && (
                                                <p
                                                    className={cn(
                                                        'text-sm',
                                                        filled
                                                            ? 'text-soft-aqua'
                                                            : 'text-royal-ink',
                                                    )}
                                                >
                                                    {offer.badge}
                                                </p>
                                            )}
                                            <h3
                                                className={cn(
                                                    'mt-1',
                                                    filled
                                                        ? 'text-white'
                                                        : 'text-ocean',
                                                )}
                                            >
                                                {offer.title}
                                            </h3>
                                            <p
                                                className={cn(
                                                    'mt-2 text-sm leading-relaxed',
                                                    filled
                                                        ? 'text-ice/90'
                                                        : 'text-charcoal/70',
                                                )}
                                            >
                                                {offer.summary}
                                            </p>
                                            <Link
                                                href={promotions().url}
                                                className={cn(
                                                    'btn mt-5',
                                                    filled
                                                        ? 'bg-white text-ocean hover:bg-ice'
                                                        : 'btn-royal',
                                                )}
                                            >
                                                {filled
                                                    ? 'Claim offer'
                                                    : 'Learn more'}
                                            </Link>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </section>
            )}

            {/* Visit: find a clinic beside the enquiry form. */}
            <section className="bg-ice/45 py-20 lg:py-24">
                <div className="gutter grid gap-4 lg:grid-cols-2">
                    <div className="border border-white bg-white/70 p-7 sm:p-9">
                        <p className="text-sm text-royal-ink">Visit us</p>
                        <h2 className="mt-1 text-[clamp(28px,3vw,40px)] text-ocean">
                            Find a clinic near you
                        </h2>
                        <ul className="mt-6 grid gap-3 sm:grid-cols-2">
                            {branches.map((b) => (
                                <li key={b.id}>
                                    <Link
                                        href={
                                            book({
                                                query: { branch: b.id },
                                            }).url
                                        }
                                        className="group flex h-full flex-col border border-ice bg-white p-4 transition-colors duration-200 ease-out hover:border-royal/40"
                                    >
                                        <span className="flex items-center justify-between font-medium text-ocean">
                                            {b.name}
                                            <ArrowUpRight className="size-4 text-royal-ink transition-transform duration-200 ease-out group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
                                        </span>
                                        <span className="mt-1 flex gap-1.5 text-xs leading-snug text-charcoal/65">
                                            <MapPin className="mt-px size-3.5 shrink-0" />
                                            {b.address}
                                        </span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>
                    <EnquiryCard bookable={bookable} />
                </div>
            </section>

            {faqs.length > 0 && <FaqSection faqs={faqs} />}
        </>
    );
}

/**
 * Moves the hero's clear square. With a mouse over the hero the square eases
 * towards the pointer; otherwise it wanders a slow loop over the face. Both
 * feed one eased position, so leaving the hero glides back into the drift
 * instead of jumping. The position is written as two CSS variables on the
 * section (no React state, so nothing re-renders per frame), and the loop
 * sleeps while the hero is off screen. Reduced motion keeps the pointer
 * following but drops the drift.
 */
function useSkinLens(ref: RefObject<HTMLElement | null>) {
    useEffect(() => {
        const el = ref.current;

        if (!el) {
            return;
        }

        const still = window.matchMedia(
            '(prefers-reduced-motion: reduce)',
        ).matches;
        let pointer: { x: number; y: number } | null = null;
        let pos: { x: number; y: number } | null = null;
        let frame = 0;
        let visible = true;
        const start = performance.now();

        // Normalised centre and reach of the drift: higher up and tighter on
        // narrow screens, where the copy fills the lower half.
        const path = () =>
            el.clientWidth < 768
                ? { cx: 0.5, cy: 0.3, rx: 0.12, ry: 0.06 }
                : { cx: 0.54, cy: 0.48, rx: 0.15, ry: 0.11 };

        const tick = (now: number) => {
            frame = 0;
            const w = el.clientWidth;
            const h = el.clientHeight;
            const half =
                (el.querySelector<HTMLElement>('.skin-lens')?.offsetWidth ??
                    0) / 2;
            const t = (now - start) / 1000;
            const p = path();
            const drift = still
                ? { x: p.cx * w, y: (p.cy - p.ry) * h }
                : {
                      x: (p.cx + p.rx * Math.sin((t * 2 * Math.PI) / 24)) * w,
                      y:
                          (p.cy + p.ry * Math.sin((t * 2 * Math.PI) / 17 + 1)) *
                          h,
                  };
            const target = pointer ?? drift;
            pos ??= { ...drift };

            // Quick when following the hand, unhurried when returning.
            const ease = still && pointer ? 1 : pointer ? 0.14 : 0.035;
            pos.x += (target.x - pos.x) * ease;
            pos.y += (target.y - pos.y) * ease;
            pos.x = Math.min(Math.max(pos.x, half), w - half);
            pos.y = Math.min(Math.max(pos.y, half), h - half);

            el.style.setProperty('--lx', `${pos.x}px`);
            el.style.setProperty('--ly', `${pos.y}px`);

            if (visible && (!still || pointer)) {
                frame = requestAnimationFrame(tick);
            }
        };

        const run = () => {
            if (!frame) {
                frame = requestAnimationFrame(tick);
            }
        };

        const move = (e: PointerEvent) => {
            if (e.pointerType !== 'mouse') {
                return;
            }

            const box = el.getBoundingClientRect();
            pointer = { x: e.clientX - box.left, y: e.clientY - box.top };
            run();
        };

        const leave = () => {
            pointer = null;
            run();
        };

        const observer = new IntersectionObserver(([entry]) => {
            visible = entry.isIntersecting;

            if (visible) {
                run();
            }
        });

        el.addEventListener('pointermove', move);
        el.addEventListener('pointerleave', leave);
        observer.observe(el);
        run();

        return () => {
            cancelAnimationFrame(frame);
            observer.disconnect();
            el.removeEventListener('pointermove', move);
            el.removeEventListener('pointerleave', leave);
        };
    }, [ref]);
}

function FaqSection({ faqs }: { faqs: Faq[] }) {
    const [open, setOpen] = useState<number | null>(0);

    return (
        <section className="bg-white py-20 lg:py-24">
            <div className="gutter grid gap-10 lg:grid-cols-12">
                <h2 className="text-ocean lg:col-span-4">
                    Questions, answered
                </h2>
                <div className="divide-y divide-ice border-y border-ice lg:col-span-7 lg:col-start-6">
                    {faqs.slice(0, 5).map((faq, i) => {
                        const isOpen = open === i;

                        return (
                            <div key={faq.id}>
                                <button
                                    type="button"
                                    aria-expanded={isOpen}
                                    onClick={() => setOpen(isOpen ? null : i)}
                                    className="flex w-full items-center justify-between gap-6 py-5 text-left text-base font-medium text-ocean transition-colors hover:text-royal-ink"
                                >
                                    {faq.question}
                                    <span
                                        aria-hidden="true"
                                        className={cn(
                                            'text-xl font-light text-royal-ink transition-transform duration-200 ease-out',
                                            isOpen && 'rotate-45',
                                        )}
                                    >
                                        +
                                    </span>
                                </button>
                                {isOpen && (
                                    <p className="pb-6 text-sm leading-relaxed text-charcoal/75">
                                        {faq.answer}
                                    </p>
                                )}
                            </div>
                        );
                    })}
                </div>
            </div>
        </section>
    );
}

function EnquiryCard({ bookable }: { bookable: Treatment[] }) {
    const form = useForm({
        form: 'enquiry',
        first_name: '',
        phone: '',
        email: '',
        treatment_id: '',
        message: '',
        privacy_consent: true,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(leads.store().url, {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    const field =
        'h-11 w-full border border-ice bg-white px-3 text-sm text-charcoal transition-colors placeholder:text-charcoal/50 focus:border-royal focus:outline-none';

    return (
        <form
            onSubmit={submit}
            noValidate
            className="border border-white bg-white/70 p-7 sm:p-9"
        >
            <p className="text-sm text-royal-ink">Ask us anything</p>
            <h2 className="mt-1 text-[clamp(28px,3vw,40px)] text-ocean">
                Send an enquiry
            </h2>
            <div className="mt-6 grid gap-4 sm:grid-cols-2">
                <label className="grid gap-1.5 text-xs font-medium text-charcoal/80">
                    Name
                    <input
                        required
                        autoComplete="name"
                        value={form.data.first_name}
                        onChange={(e) =>
                            form.setData('first_name', e.target.value)
                        }
                        className={field}
                    />
                    {form.errors.first_name && (
                        <span className="text-destructive">
                            {form.errors.first_name}
                        </span>
                    )}
                </label>
                <label className="grid gap-1.5 text-xs font-medium text-charcoal/80">
                    Mobile number
                    <input
                        type="tel"
                        autoComplete="tel"
                        value={form.data.phone}
                        onChange={(e) => form.setData('phone', e.target.value)}
                        className={field}
                    />
                    {form.errors.phone && (
                        <span className="text-destructive">
                            {form.errors.phone}
                        </span>
                    )}
                </label>
                <label className="grid gap-1.5 text-xs font-medium text-charcoal/80">
                    Email
                    <input
                        type="email"
                        autoComplete="email"
                        value={form.data.email}
                        onChange={(e) => form.setData('email', e.target.value)}
                        className={field}
                    />
                    {form.errors.email && (
                        <span className="text-destructive">
                            {form.errors.email}
                        </span>
                    )}
                </label>
                <label className="grid gap-1.5 text-xs font-medium text-charcoal/80">
                    Treatment
                    <select
                        value={form.data.treatment_id}
                        onChange={(e) =>
                            form.setData('treatment_id', e.target.value)
                        }
                        className={field}
                    >
                        <option value="">Not sure yet</option>
                        {bookable.map((t) => (
                            <option key={t.id} value={t.id}>
                                {t.name}
                            </option>
                        ))}
                    </select>
                </label>
                <label className="grid gap-1.5 text-xs font-medium text-charcoal/80 sm:col-span-2">
                    Message
                    <textarea
                        rows={3}
                        value={form.data.message}
                        onChange={(e) =>
                            form.setData('message', e.target.value)
                        }
                        className={cn(field, 'h-auto resize-none py-2.5')}
                    />
                </label>
            </div>
            <button
                type="submit"
                disabled={form.processing}
                className="btn btn-solid mt-5 w-full sm:w-auto"
            >
                {form.processing ? 'Sending...' : 'Send enquiry'}
            </button>
        </form>
    );
}
