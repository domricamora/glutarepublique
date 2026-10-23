import { useId, useState } from 'react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

/*
 * The dashboard's charts, hand-drawn in SVG.
 *
 * No charting library: the scale is the part that matters, and a library
 * makes a chart that does not start at zero easy to draw by accident. Every
 * chart here starts at zero, has one axis, and answers hover with the exact
 * figure, because a chart nobody can read the number off is decoration.
 *
 * Colours come from the --chart-* tokens (validated for colour blindness in
 * light and dark), in a fixed order: series 1 is always ocean.
 */

export const SERIES = [
    'var(--chart-1)',
    'var(--chart-2)',
    'var(--chart-3)',
    'var(--chart-4)',
    'var(--chart-5)',
];

const compact = new Intl.NumberFormat('en-PH', {
    notation: 'compact',
    maximumFractionDigits: 1,
});

/** A round number at or above the peak, so gridlines land on readable values. */
const niceMax = (peak: number): number => {
    if (peak <= 0) return 1;
    const step = 10 ** Math.floor(Math.log10(peak));
    const unit = [1, 2, 2.5, 5, 10].find((u) => u * step >= peak) ?? 10;

    return unit * step;
};

function Tip({ x, children }: { x: number; children: ReactNode }) {
    return (
        <div
            role="status"
            className="pointer-events-none absolute top-0 z-10 -translate-x-1/2 border border-border bg-popover px-3 py-2 text-xs whitespace-nowrap text-popover-foreground"
            style={{ left: `clamp(4.5rem, ${x}%, calc(100% - 4.5rem))` }}
        >
            {children}
        </div>
    );
}

/** A small trend line for a KPI tile. Decorative: the tile states the figure. */
export function Sparkline({
    values,
    className,
}: {
    values: number[];
    className?: string;
}) {
    const id = useId();
    if (values.length < 2) return null;

    const peak = Math.max(1, ...values);
    const pts = values.map((v, i) => [
        (i / (values.length - 1)) * 100,
        30 - (Math.max(0, v) / peak) * 26,
    ]);
    const line = pts
        .map(([x, y], i) => `${i ? 'L' : 'M'}${x.toFixed(2)} ${y.toFixed(2)}`)
        .join(' ');

    return (
        <svg
            viewBox="0 0 100 32"
            preserveAspectRatio="none"
            className={cn('h-8 w-full', className)}
            aria-hidden
        >
            <defs>
                <linearGradient id={id} x1="0" x2="0" y1="0" y2="1">
                    <stop
                        offset="0%"
                        stopColor="var(--chart-1)"
                        stopOpacity="0.22"
                    />
                    <stop
                        offset="100%"
                        stopColor="var(--chart-1)"
                        stopOpacity="0"
                    />
                </linearGradient>
            </defs>
            <path d={`${line} L100 32 L0 32 Z`} fill={`url(#${id})`} />
            <path
                d={line}
                fill="none"
                stroke="var(--chart-1)"
                strokeWidth="1.5"
                vectorEffect="non-scaling-stroke"
                strokeLinejoin="round"
            />
        </svg>
    );
}

type Series = { key: string; label: string; color: string; dashed?: boolean };

/**
 * One or two series over time on a single axis, with gridlines, a crosshair
 * and a tooltip. The first series is filled; the second is a line.
 */
export function TrendChart<T extends { label: string }>({
    points,
    series,
    format,
    height = 240,
    className,
}: {
    points: T[];
    series: Series[];
    format: (n: number) => string;
    height?: number;
    className?: string;
}) {
    const id = useId();
    const [hover, setHover] = useState<number | null>(null);
    const value = (p: T, key: string) =>
        Number((p as Record<string, unknown>)[key] ?? 0);
    const max = niceMax(
        Math.max(
            0,
            ...points.flatMap((p) => series.map((s) => value(p, s.key))),
        ),
    );
    const W = 600;
    const H = height;
    const x = (i: number) =>
        points.length <= 1 ? 0 : (i / (points.length - 1)) * W;
    const y = (v: number) => H - (Math.max(0, v) / max) * (H - 8);
    const path = (key: string) =>
        points
            .map(
                (p, i) =>
                    `${i ? 'L' : 'M'}${x(i).toFixed(1)} ${y(value(p, key)).toFixed(1)}`,
            )
            .join(' ');
    const ticks = [0, 0.5, 1].map((t) => t * max);
    const labelAt = [0, Math.floor((points.length - 1) / 2), points.length - 1];

    const onMove = (e: React.PointerEvent<SVGSVGElement>) => {
        const box = e.currentTarget.getBoundingClientRect();
        const i = Math.round(
            ((e.clientX - box.left) / box.width) * (points.length - 1),
        );
        setHover(Math.min(points.length - 1, Math.max(0, i)));
    };

    if (points.length === 0) return null;

    return (
        <figure className={cn('space-y-3', className)}>
            <div className="relative pl-12">
                {/* The axis labels are HTML so they never stretch with the plot. */}
                {ticks.map((t) => (
                    <span
                        key={t}
                        className="absolute left-0 -translate-y-1/2 text-[0.6875rem] text-muted-foreground tabular-nums"
                        style={{ top: `${(y(t) / H) * 100}%` }}
                    >
                        {compact.format(t)}
                    </span>
                ))}
                <svg
                    viewBox={`0 0 ${W} ${H}`}
                    preserveAspectRatio="none"
                    style={{ height }}
                    className="w-full touch-none overflow-visible"
                    role="img"
                    aria-label={`${series.map((s) => s.label).join(' and ')} from ${points[0].label} to ${points.at(-1)?.label}. Latest ${format(value(points.at(-1) as T, series[0].key))}.`}
                    onPointerMove={onMove}
                    onPointerLeave={() => setHover(null)}
                >
                    <defs>
                        <linearGradient id={id} x1="0" x2="0" y1="0" y2="1">
                            <stop
                                offset="0%"
                                stopColor={series[0].color}
                                stopOpacity="0.2"
                            />
                            <stop
                                offset="100%"
                                stopColor={series[0].color}
                                stopOpacity="0"
                            />
                        </linearGradient>
                    </defs>
                    {ticks.map((t) => (
                        <line
                            key={t}
                            x1={0}
                            x2={W}
                            y1={y(t)}
                            y2={y(t)}
                            stroke="currentColor"
                            strokeOpacity={t === 0 ? 0.25 : 0.08}
                            vectorEffect="non-scaling-stroke"
                        />
                    ))}
                    <path
                        d={`${path(series[0].key)} L${W} ${H} L0 ${H} Z`}
                        fill={`url(#${id})`}
                    />
                    {series.map((s) => (
                        <path
                            key={s.key}
                            d={path(s.key)}
                            fill="none"
                            stroke={s.color}
                            strokeWidth="2"
                            strokeDasharray={s.dashed ? '5 4' : undefined}
                            strokeLinejoin="round"
                            vectorEffect="non-scaling-stroke"
                        />
                    ))}
                    {hover !== null && (
                        <line
                            x1={x(hover)}
                            x2={x(hover)}
                            y1={0}
                            y2={H}
                            stroke="currentColor"
                            strokeOpacity="0.25"
                            vectorEffect="non-scaling-stroke"
                        />
                    )}
                </svg>
                {hover !== null && (
                    <>
                        {series.map((s) => (
                            <span
                                key={s.key}
                                aria-hidden
                                className="pointer-events-none absolute size-2.5 -translate-x-1/2 -translate-y-1/2 rounded-full ring-2 ring-card"
                                style={{
                                    background: s.color,
                                    left: `calc(3rem + (100% - 3rem) * ${x(hover) / W})`,
                                    top: `${(y(value(points[hover], s.key)) / H) * 100}%`,
                                }}
                            />
                        ))}
                        <Tip x={(x(hover) / W) * 100}>
                            <p className="font-medium">{points[hover].label}</p>
                            {series.map((s) => (
                                <p
                                    key={s.key}
                                    className="mt-0.5 flex items-center gap-2 tabular-nums"
                                >
                                    <span
                                        className="size-2 rounded-full"
                                        style={{ background: s.color }}
                                        aria-hidden
                                    />
                                    {s.label}{' '}
                                    <span className="ml-auto pl-3 font-medium">
                                        {format(value(points[hover], s.key))}
                                    </span>
                                </p>
                            ))}
                        </Tip>
                    </>
                )}
            </div>
            <div className="flex justify-between pl-12 text-[0.6875rem] text-muted-foreground">
                {labelAt.map((i, n) => (
                    <span key={n}>{points[i]?.label}</span>
                ))}
            </div>
            {series.length > 1 && (
                <figcaption className="flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground">
                    {series.map((s) => (
                        <span key={s.key} className="flex items-center gap-1.5">
                            <span
                                className={cn(
                                    'h-0.5 w-4',
                                    s.dashed &&
                                        'border-t-2 border-dashed bg-transparent',
                                )}
                                style={
                                    s.dashed
                                        ? { borderColor: s.color }
                                        : { background: s.color }
                                }
                                aria-hidden
                            />
                            {s.label}
                        </span>
                    ))}
                </figcaption>
            )}
        </figure>
    );
}

/** Vertical columns, one per period, with the figure on hover. */
export function ColumnChart({
    points,
    format,
    color = SERIES[0],
    height = 180,
}: {
    points: { label: string; value: number | null }[];
    format: (n: number) => string;
    color?: string;
    height?: number;
}) {
    const [hover, setHover] = useState<number | null>(null);
    const max = niceMax(Math.max(0, ...points.map((p) => p.value ?? 0)));

    return (
        <figure className="space-y-2">
            <div className="relative">
                <div
                    className="flex items-end gap-1.5"
                    style={{ height }}
                    role="img"
                    aria-label={`Monthly totals; latest ${format(points.at(-1)?.value ?? 0)}.`}
                >
                    {points.map((p, i) => (
                        <div
                            key={p.label + i}
                            className="group flex h-full flex-1 cursor-default items-end"
                            onPointerEnter={() => setHover(i)}
                            onPointerLeave={() => setHover(null)}
                        >
                            <div
                                className="w-full transition-opacity duration-150"
                                style={{
                                    height: `${Math.max(1.5, ((p.value ?? 0) / max) * 100)}%`,
                                    background: color,
                                    opacity:
                                        hover === null || hover === i
                                            ? 1
                                            : 0.45,
                                }}
                            />
                        </div>
                    ))}
                </div>
                {hover !== null && (
                    <Tip x={((hover + 0.5) / points.length) * 100}>
                        <p className="font-medium">{points[hover].label}</p>
                        <p className="mt-0.5 tabular-nums">
                            {format(points[hover].value ?? 0)}
                        </p>
                    </Tip>
                )}
            </div>
            <div className="flex gap-1.5 text-center text-[0.6875rem] text-muted-foreground">
                {points.map((p, i) => (
                    <span key={p.label + i} className="flex-1 truncate">
                        {p.label}
                    </span>
                ))}
            </div>
        </figure>
    );
}

/** Ranked horizontal bars with the label and figure printed, so no hover is needed. */
export function HBars({
    rows,
    format,
    color = SERIES[0],
}: {
    rows: { label: string; value: number; note?: string }[];
    format: (n: number) => string;
    color?: string;
}) {
    const max = Math.max(1, ...rows.map((r) => r.value));

    return (
        <ul className="space-y-3.5">
            {rows.map((r) => (
                <li key={r.label}>
                    <div className="flex items-baseline gap-3 text-sm">
                        <span className="min-w-0 truncate">{r.label}</span>
                        {r.note && (
                            <span className="text-xs text-muted-foreground">
                                {r.note}
                            </span>
                        )}
                        <span className="ml-auto font-medium tabular-nums">
                            {format(r.value)}
                        </span>
                    </div>
                    <div
                        className="mt-1.5 h-2"
                        style={{
                            width: `${Math.max(1.5, (r.value / max) * 100)}%`,
                            background: color,
                        }}
                    />
                </li>
            ))}
        </ul>
    );
}

/** Shares of a whole as a ring, legend beside it with percentages and figures. */
export function Donut({
    segments,
    format,
    center,
}: {
    segments: { label: string; value: number }[];
    format: (n: number) => string;
    center?: { value: string; label: string };
}) {
    // Past five categories the rest fold into one slice, rather than inventing hues.
    const shown =
        segments.length > 5
            ? [
                  ...segments.slice(0, 4),
                  {
                      label: 'Everything else',
                      value: segments.slice(4).reduce((s, x) => s + x.value, 0),
                  },
              ]
            : segments;
    const total = shown.reduce((s, x) => s + Math.max(0, x.value), 0);
    const r = 40;
    const c = 2 * Math.PI * r;
    const gap = shown.filter((s) => s.value > 0).length > 1 ? 1.2 : 0;
    const share = (v: number) =>
        total > 0 ? Math.round((Math.max(0, v) / total) * 100) : 0;
    let offset = 0;

    return (
        <div className="flex flex-wrap items-center gap-6">
            <div className="relative size-36 shrink-0">
                <svg
                    viewBox="0 0 100 100"
                    className="size-full -rotate-90"
                    role="img"
                    aria-label={shown
                        .map((s) => `${s.label} ${share(s.value)}%`)
                        .join(', ')}
                >
                    <circle
                        cx="50"
                        cy="50"
                        r={r}
                        fill="none"
                        strokeWidth="13"
                        className="stroke-muted"
                    />
                    {shown.map((s, i) => {
                        const len =
                            total > 0 ? (Math.max(0, s.value) / total) * c : 0;
                        const drawn = Math.max(0, len - gap);
                        const el = (
                            <circle
                                key={s.label}
                                cx="50"
                                cy="50"
                                r={r}
                                fill="none"
                                strokeWidth="13"
                                stroke={SERIES[i % SERIES.length]}
                                strokeDasharray={`${drawn} ${c - drawn}`}
                                strokeDashoffset={-offset}
                            />
                        );
                        offset += len;

                        return el;
                    })}
                </svg>
                {center && (
                    <div className="absolute inset-0 flex flex-col items-center justify-center text-center">
                        <span className="font-display text-xl tabular-nums">
                            {center.value}
                        </span>
                        <span className="text-[0.6875rem] text-muted-foreground">
                            {center.label}
                        </span>
                    </div>
                )}
            </div>
            <ul className="min-w-[11rem] flex-1 space-y-2 text-sm">
                {shown.map((s, i) => (
                    <li key={s.label} className="flex items-center gap-2">
                        <span
                            className="size-2.5 shrink-0 rounded-full"
                            style={{ background: SERIES[i % SERIES.length] }}
                            aria-hidden
                        />
                        <span className="truncate text-muted-foreground">
                            {s.label}
                        </span>
                        <span className="ml-auto tabular-nums">
                            {share(s.value)}%
                        </span>
                        <span className="w-20 text-right text-xs text-muted-foreground tabular-nums">
                            {format(s.value)}
                        </span>
                    </li>
                ))}
            </ul>
        </div>
    );
}

/**
 * The enquiry funnel: how many leads reached at least each stage. Cumulative
 * from the bottom, because a lead now at "Customer" also passed every stage
 * before it; counting only the current stage would draw a funnel that grows.
 */
export function Funnel({
    stages,
}: {
    stages: { label: string; count: number }[];
}) {
    const reached = stages.map((_, i) =>
        stages.slice(i).reduce((s, x) => s + x.count, 0),
    );
    const top = Math.max(1, reached[0] ?? 0);

    return (
        <ol className="space-y-2">
            {stages.map((s, i) => (
                <li
                    key={s.label}
                    className="grid grid-cols-[9.5rem_1fr_3.5rem] items-center gap-3 text-sm max-sm:grid-cols-[7rem_1fr_3rem]"
                >
                    <span className="truncate text-muted-foreground">
                        {s.label}
                    </span>
                    <span
                        className="h-6"
                        style={{
                            width: `${Math.max(2, (reached[i] / top) * 100)}%`,
                            background: SERIES[0],
                            opacity: 1 - i * 0.07,
                        }}
                    />
                    <span className="text-right tabular-nums">
                        {reached[i]}
                        <span className="ml-1 text-xs text-muted-foreground">
                            {Math.round((reached[i] / top) * 100)}%
                        </span>
                    </span>
                </li>
            ))}
        </ol>
    );
}
