import { Head, router } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Donut, HBars, SERIES, TrendChart } from '@/components/admin/charts';
import { ago } from '@/lib/admin';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import traffic from '@/routes/admin/traffic';

type Group = { label: string; views: number; visitors: number };

type Props = {
    days: number;
    windows: number[];
    totals: {
        views: number;
        visitors: number;
        per_visit: number | null;
        enquiries: number;
        conversion: number | null;
    };
    daily: { label: string; views: number; visitors: number }[];
    pages: Group[];
    sources: Group[];
    campaigns: Group[];
    devices: Group[];
    log: {
        id: number;
        path: string;
        source: string;
        campaign: string | null;
        referrer: string | null;
        device: string;
        at: string;
    }[];
    live: number;
};

const count = new Intl.NumberFormat('en-PH');
const n = (v: number) => count.format(v);

function Tile({
    label,
    value,
    note,
}: {
    label: string;
    value: string;
    note: string;
}) {
    return (
        <div className="border border-border bg-card p-5">
            <p className="text-sm text-muted-foreground">{label}</p>
            <p className="mt-2 font-display text-3xl tabular-nums">{value}</p>
            <p className="mt-1 text-xs text-muted-foreground">{note}</p>
        </div>
    );
}

function Panel({
    title,
    note,
    children,
}: {
    title: string;
    note?: string;
    children: ReactNode;
}) {
    return (
        <section className="border border-border bg-card p-5 md:p-6">
            <h2 className="font-display text-xl">{title}</h2>
            {note && (
                <p className="mt-1 text-sm text-muted-foreground">{note}</p>
            )}
            <div className="mt-5">{children}</div>
        </section>
    );
}

const empty = (
    <p className="text-sm text-muted-foreground">
        Nothing recorded in this period.
    </p>
);

const bars = (rows: Group[]) =>
    rows.length ? (
        <HBars
            rows={rows.map((r) => ({
                label: r.label,
                value: r.views,
                note: `${n(r.visitors)} visitors`,
            }))}
            format={n}
        />
    ) : (
        empty
    );

export default function Traffic(props: Props) {
    const { totals } = props;

    return (
        <>
            <Head title="Website traffic" />
            <div className="flex flex-1 flex-col gap-6 px-4 py-6 md:px-8 md:py-8">
                <header className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h1 className="font-display text-3xl md:text-4xl">
                            Website traffic
                        </h1>
                        <p className="mt-2 max-w-2xl text-muted-foreground">
                            Counted by the website itself: no outside tracker,
                            no cookies, and no IP address kept.
                            {props.live > 0 &&
                                ` ${props.live} on the site in the last 30 minutes.`}
                        </p>
                    </div>
                    <div
                        className="flex border border-border"
                        role="group"
                        aria-label="Period"
                    >
                        {props.windows.map((d) => (
                            <button
                                key={d}
                                type="button"
                                aria-pressed={d === props.days}
                                onClick={() =>
                                    router.get(
                                        traffic.index({ query: { days: d } })
                                            .url,
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                                className={cn(
                                    'px-3 py-2 text-sm transition-colors duration-150 ease-out',
                                    d === props.days
                                        ? 'bg-plum text-white'
                                        : 'text-muted-foreground hover:bg-mist hover:text-foreground dark:hover:bg-white/5',
                                )}
                            >
                                {d} days
                            </button>
                        ))}
                    </div>
                </header>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <Tile
                        label="Visitors"
                        value={n(totals.visitors)}
                        note="Unique per day, added up"
                    />
                    <Tile
                        label="Page views"
                        value={n(totals.views)}
                        note={
                            totals.per_visit
                                ? `${totals.per_visit} pages per visitor`
                                : 'No visits yet'
                        }
                    />
                    <Tile
                        label="Online enquiries"
                        value={n(totals.enquiries)}
                        note="Bookings, forms and chats from the web"
                    />
                    <Tile
                        label="Visitor to enquiry"
                        value={
                            totals.conversion === null
                                ? 'n/a'
                                : `${totals.conversion}%`
                        }
                        note="Share of visitors who got in touch"
                    />
                </div>

                <Panel
                    title="Visitors and page views"
                    note={`The last ${props.days} days`}
                >
                    <TrendChart
                        points={props.daily}
                        series={[
                            {
                                key: 'views',
                                label: 'Page views',
                                color: SERIES[0],
                            },
                            {
                                key: 'visitors',
                                label: 'Visitors',
                                color: SERIES[1],
                            },
                        ]}
                        format={n}
                    />
                </Panel>

                <div className="grid gap-6 lg:grid-cols-2">
                    <Panel title="Top pages" note="By views">
                        {bars(props.pages)}
                    </Panel>
                    <Panel
                        title="Where visitors come from"
                        note="Campaign tag, else the site that sent them, else direct"
                    >
                        {bars(props.sources)}
                    </Panel>
                    <Panel
                        title="Campaigns"
                        note="Links tagged with utm_campaign"
                    >
                        {bars(props.campaigns)}
                    </Panel>
                    <Panel title="Devices">
                        {props.devices.length ? (
                            <Donut
                                segments={props.devices.map((d) => ({
                                    label: d.label,
                                    value: d.views,
                                }))}
                                format={n}
                                center={{
                                    value: n(totals.views),
                                    label: 'views',
                                }}
                            />
                        ) : (
                            empty
                        )}
                    </Panel>
                </div>

                <Panel title="Traffic log" note="The latest 50 page views">
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[40rem] text-sm">
                            <thead>
                                <tr className="border-b border-border text-left text-muted-foreground">
                                    <th className="py-2 pr-4 font-normal">
                                        When
                                    </th>
                                    <th className="py-2 pr-4 font-normal">
                                        Page
                                    </th>
                                    <th className="py-2 pr-4 font-normal">
                                        Source
                                    </th>
                                    <th className="py-2 pr-4 font-normal">
                                        Campaign
                                    </th>
                                    <th className="py-2 font-normal">Device</th>
                                </tr>
                            </thead>
                            <tbody>
                                {props.log.map((v) => (
                                    <tr
                                        key={v.id}
                                        className="border-b border-border last:border-0"
                                    >
                                        <td className="py-2 pr-4 whitespace-nowrap text-muted-foreground">
                                            {ago(v.at)}
                                        </td>
                                        <td className="py-2 pr-4 break-all">
                                            {v.path}
                                        </td>
                                        <td className="py-2 pr-4">
                                            {v.source}
                                            {v.referrer &&
                                                v.referrer !== v.source && (
                                                    <span className="block text-xs text-muted-foreground">
                                                        {v.referrer}
                                                    </span>
                                                )}
                                        </td>
                                        <td className="py-2 pr-4">
                                            {v.campaign ?? '—'}
                                        </td>
                                        <td className="py-2 capitalize">
                                            {v.device}
                                        </td>
                                    </tr>
                                ))}
                                {props.log.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="py-8 text-center text-muted-foreground"
                                        >
                                            No visits recorded yet.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </Panel>
            </div>
        </>
    );
}

Traffic.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Website traffic', href: traffic.index() },
    ],
};
