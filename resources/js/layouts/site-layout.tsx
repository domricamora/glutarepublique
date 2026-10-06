import { Link, useForm, usePage } from '@inertiajs/react';
import { Menu, Phone, X, MapPin, Mail } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { useEffect, useState } from 'react';
import { SOCIAL_LABELS, SocialIcon } from '@/components/site/social-icon';
import Wordmark from '@/components/site/wordmark';
import { cn } from '@/lib/utils';
import { ChatWidget } from '@/components/site/chat-widget';
import {
    about,
    beforeAfter,
    book,
    contact,
    home,
    legal,
    login,
    membership,
    promotions,
} from '@/routes';
import blog from '@/routes/blog';
import leads from '@/routes/leads';
import treatments from '@/routes/treatments';

const nav = [
    { label: 'Home', href: home().url },
    { label: 'About Us', href: about().url },
    { label: 'Treatments', href: treatments.index().url },
    { label: 'Our Doctors', href: `${home().url}#doctors` },
    { label: 'Results', href: beforeAfter().url },
    { label: 'Blog', href: blog.index().url },
    { label: 'Contact', href: contact().url },
];

export default function SiteLayout({ children }: { children: ReactNode }) {
    const { clinic, flash } = usePage<{ flash?: { success?: string | null } }>()
        .props;
    const [open, setOpen] = useState(false);

    useEffect(() => {
        document.body.style.overflow = open ? 'hidden' : '';
    }, [open]);

    return (
        <div className="site theme-light min-h-dvh bg-pure-white text-charcoal">
            <LocalBusinessSchema />
            <a
                href="#main"
                className="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:bg-white focus:px-4 focus:py-2"
            >
                Skip to content
            </a>

            {/* Announcement topbar */}
            <div className="bg-ocean px-4 py-2 text-center text-xs tracking-wider text-pearl font-medium">
                <span className="text-aqua font-semibold">Gluta RepubliQ Aesthetics:</span> Cebu Galleria II · San Pedro · Sta. Rosa · Bacoor · Licensed Medical Care
            </div>

            {/* Header matching mockup */}
            <header className="sticky top-0 z-40 border-b border-ice bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/90">
                <div className="mx-auto flex h-20 max-w-7xl items-center justify-between gap-6 px-4 sm:px-8 lg:px-12">
                    <Link
                        href={home().url}
                        className="press min-w-0"
                        aria-label={`${clinic.name} home`}
                    >
                        <Wordmark />
                    </Link>

                    {/* Navigation matching mockup.jpg */}
                    <nav
                        aria-label="Main"
                        className="hidden items-center gap-7 text-[13px] font-semibold tracking-[0.08em] uppercase text-charcoal/80 xl:flex"
                    >
                        {nav.map((item) => (
                            <Link
                                key={item.label}
                                href={item.href}
                                className="transition-colors duration-150 hover:text-royal hover:underline underline-offset-8"
                            >
                                {item.label}
                            </Link>
                        ))}
                    </nav>

                    {/* Right side CTA matching mockup.jpg */}
                    <div className="flex items-center gap-3">
                        <a
                            href={`tel:${clinic.contact.phone.replace(/[^0-9+]/g, '')}`}
                            className="press hidden sm:inline-flex items-center gap-2 rounded-full bg-ocean px-5 py-2.5 text-xs font-bold tracking-wider text-white shadow-sm transition hover:bg-royal"
                        >
                            <Phone className="size-3.5 fill-current" />
                            <span>{clinic.contact.phone}</span>
                        </a>

                        <Link
                            href={book().url}
                            className="press btn btn-royal hidden md:inline-flex text-xs"
                        >
                            Book Now
                        </Link>

                        <button
                            type="button"
                            onClick={() => setOpen(!open)}
                            className="press -mr-2 p-2 xl:hidden text-ocean"
                            aria-expanded={open}
                            aria-controls="mobile-nav"
                            aria-label={open ? 'Close menu' : 'Open menu'}
                        >
                            {open ? (
                                <X className="size-6" />
                            ) : (
                                <Menu className="size-6" />
                            )}
                        </button>
                    </div>
                </div>
            </header>

            {/* Mobile Nav */}
            <nav
                id="mobile-nav"
                aria-label="Mobile"
                className={cn(
                    'fixed inset-x-0 top-20 bottom-0 z-30 overflow-y-auto bg-white px-6 pt-6 pb-24 xl:hidden shadow-xl',
                    open ? 'block' : 'hidden',
                )}
            >
                <ul className="divide-y divide-ice border-y border-ice">
                    {nav.map((item) => (
                        <li key={item.label}>
                            <Link
                                href={item.href}
                                onClick={() => setOpen(false)}
                                className="flex py-4 font-display text-2xl text-ocean hover:text-royal"
                            >
                                {item.label}
                            </Link>
                        </li>
                    ))}
                    <li>
                        <Link
                            href={login().url}
                            onClick={() => setOpen(false)}
                            className="flex py-4 font-sans text-lg text-charcoal/70"
                        >
                            Staff Portal Login
                        </Link>
                    </li>
                </ul>

                <div className="mt-8 flex flex-col gap-3">
                    <a
                        href={`tel:${clinic.contact.phone.replace(/[^0-9+]/g, '')}`}
                        className="press flex h-12 items-center justify-center gap-2 rounded-full bg-ocean font-bold text-white shadow"
                    >
                        <Phone className="size-4" />
                        <span>Call {clinic.contact.phone}</span>
                    </a>
                    <Link
                        href={book().url}
                        onClick={() => setOpen(false)}
                        className="press btn btn-royal h-12"
                    >
                        Book Consultation
                    </Link>
                </div>
            </nav>

            <main id="main">{children}</main>

            <SiteFooter />
            <ChatWidget />

            {/* Mobile Sticky Booking Bar */}
            <div className="fixed inset-x-0 bottom-0 z-30 border-t border-ice bg-white/95 p-3 backdrop-blur sm:hidden">
                <div className="flex gap-2">
                    <a
                        href={`tel:${clinic.contact.phone.replace(/[^0-9+]/g, '')}`}
                        className="press flex size-12 shrink-0 items-center justify-center rounded-full bg-ice text-ocean"
                        aria-label="Call clinic"
                    >
                        <Phone className="size-5" />
                    </a>
                    <Link
                        href={book().url}
                        className="press flex-1 btn btn-royal h-12"
                    >
                        Book Appointment
                    </Link>
                </div>
            </div>

            {flash?.success && (
                <div
                    role="status"
                    className="fixed right-4 bottom-20 z-50 max-w-sm rounded-lg bg-ocean px-5 py-4 text-sm text-white shadow-xl sm:bottom-6"
                >
                    {flash.success}
                </div>
            )}
        </div>
    );
}

function LocalBusinessSchema() {
    const { clinic } = usePage().props;
    const schema = {
        '@context': 'https://schema.org',
        '@type': 'MedicalBusiness',
        name: clinic.name,
        description: `${clinic.tagline} Physician-led glutathione and aesthetic care clinic.`,
        url: home().url,
        telephone: clinic.contact.phone,
        email: clinic.contact.email,
        priceRange: '₱₱',
        currenciesAccepted: 'PHP',
        paymentAccepted: 'Cash, GCash, Maya, credit card',
        address: {
            '@type': 'PostalAddress',
            streetAddress: clinic.contact.address,
            addressCountry: 'PH',
        },
        openingHoursSpecification: [
            {
                '@type': 'OpeningHoursSpecification',
                dayOfWeek: [
                    'Monday',
                    'Tuesday',
                    'Wednesday',
                    'Thursday',
                    'Friday',
                ],
                opens: '10:00',
                closes: '20:00',
            },
            {
                '@type': 'OpeningHoursSpecification',
                dayOfWeek: ['Saturday', 'Sunday'],
                opens: '10:00',
                closes: '19:00',
            },
        ],
        medicalSpecialty: ['Dermatology', 'Aesthetic Medicine'],
        availableService: [
            { '@type': 'MedicalProcedure', name: 'Glutathione IV Drips' },
            { '@type': 'MedicalProcedure', name: 'Laser Skin Rejuvenation' },
            { '@type': 'MedicalProcedure', name: 'HydraGlow Facial' },
            { '@type': 'MedicalProcedure', name: 'Acne & Pigmentation Treatment' },
        ],
    };

    return (
        <script
            type="application/ld+json"
            dangerouslySetInnerHTML={{ __html: JSON.stringify(schema) }}
        />
    );
}

function SiteFooter() {
    const { clinic, socials } = usePage().props;

    return (
        <footer className="on-dark bg-ocean text-white pb-24 sm:pb-0">
            <div className="mx-auto max-w-7xl px-4 py-16 sm:px-8 lg:px-12 lg:py-20">
                <div className="grid gap-12 lg:grid-cols-12 lg:gap-8">
                    {/* Brand column matching mockup */}
                    <div className="lg:col-span-4">
                        <Wordmark
                            variant="light"
                            markClassName="size-16"
                            nameClassName="text-[2rem] text-white"
                        />
                        <p className="mt-5 max-w-sm text-sm leading-relaxed text-ice/85">
                            Advanced Aesthetic Care. Radiant You. Licensed medical-grade glutathione therapies, laser rejuvenation, and personalized dermatology.
                        </p>

                        <div className="mt-6 flex items-center gap-3">
                            {socials && Object.keys(socials).length > 0 ? (
                                Object.entries(socials).map(([platform, href]) => (
                                    <a
                                        key={platform}
                                        href={href}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        aria-label={SOCIAL_LABELS[platform] ?? platform}
                                        className="flex size-9 items-center justify-center rounded-full border border-white/20 text-pearl transition hover:border-aqua hover:bg-aqua hover:text-ocean"
                                    >
                                        <SocialIcon platform={platform} className="size-4" />
                                    </a>
                                ))
                            ) : (
                                <>
                                    <a
                                        href="https://www.facebook.com/GlutaRepubliQ"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="flex size-9 items-center justify-center rounded-full border border-white/20 text-pearl transition hover:border-aqua hover:bg-aqua hover:text-ocean"
                                    >
                                        <SocialIcon platform="facebook" className="size-4" />
                                    </a>
                                    <a
                                        href="https://www.instagram.com/glutarepubliq"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="flex size-9 items-center justify-center rounded-full border border-white/20 text-pearl transition hover:border-aqua hover:bg-aqua hover:text-ocean"
                                    >
                                        <SocialIcon platform="instagram" className="size-4" />
                                    </a>
                                    <a
                                        href="https://www.tiktok.com/@glutarepubliq"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="flex size-9 items-center justify-center rounded-full border border-white/20 text-pearl transition hover:border-aqua hover:bg-aqua hover:text-ocean"
                                    >
                                        <SocialIcon platform="tiktok" className="size-4" />
                                    </a>
                                </>
                            )}
                        </div>
                    </div>

                    {/* Quick links */}
                    <div className="lg:col-span-2">
                        <h3 className="font-sans text-xs font-bold uppercase tracking-[0.14em] text-aqua">
                            Quick Links
                        </h3>
                        <ul className="mt-4 space-y-2.5 text-sm text-pearl/80">
                            <li><Link href={about().url} className="hover:text-aqua transition">About Us</Link></li>
                            <li><Link href={treatments.index().url} className="hover:text-aqua transition">Treatments</Link></li>
                            <li><Link href={`${home().url}#doctors`} className="hover:text-aqua transition">Our Doctors</Link></li>
                            <li><Link href={beforeAfter().url} className="hover:text-aqua transition">Results</Link></li>
                            <li><Link href={blog.index().url} className="hover:text-aqua transition">Blog</Link></li>
                            <li><Link href={contact().url} className="hover:text-aqua transition">Contact Us</Link></li>
                        </ul>
                    </div>

                    {/* Treatments column matching mockup */}
                    <div className="lg:col-span-3">
                        <h3 className="font-sans text-xs font-bold uppercase tracking-[0.14em] text-aqua">
                            Treatments
                        </h3>
                        <ul className="mt-4 space-y-2.5 text-sm text-pearl/80">
                            <li><Link href={treatments.index().url} className="hover:text-aqua transition">Laser Skin Rejuvenation</Link></li>
                            <li><Link href={treatments.index().url} className="hover:text-aqua transition">Pigmentation Treatment</Link></li>
                            <li><Link href={treatments.index().url} className="hover:text-aqua transition">Acne & Scar Treatment</Link></li>
                            <li><Link href={treatments.index().url} className="hover:text-aqua transition">Anti-Aging Solutions</Link></li>
                            <li><Link href={treatments.index().url} className="hover:text-aqua transition">Glutathione Therapy</Link></li>
                            <li><Link href={treatments.index().url} className="hover:text-aqua transition">HydraGlow Facial</Link></li>
                        </ul>
                    </div>

                    {/* Contact Us column matching mockup */}
                    <div className="lg:col-span-3">
                        <h3 className="font-sans text-xs font-bold uppercase tracking-[0.14em] text-aqua">
                            Contact Us
                        </h3>
                        <ul className="mt-4 space-y-3 text-sm text-pearl/80">
                            <li className="flex items-center gap-2.5">
                                <Phone className="size-4 text-aqua shrink-0" />
                                <a href={`tel:${clinic.contact.phone.replace(/[^0-9+]/g, '')}`} className="hover:text-aqua transition">
                                    {clinic.contact.phone}
                                </a>
                            </li>
                            <li className="flex items-center gap-2.5">
                                <Mail className="size-4 text-aqua shrink-0" />
                                <a href={`mailto:${clinic.contact.email}`} className="hover:text-aqua transition">
                                    {clinic.contact.email}
                                </a>
                            </li>
                            <li className="flex items-start gap-2.5">
                                <MapPin className="size-4 text-aqua shrink-0 mt-0.5" />
                                <span>{clinic.contact.address}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            {/* Copyright bottom bar matching mockup */}
            <div className="border-t border-white/10">
                <div className="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-6 text-xs text-pearl/60 sm:flex-row sm:items-center sm:justify-between sm:px-8 lg:px-12">
                    <p>© {new Date().getFullYear()} {clinic.name}. All Rights Reserved.</p>
                    <ul className="flex flex-wrap gap-5">
                        <li>
                            <Link href={legal('privacy-policy').url} className="hover:text-aqua transition">
                                Privacy Policy
                            </Link>
                        </li>
                        <li>
                            <Link href={legal('terms').url} className="hover:text-aqua transition">
                                Terms & Conditions
                            </Link>
                        </li>
                        <li>
                            <Link href={legal('data-privacy-notice').url} className="hover:text-aqua transition">
                                Data Privacy Notice
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>
        </footer>
    );
}
