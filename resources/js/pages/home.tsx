import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Award,
    Check,
    CheckCircle2,
    ChevronDown,
    ChevronRight,
    Clock,
    Droplet,
    Mail,
    MapPin,
    Phone,
    Play,
    ShieldCheck,
    Sparkles,
    UserCheck,
    WandSparkles,
    X,
} from 'lucide-react';
import type { CSSProperties, FormEvent } from 'react';
import { useState } from 'react';
import { SocialIcon } from '@/components/site/social-icon';
import { useReveal } from '@/lib/site';
import { cn } from '@/lib/utils';
import { book, contact } from '@/routes';
import leads from '@/routes/leads';
import treatmentRoutes from '@/routes/treatments';
import type {
    Branch,
    Category,
    Testimonial,
    Treatment,
} from '@/types/site';

type Props = {
    categories: Category[];
    featured: Treatment[];
    bookable: Treatment[];
    branches: Branch[];
    testimonials: Testimonial[];
    specialists?: any[];
    faqs?: { id: number; question: string; answer: string }[];
};

const stagger = (i: number) => ({ '--i': i }) as CSSProperties;

export default function Home({
    categories,
    featured,
    bookable,
    branches,
    testimonials,
    specialists,
    faqs: dbFaqs,
}: Props) {
    const { clinic, mediaUrl } = usePage().props;
    const media = (file: string) => `${mediaUrl}/${file}`;
    useReveal();

    const [tourOpen, setTourOpen] = useState(false);
    const [openFaq, setOpenFaq] = useState<number | null>(0);

    const defaultFaqs = [
        {
            id: 1,
            question: 'Are the treatments safe?',
            answer:
                'Yes, all our treatments and glutathione formulations are performed using FDA-approved technology under the direct supervision of licensed physicians and registered nurses.',
        },
        {
            id: 2,
            question: 'How many sessions are required?',
            answer:
                'Visible radiance is often noticed after 1–2 sessions. For sustained brightening and collagen remodeling, a customized series of 4–8 sessions is typically recommended.',
        },
        {
            id: 3,
            question: 'Is there any downtime?',
            answer:
                'Most treatments, including our signature glutathione drips and HydraGlow facials, have zero downtime. Gentle laser sessions may have mild rosy flushing for 1–2 hours.',
        },
        {
            id: 4,
            question: 'Do you offer customized treatment plans?',
            answer:
                'Every client begins with a thorough skin assessment. Your physician customizes the protocol, active concentrations, and schedule for your unique skin profile.',
        },
    ];

    const activeFaqs = dbFaqs && dbFaqs.length > 0 ? dbFaqs : defaultFaqs;

    const signatureTreatments = [
        {
            id: 'laser',
            name: 'Laser Skin Rejuvenation',
            desc: 'Improve texture, tone & achieve a youthful, flawless glow.',
            icon: Sparkles,
            slug: 'laser-skin-rejuvenation',
        },
        {
            id: 'pigmentation',
            name: 'Pigmentation Treatment',
            desc: 'Target stubborn dark spots, melasma & uneven skin tone.',
            icon: WandSparkles,
            slug: 'pigmentation-treatment',
        },
        {
            id: 'acne',
            name: 'Acne & Scar Treatment',
            desc: 'Clear active acne, prevent breakouts & fade deep acne scars.',
            icon: Droplet,
            slug: 'acne-scar-treatment',
        },
        {
            id: 'anti-aging',
            name: 'Anti-Aging Solutions',
            desc: 'Reduce fine lines, wrinkles & restore cellular firmness.',
            icon: Clock,
            slug: 'anti-aging-solutions',
        },
        {
            id: 'gluta',
            name: 'Glutathione Therapy',
            desc: 'Medical-grade antioxidant drips for radiant, glowing skin.',
            icon: ShieldCheck,
            slug: 'glutathione-therapy',
        },
        {
            id: 'hydraglow',
            name: 'HydraGlow Facial',
            desc: 'Deep cleansing, intense hydration & instant glass-skin radiance.',
            icon: Award,
            slug: 'hydraglow-facial',
        },
    ];

    const doctorsList = [
        {
            id: 1,
            name: 'Dr. Camille Rivera',
            title: 'MD Dermatology',
            role: 'Laser & Aesthetic Specialist',
            exp: '10+ Years of Experience',
            photo: media('asian-dermatologist-consultation.jpg'),
        },
        {
            id: 2,
            name: 'Dr. Adrian Santos',
            title: 'MD Aesthetic Medicine',
            role: 'Physician & Dermal Specialist',
            exp: '9+ Years of Experience',
            photo: media('asian-doctor-male.jpg'),
        },
        {
            id: 3,
            name: 'Dr. Rhea Kapoor',
            title: 'MD Dermatology',
            role: 'Cosmetic Dermatologist',
            exp: '8+ Years of Experience',
            photo: media('doctor-1.jpg'),
        },
    ];

    return (
        <>
            <Head title="Gluta RepubliQ Aesthetics | Advanced Care. Radiant You.">
                <meta
                    name="description"
                    content="Gluta RepubliQ Aesthetics: Advanced glutathione therapies, laser rejuvenation, HydraGlow facials, and physician-led skincare across Cebu, San Pedro, Sta. Rosa, and Bacoor."
                />
            </Head>

            {/* HERO SECTION MATCHING MOCKUP.JPG */}
            <section className="relative overflow-hidden bg-gradient-to-b from-pearl via-white to-pearl/50 py-12 lg:py-20">
                <div className="mx-auto max-w-7xl px-4 sm:px-8 lg:px-12">
                    <div className="grid items-center gap-12 lg:grid-cols-12 lg:gap-8">
                        {/* Hero Left Content */}
                        <div className="lg:col-span-7">
                            <p className="eyebrow">
                                REVEAL YOUR CONFIDENCE
                            </p>

                            <h1 className="mt-4 font-display font-medium text-ocean text-[46px] sm:text-[64px] lg:text-[76px] leading-[0.98] tracking-tight">
                                Advanced Care.<br />
                                <span className="italic text-royal">Radiant You.</span>
                                <span className="inline-block text-aqua ml-2 text-[0.6em] -translate-y-4">✦</span>
                            </h1>

                            <p className="mt-6 max-w-xl font-sans text-base sm:text-lg leading-[1.7] text-charcoal/80 font-normal">
                                Gluta RepubliQ offers advanced dermatology, glutathione therapy, and laser treatments tailored to your unique skin. Safe. Effective. Personalized.
                            </p>

                            {/* CTA Buttons Row */}
                            <div className="mt-8 flex flex-wrap items-center gap-4">
                                <Link
                                    href={book().url}
                                    className="press inline-flex items-center gap-3 rounded-full bg-ocean px-8 py-3.5 text-xs sm:text-sm font-bold uppercase tracking-[0.1em] text-white shadow-lg shadow-ocean/20 transition hover:bg-royal"
                                >
                                    <span>Book Consultation</span>
                                    <span className="flex size-6 items-center justify-center rounded-full bg-white/20 text-white">
                                        <ArrowRight className="size-3.5" />
                                    </span>
                                </Link>

                                <button
                                    type="button"
                                    onClick={() => setTourOpen(true)}
                                    className="press inline-flex items-center gap-2.5 rounded-full border border-royal/30 bg-white/80 px-6 py-3.5 text-xs sm:text-sm font-bold uppercase tracking-[0.08em] text-ocean shadow-sm transition hover:border-royal hover:bg-ice"
                                >
                                    <span className="flex size-5 items-center justify-center rounded-full bg-royal text-white">
                                        <Play className="size-2.5 fill-current ml-0.5" />
                                    </span>
                                    <span>Watch Clinic Tour</span>
                                </button>
                            </div>

                            {/* Trust Badges */}
                            <div className="mt-12 pt-8 border-t border-ice">
                                <div className="grid grid-cols-2 gap-4 sm:grid-cols-4 sm:gap-6">
                                    {[
                                        [Award, 'FDA Approved Technology'],
                                        [UserCheck, 'Expert Dermatologists'],
                                        [Sparkles, 'Personalized Treatment'],
                                        [ShieldCheck, 'Safe & Hygienic Environment'],
                                    ].map(([Icon, label], idx) => {
                                        const I = Icon as typeof Award;
                                        return (
                                            <div key={idx} className="flex flex-col items-start gap-2">
                                                <div className="flex size-9 items-center justify-center rounded-full bg-ice text-royal">
                                                    <I className="size-4" />
                                                </div>
                                                <span className="text-xs font-semibold text-ocean leading-tight">
                                                    {label as string}
                                                </span>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        </div>

                        {/* Hero Right Image Frame */}
                        <div className="lg:col-span-5 flex justify-center">
                            <div className="relative w-full max-w-md">
                                <div className="relative overflow-hidden rounded-t-[14rem] rounded-b-3xl border-4 border-white bg-ice shadow-2xl shadow-ocean/15">
                                    <img
                                        src={media('hero-treatment.jpg')}
                                        alt="Gluta RepubliQ advanced laser treatment"
                                        className="h-[28rem] sm:h-[34rem] w-full rounded-t-[13.5rem] rounded-b-2xl object-cover object-top"
                                    />
                                    <div className="absolute inset-0 bg-gradient-to-t from-ocean/40 via-transparent to-transparent" />
                                    <div className="absolute bottom-6 left-6 right-6 rounded-2xl bg-white/95 p-4 backdrop-blur shadow-lg border border-ice">
                                        <p className="text-[11px] font-bold uppercase tracking-wider text-royal">
                                            Signature Care
                                        </p>
                                        <p className="font-display text-lg font-semibold text-ocean">
                                            Physician-Led Skin Perfection
                                        </p>
                                        <p className="text-xs text-charcoal/70 mt-0.5">
                                            Robinsons Galleria Cebu II & Manila Branches
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* OUR SIGNATURE TREATMENTS MATCHING MOCKUP.JPG */}
            <section className="py-20 lg:py-24 bg-white border-y border-ice">
                <div className="mx-auto max-w-7xl px-4 sm:px-8 lg:px-12">
                    <div className="text-center">
                        <span className="inline-flex items-center gap-4 text-xs font-bold tracking-[0.22em] uppercase text-royal">
                            <span className="h-px w-10 bg-royal/40" />
                            OUR SIGNATURE TREATMENTS
                            <span className="h-px w-10 bg-royal/40" />
                        </span>
                        <h2 className="mt-3 text-3xl sm:text-4xl lg:text-5xl font-display text-ocean font-medium">
                            Tailored Skin & Body Solutions
                        </h2>
                    </div>

                    <div className="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {signatureTreatments.map((item, i) => {
                            const IconComponent = item.icon;
                            return (
                                <div
                                    key={item.id}
                                    data-reveal
                                    style={stagger(i)}
                                    className="group relative flex flex-col justify-between rounded-2xl border border-ice bg-pearl/60 p-8 transition-all duration-300 hover:border-royal hover:bg-white hover:shadow-xl hover:shadow-royal/5"
                                >
                                    <div>
                                        <div className="flex size-14 items-center justify-center rounded-2xl bg-white border border-ice text-royal shadow-sm transition-transform duration-300 group-hover:scale-110 group-hover:bg-royal group-hover:text-white">
                                            <IconComponent className="size-6" />
                                        </div>
                                        <h3 className="mt-6 font-display text-2xl font-semibold text-ocean">
                                            {item.name}
                                        </h3>
                                        <p className="mt-3 text-sm leading-relaxed text-charcoal/75 font-sans">
                                            {item.desc}
                                        </p>
                                    </div>
                                    <div className="mt-8 pt-4 border-t border-ice/60">
                                        <Link
                                            href={treatmentRoutes.index().url}
                                            className="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-royal transition group-hover:text-aqua"
                                        >
                                            <span>Learn More</span>
                                            <ArrowRight className="size-3.5 transition-transform group-hover:translate-x-1" />
                                        </Link>
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    <div className="mt-12 text-center">
                        <Link
                            href={treatmentRoutes.index().url}
                            className="press btn btn-royal"
                        >
                            View All Treatments & Packages
                        </Link>
                    </div>
                </div>
            </section>

            {/* REAL RESULTS. REAL CONFIDENCE. MATCHING MOCKUP.JPG */}
            <section className="py-20 lg:py-24 bg-pearl">
                <div className="mx-auto max-w-7xl px-4 sm:px-8 lg:px-12">
                    <div className="grid items-center gap-12 lg:grid-cols-12 lg:gap-10">
                        {/* Left Title and CTA */}
                        <div className="lg:col-span-4">
                            <h2 className="font-display text-4xl sm:text-5xl text-ocean font-medium leading-[1.05]">
                                REAL RESULTS.<br />
                                <span className="text-royal italic">REAL CONFIDENCE.</span>
                            </h2>
                            <p className="mt-6 text-base leading-relaxed text-charcoal/75">
                                Transformations that speak for themselves. See the real impact of expert medical care and advanced glutathione and laser technology.
                            </p>
                            <div className="mt-8">
                                <Link
                                    href={treatmentRoutes.index().url}
                                    className="press inline-flex items-center gap-3 rounded-full bg-ocean px-7 py-3.5 text-xs font-bold uppercase tracking-[0.1em] text-white shadow transition hover:bg-royal"
                                >
                                    <span>View More Results</span>
                                    <span className="flex size-5 items-center justify-center rounded-full bg-white/20 text-white">
                                        <ArrowRight className="size-3" />
                                    </span>
                                </Link>
                            </div>
                        </div>

                        {/* Right Before & After Cards */}
                        <div className="lg:col-span-8 grid gap-4 sm:grid-cols-3">
                            {[
                                { file: 'mockup-ba1.jpg', label: 'Acne & Scar Clearance' },
                                { file: 'mockup-ba2.jpg', label: 'Pigmentation & Melasma' },
                                { file: 'mockup-ba3.jpg', label: 'Anti-Aging & Radiance' },
                            ].map((c, idx) => (
                                <div
                                    key={idx}
                                    className="overflow-hidden rounded-2xl bg-white border border-ice shadow-sm"
                                >
                                    <div className="relative aspect-[4/3] overflow-hidden bg-ice">
                                        <img
                                            src={media(c.file)}
                                            alt={c.label}
                                            className="h-full w-full object-cover"
                                            loading="lazy"
                                        />
                                    </div>
                                    <div className="p-4 text-center">
                                        <p className="font-display text-base font-semibold text-ocean">
                                            {c.label}
                                        </p>
                                        <div className="mt-2 flex justify-center gap-4 text-[11px] font-bold uppercase tracking-wider text-royal">
                                            <span>Before</span>
                                            <span className="text-aqua">·</span>
                                            <span>After</span>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </section>

            {/* ABOUT GLUTA REPUBLIQ ("Where Science Meets Aesthetics") MATCHING MOCKUP.JPG */}
            <section className="py-20 lg:py-28 bg-white border-y border-ice overflow-hidden">
                <div className="mx-auto max-w-7xl px-4 sm:px-8 lg:px-12">
                    <div className="grid items-center gap-12 lg:grid-cols-12 lg:gap-14">
                        {/* Left Arch Photo */}
                        <div className="lg:col-span-5 flex justify-center">
                            <div className="relative w-full max-w-md">
                                <div className="overflow-hidden rounded-t-[14rem] rounded-b-3xl border-4 border-white shadow-2xl shadow-ocean/10 bg-ice">
                                    <img
                                        src={media('about-clinic.jpg')}
                                        alt="Gluta RepubliQ clinic interior"
                                        className="h-[28rem] sm:h-[32rem] w-full object-cover"
                                        loading="lazy"
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Right Content */}
                        <div className="lg:col-span-7">
                            <p className="eyebrow">ABOUT GLUTA REPUBLIQ</p>
                            <h2 className="mt-4 font-display text-4xl sm:text-5xl lg:text-6xl text-ocean font-medium leading-[1.05]">
                                Where Science Meets<br />
                                <span className="italic text-royal">Aesthetics</span>
                            </h2>

                            <p className="mt-6 text-base sm:text-lg leading-relaxed text-charcoal/80">
                                At Gluta RepubliQ, we combine medical expertise with advanced technology to deliver safe, effective & personalized skin solutions. Your skin is our priority.
                            </p>

                            <ul className="mt-8 space-y-4">
                                {[
                                    'Advanced USFDA & FDA Approved Lasers',
                                    'Experienced Dermatologists & Registered Nurses',
                                    'Customized Treatment & Drip Plans',
                                    'Luxurious, Private & Hygienic Environment',
                                ].map((bullet, i) => (
                                    <li key={i} className="flex items-center gap-3">
                                        <div className="flex size-6 shrink-0 items-center justify-center rounded-full bg-ice text-royal">
                                            <Check className="size-3.5 stroke-[2.5]" />
                                        </div>
                                        <span className="font-sans text-sm sm:text-base font-medium text-charcoal">
                                            {bullet}
                                        </span>
                                    </li>
                                ))}
                            </ul>

                            {/* Oval Stamp Badge matching mockup */}
                            <div className="mt-10 flex items-center gap-6">
                                <div className="inline-flex flex-col items-center justify-center rounded-full border border-royal/30 bg-pearl px-8 py-4 text-center shadow-sm">
                                    <span className="text-aqua text-xs">✦</span>
                                    <span className="font-display text-sm font-semibold text-ocean">Your Skin</span>
                                    <span className="font-display text-sm font-semibold text-royal">Our Expertise</span>
                                    <span className="font-display text-sm font-semibold text-ocean">Your Confidence</span>
                                    <span className="text-aqua text-xs">✦</span>
                                </div>

                                <Link
                                    href={contact().url}
                                    className="press btn btn-outline"
                                >
                                    Learn Our Story
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* OUR SPECIALIST DERMATOLOGISTS MATCHING MOCKUP.JPG */}
            <section id="doctors" className="py-20 lg:py-24 bg-pearl scroll-mt-20">
                <div className="mx-auto max-w-7xl px-4 sm:px-8 lg:px-12">
                    <div className="text-center">
                        <span className="inline-flex items-center gap-4 text-xs font-bold tracking-[0.22em] uppercase text-royal">
                            <span className="h-px w-10 bg-royal/40" />
                            OUR SPECIALIST DERMATOLOGISTS
                            <span className="h-px w-10 bg-royal/40" />
                        </span>
                        <h2 className="mt-3 text-3xl sm:text-4xl lg:text-5xl font-display text-ocean font-medium">
                            Expert Clinical Practitioners
                        </h2>
                    </div>

                    <div className="mt-14 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                        {doctorsList.map((doc, idx) => (
                            <div
                                key={doc.id}
                                data-reveal
                                style={stagger(idx)}
                                className="group flex flex-col items-center rounded-2xl border border-ice bg-white p-8 text-center shadow-sm transition hover:border-royal hover:shadow-xl"
                            >
                                <div className="overflow-hidden rounded-t-[7rem] rounded-b-2xl border-2 border-ice bg-ice/50 size-44 shadow-inner">
                                    <img
                                        src={doc.photo}
                                        alt={doc.name}
                                        className="h-full w-full object-cover object-top transition-transform duration-300 group-hover:scale-105"
                                        loading="lazy"
                                    />
                                </div>

                                <h3 className="mt-6 font-display text-2xl font-semibold text-ocean">
                                    {doc.name}
                                </h3>
                                <p className="mt-1 font-sans text-xs font-bold uppercase tracking-wider text-royal">
                                    {doc.title}
                                </p>
                                <p className="mt-2 text-sm text-charcoal/70">
                                    {doc.role}
                                </p>
                                <p className="mt-1 text-xs font-semibold text-aqua">
                                    {doc.exp}
                                </p>

                                <div className="mt-5 flex gap-2">
                                    <a
                                        href={`tel:${clinic.contact.phone.replace(/[^0-9+]/g, '')}`}
                                        className="flex size-8 items-center justify-center rounded-full border border-ice text-royal transition hover:bg-royal hover:text-white"
                                        aria-label="Call clinic for doctor"
                                    >
                                        <Phone className="size-3.5" />
                                    </a>
                                    <Link
                                        href={book().url}
                                        className="flex size-8 items-center justify-center rounded-full border border-ice text-royal transition hover:bg-royal hover:text-white"
                                        aria-label="Book doctor appointment"
                                    >
                                        <ArrowRight className="size-3.5" />
                                    </Link>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* BOOK CONSULTATION & FAQ MATCHING MOCKUP.JPG */}
            <section className="py-20 lg:py-24 bg-white border-t border-ice">
                <div className="mx-auto max-w-7xl px-4 sm:px-8 lg:px-12">
                    <div className="grid gap-12 lg:grid-cols-12 lg:gap-12">
                        {/* Left Card: Dark Deep Ocean Navy Booking Card */}
                        <div className="lg:col-span-7">
                            <ConsultationCard bookable={bookable} />
                        </div>

                        {/* Right: FAQ Accordion matching mockup */}
                        <div className="lg:col-span-5 flex flex-col justify-center">
                            <span className="eyebrow">COMMON QUESTIONS</span>
                            <h2 className="mt-2 font-display text-3xl sm:text-4xl text-ocean font-medium leading-tight">
                                Frequently Asked Questions
                            </h2>
                            <p className="mt-3 text-sm text-charcoal/70">
                                Everything you need to know about our treatments, protocols, and safety standards.
                            </p>

                            <div className="mt-8 space-y-3">
                                {activeFaqs.map((faq, i) => {
                                    const isOpen = openFaq === i;
                                    return (
                                        <div
                                            key={faq.id}
                                            className="rounded-2xl border border-ice bg-pearl/60 overflow-hidden transition"
                                        >
                                            <button
                                                type="button"
                                                onClick={() => setOpenFaq(isOpen ? null : i)}
                                                className="flex w-full items-center justify-between p-5 text-left font-sans text-sm sm:text-base font-semibold text-ocean transition hover:text-royal"
                                            >
                                                <span>{faq.question}</span>
                                                <span className="flex size-7 shrink-0 items-center justify-center rounded-full bg-white text-royal text-sm font-bold shadow-sm">
                                                    {isOpen ? '−' : '+'}
                                                </span>
                                            </button>
                                            {isOpen && (
                                                <div className="px-5 pb-5 pt-1 text-sm leading-relaxed text-charcoal/80 border-t border-ice/60 bg-white">
                                                    {faq.answer}
                                                </div>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>

                            <div className="mt-8 p-6 rounded-2xl bg-ice/40 border border-ice flex items-center justify-between gap-4">
                                <div>
                                    <p className="text-xs font-bold uppercase tracking-wider text-royal">
                                        Have more questions?
                                    </p>
                                    <p className="text-sm text-charcoal/80">
                                        Our patient coordinators are ready to help.
                                    </p>
                                </div>
                                <a
                                    href={`tel:${clinic.contact.phone.replace(/[^0-9+]/g, '')}`}
                                    className="press btn btn-solid text-xs whitespace-nowrap"
                                >
                                    Call Us
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* Video Modal if tour requested */}
            {tourOpen && (
                <div
                    role="dialog"
                    aria-modal="true"
                    className="fixed inset-0 z-50 flex items-center justify-center bg-ocean/80 p-4 backdrop-blur"
                >
                    <div className="relative w-full max-w-4xl overflow-hidden rounded-3xl bg-black shadow-2xl">
                        <button
                            type="button"
                            onClick={() => setTourOpen(false)}
                            className="absolute right-4 top-4 z-10 flex size-10 items-center justify-center rounded-full bg-white/20 text-white backdrop-blur transition hover:bg-white/40"
                            aria-label="Close clinic tour"
                        >
                            <X className="size-6" />
                        </button>
                        <div className="relative aspect-video">
                            <video
                                src={media('video/hero-5308682.mp4')}
                                poster={media('asian-hydraglow-facial.jpg')}
                                controls
                                autoPlay
                                className="h-full w-full object-cover"
                            />
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}

function ConsultationCard({ bookable }: { bookable: Treatment[] }) {
    const { clinic } = usePage().props;
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

    return (
        <div className="on-dark rounded-3xl bg-ocean p-8 sm:p-10 text-white shadow-2xl shadow-ocean/20">
            <div className="grid gap-8 lg:grid-cols-12">
                {/* Left side info matching mockup card */}
                <div className="lg:col-span-5 flex flex-col justify-between border-b lg:border-b-0 lg:border-r border-white/15 pb-8 lg:pb-0 lg:pr-8">
                    <div>
                        <div className="flex size-12 items-center justify-center rounded-2xl bg-white/10 text-aqua border border-white/10">
                            <Sparkles className="size-6" />
                        </div>
                        <h3 className="mt-5 font-display text-2xl sm:text-3xl font-medium tracking-tight text-white">
                            BOOK YOUR CONSULTATION
                        </h3>
                        <p className="mt-3 text-sm leading-relaxed text-ice/85">
                            Take the first step towards healthy, radiant skin. Our licensed experts are here to help you.
                        </p>
                    </div>

                    <div className="mt-8 space-y-4 text-xs text-pearl/80">
                        <div className="flex items-center gap-3">
                            <Phone className="size-4 text-aqua shrink-0" />
                            <a href={`tel:${clinic.contact.phone.replace(/[^0-9+]/g, '')}`} className="hover:text-aqua transition">
                                {clinic.contact.phone}
                            </a>
                        </div>
                        <div className="flex items-center gap-3">
                            <Mail className="size-4 text-aqua shrink-0" />
                            <a href={`mailto:${clinic.contact.email}`} className="hover:text-aqua transition">
                                {clinic.contact.email}
                            </a>
                        </div>
                        <div className="flex items-start gap-3">
                            <MapPin className="size-4 text-aqua shrink-0 mt-0.5" />
                            <span>{clinic.contact.address}</span>
                        </div>
                    </div>
                </div>

                {/* Right side form */}
                <form onSubmit={submit} className="lg:col-span-7 flex flex-col gap-4" noValidate>
                    <div>
                        <label htmlFor="fn" className="sr-only">Full Name</label>
                        <input
                            id="fn"
                            type="text"
                            required
                            placeholder="Full Name"
                            value={form.data.first_name}
                            onChange={(e) => form.setData('first_name', e.target.value)}
                            className="h-12 w-full rounded-xl border border-white/20 bg-white/10 px-4 text-sm text-white placeholder:text-white/60 focus:border-aqua focus:bg-white/15 focus:outline-none transition"
                        />
                        {form.errors.first_name && (
                            <p className="mt-1 text-xs text-aqua">{form.errors.first_name}</p>
                        )}
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label htmlFor="ph" className="sr-only">Phone Number</label>
                            <input
                                id="ph"
                                type="tel"
                                required
                                placeholder="Phone Number"
                                value={form.data.phone}
                                onChange={(e) => form.setData('phone', e.target.value)}
                                className="h-12 w-full rounded-xl border border-white/20 bg-white/10 px-4 text-sm text-white placeholder:text-white/60 focus:border-aqua focus:bg-white/15 focus:outline-none transition"
                            />
                            {form.errors.phone && (
                                <p className="mt-1 text-xs text-aqua">{form.errors.phone}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="em" className="sr-only">Email Address</label>
                            <input
                                id="em"
                                type="email"
                                placeholder="Email Address"
                                value={form.data.email}
                                onChange={(e) => form.setData('email', e.target.value)}
                                className="h-12 w-full rounded-xl border border-white/20 bg-white/10 px-4 text-sm text-white placeholder:text-white/60 focus:border-aqua focus:bg-white/15 focus:outline-none transition"
                            />
                        </div>
                    </div>

                    <div>
                        <label htmlFor="tr" className="sr-only">Treatment of Interest</label>
                        <select
                            id="tr"
                            value={form.data.treatment_id}
                            onChange={(e) => form.setData('treatment_id', e.target.value)}
                            className="h-12 w-full rounded-xl border border-white/20 bg-white/10 px-4 text-sm text-white focus:border-aqua focus:bg-ocean focus:outline-none transition cursor-pointer [&>option]:text-charcoal [&>option]:bg-white"
                        >
                            <option value="">Treatment of Interest</option>
                            {bookable.map((t) => (
                                <option key={t.id} value={t.id}>
                                    {t.name}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <label htmlFor="msg" className="sr-only">Your Message</label>
                        <textarea
                            id="msg"
                            rows={3}
                            placeholder="Your Message"
                            value={form.data.message}
                            onChange={(e) => form.setData('message', e.target.value)}
                            className="w-full rounded-xl border border-white/20 bg-white/10 p-4 text-sm text-white placeholder:text-white/60 focus:border-aqua focus:bg-white/15 focus:outline-none transition resize-none"
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={form.processing}
                        className="press mt-2 flex h-13 w-full items-center justify-center gap-3 rounded-full bg-royal px-8 font-bold uppercase tracking-wider text-white shadow-lg transition hover:bg-aqua hover:text-ocean disabled:opacity-50"
                    >
                        <span>{form.processing ? 'Booking...' : 'Book Appointment'}</span>
                        <ArrowRight className="size-4" />
                    </button>
                </form>
            </div>
        </div>
    );
}
