import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, ChevronLeft, ChevronRight, Download } from 'lucide-react';
import { useState } from 'react';
import { Input } from '@/components/ui/input';
import { money } from '@/lib/pos';
import { cn } from '@/lib/utils';
import accounting from '@/routes/admin/accounting';

type Line = {
    code: string;
    name: string;
    type: string;
    period: number;
    previous: number;
    year: number;
};

type Preset = 'month' | 'quarter' | 'year';

const PRESETS: { value: Preset | null; label: string }[] = [
    { value: 'month', label: 'Month' },
    { value: 'quarter', label: 'Quarter' },
    { value: 'year', label: 'Year' },
    { value: null, label: 'Custom' },
];

/** The trial balance carries its own gross columns; the statements use `year`. */
type TrialLine = Line & { debit: number; credit: number };

type Props = {
    from: string;
    to: string;
    preset: Preset | null;
    compare: { label: string; from: string; to: string };
    trial_balance: TrialLine[];
    trial_totals: { debit: number; credit: number };
    income_statement: {
        revenue: Line[];
        expenses: Line[];
        total_revenue: number;
        total_expenses: number;
        net: number;
        previous_revenue: number;
        previous_expenses: number;
        previous_net: number;
    };
    balance_sheet: {
        assets: Line[];
        liabilities: Line[];
        equity: Line[];
        total_assets: number;
        total_liabilities: number;
        total_equity: number;
        retained: number;
        balances: boolean;
    };
};

const Tabs = ['Trial balance', 'Income', 'Balance sheet'] as const;

export default function AccountingReports(props: Props) {
    const [tab, setTab] = useState<(typeof Tabs)[number]>('Trial balance');
    const year = props.to.slice(0, 4);
    const query = (preset: Preset | null, from: string, to: string) =>
        preset ? { preset, to } : { from, to };

    const go = (from: string, to: string, preset = props.preset) =>
        router.get(
            accounting.reports({ query: query(preset, from, to) }).url,
            {},
            { preserveScroll: true },
        );

    // Step a preset window back or forward by moving the anchor date across its edge.
    const step = (direction: -1 | 1) => {
        const edge = new Date(
            `${direction < 0 ? props.from : props.to}T00:00:00`,
        );
        edge.setDate(edge.getDate() + direction);
        go(props.from, edge.toLocaleDateString('en-CA'));
    };

    return (
        <>
            <Head title="Reports" />
            <div className="flex flex-1 flex-col gap-6 px-4 py-6 md:px-8 md:py-8">
                <header className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <Link
                            href={accounting.index().url}
                            className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Accounting
                        </Link>
                        <h1 className="mt-2 font-display text-3xl md:text-4xl">
                            Reports
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {props.from} to {props.to}, compared with the{' '}
                            {props.compare.label} ({props.compare.from} to{' '}
                            {props.compare.to}). Balance sheet and trial
                            balance: year to date from 1 Jan {year}.
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <div className="flex" role="group" aria-label="Period">
                            {PRESETS.map((p) => (
                                <button
                                    key={p.label}
                                    type="button"
                                    aria-pressed={props.preset === p.value}
                                    onClick={() =>
                                        go(props.from, props.to, p.value)
                                    }
                                    className={cn(
                                        'press -ml-px h-10 border px-3 text-sm first:ml-0',
                                        props.preset === p.value
                                            ? 'relative border-plum bg-lilac/60 text-plum'
                                            : 'border-border bg-background hover:bg-mist dark:hover:bg-white/5',
                                    )}
                                >
                                    {p.label}
                                </button>
                            ))}
                        </div>
                        {props.preset ? (
                            <>
                                <button
                                    type="button"
                                    onClick={() => step(-1)}
                                    aria-label={`Previous ${props.preset}`}
                                    className="press h-10 border border-border px-3 hover:bg-mist dark:hover:bg-white/5"
                                >
                                    <ChevronLeft className="h-4 w-4" />
                                </button>
                                <button
                                    type="button"
                                    onClick={() => step(1)}
                                    aria-label={`Next ${props.preset}`}
                                    className="press h-10 border border-border px-3 hover:bg-mist dark:hover:bg-white/5"
                                >
                                    <ChevronRight className="h-4 w-4" />
                                </button>
                            </>
                        ) : (
                            <>
                                <Input
                                    type="date"
                                    value={props.from}
                                    onChange={(e) =>
                                        go(e.target.value, props.to)
                                    }
                                    aria-label="From"
                                    className="h-10"
                                />
                                <Input
                                    type="date"
                                    value={props.to}
                                    onChange={(e) =>
                                        go(props.from, e.target.value)
                                    }
                                    aria-label="To"
                                    className="h-10"
                                />
                            </>
                        )}
                        {/* A file download, so a plain link rather than an Inertia visit. */}
                        <a
                            href={
                                accounting.reports.export({
                                    query: query(
                                        props.preset,
                                        props.from,
                                        props.to,
                                    ),
                                }).url
                            }
                            className="press inline-flex h-10 items-center gap-2 border border-border px-4 text-sm whitespace-nowrap hover:bg-mist dark:hover:bg-white/5"
                        >
                            <Download className="h-4 w-4" />
                            Export CSV
                        </a>
                    </div>
                </header>

                <div className="flex flex-wrap gap-2">
                    {Tabs.map((name) => (
                        <button
                            key={name}
                            type="button"
                            onClick={() => setTab(name)}
                            aria-pressed={tab === name}
                            className={cn(
                                'press h-10 border px-4 text-sm',
                                tab === name
                                    ? 'border-plum bg-lilac/60 text-plum'
                                    : 'border-border bg-background hover:bg-mist dark:hover:bg-white/5',
                            )}
                        >
                            {name}
                        </button>
                    ))}
                </div>

                {tab === 'Trial balance' && (
                    <div className="overflow-x-auto border border-border">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-border text-left text-muted-foreground">
                                    <th className="px-4 py-3 font-normal">
                                        Code
                                    </th>
                                    <th className="px-4 py-3 font-normal">
                                        Account
                                    </th>
                                    <th className="px-4 py-3 text-right font-normal">
                                        Debit
                                    </th>
                                    <th className="px-4 py-3 text-right font-normal">
                                        Credit
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {props.trial_balance.map((line) => (
                                    <tr
                                        key={line.code}
                                        className="border-b border-border last:border-0"
                                    >
                                        <td className="px-4 py-3 text-muted-foreground tabular-nums">
                                            {line.code}
                                        </td>
                                        <td className="px-4 py-3">
                                            {line.name}
                                        </td>
                                        <td className="px-4 py-3 text-right tabular-nums">
                                            {line.debit > 0
                                                ? money(line.debit)
                                                : ''}
                                        </td>
                                        <td className="px-4 py-3 text-right tabular-nums">
                                            {line.credit > 0
                                                ? money(line.credit)
                                                : ''}
                                        </td>
                                    </tr>
                                ))}
                                {props.trial_balance.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={4}
                                            className="px-4 py-10 text-center text-muted-foreground"
                                        >
                                            Nothing posted in this period.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                            <tfoot>
                                <tr className="border-t-2 border-border font-medium">
                                    <td className="px-4 py-3" colSpan={2}>
                                        Totals
                                    </td>
                                    <td className="px-4 py-3 text-right tabular-nums">
                                        {money(props.trial_totals.debit)}
                                    </td>
                                    <td className="px-4 py-3 text-right tabular-nums">
                                        {money(props.trial_totals.credit)}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                )}

                {tab === 'Income' && (
                    <div className="flex flex-col gap-6">
                        <IncomeTable
                            title="Revenue"
                            empty="No revenue in either period."
                            rows={props.income_statement.revenue}
                            total={props.income_statement.total_revenue}
                            previous={props.income_statement.previous_revenue}
                            compare={props.compare.label}
                        />
                        <IncomeTable
                            title="Expenses"
                            empty="No expenses in either period."
                            rows={props.income_statement.expenses}
                            total={props.income_statement.total_expenses}
                            previous={props.income_statement.previous_expenses}
                            compare={props.compare.label}
                            lowerIsBetter
                        />
                        <section className="border border-2 border-border bg-background p-5">
                            <p className="flex flex-wrap items-baseline justify-between gap-2">
                                <span className="font-display text-xl">
                                    Net{' '}
                                    {props.income_statement.net < 0
                                        ? 'loss'
                                        : 'income'}
                                </span>
                                <span className="font-display text-3xl tabular-nums">
                                    {money(props.income_statement.net)}
                                </span>
                            </p>
                            <p className="mt-1 text-right text-sm text-muted-foreground tabular-nums">
                                {money(props.income_statement.previous_net)} in
                                the {props.compare.label},{' '}
                                <Change
                                    now={props.income_statement.net}
                                    before={props.income_statement.previous_net}
                                />
                            </p>
                        </section>
                    </div>
                )}

                {tab === 'Balance sheet' && (
                    <div className="grid gap-6 lg:grid-cols-2">
                        <Section
                            title="Assets"
                            rows={props.balance_sheet.assets}
                            total={props.balance_sheet.total_assets}
                        />
                        <div className="flex flex-col gap-6">
                            <Section
                                title="Liabilities"
                                rows={props.balance_sheet.liabilities}
                                total={props.balance_sheet.total_liabilities}
                            />
                            <Section
                                title="Equity"
                                rows={props.balance_sheet.equity}
                                total={props.balance_sheet.total_equity}
                            />
                            <p className="flex justify-between text-sm">
                                <span>Retained for the year</span>
                                <span className="tabular-nums">
                                    {money(props.balance_sheet.retained)}
                                </span>
                            </p>
                        </div>

                        <section className="border border-2 border-border bg-background p-5 lg:col-span-2">
                            <p className="flex flex-wrap items-baseline justify-between gap-2">
                                <span className="font-display text-xl">
                                    {props.balance_sheet.balances
                                        ? 'The books balance'
                                        : 'These do not balance'}
                                </span>
                                <span className="text-sm text-muted-foreground">
                                    assets{' '}
                                    {money(props.balance_sheet.total_assets)} ·
                                    liabilities and equity{' '}
                                    {money(
                                        props.balance_sheet.total_liabilities +
                                            props.balance_sheet.total_equity +
                                            props.balance_sheet.retained,
                                    )}
                                </span>
                            </p>
                            {!props.balance_sheet.balances && (
                                <p className="mt-2 text-sm text-destructive">
                                    Assets do not equal liabilities plus equity.
                                    Check the journal for a posting made outside
                                    this system.
                                </p>
                            )}
                        </section>
                    </div>
                )}
            </div>
        </>
    );
}

const Section = ({
    title,
    rows,
    total,
}: {
    title: string;
    rows: Line[];
    total: number;
}) => (
    <section className="border border-border bg-background p-5">
        <h2 className="font-display text-xl">{title}</h2>
        <ul className="mt-3 flex flex-col gap-2 text-sm">
            {rows.map((line) => (
                <li key={line.code} className="flex justify-between gap-4">
                    <span>{line.name}</span>
                    <span className="tabular-nums">{money(line.year)}</span>
                </li>
            ))}
            {rows.length === 0 && (
                <li className="text-muted-foreground">Nothing here yet.</li>
            )}
        </ul>
        <p className="mt-4 flex justify-between border-t border-border pt-2 text-sm font-medium">
            <span>Total {title.toLowerCase()}</span>
            <span className="tabular-nums">{money(total)}</span>
        </p>
    </section>
);

/** The difference against the compared period, with the percentage when it means something. */
const Change = ({
    now,
    before,
    lowerIsBetter = false,
}: {
    now: number;
    before: number;
    lowerIsBetter?: boolean;
}) => {
    const diff = now - before;
    const good = lowerIsBetter ? diff < -0.005 : diff > 0.005;
    const bad = lowerIsBetter ? diff > 0.005 : diff < -0.005;
    const pct =
        Math.abs(before) > 0.005 ? (diff / Math.abs(before)) * 100 : null;

    return (
        <span
            className={cn(
                'tabular-nums',
                good && 'text-emerald-700',
                bad && 'text-destructive',
            )}
        >
            {diff >= 0 ? '+' : '-'}
            {money(Math.abs(diff))}
            {pct !== null
                ? ` (${diff >= 0 ? '+' : '-'}${Math.abs(pct).toFixed(1)}%)`
                : ''}
        </span>
    );
};

const IncomeTable = ({
    title,
    empty,
    rows,
    total,
    previous,
    compare,
    lowerIsBetter = false,
}: {
    title: string;
    empty: string;
    rows: Line[];
    total: number;
    previous: number;
    compare: string;
    lowerIsBetter?: boolean;
}) => (
    <section className="border border-border bg-background">
        {/* Fixed columns so the revenue and expense tables line up. */}
        <table className="w-full table-fixed text-sm">
            <thead>
                <tr className="border-b border-border text-left text-muted-foreground">
                    <th className="px-4 py-3 font-display text-xl font-normal text-foreground">
                        {title}
                    </th>
                    <th className="w-28 px-4 py-3 text-right font-normal sm:w-40">
                        This period
                    </th>
                    <th className="w-28 px-4 py-3 text-right font-normal sm:w-40">
                        <span className="inline-block first-letter:uppercase">
                            {compare}
                        </span>
                    </th>
                    <th className="hidden w-48 px-4 py-3 text-right font-normal sm:table-cell">
                        Change
                    </th>
                </tr>
            </thead>
            <tbody>
                {rows.map((line) => (
                    <tr
                        key={line.code}
                        className="border-b border-border last:border-0"
                    >
                        <td className="px-4 py-3">{line.name}</td>
                        <td className="px-4 py-3 text-right tabular-nums">
                            {money(line.period)}
                        </td>
                        <td className="px-4 py-3 text-right text-muted-foreground tabular-nums">
                            {money(line.previous)}
                        </td>
                        <td className="hidden px-4 py-3 text-right sm:table-cell">
                            <Change
                                now={line.period}
                                before={line.previous}
                                lowerIsBetter={lowerIsBetter}
                            />
                        </td>
                    </tr>
                ))}
                {rows.length === 0 && (
                    <tr>
                        <td
                            colSpan={4}
                            className="px-4 py-8 text-center text-muted-foreground"
                        >
                            {empty}
                        </td>
                    </tr>
                )}
            </tbody>
            <tfoot>
                <tr className="border-t-2 border-border font-medium">
                    <td className="px-4 py-3">Total {title.toLowerCase()}</td>
                    <td className="px-4 py-3 text-right tabular-nums">
                        {money(total)}
                    </td>
                    <td className="px-4 py-3 text-right text-muted-foreground tabular-nums">
                        {money(previous)}
                    </td>
                    <td className="hidden px-4 py-3 text-right sm:table-cell">
                        <Change
                            now={total}
                            before={previous}
                            lowerIsBetter={lowerIsBetter}
                        />
                    </td>
                </tr>
            </tfoot>
        </table>
    </section>
);
