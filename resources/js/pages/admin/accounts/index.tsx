import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import accounts from '@/routes/admin/accounts';

type Account = {
    id: number;
    name: string;
    email: string;
    role: string | null;
    two_factor: boolean;
    disabled: boolean;
    created_at: string | null;
};

type Role = { name: string; permissions: string[] };

type Props = { accounts: Account[]; roles: Role[]; me: number };

const field = 'h-9 w-full border border-border bg-background px-2 text-sm';

/** What a role opens, in words, from its permission list. */
const reach = (permissions: string[]): string => {
    const areas = [
        ['appointments', 'Appointments'],
        ['leads', 'Leads and chat'],
        ['pos', 'Point of sale'],
        ['inventory', 'Catalogue and stock'],
        ['hr', 'Staff'],
        ['payroll', 'Payroll'],
        ['accounting', 'Accounting'],
        ['content', 'Website pages'],
        ['settings', 'Settings and accounts'],
    ]
        .filter(([key]) => permissions.some((p) => p.startsWith(`${key}.`)))
        .map(([, label]) => label);

    return areas.length ? areas.join(', ') : 'Dashboard only';
};

export default function AccountsIndex({ accounts: list, roles, me }: Props) {
    const [editing, setEditing] = useState<number | null>(null);
    const create = useForm({
        name: '',
        email: '',
        role: roles.find((r) => r.name === 'Receptionist')?.name ?? '',
        password: '',
    });

    return (
        <>
            <Head title="Staff accounts" />
            <div className="flex flex-1 flex-col gap-8 px-4 py-6 md:px-8 md:py-8">
                <header>
                    <h1 className="font-display text-3xl md:text-4xl">
                        Staff accounts
                    </h1>
                    <p className="mt-2 max-w-2xl text-muted-foreground">
                        Who can sign in, and what each person can open. A role
                        limits a person to its own screens. Switch off an
                        account when someone leaves: their sales and notes keep
                        their name.
                    </p>
                </header>

                <ul className="divide-y divide-border border-y border-border">
                    {list.map((account) =>
                        editing === account.id ? (
                            <EditRow
                                key={account.id}
                                account={account}
                                roles={roles}
                                self={account.id === me}
                                onDone={() => setEditing(null)}
                            />
                        ) : (
                            <li
                                key={account.id}
                                className="flex flex-wrap items-center justify-between gap-3 py-3"
                            >
                                <div className="min-w-0">
                                    <p className="flex flex-wrap items-center gap-2 text-sm font-medium">
                                        {account.name}
                                        {account.id === me && (
                                            <Badge variant="outline">You</Badge>
                                        )}
                                        {account.disabled && (
                                            <Badge variant="secondary">
                                                Switched off
                                            </Badge>
                                        )}
                                    </p>
                                    <p className="mt-0.5 text-xs break-all text-muted-foreground">
                                        {account.email} &middot;{' '}
                                        {account.role ?? 'No role'}
                                        {account.two_factor &&
                                            ' · Two-factor on'}
                                    </p>
                                </div>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setEditing(account.id)}
                                >
                                    Edit
                                </Button>
                            </li>
                        ),
                    )}
                </ul>

                <section className="max-w-2xl space-y-4">
                    <div>
                        <h2 className="font-display text-xl">Add an account</h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Give the person their email and this first password
                            in person. They can change it, and turn on
                            two-factor or a passkey, under their own settings.
                        </p>
                    </div>
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            create.post(accounts.store().url, {
                                preserveScroll: true,
                                onSuccess: () => create.reset(),
                            });
                        }}
                        className="grid gap-4 sm:grid-cols-2"
                    >
                        <div className="grid gap-1">
                            <Label htmlFor="new-name">Name</Label>
                            <Input
                                id="new-name"
                                value={create.data.name}
                                onChange={(e) =>
                                    create.setData('name', e.target.value)
                                }
                                required
                            />
                            <FieldError message={create.errors.name} />
                        </div>
                        <div className="grid gap-1">
                            <Label htmlFor="new-email">Email</Label>
                            <Input
                                id="new-email"
                                type="email"
                                value={create.data.email}
                                onChange={(e) =>
                                    create.setData('email', e.target.value)
                                }
                                required
                            />
                            <FieldError message={create.errors.email} />
                        </div>
                        <div className="grid gap-1">
                            <Label htmlFor="new-role">Role</Label>
                            <select
                                id="new-role"
                                value={create.data.role}
                                onChange={(e) =>
                                    create.setData('role', e.target.value)
                                }
                                className={field}
                            >
                                {roles.map((r) => (
                                    <option key={r.name} value={r.name}>
                                        {r.name}
                                    </option>
                                ))}
                            </select>
                            <p className="text-xs text-muted-foreground">
                                Opens:{' '}
                                {reach(
                                    roles.find(
                                        (r) => r.name === create.data.role,
                                    )?.permissions ?? [],
                                )}
                            </p>
                        </div>
                        <div className="grid gap-1">
                            <Label htmlFor="new-password">First password</Label>
                            <Input
                                id="new-password"
                                type="text"
                                autoComplete="new-password"
                                value={create.data.password}
                                onChange={(e) =>
                                    create.setData('password', e.target.value)
                                }
                                minLength={10}
                                required
                            />
                            <FieldError message={create.errors.password} />
                        </div>
                        <div className="sm:col-span-2">
                            <Button type="submit" disabled={create.processing}>
                                Add account
                            </Button>
                        </div>
                    </form>
                </section>
            </div>
        </>
    );
}

function EditRow({
    account,
    roles,
    self,
    onDone,
}: {
    account: Account;
    roles: Role[];
    self: boolean;
    onDone: () => void;
}) {
    const form = useForm({
        name: account.name,
        role: account.role ?? roles[0]?.name ?? '',
        disabled: account.disabled,
        password: '',
    });

    return (
        <li className="py-4">
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    form.patch(accounts.update(account.id).url, {
                        preserveScroll: true,
                        onSuccess: onDone,
                    });
                }}
                className="grid gap-4 sm:grid-cols-2"
            >
                <div className="grid gap-1">
                    <Label htmlFor={`name-${account.id}`}>Name</Label>
                    <Input
                        id={`name-${account.id}`}
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                        required
                    />
                    <FieldError message={form.errors.name} />
                </div>
                <div className="grid gap-1">
                    <Label htmlFor={`role-${account.id}`}>Role</Label>
                    <select
                        id={`role-${account.id}`}
                        value={form.data.role}
                        disabled={self}
                        onChange={(e) => form.setData('role', e.target.value)}
                        className={field}
                    >
                        {roles.map((r) => (
                            <option key={r.name} value={r.name}>
                                {r.name}
                            </option>
                        ))}
                    </select>
                    <p className="text-xs text-muted-foreground">
                        Opens:{' '}
                        {reach(
                            roles.find((r) => r.name === form.data.role)
                                ?.permissions ?? [],
                        )}
                    </p>
                    <FieldError message={form.errors.role} />
                </div>
                <div className="grid gap-1">
                    <Label htmlFor={`password-${account.id}`}>
                        New password (leave blank to keep)
                    </Label>
                    <Input
                        id={`password-${account.id}`}
                        type="text"
                        autoComplete="new-password"
                        value={form.data.password}
                        onChange={(e) =>
                            form.setData('password', e.target.value)
                        }
                        minLength={10}
                    />
                    <FieldError message={form.errors.password} />
                </div>
                <label className="flex items-center gap-2 self-end pb-2 text-sm">
                    <input
                        type="checkbox"
                        checked={form.data.disabled}
                        disabled={self}
                        onChange={(e) =>
                            form.setData('disabled', e.target.checked)
                        }
                    />
                    Switch off this account
                </label>
                <div className="flex gap-2 sm:col-span-2">
                    <Button type="submit" disabled={form.processing}>
                        Save
                    </Button>
                    <Button type="button" variant="ghost" onClick={onDone}>
                        Cancel
                    </Button>
                </div>
            </form>
        </li>
    );
}

function FieldError({ message }: { message?: string }) {
    return message ? (
        <p className="text-sm text-destructive">{message}</p>
    ) : null;
}

AccountsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Staff accounts', href: accounts.index() },
    ],
};
