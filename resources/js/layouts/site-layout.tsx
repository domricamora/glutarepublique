import { Link, useForm, usePage } from '@inertiajs/react';
import { Menu, Phone, X } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { useEffect, useState } from 'react';
import { ChatWidget } from '@/components/site/chat-widget';
import { SOCIAL_LABELS, SocialIcon } from '@/components/site/social-icon';
import Wordmark from '@/components/site/wordmark';
import { cn } from '@/lib/utils';
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
    { label: 'Before & After', href: beforeAfter().url },
    { label: 'Promotions', href: promotions().url },
    { label: 'Blog', href: blog.index().url },
    { label: 'Contact', href: contact().url },
];

// Shown when no social links are configured for the organisation.
const DEFAULT_SOCIALS: Record<string, string> = {
    facebook: 'https://www.facebook.com/GlutaRepubliQSomoAVistaMall',
    instagram: 'https://www.instagram.com/glutarepubliq',
    tiktok: 'https://www.tiktok.com/@glutarepubliq',
};

export default function SiteLayout({ children }: { children: ReactNode }) {
    const { clinic, flash } = usePage<{ flash?: { success?: string | null } }>()
        .props;
    const [open, setOpen] = useState(false);
    const tel = `tel:${clinic.contact.phone.replace(/[^0-9+]/g, '')}`;

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

            <header className="sticky top-0 z-40 border-b border-ice bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/85">
                <div className="gutter flex h-[4.5rem] items-center justify-between gap-6">
                    <Link
                        href={home().url}
                        className="min-w-0"
                        aria-label={`${clinic.name} home`}
                    >
                        <Wordmark />
                    </Link>

                    <nav
                        aria-label="Main"
                        className="hidden items-center gap-7 xl:flex"
                    >
                        {nav.map((item) => (
                            <Link
                                key={item.label}
                                href={item.href}
                                className="nav-link text-charcoal/80 hover:text-royal"
                            >
                                {item.label}
                            </Link>
                        ))}
                    </nav>

                    <div className="flex items-center gap-2">
                        <Link
                            href={book().url}
                            className="btn btn-solid hidden md:inline-flex"
                        >
                            Book consultation
                        </Link>
                        <button
                            type="button"
                            onClick={() => setOpen(!open)}
                            className="press -mr-2 p-2 text-ocean xl:hidden"
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

            <nav
                id="mobile-nav"
                aria-label="Mobile"
                className={cn(
                    'fixed inset-x-0 top-[4.5rem] bottom-0 z-30 overflow-y-auto bg-white px-6 pt-4 pb-28 xl:hidden',
                    open ? 'block' : 'hidden',
                )}
            >
                <ul className="divide-y divide-ice border-b border-ice">
                    {nav.map((item) => (
                        <li key={item.label}>
                            <Link
                                href={item.href}
                                onClick={() => setOpen(false)}
                                className="flex py-4 font-display text-2xl font-light text-ocean hover:text-royal"
                            >
                                {item.label}
                            </Link>
                        </li>
                    ))}
                    <li>
                        <Link
                            href={membership().url}
                            onClick={() => setOpen(false)}
                            className="flex py-4 font-display text-2xl font-light text-ocean hover:text-royal"
                        >
                            Membership
                        </Link>
                    </li>
                </ul>
                <div className="mt-8 grid gap-3">
                    <Link
                        href={book().url}
                        onClick={() => setOpen(false)}
                        className="btn btn-solid h-12"
                    >
                        Book consultation
                    </Link>
                    <a href={tel} className="btn btn-outline h-12">
                        <Phone className="size-4" />
                        Call {clinic.contact.phone}
                    </a>
                    <Link
                        href={login().url}
                        className="pt-2 text-center text-sm text-charcoal/60"
                    >
                        Staff login
                    </Link>
                </div>
            </nav>

            <main id="main">{children}</main>

            <SiteFooter />
            <ChatWidget />

            {/* Phones keep booking one tap away. */}
            <div className="fixed inset-x-0 bottom-0 z-30 border-t border-ice bg-white/95 p-3 backdrop-blur sm:hidden">
                <div className="flex gap-2">
                    <a
                        href={tel}
                        className="press grid size-12 shrink-0 place-items-center border border-ice text-ocean"
                        aria-label="Call clinic"
                    >
                        <Phone className="size-5" />
                    </a>
                    <Link
                        href={book().url}
                        className="btn btn-solid h-12 flex-1"
                    >
                        Book consultation
                    </Link>
                </div>
            </div>

            {flash?.success && (
                <div
                    role="status"
                    className="fixed right-4 bottom-20 z-50 max-w-sm bg-ocean px-5 py-4 text-sm text-white shadow-xl sm:bottom-6"
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
            {
                '@type': 'MedicalProcedure',
                name: 'Acne & Pigmentation Treatment',
            },
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
    const links =
        socials && Object.keys(socials).length > 0 ? socials : DEFAULT_SOCIALS;

    const columns: [string, [string, string][]][] = [
        [
            'Treatments',
            [
                ['All treatments', treatments.index().url],
                ['Before & after', beforeAfter().url],
                ['Promotions', promotions().url],
                ['Membership', membership().url],
            ],
        ],
        [
            'Clinic',
            [
                ['About us', about().url],
                ['Our doctors', `${home().url}#doctors`],
                ['Blog', blog.index().url],
                ['Contact', contact().url],
                ['Staff login', login().url],
            ],
        ],
    ];

    return (
        <footer className="on-dark bg-ocean pb-24 text-white sm:pb-0">
            <div className="gutter grid gap-12 py-16 lg:grid-cols-12 lg:py-20">
                <div className="lg:col-span-4">
                    <Wordmark
                        variant="light"
                        markClassName="size-20"
                        nameClassName="text-[1.9rem] text-white"
                    />
                    <p className="mt-6 max-w-xs text-sm leading-relaxed text-ice/80">
                        {clinic.contact.address}
                        <br />
                        <a
                            href={`tel:${clinic.contact.phone.replace(/[^0-9+]/g, '')}`}
                            className="hover:text-aqua"
                        >
                            {clinic.contact.phone}
                        </a>
                        <br />
                        <a
                            href={`mailto:${clinic.contact.email}`}
                            className="hover:text-aqua"
                        >
                            {clinic.contact.email}
                        </a>
                    </p>
                    <div className="mt-6 flex gap-2">
                        {Object.entries(links).map(([platform, href]) => (
                            <a
                                key={platform}
                                href={href}
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label={SOCIAL_LABELS[platform] ?? platform}
                                className="grid size-9 place-items-center border border-white/20 text-pearl transition-colors hover:border-aqua hover:bg-aqua hover:text-ocean"
                            >
                                <SocialIcon
                                    platform={platform}
                                    className="size-4"
                                />
                            </a>
                        ))}
                    </div>
                </div>

                {columns.map(([title, items]) => (
                    <div key={title} className="lg:col-span-2">
                        <h3 className="font-sans text-sm font-medium text-white">
                            {title}
                        </h3>
                        <ul className="mt-4 space-y-2.5 text-sm text-ice/75">
                            {items.map(([label, href]) => (
                                <li key={label}>
                                    <Link
                                        href={href}
                                        className="transition-colors hover:text-aqua"
                                    >
                                        {label}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>
                ))}

                <div className="lg:col-span-4">
                    <h3 className="font-sans text-sm font-medium text-white">
                        Newsletter
                    </h3>
                    <p className="mt-4 text-sm text-ice/75">
                        Offers and skincare notes, about once a month.
                    </p>
                    <Newsletter />
                </div>
            </div>

            <div className="border-t border-white/10">
                <div className="gutter flex flex-col gap-4 py-6 text-xs text-ice/60 sm:flex-row sm:items-center sm:justify-between">
                    <p>
                        © {new Date().getFullYear()} {clinic.name}. All rights
                        reserved.
                    </p>
                    <ul className="flex flex-wrap gap-5">
                        <li>
                            <Link
                                href={legal('privacy-policy').url}
                                className="hover:text-aqua"
                            >
                                Privacy policy
                            </Link>
                        </li>
                        <li>
                            <Link
                                href={legal('terms').url}
                                className="hover:text-aqua"
                            >
                                Terms
                            </Link>
                        </li>
                        <li>
                            <Link
                                href={legal('data-privacy-notice').url}
                                className="hover:text-aqua"
                            >
                                Data privacy notice
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>
        </footer>
    );
}

function Newsletter() {
    const form = useForm({
        form: 'newsletter',
        email: '',
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
        <form onSubmit={submit} className="mt-4">
            <div className="flex">
                <label htmlFor="newsletter-email" className="sr-only">
                    Email
                </label>
                <input
                    id="newsletter-email"
                    type="email"
                    required
                    placeholder="Email address"
                    autoComplete="email"
                    value={form.data.email}
                    onChange={(e) => form.setData('email', e.target.value)}
                    className="h-11 min-w-0 flex-1 border border-white/25 bg-white/5 px-3 text-sm text-white placeholder:text-ice/60 focus:border-aqua focus:outline-none"
                />
                <button
                    type="submit"
                    disabled={form.processing}
                    className="press h-11 shrink-0 bg-aqua px-5 text-sm font-medium text-ocean transition-colors hover:bg-white disabled:opacity-60"
                >
                    Subscribe
                </button>
            </div>
            {form.errors.email && (
                <p className="mt-2 text-xs text-soft-aqua">
                    {form.errors.email}
                </p>
            )}
        </form>
    );
}
