import { Head, Link, router, useForm } from '@inertiajs/react';
import { CalendarPlus, Mail, Phone, ShoppingBag } from 'lucide-react';
import type { FormEvent } from 'react';
import StatusSelect from '@/components/admin/status-select';
import InputError from '@/components/input-error';
import type { AppointmentRow, Labels, LeadRow } from '@/lib/admin';
import { ago, clock, formatDate, sourceLabel, useCan } from '@/lib/admin';
import { money, statusTone } from '@/lib/pos';
import { cn } from '@/lib/utils';
import appointmentRoutes from '@/routes/admin/appointments';
import leads from '@/routes/admin/leads';
import pos from '@/routes/admin/pos';

/** A visit still ahead of the client is the only one worth moving. */
const canMove = (appointment: AppointmentRow): boolean =>
    ['pending', 'confirmed'].includes(appointment.status);

type Lead = LeadRow & {
    message: string | null;
    branch: string | null;
    utm_source: string | null;
    utm_medium: string | null;
    landing_page: string | null;
    referrer: string | null;
    device: string | null;
    privacy_consent_at: string | null;
    marketing_consent: boolean;
};

type Activity = {
    id: number;
    type: string;
    description: string;
    author: string | null;
    created_at: string;
};

/** Visits, purchases and spend; null for people who may not see the till. */
type ClientRecord = {
    visits: number;
    last_visit: string | null;
    spend: number;
    purchases: number;
    average: number;
    statuses: Labels;
    sales: {
        id: number;
        reference: string;
        date: string;
        branch: string | null;
        total: number;
        status: string;
        items: string;
    }[];
};

type Props = {
    lead: Lead;
    activities: Activity[];
    appointments: AppointmentRow[];
    record: ClientRecord | null;
    stages: Labels;
    statuses: Labels;
};

export default function LeadShow({
    lead,
    activities,
    appointments,
    record,
    stages,
    statuses,
}: Props) {
    const can = useCan();
    const editable = can('leads.edit');
    const keys = Object.keys(stages);
    const current = keys.indexOf(lead.stage);
    const note = useForm({ note: '' });

    const addNote = (e: FormEvent) => {
        e.preventDefault();
        note.post(leads.notes.store(lead.id).url, {
            preserveScroll: true,
            onSuccess: () => note.reset(),
        });
    };

    const details: [string, string | null][] = [
        ['Interested in', lead.treatment ?? 'General enquiry'],
        ['Preferred branch', lead.branch],
        [
            'Came from',
            [sourceLabel(lead.source), lead.utm_medium]
                .filter(Boolean)
                .join(', '),
        ],
        ['Campaign', lead.campaign],
        ['Form', sourceLabel(lead.form)],
        ['Device', lead.device && sourceLabel(lead.device)],
        [
            'Privacy consent',
            lead.privacy_consent_at
                ? formatDate(lead.privacy_consent_at, {
                      day: 'numeric',
                      month: 'short',
                      year: 'numeric',
                  })
                : 'Not recorded',
        ],
        [
            'Marketing messages',
            lead.marketing_consent ? 'Agreed' : 'Not agreed',
        ],
    ];

    return (
        <>
            <Head title={lead.name} />
            <div className="flex flex-1 flex-col gap-8 px-4 py-6 md:px-8 md:py-8">
                <header className="flex flex-wrap items-end justify-between gap-4">
                    <div className="min-w-0">
                        <p className="text-sm text-muted-foreground">
                            Lead since{' '}
                            {formatDate(lead.created_at, {
                                day: 'numeric',
                                month: 'long',
                                year: 'numeric',
                            })}
                        </p>
                        <h1 className="mt-1 font-display text-3xl md:text-4xl">
                            {lead.name}
                        </h1>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {can('pos.create') && (
                            <Link
                                href={
                                    pos.index({ query: { lead: lead.id } }).url
                                }
                                className="inline-flex items-center gap-2 border border-border px-4 py-2 text-sm font-medium transition-[background-color,transform] duration-150 ease-out hover:bg-mist active:scale-[0.97] dark:hover:bg-white/5"
                            >
                                <ShoppingBag className="size-4" aria-hidden />{' '}
                                Ring up a sale
                            </Link>
                        )}
                        {can('appointments.create') && (
                            <Link
                                href={
                                    appointmentRoutes.create({
                                        query: { lead: lead.id },
                                    }).url
                                }
                                className="inline-flex items-center gap-2 bg-plum px-4 py-2 text-sm font-medium text-white transition-[background-color,transform] duration-150 ease-out hover:bg-plum-deep active:scale-[0.97]"
                            >
                                <CalendarPlus className="size-4" aria-hidden />{' '}
                                Book a visit
                            </Link>
                        )}
                        {lead.phone && (
                            <a
                                href={`tel:${lead.phone.replace(/\s/g, '')}`}
                                className="inline-flex items-center gap-2 bg-plum px-4 py-2 text-sm font-medium text-white transition-[background-color,transform] duration-150 ease-out hover:bg-plum-deep active:scale-[0.97]"
                            >
                                <Phone className="size-4" aria-hidden /> Call{' '}
                                {lead.phone}
                            </a>
                        )}
                        {lead.email && (
                            <a
                                href={`mailto:${lead.email}`}
                                className="inline-flex items-center gap-2 border border-plum px-4 py-2 text-sm font-medium text-plum transition-[background-color,transform] duration-150 ease-out hover:bg-lilac/50 active:scale-[0.97] dark:border-lilac dark:text-lilac"
                            >
                                <Mail className="size-4" aria-hidden /> Email
                            </a>
                        )}
                    </div>
                </header>

                <section aria-labelledby="pipeline">
                    <h2 id="pipeline" className="sr-only">
                        Pipeline stage
                    </h2>
                    <ol className="grid grid-cols-3 gap-px border border-border bg-border sm:grid-cols-5 xl:grid-cols-9">
                        {keys.map((key, i) => {
                            const state =
                                i < current
                                    ? 'done'
                                    : i === current
                                      ? 'current'
                                      : 'ahead';
                            const cls = cn(
                                'flex h-full w-full flex-col items-start gap-1 px-3 py-2.5 text-left text-xs transition-colors duration-150 ease-out',
                                state === 'current' && 'bg-plum text-white',
                                state === 'done' &&
                                    'bg-lilac/60 text-plum dark:bg-plum/40 dark:text-lilac',
                                state === 'ahead' &&
                                    'bg-background text-muted-foreground',
                            );
                            const body = (
                                <>
                                    <span className="tabular-nums opacity-70">
                                        {i + 1}
                                    </span>
                                    <span className="font-medium">
                                        {stages[key]}
                                    </span>
                                </>
                            );

                            return (
                                <li key={key} className="bg-background">
                                    {editable && state !== 'current' ? (
                                        <button
                                            type="button"
                                            onClick={() =>
                                                router.patch(
                                                    leads.update(lead.id).url,
                                                    { stage: key },
                                                    { preserveScroll: true },
                                                )
                                            }
                                            className={cn(
                                                cls,
                                                'hover:bg-mist hover:text-foreground dark:hover:bg-white/10',
                                            )}
                                            aria-label={`Move to ${stages[key]}`}
                                        >
                                            {body}
                                        </button>
                                    ) : (
                                        <span
                                            className={cls}
                                            aria-current={
                                                state === 'current'
                                                    ? 'step'
                                                    : undefined
                                            }
                                        >
                                            {body}
                                        </span>
                                    )}
                                </li>
                            );
                        })}
                    </ol>
                </section>

                <div className="grid gap-10 lg:grid-cols-[minmax(0,1fr)_22rem] xl:grid-cols-[minmax(0,1fr)_26rem]">
                    <section aria-labelledby="activity" className="min-w-0">
                        <h2 id="activity" className="mb-4 text-lg font-medium">
                            Activity
                        </h2>
                        {editable && (
                            <form onSubmit={addNote} className="mb-6">
                                <label htmlFor="note" className="sr-only">
                                    Add a note
                                </label>
                                <textarea
                                    id="note"
                                    rows={3}
                                    value={note.data.note}
                                    onChange={(e) =>
                                        note.setData('note', e.target.value)
                                    }
                                    placeholder="Log a call, a question or a follow-up"
                                    className="block w-full resize-y border border-input bg-background px-3 py-2 text-sm outline-none focus:border-violet"
                                />
                                <InputError
                                    message={note.errors.note}
                                    className="mt-2"
                                />
                                <button
                                    type="submit"
                                    disabled={
                                        note.processing ||
                                        !note.data.note.trim()
                                    }
                                    className="mt-2 bg-plum px-4 py-2 text-sm font-medium text-white transition-[background-color,transform] duration-150 ease-out hover:bg-plum-deep active:scale-[0.97] disabled:opacity-50"
                                >
                                    Add note
                                </button>
                            </form>
                        )}
                        <ol className="border-l border-border">
                            {activities.map((a) => (
                                <li
                                    key={a.id}
                                    className="relative pb-5 pl-5 last:pb-0"
                                >
                                    <span
                                        aria-hidden
                                        className={cn(
                                            'absolute top-1.5 -left-[4.5px] size-2 rotate-45',
                                            a.type === 'note'
                                                ? 'bg-gold'
                                                : 'bg-plum/40 dark:bg-lilac/60',
                                        )}
                                    />
                                    <p className="text-sm whitespace-pre-line">
                                        {a.description}
                                    </p>
                                    <p className="mt-0.5 text-xs text-muted-foreground">
                                        {a.author ?? 'Website'},{' '}
                                        {ago(a.created_at)}
                                    </p>
                                </li>
                            ))}
                        </ol>
                    </section>

                    <aside className="flex min-w-0 flex-col gap-8">
                        {lead.message && (
                            <section aria-labelledby="message">
                                <h2
                                    id="message"
                                    className="mb-2 text-lg font-medium"
                                >
                                    Their message
                                </h2>
                                <blockquote className="border-l-2 border-gold pl-4 text-sm whitespace-pre-line">
                                    {lead.message}
                                </blockquote>
                            </section>
                        )}

                        {record && (
                            <section aria-labelledby="record">
                                <h2
                                    id="record"
                                    className="mb-2 text-lg font-medium"
                                >
                                    Client record
                                </h2>
                                <dl className="grid grid-cols-2 border-y border-border text-sm sm:grid-cols-4">
                                    {(
                                        [
                                            ['Visits', String(record.visits)],
                                            [
                                                'Last visit',
                                                record.last_visit
                                                    ? formatDate(
                                                          record.last_visit,
                                                          {
                                                              day: 'numeric',
                                                              month: 'short',
                                                              year: 'numeric',
                                                          },
                                                      )
                                                    : 'None yet',
                                            ],
                                            [
                                                'Paid to date',
                                                money(record.spend),
                                            ],
                                            [
                                                'Purchases',
                                                record.purchases
                                                    ? `${record.purchases}, avg ${money(record.average)}`
                                                    : '0',
                                            ],
                                        ] as const
                                    ).map(([k, v]) => (
                                        <div key={k} className="py-3 pr-4">
                                            <dt className="text-xs text-muted-foreground">
                                                {k}
                                            </dt>
                                            <dd className="mt-1 font-medium tabular-nums">
                                                {v}
                                            </dd>
                                        </div>
                                    ))}
                                </dl>
                                {record.sales.length > 0 ? (
                                    <ul className="divide-y divide-border border-b border-border">
                                        {record.sales.map((s) => (
                                            <li key={s.id}>
                                                <Link
                                                    href={
                                                        pos.sales.show(s.id).url
                                                    }
                                                    className="flex items-center justify-between gap-3 py-3 text-sm hover:bg-mist dark:hover:bg-white/5"
                                                >
                                                    <span className="min-w-0">
                                                        <span className="block font-medium">
                                                            {formatDate(
                                                                s.date,
                                                                {
                                                                    day: 'numeric',
                                                                    month: 'short',
                                                                    year: 'numeric',
                                                                },
                                                            )}
                                                            , {s.reference}
                                                        </span>
                                                        <span className="block truncate text-muted-foreground">
                                                            {s.items}
                                                            {s.branch
                                                                ? `, ${s.branch}`
                                                                : ''}
                                                        </span>
                                                    </span>
                                                    <span className="flex shrink-0 items-center gap-3">
                                                        <span
                                                            className={`border px-2 py-1 text-xs ${statusTone(s.status)}`}
                                                        >
                                                            {record.statuses[
                                                                s.status
                                                            ] ?? s.status}
                                                        </span>
                                                        <span className="tabular-nums">
                                                            {money(s.total)}
                                                        </span>
                                                    </span>
                                                </Link>
                                            </li>
                                        ))}
                                    </ul>
                                ) : (
                                    <p className="py-3 text-sm text-muted-foreground">
                                        No purchases linked to this client yet.
                                        Pick them as the client at the till to
                                        build their record.
                                    </p>
                                )}
                            </section>
                        )}

                        <section aria-labelledby="visits">
                            <h2
                                id="visits"
                                className="mb-2 text-lg font-medium"
                            >
                                Appointments
                            </h2>
                            {appointments.length ? (
                                <ul className="divide-y divide-border border-y border-border">
                                    {appointments.map((a) => (
                                        <li
                                            key={a.id}
                                            className="flex items-center justify-between gap-3 py-3"
                                        >
                                            <span className="min-w-0 text-sm">
                                                <span className="block font-medium">
                                                    {formatDate(a.starts_at, {
                                                        weekday: 'short',
                                                        day: 'numeric',
                                                        month: 'short',
                                                    })}
                                                    ,{' '}
                                                    {clock(a.starts_at).join(
                                                        ' ',
                                                    )}
                                                </span>
                                                <span className="block truncate text-muted-foreground">
                                                    {a.treatment}, {a.branch}
                                                </span>
                                            </span>
                                            <span className="flex shrink-0 flex-col items-end gap-1">
                                                <StatusSelect
                                                    id={a.id}
                                                    status={a.status}
                                                    statuses={statuses}
                                                />
                                                {can('appointments.edit') &&
                                                    canMove(a) && (
                                                        <Link
                                                            href={
                                                                appointmentRoutes.create(
                                                                    {
                                                                        query: {
                                                                            reschedule:
                                                                                a.id,
                                                                        },
                                                                    },
                                                                ).url
                                                            }
                                                            className="text-xs text-violet underline-offset-4 hover:underline dark:text-lilac"
                                                        >
                                                            Move
                                                        </Link>
                                                    )}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    No appointments yet.
                                </p>
                            )}
                        </section>

                        <section aria-labelledby="details">
                            <h2
                                id="details"
                                className="mb-2 text-lg font-medium"
                            >
                                Details
                            </h2>
                            <dl className="divide-y divide-border border-y border-border text-sm">
                                {details
                                    .filter(([, v]) => v)
                                    .map(([k, v]) => (
                                        <div
                                            key={k}
                                            className="flex justify-between gap-4 py-2"
                                        >
                                            <dt className="text-muted-foreground">
                                                {k}
                                            </dt>
                                            <dd className="text-right">{v}</dd>
                                        </div>
                                    ))}
                            </dl>
                        </section>
                    </aside>
                </div>

                <Link
                    href={leads.index().url}
                    className="self-start text-sm text-violet underline-offset-4 hover:underline dark:text-lilac"
                >
                    Back to all leads
                </Link>
            </div>
        </>
    );
}

LeadShow.layout = {
    breadcrumbs: [{ title: 'Leads', href: leads.index() }],
};
