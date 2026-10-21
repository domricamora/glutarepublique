import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowRight, ArrowUpRight, Gift, MapPin, Sparkles } from 'lucide-react';
import type { CSSProperties, FormEvent } from 'react';
import { useState } from 'react';
import BeforeAfter from '@/components/site/before-after';
import HeroVideo from '@/components/site/hero-video';
import { formatPrice, useReveal } from '@/lib/site';
import { cn } from '@/lib/utils';
import { about, beforeAfter, book, membership, promotions } from '@/routes';
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
        photo: 'doctor-1.jpg',
    },
];

export default function Home({
    featured,
    bookable,
    branches,
    faqs = [],
    offers = [],
}: Props) {
    const { mediaUrl } = usePage().props;
    const media = (file: string) => `${mediaUrl}/${file}`;
    useReveal();

    // Three signature treatments for the numbered row: a facial, a drip and a
    // laser protocol, so the row does not show the same service twice.
    const signature = [featured[0], featured[1], featured[3] ?? featured[2]]
        .filter(Boolean)
        .slice(0, 3);

    const strip = [
        {
            file: 'asian-hydraglow-facial.jpg',
            alt: 'HydraGlow facial in progress',
            h: 'lg:h-[19rem]',
        },
        {
            file: 'hero-treatment.jpg',
            alt: 'Laser treatment at the clinic',
            h: 'lg:h-[16rem]',
        },
        { video: true, h: 'lg:h-[22rem]' },
        {
            file: 'asian-gluta-drip.jpg',
            alt: 'Client relaxing during a glutathione drip',
            h: 'lg:h-[16rem]',
        },
        { file: 'facial.jpg', alt: 'Facial mask treatment', h: 'lg:h-[19rem]' },
    ];

    return (
        <>
            <Head title="Gluta RepubliQ Aesthetics | Advanced Care. Radiant You.">
                <meta
                    name="description"
                    content="Gluta RepubliQ Aesthetics: glutathione therapy, laser rejuvenation, HydraGlow facials and physician-led skincare in Cebu, San Pedro, Sta. Rosa and Bacoor."
                />
            </Head>

            {/* Hero: centered serif statement over a staggered photo strip. */}
            <section className="relative overflow-hidden bg-pearl pt-14 sm:pt-20">
                <div className="gutter text-center">
                    <h1 className="mx-auto max-w-4xl text-ocean">
                        Advanced care.
                        <br />
                        <span className="text-royal italic">Radiant you.</span>
                    </h1>
                    <p className="mx-auto mt-6 max-w-md text-base leading-relaxed text-charcoal/75 sm:text-lg">
                        Doctor-led glutathione, laser and facial treatments,
                        planned around your skin.
                    </p>
                    <div className="mt-8 flex flex-wrap items-center justify-center gap-3">
                        <Link href={book().url} className="btn btn-solid">
                            Book consultation
                        </Link>
                        <Link
                            href={treatmentRoutes.index().url}
                            className="btn btn-outline"
                        >
                            Explore treatments
                        </Link>
                    </div>
                </div>

                <div className="mt-14 grid grid-cols-2 items-end gap-2 sm:grid-cols-3 sm:gap-3 lg:mt-16 lg:grid-cols-5">
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
                                className="group inline-flex items-center gap-2 text-sm font-medium text-royal hover:text-ocean"
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
                                        <span className="font-display text-3xl font-light text-aqua">
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

            {/* Statement band: framed portrait over its own blurred field. */}
            <section className="relative isolate overflow-hidden">
                <img
                    src={media('skin-closeup.jpg')}
                    alt=""
                    aria-hidden="true"
                    className="absolute inset-0 -z-20 h-full w-full scale-110 object-cover blur-2xl"
                />
                <div
                    aria-hidden="true"
                    className="absolute inset-0 -z-10 bg-gradient-to-br from-ice/85 via-soft-aqua/55 to-royal/45"
                />
                <div className="gutter grid gap-10 py-16 lg:grid-cols-12 lg:py-24">
                    <div className="flex flex-col justify-end gap-6 lg:col-span-4">
                        <p className="max-w-xs text-lg leading-snug text-ocean">
                            Every plan starts with a doctor looking at your
                            skin, not a menu.
                        </p>
                        <div className="flex flex-wrap gap-2">
                            <Link
                                href={about().url}
                                className="btn btn-outline bg-white/40"
                            >
                                How we work
                            </Link>
                            <Link
                                href={membership().url}
                                className="btn btn-outline bg-white/40"
                            >
                                Membership
                            </Link>
                        </div>
                    </div>

                    <div className="relative lg:col-span-8">
                        <div className="relative mx-auto w-full max-w-sm lg:mx-0 lg:ml-[12%]">
                            <img
                                src={media('skin-closeup.jpg')}
                                alt="Close-up of healthy, even skin"
                                loading="lazy"
                                className="aspect-[4/5] w-full border border-white/70 object-cover"
                            />
                            <span className="absolute top-6 -left-44 hidden w-40 text-right text-xs leading-snug text-ocean xl:block">
                                Doctor-led consultation
                                <span className="absolute top-1/2 -right-4 h-px w-4 bg-ocean/40" />
                            </span>
                            <span className="absolute top-6 -right-44 hidden w-40 text-xs leading-snug text-ocean xl:block">
                                <span className="absolute top-1/2 -left-4 h-px w-4 bg-ocean/40" />
                                Plans built for Filipino skin
                            </span>
                            <Link
                                href={treatmentRoutes.index().url}
                                className="press absolute -right-4 -bottom-4 grid size-24 place-items-center bg-ocean text-xs font-medium tracking-[0.18em] text-white uppercase transition-colors hover:bg-royal sm:-right-8"
                            >
                                Discover
                            </Link>
                        </div>
                        <p
                            aria-hidden="true"
                            className="pointer-events-none mt-10 font-display text-[clamp(3.5rem,11vw,10rem)] leading-[0.88] font-light tracking-[-0.03em] text-white uppercase lg:absolute lg:right-0 lg:bottom-0 lg:mt-0 lg:text-right"
                        >
                            Radiant
                            <br />
                            you
                        </p>
                    </div>
                </div>
            </section>

            {/* About: photo grid beside the clinic story. */}
            <section className="bg-white py-20 lg:py-28">
                <div className="gutter">
                    <p className="eyebrow text-center">About us</p>
                    <div className="mt-10 grid items-center gap-12 lg:grid-cols-12">
                        <div className="grid grid-cols-2 gap-3 lg:col-span-6">
                            {[
                                [
                                    'about-clinic.jpg',
                                    'Gluta RepubliQ reception',
                                ],
                                [
                                    'asian-dermatologist-consultation.jpg',
                                    'Doctor in a consultation room',
                                ],
                                [
                                    'asian-laser-treatment.jpg',
                                    'Laser treatment in progress',
                                ],
                                ['reception.jpg', 'Clinic lounge'],
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
                                <span className="text-royal italic">
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
                                className="group inline-flex items-center gap-2 pt-2 text-sm font-medium text-royal hover:text-ocean"
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
                                    <p className="mt-1 text-sm text-royal">
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
                        <p className="eyebrow text-center">Special offers</p>
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
                                                    : 'border border-ice text-royal',
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
                                                            : 'text-royal',
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
                                                        ? 'text-ice/85'
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
                        <p className="text-sm text-royal">Visit us</p>
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
                                            <ArrowUpRight className="size-4 text-royal transition-transform duration-200 ease-out group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
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
                                    className="flex w-full items-center justify-between gap-6 py-5 text-left text-base font-medium text-ocean transition-colors hover:text-royal"
                                >
                                    {faq.question}
                                    <span
                                        aria-hidden="true"
                                        className={cn(
                                            'text-xl font-light text-royal transition-transform duration-200 ease-out',
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
            <p className="text-sm text-royal">Ask us anything</p>
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
