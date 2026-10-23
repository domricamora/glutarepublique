import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { login as passkeyLogin, loginOptions } from '@/routes/passkey';
import PasskeyVerify from '@/components/passkey-verify';

type Props = {
    status?: string;
    canResetPassword: boolean;
};

/** The staging demo logins seeded by DatabaseSeeder; the password is shared with testers separately. */
const DEMO: [string, string, string][] = [
    ['Owner', 'owner@glutarepubliq.com', 'Dashboard, money, stock and people'],
    [
        'Front desk',
        'reception@glutarepubliq.com',
        'Agenda, leads, chat and the till',
    ],
    ['Super admin', 'admin@glutarepubliq.com', 'Everything, settings included'],
];

export default function Login({ status, canResetPassword }: Props) {
    return (
        <>
            <Head title="Log in" />

            {/* The passkey package defaults to /passkeys/... on the domain root,
                which 404s while the app is served from a sub-folder. */}
            <PasskeyVerify
                routes={{
                    options: loginOptions(),
                    submit: passkeyLogin(),
                }}
            />

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="email"
                                    placeholder="email@example.com"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center">
                                    <Label htmlFor="password">Password</Label>
                                    {canResetPassword && (
                                        <TextLink
                                            href={request()}
                                            className="ml-auto text-sm"
                                            tabIndex={5}
                                        >
                                            Forgot your password?
                                        </TextLink>
                                    )}
                                </div>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    tabIndex={2}
                                    autoComplete="current-password"
                                    placeholder="Password"
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="flex items-center space-x-3">
                                <Checkbox
                                    id="remember"
                                    name="remember"
                                    tabIndex={3}
                                />
                                <Label htmlFor="remember">Remember me</Label>
                            </div>

                            <Button
                                type="submit"
                                className="mt-4 w-full"
                                tabIndex={4}
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing && <Spinner />}
                                Log in
                            </Button>
                        </div>

                        <section
                            aria-labelledby="demo-heading"
                            className="border border-border bg-mist p-4 dark:bg-white/5"
                        >
                            <h2
                                id="demo-heading"
                                className="text-sm font-medium"
                            >
                                Exploring the demo? Sign in as
                            </h2>
                            <ul className="mt-3 grid gap-2">
                                {DEMO.map(([role, email, sees]) => (
                                    <li key={email}>
                                        <button
                                            type="button"
                                            disabled={processing}
                                            onClick={() => {
                                                // Fills the address only; the password is still typed.
                                                const field =
                                                    document.getElementById(
                                                        'email',
                                                    ) as HTMLInputElement | null;

                                                if (field) {
                                                    field.value = email;
                                                }

                                                document
                                                    .getElementById('password')
                                                    ?.focus();
                                            }}
                                            className="press flex w-full items-center justify-between gap-3 border border-border bg-card px-3 py-2.5 text-left transition-colors hover:border-royal disabled:opacity-50"
                                        >
                                            <span>
                                                <span className="block text-sm font-medium">
                                                    {role}
                                                </span>
                                                <span className="block text-xs text-muted-foreground">
                                                    {sees}
                                                </span>
                                            </span>
                                            <span className="text-xs text-rose-ink">
                                                Use
                                            </span>
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        </section>

                        <p className="text-center text-sm text-muted-foreground">
                            No account yet? Ask the clinic administrator.
                        </p>
                    </>
                )}
            </Form>

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    {status}
                </div>
            )}
        </>
    );
}

Login.layout = {
    title: 'Welcome back',
    description: 'Sign in to the clinic desk.',
};
