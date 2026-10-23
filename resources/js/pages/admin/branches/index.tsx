import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { DAY_NAMES, hoursSummary, openingLabel } from '@/lib/site';
import { dashboard } from '@/routes';
import branchRoutes from '@/routes/admin/branches';

type Hours = ([string, string] | null)[];

type Branch = {
    id: number;
    name: string;
    slug: string;
    address: string | null;
    city: string | null;
    phone: string | null;
    email: string | null;
    hours: Hours;
    map_url: string | null;
    is_active: boolean;
    opens_on: string | null;
};

const MONDAY_FIRST = [1, 2, 3, 4, 5, 6, 0];

const blank = {
    name: '',
    address: '',
    city: '',
    phone: '',
    email: '',
    map_url: '',
    is_active: true,
    opens_on: '',
    hours: [
        null,
        ['10:00', '20:00'],
        ['10:00', '20:00'],
        ['10:00', '20:00'],
        ['10:00', '20:00'],
        ['10:00', '20:00'],
        ['10:00', '18:00'],
    ] as Hours,
};

export default function BranchesIndex({ branches }: { branches: Branch[] }) {
    const [editing, setEditing] = useState<number | 'new' | null>(null);

    return (
        <>
            <Head title="Branches" />
            <div className="flex flex-1 flex-col gap-8 px-4 py-6 md:px-8 md:py-8">
                <header className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h1 className="font-display text-3xl md:text-4xl">
                            Branches
                        </h1>
                        <p className="mt-2 max-w-2xl text-muted-foreground">
                            Address, contact and opening hours. The hours here
                            are the ones the website shows, the open or closed
                            badge uses, and online booking offers times within.
                        </p>
                    </div>
                    {editing !== 'new' && (
                        <Button onClick={() => setEditing('new')}>
                            Add a branch
                        </Button>
                    )}
                </header>

                {editing === 'new' && (
                    <BranchForm onDone={() => setEditing(null)} />
                )}

                <ul className="divide-y divide-border border-y border-border">
                    {branches.map((branch) =>
                        editing === branch.id ? (
                            <li key={branch.id} className="py-5">
                                <BranchForm
                                    branch={branch}
                                    onDone={() => setEditing(null)}
                                />
                            </li>
                        ) : (
                            <li
                                key={branch.id}
                                className="grid gap-4 py-5 md:grid-cols-[1fr_auto_auto] md:items-start"
                            >
                                <div className="min-w-0">
                                    <p className="flex flex-wrap items-center gap-2 font-medium">
                                        {branch.name}
                                        {!branch.is_active && (
                                            <Badge variant="secondary">
                                                Hidden
                                            </Badge>
                                        )}
                                        {openingLabel(branch) && (
                                            <Badge variant="outline">
                                                {openingLabel(branch)}
                                            </Badge>
                                        )}
                                    </p>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {[branch.address, branch.city]
                                            .filter(Boolean)
                                            .join(', ')}
                                    </p>
                                    <p className="mt-1 text-sm break-all text-muted-foreground">
                                        {[branch.phone, branch.email]
                                            .filter(Boolean)
                                            .join(' · ')}
                                    </p>
                                </div>
                                <dl className="grid grid-cols-[auto_auto] gap-x-4 gap-y-1 text-sm">
                                    {hoursSummary(branch.hours).map((row) => (
                                        <div
                                            key={row.days}
                                            className="contents"
                                        >
                                            <dt className="text-muted-foreground">
                                                {row.days}
                                            </dt>
                                            <dd className="tabular-nums">
                                                {row.time}
                                            </dd>
                                        </div>
                                    ))}
                                </dl>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setEditing(branch.id)}
                                    className="justify-self-start"
                                >
                                    Edit
                                </Button>
                            </li>
                        ),
                    )}
                </ul>
            </div>
        </>
    );
}

function BranchForm({
    branch,
    onDone,
}: {
    branch?: Branch;
    onDone: () => void;
}) {
    const form = useForm(
        branch
            ? {
                  name: branch.name,
                  address: branch.address ?? '',
                  city: branch.city ?? '',
                  phone: branch.phone ?? '',
                  email: branch.email ?? '',
                  map_url: branch.map_url ?? '',
                  is_active: branch.is_active,
                  opens_on: branch.opens_on ?? '',
                  hours: branch.hours,
              }
            : blank,
    );

    const setDay = (day: number, slot: [string, string] | null) =>
        form.setData(
            'hours',
            form.data.hours.map((h, i) => (i === day ? slot : h)),
        );

    const text = (
        key:
            | 'name'
            | 'address'
            | 'city'
            | 'phone'
            | 'email'
            | 'map_url'
            | 'opens_on',
        label: string,
        type = 'text',
    ) => (
        <div className="grid gap-1">
            <Label htmlFor={`${key}-${branch?.id ?? 'new'}`}>{label}</Label>
            <Input
                id={`${key}-${branch?.id ?? 'new'}`}
                type={type}
                value={form.data[key]}
                onChange={(e) => form.setData(key, e.target.value)}
                required={key === 'name'}
            />
            {form.errors[key] && (
                <p className="text-sm text-destructive">{form.errors[key]}</p>
            )}
        </div>
    );

    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                const options = { preserveScroll: true, onSuccess: onDone };

                if (branch) {
                    form.patch(branchRoutes.update(branch.id).url, options);
                } else {
                    form.post(branchRoutes.store().url, options);
                }
            }}
            className="grid gap-6 lg:grid-cols-2"
        >
            <div className="grid content-start gap-4 sm:grid-cols-2">
                {text('name', 'Name')}
                {text('city', 'City')}
                <div className="sm:col-span-2">
                    {text('address', 'Address')}
                </div>
                {text('phone', 'Phone')}
                {text('email', 'Email', 'email')}
                <div className="sm:col-span-2">
                    {text('map_url', 'Map link (https)', 'url')}
                </div>
                <label className="flex items-center gap-2 text-sm sm:col-span-2">
                    <input
                        type="checkbox"
                        checked={form.data.is_active}
                        onChange={(e) =>
                            form.setData('is_active', e.target.checked)
                        }
                    />
                    Show on the website and take bookings
                </label>
                <div className="sm:col-span-2">
                    {text(
                        'opens_on',
                        'Opens on (leave empty once open)',
                        'date',
                    )}
                    <p className="mt-1 text-xs text-muted-foreground">
                        Before this date the website shows the branch as opening
                        soon and online booking skips it.
                    </p>
                </div>
            </div>

            <fieldset className="grid content-start gap-2">
                <legend className="mb-2 text-sm font-medium">
                    Opening hours
                </legend>
                {MONDAY_FIRST.map((day) => {
                    const slot = form.data.hours[day];
                    const error = (form.errors as Record<string, string>)[
                        `hours.${day}`
                    ];

                    return (
                        <div key={day}>
                            <div className="flex flex-wrap items-center gap-3 text-sm">
                                <label className="flex w-20 items-center gap-2">
                                    <input
                                        type="checkbox"
                                        checked={slot !== null}
                                        onChange={(e) =>
                                            setDay(
                                                day,
                                                e.target.checked
                                                    ? ['10:00', '19:00']
                                                    : null,
                                            )
                                        }
                                    />
                                    {DAY_NAMES[day]}
                                </label>
                                {slot ? (
                                    <>
                                        <input
                                            type="time"
                                            aria-label={`${DAY_NAMES[day]} opens`}
                                            value={slot[0]}
                                            onChange={(e) =>
                                                setDay(day, [
                                                    e.target.value,
                                                    slot[1],
                                                ])
                                            }
                                            className="h-9 border border-border bg-background px-2 tabular-nums"
                                        />
                                        <span className="text-muted-foreground">
                                            to
                                        </span>
                                        <input
                                            type="time"
                                            aria-label={`${DAY_NAMES[day]} closes`}
                                            value={slot[1]}
                                            onChange={(e) =>
                                                setDay(day, [
                                                    slot[0],
                                                    e.target.value,
                                                ])
                                            }
                                            className="h-9 border border-border bg-background px-2 tabular-nums"
                                        />
                                    </>
                                ) : (
                                    <span className="text-muted-foreground">
                                        Closed
                                    </span>
                                )}
                            </div>
                            {error && (
                                <p className="mt-1 text-sm text-destructive">
                                    {error}
                                </p>
                            )}
                        </div>
                    );
                })}
            </fieldset>

            <div className="flex gap-2 lg:col-span-2">
                <Button type="submit" disabled={form.processing}>
                    {branch ? 'Save branch' : 'Add branch'}
                </Button>
                <Button type="button" variant="ghost" onClick={onDone}>
                    Cancel
                </Button>
            </div>
        </form>
    );
}

BranchesIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Branches', href: branchRoutes.index() },
    ],
};
