import { Head, useForm } from '@inertiajs/react';
import { Check, X } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { ago } from '@/lib/admin';
import { money } from '@/lib/pos';
import { dashboard } from '@/routes';
import approvals from '@/routes/admin/approvals';
import myLeave from '@/routes/admin/my-leave';

type Row = {
    id: number;
    type: string;
    type_label: string;
    status: 'pending' | 'approved' | 'rejected';
    summary: string;
    amount: number | null;
    requested_by: string | null;
    requested_at: string | null;
    decided_by: string | null;
    decided_at: string | null;
    decision_note: string | null;
    outcome: string | null;
};

type Props = {
    owner: boolean;
    pending: Row[];
    decided: Row[];
    canFileLeave: boolean;
    leaveTypes: Record<string, string>;
};

export default function Approvals({
    owner,
    pending,
    decided,
    canFileLeave,
    leaveTypes,
}: Props) {
    return (
        <>
            <Head title="Approvals" />
            <div className="flex flex-1 flex-col gap-8 px-4 py-6 md:px-8 md:py-8">
                <header>
                    <h1 className="font-display text-3xl md:text-4xl">
                        Approvals
                    </h1>
                    <p className="mt-2 max-w-2xl text-muted-foreground">
                        {owner
                            ? 'Refunds, discounts over the limit, payroll runs and leave wait here for your decision. Approving carries the action out, and every step is in the audit log.'
                            : 'Requests you have sent to the owner, and what she decided.'}
                    </p>
                </header>

                {canFileLeave && <FileLeave types={leaveTypes} />}

                <section className="space-y-3">
                    <h2 className="font-display text-xl">
                        Waiting ({pending.length})
                    </h2>
                    {pending.length === 0 ? (
                        <p className="border-y border-border py-8 text-muted-foreground">
                            Nothing is waiting.
                        </p>
                    ) : (
                        <ul className="divide-y divide-border border-y border-border">
                            {pending.map((row) => (
                                <Pending key={row.id} row={row} owner={owner} />
                            ))}
                        </ul>
                    )}
                </section>

                <section className="space-y-3">
                    <h2 className="font-display text-xl">Decided</h2>
                    <ul className="divide-y divide-border border-y border-border">
                        {decided.map((row) => (
                            <li
                                key={row.id}
                                className="flex flex-wrap items-start justify-between gap-3 py-3 text-sm"
                            >
                                <div className="min-w-0">
                                    <p>{row.summary}</p>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        Asked by {row.requested_by} ·{' '}
                                        {row.status} by {row.decided_by}{' '}
                                        {row.decided_at && ago(row.decided_at)}
                                        {row.decision_note &&
                                            ` · "${row.decision_note}"`}
                                    </p>
                                    {row.outcome && (
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            {row.outcome}
                                        </p>
                                    )}
                                </div>
                                <Badge
                                    variant={
                                        row.status === 'approved'
                                            ? 'secondary'
                                            : 'outline'
                                    }
                                >
                                    {row.status === 'approved'
                                        ? 'Approved'
                                        : 'Rejected'}
                                </Badge>
                            </li>
                        ))}
                        {decided.length === 0 && (
                            <li className="py-8 text-muted-foreground">
                                Nothing decided yet.
                            </li>
                        )}
                    </ul>
                </section>
            </div>
        </>
    );
}

/** Every member of staff files their own leave here; the owner decides. */
function FileLeave({ types }: { types: Record<string, string> }) {
    const today = new Date().toISOString().slice(0, 10);
    const form = useForm({
        type: Object.keys(types)[0] ?? 'annual',
        from_date: today,
        to_date: today,
        days: '1',
        reason: '',
    });
    const field = 'h-10 border border-border bg-background px-2 text-sm';

    return (
        <section className="space-y-3">
            <h2 className="font-display text-xl">File for leave</h2>
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    form.post(myLeave.store().url, {
                        preserveScroll: true,
                        onSuccess: () => form.reset('reason'),
                    });
                }}
                className="grid max-w-4xl gap-3 border border-border bg-background p-5 sm:grid-cols-6"
            >
                <label className="grid gap-1 text-sm sm:col-span-2">
                    Type
                    <select
                        value={form.data.type}
                        onChange={(e) => form.setData('type', e.target.value)}
                        className={field}
                    >
                        {Object.entries(types).map(([k, v]) => (
                            <option key={k} value={k}>
                                {v}
                            </option>
                        ))}
                    </select>
                </label>
                <label className="grid gap-1 text-sm sm:col-span-2">
                    From
                    <input
                        type="date"
                        min={today}
                        value={form.data.from_date}
                        onChange={(e) =>
                            form.setData('from_date', e.target.value)
                        }
                        className={field}
                    />
                </label>
                <label className="grid gap-1 text-sm sm:col-span-2">
                    To
                    <input
                        type="date"
                        min={form.data.from_date}
                        value={form.data.to_date}
                        onChange={(e) =>
                            form.setData('to_date', e.target.value)
                        }
                        className={field}
                    />
                </label>
                <label className="grid gap-1 text-sm">
                    Days
                    <input
                        type="number"
                        min={0.5}
                        step={0.5}
                        value={form.data.days}
                        onChange={(e) => form.setData('days', e.target.value)}
                        className={field}
                    />
                </label>
                <label className="grid gap-1 text-sm sm:col-span-4">
                    Reason
                    <Input
                        value={form.data.reason}
                        onChange={(e) => form.setData('reason', e.target.value)}
                        maxLength={200}
                    />
                </label>
                <div className="flex items-end">
                    <Button
                        type="submit"
                        disabled={form.processing}
                        className="w-full"
                    >
                        Send
                    </Button>
                </div>
                {Object.values(form.errors)[0] && (
                    <p className="text-sm text-destructive sm:col-span-6">
                        {Object.values(form.errors)[0]}
                    </p>
                )}
            </form>
        </section>
    );
}

function Pending({ row, owner }: { row: Row; owner: boolean }) {
    const [rejecting, setRejecting] = useState(false);
    const form = useForm<{ decision: 'approve' | 'reject'; note: string }>({
        decision: 'approve',
        note: '',
    });
    const errors = form.errors as Record<string, string | undefined>;

    const send = (decision: 'approve' | 'reject') => {
        form.transform((data) => ({ ...data, decision }));
        form.post(approvals.decide(row.id).url, { preserveScroll: true });
    };

    return (
        <li className="grid gap-3 py-4 md:grid-cols-[1fr_auto] md:items-start">
            <div className="min-w-0">
                <p className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                    <Badge variant="outline">{row.type_label}</Badge>
                    Asked by {row.requested_by}{' '}
                    {row.requested_at && ago(row.requested_at)}
                </p>
                <p className="mt-2">{row.summary}</p>
                {row.amount !== null && (
                    <p className="mt-1 font-display text-2xl tabular-nums">
                        {money(row.amount)}
                    </p>
                )}
                {(errors.note ?? errors.approval) && (
                    <p className="mt-1 text-sm text-destructive">
                        {errors.note ?? errors.approval}
                    </p>
                )}
            </div>
            {owner ? (
                <div className="flex flex-wrap items-center gap-2">
                    {rejecting ? (
                        <>
                            <Input
                                autoFocus
                                placeholder="Why not?"
                                value={form.data.note}
                                onChange={(e) =>
                                    form.setData('note', e.target.value)
                                }
                                className="h-10 min-w-48 flex-1"
                            />
                            <Button
                                type="button"
                                variant="outline"
                                disabled={form.processing}
                                onClick={() => send('reject')}
                            >
                                Reject
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={() => setRejecting(false)}
                            >
                                Cancel
                            </Button>
                        </>
                    ) : (
                        <>
                            <Button
                                type="button"
                                disabled={form.processing}
                                onClick={() => send('approve')}
                            >
                                <Check className="h-4 w-4" /> Approve
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setRejecting(true)}
                            >
                                <X className="h-4 w-4" /> Reject
                            </Button>
                        </>
                    )}
                </div>
            ) : (
                <Badge variant="secondary">Waiting for the owner</Badge>
            )}
        </li>
    );
}

Approvals.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Approvals', href: approvals.index() },
    ],
};
