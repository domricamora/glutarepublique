import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, ChevronLeft, ChevronRight, Copy } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { useCan } from '@/lib/admin';
import { cn } from '@/lib/utils';
import hr from '@/routes/admin/hr';

type Cell = {
    rest: boolean;
    starts: string | null;
    ends: string | null;
    minutes: number;
};

type Props = {
    week: string;
    days: { date: string; label: string }[];
    staff: { id: number; name: string; position: string }[];
    grid: Record<string, Cell>;
    branches: { id: number; name: string }[];
    branch: number | null;
};

const LENGTHS = [4, 6, 8, 9, 10, 12];

const shiftWeek = (week: string, by: number): string => {
    const d = new Date(`${week}T12:00:00`);
    d.setDate(d.getDate() + by * 7);

    return d.toISOString().slice(0, 10);
};

/** "09:00" plus some hours, wrapping past midnight. */
const addHours = (start: string, hours: number): string => {
    const [h, m] = start.split(':').map(Number);
    const total = (h * 60 + m + hours * 60) % (24 * 60);

    return `${String(Math.floor(total / 60)).padStart(2, '0')}:${String(total % 60).padStart(2, '0')}`;
};

const time = 'h-10 border border-border bg-background px-2 tabular-nums';

export default function HrSchedule({
    week,
    days,
    staff,
    grid,
    branches,
    branch,
}: Props) {
    const can = useCan();
    const editable = can('hr.create');
    const [editing, setEditing] = useState<{
        id: number;
        name: string;
        date: string;
        label: string;
    } | null>(null);
    const form = useForm({
        employee_id: 0,
        date: '',
        rest: false,
        starts: '10:00',
        ends: '19:00',
    });

    const go = (next: { week?: string; branch?: number | null }) =>
        router.get(
            hr.schedule({
                query: {
                    week: next.week || undefined,
                    branch:
                        (next.branch === undefined ? branch : next.branch) ??
                        undefined,
                },
            }).url,
            {},
            { preserveScroll: true },
        );

    const open = (
        person: Props['staff'][number],
        day: Props['days'][number],
    ) => {
        const cell = grid[`${person.id}|${day.date}`];
        form.clearErrors();
        form.setData({
            employee_id: person.id,
            date: day.date,
            rest: cell?.rest ?? false,
            starts: cell?.starts ?? '10:00',
            ends: cell?.ends ?? '19:00',
        });
        setEditing({
            id: person.id,
            name: person.name,
            date: day.date,
            label: day.label,
        });
    };

    const planned = (personId: number) =>
        days.reduce(
            (sum, d) => sum + (grid[`${personId}|${d.date}`]?.minutes ?? 0),
            0,
        ) / 60;

    return (
        <>
            <Head title="Work schedule" />
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
                            Work schedule
                        </h1>
                        <p className="mt-1 max-w-2xl text-sm text-muted-foreground">
                            Week of {days[0]?.label} to {days[6]?.label}. The
                            kiosk and the time record use these shifts to mark
                            who is late or absent.
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <div className="flex border border-border">
                            <button
                                type="button"
                                aria-label="Previous week"
                                onClick={() =>
                                    go({ week: shiftWeek(week, -1) })
                                }
                                className="h-10 px-3 hover:bg-mist dark:hover:bg-white/5"
                            >
                                <ChevronLeft className="h-4 w-4" />
                            </button>
                            <button
                                type="button"
                                onClick={() => go({ week: '' })}
                                className="h-10 border-x border-border px-3 text-sm hover:bg-mist dark:hover:bg-white/5"
                            >
                                This week
                            </button>
                            <button
                                type="button"
                                aria-label="Next week"
                                onClick={() => go({ week: shiftWeek(week, 1) })}
                                className="h-10 px-3 hover:bg-mist dark:hover:bg-white/5"
                            >
                                <ChevronRight className="h-4 w-4" />
                            </button>
                        </div>
                        <select
                            aria-label="Branch"
                            value={branch ?? ''}
                            onChange={(e) =>
                                go({
                                    week,
                                    branch: e.target.value
                                        ? Number(e.target.value)
                                        : null,
                                })
                            }
                            className="h-10 border border-border bg-background px-2 text-sm"
                        >
                            <option value="">All branches</option>
                            {branches.map((b) => (
                                <option key={b.id} value={b.id}>
                                    {b.name}
                                </option>
                            ))}
                        </select>
                        {editable && (
                            <Button
                                type="button"
                                variant="outline"
                                className="h-10"
                                onClick={() =>
                                    router.post(
                                        hr.schedule.copy().url,
                                        { week },
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                <Copy className="h-4 w-4" /> Copy last week
                            </Button>
                        )}
                    </div>
                </header>

                <div className="overflow-x-auto border border-border">
                    <table className="w-full min-w-[56rem] text-sm">
                        <thead>
                            <tr className="border-b border-border text-left text-muted-foreground">
                                <th className="sticky left-0 bg-background px-4 py-3 font-normal">
                                    Staff
                                </th>
                                {days.map((d) => (
                                    <th
                                        key={d.date}
                                        className="px-2 py-3 text-center font-normal"
                                    >
                                        {d.label}
                                    </th>
                                ))}
                                <th className="px-4 py-3 text-right font-normal">
                                    Hours
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {staff.map((person) => (
                                <tr
                                    key={person.id}
                                    className="border-b border-border last:border-0"
                                >
                                    <td className="sticky left-0 bg-background px-4 py-2">
                                        {person.name}
                                        <span className="block text-xs text-muted-foreground">
                                            {person.position}
                                        </span>
                                    </td>
                                    {days.map((d) => {
                                        const cell =
                                            grid[`${person.id}|${d.date}`];

                                        return (
                                            <td key={d.date} className="p-1">
                                                <button
                                                    type="button"
                                                    disabled={!editable}
                                                    onClick={() =>
                                                        open(person, d)
                                                    }
                                                    aria-label={`${person.name}, ${d.label}`}
                                                    className={cn(
                                                        'flex h-12 w-full items-center justify-center border px-1 text-xs tabular-nums transition-colors duration-150 ease-out',
                                                        cell?.rest
                                                            ? 'border-border bg-mist text-muted-foreground dark:bg-white/5'
                                                            : cell
                                                              ? 'border-royal/40 bg-ice text-ocean dark:bg-white/10 dark:text-white'
                                                              : 'border-dashed border-border text-muted-foreground hover:border-royal',
                                                    )}
                                                >
                                                    {cell?.rest
                                                        ? 'Rest'
                                                        : cell
                                                          ? `${cell.starts} to ${cell.ends}`
                                                          : editable
                                                            ? '+'
                                                            : ''}
                                                </button>
                                            </td>
                                        );
                                    })}
                                    <td className="px-4 py-2 text-right tabular-nums">
                                        {planned(person.id) > 0
                                            ? planned(person.id).toFixed(1)
                                            : '—'}
                                    </td>
                                </tr>
                            ))}
                            {staff.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={9}
                                        className="px-4 py-10 text-center text-muted-foreground"
                                    >
                                        Nobody on the staff roll for this
                                        branch.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            <Dialog
                open={editing !== null}
                onOpenChange={(o) => !o && setEditing(null)}
            >
                <DialogContent className="rounded-none">
                    <DialogTitle>{editing?.name}</DialogTitle>
                    <DialogDescription>{editing?.label}</DialogDescription>
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            form.post(hr.schedule.save().url, {
                                preserveScroll: true,
                                onSuccess: () => setEditing(null),
                            });
                        }}
                        className="grid gap-4"
                    >
                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={form.data.rest}
                                onChange={(e) =>
                                    form.setData('rest', e.target.checked)
                                }
                            />
                            Rest day
                        </label>
                        {!form.data.rest && (
                            <>
                                <div className="flex flex-wrap items-center gap-3 text-sm">
                                    <input
                                        type="time"
                                        aria-label="Starts"
                                        value={form.data.starts}
                                        onChange={(e) =>
                                            form.setData(
                                                'starts',
                                                e.target.value,
                                            )
                                        }
                                        className={time}
                                    />
                                    <span className="text-muted-foreground">
                                        to
                                    </span>
                                    <input
                                        type="time"
                                        aria-label="Ends"
                                        value={form.data.ends}
                                        onChange={(e) =>
                                            form.setData('ends', e.target.value)
                                        }
                                        className={time}
                                    />
                                </div>
                                <div className="flex flex-wrap items-center gap-2 text-sm">
                                    <span className="text-muted-foreground">
                                        Length
                                    </span>
                                    {LENGTHS.map((h) => (
                                        <button
                                            key={h}
                                            type="button"
                                            onClick={() =>
                                                form.setData(
                                                    'ends',
                                                    addHours(
                                                        form.data.starts,
                                                        h,
                                                    ),
                                                )
                                            }
                                            className="press h-8 border border-border px-2.5 tabular-nums hover:border-royal"
                                        >
                                            {h} h
                                        </button>
                                    ))}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    An end earlier than the start is a night
                                    shift that ends the next morning.
                                </p>
                            </>
                        )}
                        <InputError
                            message={form.errors.starts ?? form.errors.ends}
                        />
                        <div className="flex flex-wrap gap-2">
                            <Button type="submit" disabled={form.processing}>
                                Save
                            </Button>
                            {editing &&
                                grid[`${editing.id}|${editing.date}`] && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() =>
                                            router.delete(
                                                hr.schedule.clear().url,
                                                {
                                                    data: {
                                                        employee_id: editing.id,
                                                        date: editing.date,
                                                    },
                                                    preserveScroll: true,
                                                    onSuccess: () =>
                                                        setEditing(null),
                                                },
                                            )
                                        }
                                    >
                                        Clear day
                                    </Button>
                                )}
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={() => setEditing(null)}
                            >
                                Cancel
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}
