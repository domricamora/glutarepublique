import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Info } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import payroll from '@/routes/admin/payroll';

type Props = {
    settings: {
        sss_rate: number;
        sss_ceiling: number;
        sss_max: number;
        philhealth_rate: number;
        philhealth_ceiling: number;
        philhealth_max: number;
        pagibig_rate: number;
        pagibig_ceiling: number;
        pagibig_max: number;
        overtime_multiplier: number;
        regular_holiday_multiplier: number;
        special_holiday_multiplier: number;
        late_grace_minutes: number;
        effective_from: string | null;
        configured: boolean;
    };
    defaults: {
        sss_rate: number;
        philhealth_rate: number;
        pagibig_rate: number;
    };
    holidays: { id: number; date: string; name: string; type: string }[];
    holidayTypes: Record<string, string>;
};

type RateKey =
    | 'sss_rate'
    | 'sss_ceiling'
    | 'sss_max'
    | 'philhealth_rate'
    | 'philhealth_ceiling'
    | 'philhealth_max'
    | 'pagibig_rate'
    | 'pagibig_ceiling'
    | 'pagibig_max'
    | 'overtime_multiplier'
    | 'regular_holiday_multiplier'
    | 'special_holiday_multiplier'
    | 'late_grace_minutes';

type Group = {
    key: 'sss' | 'philhealth' | 'pagibig';
    label: string;
    who: string;
    note: string;
};

const GROUPS: Group[] = [
    {
        key: 'sss',
        label: 'SSS',
        who: 'Social Security',
        note: 'Taken on the salary up to the ceiling, never more than the maximum.',
    },
    {
        key: 'philhealth',
        label: 'PhilHealth',
        who: 'National Health Insurance',
        note: 'Taken on the salary up to the ceiling, never more than the maximum.',
    },
    {
        key: 'pagibig',
        label: 'Pag-IBIG',
        who: 'HDMF',
        note: 'The employee share, which is half of the total premium.',
    },
];

export default function PayrollSettings({
    settings,
    holidays,
    holidayTypes,
}: Props) {
    const form = useForm({
        sss_rate: String(settings.sss_rate),
        sss_ceiling: String(settings.sss_ceiling),
        sss_max: String(settings.sss_max),
        philhealth_rate: String(settings.philhealth_rate),
        philhealth_ceiling: String(settings.philhealth_ceiling),
        philhealth_max: String(settings.philhealth_max),
        pagibig_rate: String(settings.pagibig_rate),
        pagibig_ceiling: String(settings.pagibig_ceiling),
        pagibig_max: String(settings.pagibig_max),
        overtime_multiplier: String(settings.overtime_multiplier),
        regular_holiday_multiplier: String(settings.regular_holiday_multiplier),
        special_holiday_multiplier: String(settings.special_holiday_multiplier),
        late_grace_minutes: String(settings.late_grace_minutes),
        effective_from: settings.effective_from ?? '',
    });
    const holiday = useForm({ date: '', name: '', type: 'regular' });

    const field = (key: RateKey, label: string, hint?: string) => (
        <div className="grid gap-1">
            <Label htmlFor={key}>{label}</Label>
            <Input
                id={key}
                type="number"
                min={0}
                step="0.0001"
                value={form.data[key]}
                onChange={(event) => form.setData(key, event.target.value)}
            />
            {hint && <p className="text-xs text-muted-foreground">{hint}</p>}
            {form.errors[key] && (
                <p className="text-sm text-destructive">{form.errors[key]}</p>
            )}
        </div>
    );

    return (
        <>
            <Head title="Deductions" />
            <div className="flex flex-1 flex-col gap-6 px-4 py-6 md:px-8 md:py-8">
                <header className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <Link
                            href={payroll.index().url}
                            className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Payroll
                        </Link>
                        <h1 className="mt-2 font-display text-3xl md:text-4xl">
                            Deductions
                        </h1>
                        <p className="mt-1 max-w-2xl text-sm text-muted-foreground">
                            What comes off a payslip, and the rates behind the
                            government contributions.
                        </p>
                    </div>
                </header>

                <p className="flex max-w-3xl items-start gap-3 border border-gold/40 bg-background px-4 py-3 text-sm">
                    <Info className="mt-0.5 h-4 w-4 shrink-0 text-gold-deep" />
                    <span>
                        These figures move with the law and are adjusted by
                        circulars through the year. Have a payroll specialist
                        check them before a real pay run, and treat them as
                        defaults rather than advice.
                    </span>
                </p>

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.patch(payroll.settings.update().url, {
                            preserveScroll: true,
                        });
                    }}
                    className="flex flex-col gap-6"
                >
                    {GROUPS.map((group) => (
                        <section
                            key={group.key}
                            className="border border-border bg-background p-5"
                        >
                            <div className="flex flex-wrap items-baseline justify-between gap-2">
                                <h2 className="font-display text-xl">
                                    {group.label}
                                </h2>
                                <span className="text-sm text-muted-foreground">
                                    {group.who}
                                </span>
                            </div>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {group.note}
                            </p>
                            <div className="mt-4 grid gap-4 sm:grid-cols-3">
                                {field(
                                    `${group.key}_rate`,
                                    'Rate',
                                    'A share of the salary: 5% is 0.05',
                                )}
                                {field(
                                    `${group.key}_ceiling`,
                                    'Salary ceiling',
                                    'The most that counts',
                                )}
                                {field(
                                    `${group.key}_max`,
                                    'Maximum',
                                    'Never more than this',
                                )}
                            </div>
                        </section>
                    ))}

                    <section className="border border-border bg-background p-5">
                        <h2 className="font-display text-xl">
                            Time and premiums
                        </h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Worked out from the schedule and the time record at
                            every pay run. Late minutes past the grace period
                            and scheduled days with no clock-in (and no leave or
                            holiday) are deducted at the hourly and daily rate.
                        </p>
                        <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            {field(
                                'late_grace_minutes',
                                'Grace period (minutes)',
                                'Late only after this',
                            )}
                            {field(
                                'overtime_multiplier',
                                'Overtime pay',
                                '1.25 is 125% of the hourly rate',
                            )}
                            {field(
                                'regular_holiday_multiplier',
                                'Regular holiday worked',
                                '2.00 is double pay',
                            )}
                            {field(
                                'special_holiday_multiplier',
                                'Special day worked',
                                '1.30 is 130%',
                            )}
                        </div>
                    </section>

                    <div className="flex flex-wrap items-end gap-4">
                        <div className="grid gap-1">
                            <Label htmlFor="effective_from">
                                Effective from
                            </Label>
                            <Input
                                id="effective_from"
                                type="date"
                                value={form.data.effective_from}
                                onChange={(event) =>
                                    form.setData(
                                        'effective_from',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                        <p className="flex-1 text-sm text-muted-foreground">
                            {settings.configured
                                ? 'These rates are the clinic’s own. Payslips already calculated keep the figures they were paid.'
                                : 'Still on the defaults from config/payroll.php. Saving writes this clinic’s own rates.'}
                        </p>
                        <Button
                            type="submit"
                            disabled={form.processing}
                            className="h-10 bg-plum text-white hover:bg-plum-deep"
                        >
                            {form.processing && <Spinner />}
                            Save rates
                        </Button>
                    </div>
                </form>

                <section className="border border-border bg-background p-5">
                    <h2 className="font-display text-xl">Holidays</h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Never counted as an absence; worked, they earn the
                        premium above. Check the year against the official
                        proclamation, which moves some dates.
                    </p>
                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            holiday.post(payroll.holidays.store().url, {
                                preserveScroll: true,
                                onSuccess: () => holiday.reset(),
                            });
                        }}
                        className="mt-4 flex flex-wrap items-end gap-2"
                    >
                        <Input
                            type="date"
                            aria-label="Date"
                            value={holiday.data.date}
                            onChange={(e) =>
                                holiday.setData('date', e.target.value)
                            }
                            className="h-10 w-auto"
                            required
                        />
                        <Input
                            aria-label="Name"
                            placeholder="Name"
                            value={holiday.data.name}
                            onChange={(e) =>
                                holiday.setData('name', e.target.value)
                            }
                            className="h-10 w-56"
                            required
                        />
                        <select
                            aria-label="Type"
                            value={holiday.data.type}
                            onChange={(e) =>
                                holiday.setData('type', e.target.value)
                            }
                            className="h-10 border border-border bg-background px-2 text-sm"
                        >
                            {Object.entries(holidayTypes).map(([k, v]) => (
                                <option key={k} value={k}>
                                    {v}
                                </option>
                            ))}
                        </select>
                        <Button
                            type="submit"
                            variant="outline"
                            className="h-10"
                            disabled={holiday.processing}
                        >
                            Add holiday
                        </Button>
                    </form>
                    <ul className="mt-4 divide-y divide-border border-y border-border text-sm">
                        {holidays.map((h) => (
                            <li
                                key={h.id}
                                className="flex flex-wrap items-center justify-between gap-2 py-2"
                            >
                                <span>
                                    <span className="tabular-nums">
                                        {h.date}
                                    </span>{' '}
                                    · {h.name}
                                    <span className="ml-2 text-xs text-muted-foreground">
                                        {holidayTypes[h.type] ?? h.type}
                                    </span>
                                </span>
                                <Link
                                    href={payroll.holidays.destroy(h.id).url}
                                    method="delete"
                                    as="button"
                                    preserveScroll
                                    className="text-xs text-muted-foreground hover:text-destructive"
                                >
                                    Remove
                                </Link>
                            </li>
                        ))}
                        {holidays.length === 0 && (
                            <li className="py-4 text-muted-foreground">
                                No holidays on the calendar yet.
                            </li>
                        )}
                    </ul>
                </section>
            </div>
        </>
    );
}
