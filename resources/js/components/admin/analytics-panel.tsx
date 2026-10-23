import { Link } from '@inertiajs/react';
import { ArrowDownRight, ArrowUpRight } from 'lucide-react';
import type { ReactNode } from 'react';
import {
    ColumnChart,
    Donut,
    Funnel,
    HBars,
    SERIES,
    Sparkline,
    TrendChart,
} from '@/components/admin/charts';
import { sourceLabel } from '@/lib/admin';
import { money } from '@/lib/pos';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';

type Split = { new: number; returning: number; retention: number | null };
type Use = {
    rate: number | null;
    booked_hours: number;
    capacity_hours: number;
    no_show_rate: number | null;
};

export type Analytics = {
    window: { days: number; from: string; to: string };
    daily: {
        date: string;
        revenue: number;
        profit: number;
        bookings: number;
        completed: number;
        no_show: number;
        leads: number;
    }[];
    bookings: { status: string; label: string; count: number }[];
    front_desk: {
        leads: number;
        won: number;
        conversion: number | null;
        conversations: number;
        open_conversations: number;
    };
    money: {
        revenue: number;
        profit: number;
        previous_revenue: number;
        previous_profit: number;
        refunds: number;
        discounts: number;
        cost_of_goods: number;
        sales: number;
        average_sale: number;
    } | null;
    top_treatments: { name: string; sold: number; revenue: number }[] | null;
    payment_mix:
        | { method: string; label: string; amount: number; count: number }[]
        | null;
    patients: (Split & { previous: Split }) | null;
    utilisation: (Use & { previous: Use }) | null;
    practitioners:
        | { name: string; title: string; visits: number; value: number }[]
        | null;
    branches: { name: string; revenue: number | null; visits: number }[] | null;
    categories: { name: string; revenue: number }[] | null;
    monthly: { month: string; revenue: number | null; visits: number | null }[];
    funnel: { stage: string; label: string; count: number }[] | null;
    sources: { source: string; leads: number; won: number }[] | null;
};

const WINDOWS: [number, string][] = [
    [7, '7 days'],
    [30, '30 days'],
    [90, '90 days'],
    [365, '12 months'],
];

const peso0 = new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    maximumFractionDigits: 0,
});
const pesos = (n: number) => peso0.format(n);
const count = (n: number) => new Intl.NumberFormat('en-PH').format(n);
const pct = (n: number | null) =>
    n === null ? 'n/a' : `${Math.round(n * 100)}%`;
const day = (iso: string) =>
    new Date(`${iso}T12:00:00`).toLocaleDateString('en-PH', {
        month: 'short',
        day: 'numeric',
    });
const month = (ym: string) =>
    new Date(`${ym}-15T12:00:00`).toLocaleDateString('en-PH', {
        month: 'short',
    });

/**
 * Change against the previous period, or nothing when there is nothing to
 * compare. Growing from zero is not an infinite percentage. Rates compare in
 * points, because "utilisation up 50%" from 20% to 30% misleads.
 */
function Delta({
    now,
    before,
    points = false,
    invert = false,
}: {
    now: number | null;
    before: number | null;
    points?: boolean;
    invert?: boolean;
}) {
    if (now === null || before === null || (!points && before === 0)) {
        return (
            <span className="text-xs text-muted-foreground">
                No earlier period to compare
            </span>
        );
    }

    const diff = points
        ? (now - before) * 100
        : ((now - before) / Math.abs(before)) * 100;

    // A change that rounds to nothing is no change; "-0%" with a down arrow says otherwise.
    if (Math.abs(diff) < (points ? 0.05 : 0.5)) {
        return (
            <span className="text-xs text-muted-foreground">
                Level with the previous period
            </span>
        );
    }

    const up = diff >= 0;
    const good = invert ? !up : up;
    const Icon = up ? ArrowUpRight : ArrowDownRight;

    return (
        <span
            className={cn(
                'inline-flex items-center gap-1 text-xs font-medium tabular-nums',
                good
                    ? 'text-emerald-700 dark:text-emerald-400'
                    : 'text-rose-ink dark:text-lilac',
            )}
        >
            <Icon className="size-3.5" aria-hidden />
            {up ? '+' : ''}
            {diff.toFixed(points ? 1 : 0)}
            {points ? ' pts' : '%'}
            <span className="font-normal text-muted-foreground">
                vs previous
            </span>
        </span>
    );
}

function Kpi({
    label,
    value,
    sub,
    delta,
    spark,
}: {
    label: string;
    value: string;
    sub?: string;
    delta?: ReactNode;
    spark?: number[];
}) {
    return (
        <div className="flex flex-col justify-between gap-3 border border-border bg-card p-5">
            <div>
                <p className="text-sm text-muted-foreground">{label}</p>
                <p className="mt-1.5 font-display text-[2rem] leading-none tracking-[-0.01em] tabular-nums">
                    {value}
                </p>
                {sub && (
                    <p className="mt-2 text-xs text-muted-foreground">{sub}</p>
                )}
            </div>
            {spark && <Sparkline values={spark} className="-mx-1" />}
            {delta}
        </div>
    );
}

function Card({
    title,
    note,
    children,
    className,
}: {
    title: string;
    note?: string;
    children: ReactNode;
    className?: string;
}) {
    return (
        <section
            className={cn('border border-border bg-card p-5 md:p-6', className)}
        >
            <h3 className="text-base font-medium">{title}</h3>
            {note && (
                <p className="mt-0.5 text-sm text-muted-foreground">{note}</p>
            )}
            <div className="mt-5">{children}</div>
        </section>
    );
}

export default function AnalyticsPanel({
    analytics: a,
}: {
    analytics: Analytics;
}) {
    const cash = a.money;
    const seen = a.patients ? a.patients.new + a.patients.returning : null;
    const seenBefore = a.patients
        ? a.patients.previous.new + a.patients.previous.returning
        : null;
    const daily = a.daily.map((d) => ({ ...d, label: day(d.date) }));
    const lastYear = a.monthly.map((m) => ({ ...m, label: month(m.month) }));

    return (
        <div className="space-y-6">
            <header className="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 className="font-display text-2xl">
                        How the clinic is doing
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        {day(a.window.from)} to {day(a.window.to)}, against the{' '}
                        {a.window.days === 365
                            ? 'year'
                            : `${a.window.days} days`}{' '}
                        before
                    </p>
                </div>
                <div
                    className="flex border border-border bg-card p-0.5"
                    role="group"
                    aria-label="Reporting period"
                >
                    {WINDOWS.map(([days, label]) => (
                        <Link
                            key={days}
                            href={dashboard({ query: { days } }).url}
                            preserveScroll
                            aria-current={
                                a.window.days === days ? 'true' : undefined
                            }
                            className={cn(
                                'px-3 py-1.5 text-sm transition-colors duration-150 ease-out',
                                a.window.days === days
                                    ? 'bg-plum text-white'
                                    : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                            )}
                        >
                            {label}
                        </Link>
                    ))}
                </div>
            </header>

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                {cash && (
                    <>
                        <Kpi
                            label="Revenue"
                            value={pesos(cash.revenue)}
                            sub={`${count(cash.sales)} sales, net of ${pesos(cash.refunds)} refunds`}
                            spark={a.daily.map((d) => d.revenue)}
                            delta={
                                <Delta
                                    now={cash.revenue}
                                    before={cash.previous_revenue}
                                />
                            }
                        />
                        <Kpi
                            label="Gross profit"
                            value={pesos(cash.profit)}
                            sub={
                                cash.revenue > 0
                                    ? `${Math.round((cash.profit / cash.revenue) * 100)}% margin before wages and rent`
                                    : 'Before wages and rent'
                            }
                            spark={a.daily.map((d) => d.profit)}
                            delta={
                                <Delta
                                    now={cash.profit}
                                    before={cash.previous_profit}
                                />
                            }
                        />
                        <Kpi
                            label="Average ticket"
                            value={pesos(cash.average_sale)}
                            sub={`${pesos(cash.discounts)} given in discounts`}
                        />
                    </>
                )}
                {a.patients && (
                    <Kpi
                        label="Patients seen"
                        value={count(seen ?? 0)}
                        sub={`${count(a.patients.new)} new, ${count(a.patients.returning)} returning`}
                        spark={a.daily.map((d) => d.completed)}
                        delta={<Delta now={seen} before={seenBefore} />}
                    />
                )}
                {a.utilisation && (
                    <Kpi
                        label="Chair utilisation"
                        value={pct(a.utilisation.rate)}
                        sub={`${count(a.utilisation.booked_hours)} of ${count(a.utilisation.capacity_hours)} practitioner hours booked`}
                        delta={
                            <Delta
                                now={a.utilisation.rate}
                                before={a.utilisation.previous.rate}
                                points
                            />
                        }
                    />
                )}
                <Kpi
                    label="Enquiry conversion"
                    value={pct(a.front_desk.conversion)}
                    sub={`${count(a.front_desk.won)} of ${count(a.front_desk.leads)} enquiries became customers`}
                    spark={a.daily.map((d) => d.leads)}
                />
                {a.utilisation && (
                    <Kpi
                        label="No-show rate"
                        value={pct(a.utilisation.no_show_rate)}
                        sub="Of visits that were due"
                        delta={
                            <Delta
                                now={a.utilisation.no_show_rate}
                                before={a.utilisation.previous.no_show_rate}
                                points
                                invert
                            />
                        }
                    />
                )}
                {a.patients && (
                    <Kpi
                        label="Returning patients"
                        value={pct(a.patients.retention)}
                        sub="Seen before this period"
                        delta={
                            <Delta
                                now={a.patients.retention}
                                before={a.patients.previous.retention}
                                points
                            />
                        }
                    />
                )}
            </div>

            {cash && (
                <div className="grid gap-4 xl:grid-cols-3">
                    <Card
                        className="xl:col-span-2"
                        title="Revenue and gross profit"
                        note="Daily, net of refunds. Gross profit is takings less the cost of goods sold; treatments carry no recorded cost, which flatters it."
                    >
                        <TrendChart
                            points={daily}
                            format={pesos}
                            series={[
                                {
                                    key: 'revenue',
                                    label: 'Revenue',
                                    color: SERIES[0],
                                },
                                {
                                    key: 'profit',
                                    label: 'Gross profit',
                                    color: SERIES[1],
                                    dashed: true,
                                },
                            ]}
                        />
                    </Card>
                    {a.categories && (
                        <Card
                            title="Where the money came from"
                            note="Revenue by treatment category"
                        >
                            {a.categories.length > 0 ? (
                                <Donut
                                    segments={a.categories.map((c) => ({
                                        label: c.name,
                                        value: c.revenue,
                                    }))}
                                    format={pesos}
                                    center={{
                                        value: new Intl.NumberFormat('en-PH', {
                                            notation: 'compact',
                                            maximumFractionDigits: 1,
                                        }).format(cash.revenue),
                                        label: 'pesos',
                                    }}
                                />
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    No sales in this period.
                                </p>
                            )}
                        </Card>
                    )}
                </div>
            )}

            <div className="grid gap-4 lg:grid-cols-2">
                {lastYear.some((m) => m.revenue !== null) && (
                    <Card
                        title="The last 12 months: revenue"
                        note="Net takings per calendar month"
                    >
                        <ColumnChart
                            points={lastYear.map((m) => ({
                                label: m.label,
                                value: m.revenue,
                            }))}
                            format={pesos}
                        />
                    </Card>
                )}
                {lastYear.some((m) => m.visits !== null) && (
                    <Card
                        title="The last 12 months: visits"
                        note="Completed visits per calendar month"
                    >
                        <ColumnChart
                            points={lastYear.map((m) => ({
                                label: m.label,
                                value: m.visits,
                            }))}
                            format={count}
                            color={SERIES[1]}
                        />
                    </Card>
                )}
            </div>

            <div className="grid gap-4 lg:grid-cols-3">
                {a.branches && (
                    <Card
                        title="Branches"
                        note={
                            cash
                                ? 'Revenue, with completed visits'
                                : 'Completed visits'
                        }
                    >
                        <HBars
                            rows={a.branches.map((b) => ({
                                label: b.name,
                                value: b.revenue ?? b.visits,
                                note:
                                    b.revenue !== null
                                        ? `${count(b.visits)} visits`
                                        : undefined,
                            }))}
                            format={cash ? pesos : count}
                        />
                    </Card>
                )}
                {a.practitioners && (
                    <Card
                        title="Busiest practitioners"
                        note="Completed visits and their list value"
                    >
                        {a.practitioners.length > 0 ? (
                            <HBars
                                rows={a.practitioners.map((p) => ({
                                    label: p.name,
                                    value: p.visits,
                                    note: pesos(p.value),
                                }))}
                                format={(n) => `${count(n)} visits`}
                                color={SERIES[1]}
                            />
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                No completed visits in this period.
                            </p>
                        )}
                    </Card>
                )}
                {a.payment_mix && (
                    <Card
                        title="How patients paid"
                        note="Refunds count against the method they went back through"
                    >
                        {a.payment_mix.length > 0 ? (
                            <Donut
                                segments={a.payment_mix.map((p) => ({
                                    label: p.label,
                                    value: p.amount,
                                }))}
                                format={pesos}
                            />
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                No payments in this period.
                            </p>
                        )}
                    </Card>
                )}
            </div>

            {(a.funnel || a.sources) && (
                <div className="grid gap-4 lg:grid-cols-2">
                    {a.funnel && (
                        <Card
                            title="Enquiry funnel"
                            note="Enquiries from this period, by how far they have got"
                        >
                            <Funnel
                                stages={a.funnel.map((f) => ({
                                    label: f.label,
                                    count: f.count,
                                }))}
                            />
                        </Card>
                    )}
                    {a.sources && (
                        <Card
                            title="Where enquiries come from"
                            note="With the share that became customers"
                        >
                            {a.sources.length > 0 ? (
                                <HBars
                                    rows={a.sources.map((s) => ({
                                        label: sourceLabel(s.source),
                                        value: s.leads,
                                        note: `${Math.round((s.won / Math.max(1, s.leads)) * 100)}% won`,
                                    }))}
                                    format={(n) => `${count(n)} leads`}
                                    color={SERIES[2]}
                                />
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    No enquiries in this period.
                                </p>
                            )}
                        </Card>
                    )}
                </div>
            )}

            {a.top_treatments && a.top_treatments.length > 0 && (
                <Card
                    title="Best-selling treatments"
                    note="By revenue at the counter"
                >
                    <HBars
                        rows={a.top_treatments.map((t) => ({
                            label: t.name,
                            value: t.revenue,
                            note: `${count(t.sold)} sold`,
                        }))}
                        format={(n) => money(n)}
                        color={SERIES[3]}
                    />
                </Card>
            )}
        </div>
    );
}
