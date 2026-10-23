import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarDays,
    LogIn,
    LogOut,
    MonitorSmartphone,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { useCan } from '@/lib/admin';
import { manilaNow, toIsoDate } from '@/lib/site';
import { cn } from '@/lib/utils';
import hr from '@/routes/admin/hr';
import kiosk from '@/routes/kiosk';

type Status = 'present' | 'late' | 'absent' | 'rest' | 'expected';

type Row = {
    id: number;
    name: string;
    position: string;
    shift: string | null;
    time_in: string | null;
    time_out: string | null;
    hours: number;
    overtime_minutes: number;
    late_minutes: number;
    status: Status;
    source: string | null;
    photo: string | null;
};

type Props = {
    date: string;
    me: { id: number; name: string } | null;
    today: {
        time_in: string | null;
        time_out: string | null;
        minutes: number;
        overtime_minutes: number;
    } | null;
    rows: Row[];
    open_shifts: number;
};

const STATUS: Record<Status, [string, string]> = {
    present: ['On time', 'text-royal-ink'],
    late: ['Late', 'text-destructive'],
    absent: ['Absent', 'text-destructive font-medium'],
    rest: ['Rest day', 'text-muted-foreground'],
    expected: ['Not in yet', 'text-muted-foreground'],
};

export default function HrAttendance({
    date,
    me,
    today,
    rows,
    open_shifts,
}: Props) {
    const can = useCan();
    const form = useForm({ date });
    const today8 = toIsoDate(manilaNow());
    const inWorking = Boolean(today?.time_in) && !today?.time_out;
    const count = (s: Status) => rows.filter((r) => r.status === s).length;

    return (
        <>
            <Head title="Time record" />
            <div className="flex flex-1 flex-col gap-6 px-4 py-6 md:px-8 md:py-8">
                <header className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <Link
                            href={hr.index().url}
                            className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Staff
                        </Link>
                        <h1 className="mt-2 font-display text-3xl md:text-4xl">
                            Time record
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {date === today8 ? 'Today' : date} ·{' '}
                            {count('present') + count('late')} in ·{' '}
                            {count('late')} late · {count('absent')} absent
                            {open_shifts > 0 &&
                                ` · ${open_shifts} still clocked in`}
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        <Input
                            type="date"
                            value={date}
                            max={today8}
                            onChange={(e) =>
                                router.get(
                                    hr.attendance({
                                        query: { date: e.target.value },
                                    }).url,
                                    {},
                                    { preserveScroll: true },
                                )
                            }
                            aria-label="Date"
                            className="h-10 w-auto"
                        />
                        <Link
                            href={hr.schedule().url}
                            className="press inline-flex h-10 items-center gap-2 border border-border px-3 text-sm hover:bg-mist dark:hover:bg-white/5"
                        >
                            <CalendarDays className="h-4 w-4" /> Schedule
                        </Link>
                        {/* The tablet page, opened in its own tab to set up a branch device. */}
                        <a
                            href={kiosk.index().url}
                            target="_blank"
                            rel="noopener"
                            className="press inline-flex h-10 items-center gap-2 border border-border px-3 text-sm hover:bg-mist dark:hover:bg-white/5"
                        >
                            <MonitorSmartphone className="h-4 w-4" /> Kiosk
                        </a>
                        {me && can('hr.create') && (
                            <Button
                                type="button"
                                disabled={form.processing}
                                onClick={() =>
                                    form.post(hr.clock().url, {
                                        preserveScroll: true,
                                    })
                                }
                                className={cn(
                                    'h-10 text-white',
                                    inWorking
                                        ? 'bg-destructive hover:bg-destructive/90'
                                        : 'bg-plum hover:bg-plum-deep',
                                )}
                            >
                                {form.processing && <Spinner />}
                                {inWorking ? (
                                    <LogOut className="h-4 w-4" />
                                ) : (
                                    <LogIn className="h-4 w-4" />
                                )}
                                {inWorking
                                    ? `Clock out${
                                          today?.time_in
                                              ? ` (in ${today.time_in.slice(0, 5)})`
                                              : ''
                                      }`
                                    : 'Clock in'}
                            </Button>
                        )}
                    </div>
                </header>

                {!me && (
                    <p className="border border-border bg-background px-4 py-3 text-sm text-muted-foreground">
                        You are not on the staff roll yet, so there is nothing
                        to clock. An HR manager can add you.
                    </p>
                )}

                <div className="overflow-x-auto border border-border">
                    <table className="w-full min-w-[44rem] text-sm">
                        <thead>
                            <tr className="border-b border-border text-left text-muted-foreground">
                                <th className="px-4 py-3 font-normal">Name</th>
                                <th className="px-4 py-3 font-normal">Shift</th>
                                <th className="px-4 py-3 font-normal">In</th>
                                <th className="px-4 py-3 font-normal">Out</th>
                                <th className="px-4 py-3 font-normal">
                                    Status
                                </th>
                                <th className="px-4 py-3 text-right font-normal">
                                    Hours
                                </th>
                                <th className="px-4 py-3 text-right font-normal">
                                    Overtime
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {rows.map((row) => (
                                <tr
                                    key={row.id}
                                    className="border-b border-border last:border-0"
                                >
                                    <td className="px-4 py-3">
                                        <span className="flex items-center gap-3">
                                            {row.photo ? (
                                                <a
                                                    href={row.photo}
                                                    target="_blank"
                                                    rel="noopener"
                                                    title="Clock-in photo"
                                                >
                                                    <img
                                                        src={row.photo}
                                                        alt={`${row.name} at clock-in`}
                                                        className="size-9 rounded-full object-cover"
                                                    />
                                                </a>
                                            ) : (
                                                <span className="size-9 shrink-0" />
                                            )}
                                            <span>
                                                {row.name}
                                                <span className="block text-xs text-muted-foreground">
                                                    {row.position}
                                                    {row.source === 'kiosk' &&
                                                        ' · kiosk'}
                                                </span>
                                            </span>
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground tabular-nums">
                                        {row.shift ?? 'Not scheduled'}
                                    </td>
                                    <td className="px-4 py-3 tabular-nums">
                                        {row.time_in ?? '—'}
                                    </td>
                                    <td className="px-4 py-3 tabular-nums">
                                        {row.time_out ??
                                            (row.time_in ? (
                                                <span className="text-gold-deep">
                                                    still in
                                                </span>
                                            ) : (
                                                '—'
                                            ))}
                                    </td>
                                    <td
                                        className={cn(
                                            'px-4 py-3',
                                            STATUS[row.status][1],
                                        )}
                                    >
                                        {STATUS[row.status][0]}
                                        {row.late_minutes > 0 &&
                                            ` by ${row.late_minutes} min`}
                                    </td>
                                    <td className="px-4 py-3 text-right tabular-nums">
                                        {row.hours > 0
                                            ? row.hours.toFixed(1)
                                            : '—'}
                                    </td>
                                    <td className="px-4 py-3 text-right tabular-nums">
                                        {row.overtime_minutes > 0
                                            ? `${(row.overtime_minutes / 60).toFixed(1)} h`
                                            : '—'}
                                    </td>
                                </tr>
                            ))}
                            {rows.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={7}
                                        className="px-4 py-10 text-center text-muted-foreground"
                                    >
                                        Nobody was scheduled or clocked in on
                                        this day.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}
