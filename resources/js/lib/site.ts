import { useEffect, useState } from 'react';
import type { Branch } from '@/types/site';

const SITE_PAGES = [
    'home',
    'book',
    'book-confirmed',
    'about',
    'doctors',
    'contact',
    'membership',
    'promotions',
    'before-after',
    'legal',
];

/** Inertia components drawn in the public site layout rather than the admin one. */
export const isSitePage = (name: string): boolean =>
    SITE_PAGES.includes(name) ||
    name.startsWith('treatments/') ||
    name.startsWith('blog/');

const peso = new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    maximumFractionDigits: 0,
});

export const formatPrice = (value: number): string => peso.format(value);

/** A treatment's price; the clinic lists some services without one (price 0). */
export const formatTreatmentPrice = (value: number): string =>
    value > 0 ? formatPrice(value) : 'Price on consultation';

/**
 * Staff who can take a service at a branch. A service with nobody assigned is
 * open to everyone there (the server applies the same rule).
 */
export function staffFor<
    T extends { branch_ids?: number[]; treatment_ids?: number[] },
>(staff: T[], branchId: number, treatmentId: number): T[] {
    const atBranch = staff.filter((s) => s.branch_ids?.includes(branchId));
    const assigned = staff.some((s) => s.treatment_ids?.includes(treatmentId));

    return assigned
        ? atBranch.filter((s) => s.treatment_ids?.includes(treatmentId))
        : atBranch;
}

/** The clinic's own promo artwork: text-heavy, so it is shown whole, never cropped. */
export const isFlyer = (src: string | null | undefined): boolean =>
    Boolean(src?.includes('/media/photos/flyers/'));

export const formatDuration = (minutes: number): string =>
    minutes >= 60
        ? `${Math.floor(minutes / 60)} hr${minutes % 60 ? ` ${minutes % 60} min` : ''}`
        : `${minutes} min`;

/** "14:30" to "2:30" and "PM", for large numerals with a small meridiem. */
export const splitTime = (time: string): [string, string] => {
    const [h, m] = time.split(':').map(Number);

    return [
        `${((h + 11) % 12) + 1}:${String(m).padStart(2, '0')}`,
        h < 12 ? 'AM' : 'PM',
    ];
};

/** Current date and time in Manila, independent of the visitor timezone. */
export const manilaNow = (): Date =>
    new Date(new Date().toLocaleString('en-US', { timeZone: 'Asia/Manila' }));

export const toIsoDate = (d: Date): string =>
    `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

/** Next n days the clinic could be open, starting today (Manila time). */
export const upcomingDays = (n: number): Date[] => {
    const start = manilaNow();

    return Array.from({ length: n }, (_, i) => {
        const d = new Date(start);
        d.setDate(start.getDate() + i);

        return d;
    });
};

const toHour = (time: string): number => {
    const [h, m] = time.split(':').map(Number);

    return h + m / 60;
};

/** The branch's own schedule, the same one App\Models\Branch::hoursOn books against. */
export const openWindow = (
    branch: Pick<Branch, 'hours'>,
    d: Date,
): [number, number] | null => {
    const day = branch.hours?.[d.getDay()];

    return day ? [toHour(day[0]), toHour(day[1])] : null;
};

/** "Opening January 2027" while a branch has not opened yet, else null. */
export function openingLabel(branch: {
    opens_on?: string | null;
}): string | null {
    if (!branch.opens_on) {
        return null;
    }
    const opens = new Date(`${branch.opens_on}T00:00:00+08:00`);

    return opens.getTime() > Date.now()
        ? `Opening ${opens.toLocaleDateString('en-PH', { month: 'long', year: 'numeric', timeZone: 'Asia/Manila' })}`
        : null;
}

export const isOpenNow = (branch: Pick<Branch, 'hours'>): boolean => {
    const now = manilaNow();
    const window = openWindow(branch, now);
    const hour = now.getHours() + now.getMinutes() / 60;

    return window !== null && hour >= window[0] && hour < window[1];
};

export const DAY_NAMES = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

/** Monday-first rows with runs of equal days folded: "Mon to Fri 10:00 to 20:00". */
export const hoursSummary = (
    hours: Branch['hours'],
): { days: string; time: string }[] => {
    const rows: { time: string; from: number; to: number }[] = [];

    [1, 2, 3, 4, 5, 6, 0].forEach((day) => {
        const slot = hours?.[day];
        const time = slot ? `${slot[0]} to ${slot[1]}` : 'Closed';
        const last = rows.at(-1);

        if (last && last.time === time) {
            last.to = day;
        } else {
            rows.push({ time, from: day, to: day });
        }
    });

    return rows.map((r) => ({
        days:
            r.from === r.to
                ? DAY_NAMES[r.from]
                : `${DAY_NAMES[r.from]} to ${DAY_NAMES[r.to]}`,
        time: r.time,
    }));
};

/** The appointment card follows the visitor between pages until they book. */
export type CardDraft = { treatment?: number; branch?: number; date?: string };

const KEY = 'glutarepubliq-card';

export function useCardDraft(): [CardDraft, (patch: CardDraft) => void] {
    const [draft, setDraft] = useState<CardDraft>({});

    useEffect(() => {
        try {
            setDraft(
                JSON.parse(localStorage.getItem(KEY) ?? '{}') as CardDraft,
            );
        } catch {
            // Private mode or blocked storage: the card simply starts empty.
        }
    }, []);

    const update = (patch: CardDraft) =>
        setDraft((current) => {
            const next = { ...current, ...patch };

            try {
                localStorage.setItem(KEY, JSON.stringify(next));
            } catch {
                // Not critical; the card still works for this page.
            }

            return next;
        });

    return [draft, update];
}

/** Adds .is-in to [data-reveal] elements as they scroll into view. */
export function useReveal(): void {
    useEffect(() => {
        const root = document.documentElement;
        const items = document.querySelectorAll<HTMLElement>('[data-reveal]');

        if (!('IntersectionObserver' in window) || items.length === 0) {
            return;
        }

        root.classList.add('reveal-ready');
        const observer = new IntersectionObserver(
            (entries) =>
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-in');
                        observer.unobserve(entry.target);
                    }
                }),
            { rootMargin: '0px 0px -8% 0px' },
        );
        items.forEach((item) => observer.observe(item));

        return () => {
            observer.disconnect();
            root.classList.remove('reveal-ready');
        };
    }, []);
}
