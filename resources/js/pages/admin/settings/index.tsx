import { Head, router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import settings from '@/routes/admin/settings/clinic';

type Social = {
    platform: string;
    label: string;
    base: string;
    handle: string | null;
};

type Webhooks = {
    url: string | null;
    secret: string | null;
    events: string[];
    options: Record<string, string>;
    last: string | null;
};

type Props = {
    socials: Social[];
    chat_enabled: boolean;
    chat_greeting: string;
    /** Null for anyone but the owner: webhooks can carry client contacts. */
    webhooks: Webhooks | null;
};

export default function ClinicSettings({
    socials,
    chat_enabled,
    chat_greeting,
    webhooks,
}: Props) {
    const form = useForm<{
        socials: Record<string, string>;
        chat_enabled: number;
        chat_greeting: string;
        webhook_url?: string;
        webhook_events?: string[];
    }>({
        socials: Object.fromEntries(
            socials.map((s) => [s.platform, s.handle ?? '']),
        ),
        chat_enabled: chat_enabled ? 1 : 0,
        chat_greeting,
        // Only sent by the owner, so a manager saving the page leaves the webhook alone.
        ...(webhooks
            ? {
                  webhook_url: webhooks.url ?? '',
                  webhook_events: webhooks.events,
              }
            : {}),
    });
    const events = form.data.webhook_events ?? [];

    return (
        <>
            <Head title="Clinic settings" />
            <div className="flex flex-1 flex-col gap-8 px-4 py-6 md:px-8 md:py-8">
                <header>
                    <h1 className="font-display text-3xl md:text-4xl">
                        Clinic settings
                    </h1>
                    <p className="mt-2 max-w-2xl text-muted-foreground">
                        The things this clinic owns rather than the software.
                        Saved here, they survive the next deployment, which a
                        config file would not.
                    </p>
                </header>

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.patch(settings.update().url, {
                            preserveScroll: true,
                        });
                    }}
                    className="space-y-10"
                >
                    <section className="max-w-2xl space-y-4">
                        <div>
                            <h2 className="font-display text-xl">
                                Social media
                            </h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                A handle or a full profile link, whichever is
                                easier to copy. Anything left blank is not shown
                                on the website at all.
                            </p>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            {socials.map((social) => (
                                <div
                                    key={social.platform}
                                    className="grid gap-1"
                                >
                                    <Label htmlFor={social.platform}>
                                        {social.label}
                                    </Label>
                                    <div className="flex items-center">
                                        <span className="shrink-0 border border-r-0 border-border bg-mist px-2 py-2 text-sm text-muted-foreground dark:bg-white/5">
                                            {social.base}
                                        </span>
                                        <Input
                                            id={social.platform}
                                            value={
                                                form.data.socials[
                                                    social.platform
                                                ] ?? ''
                                            }
                                            onChange={(e) =>
                                                form.setData('socials', {
                                                    ...form.data.socials,
                                                    [social.platform]:
                                                        e.target.value,
                                                })
                                            }
                                            placeholder="handle"
                                            className="rounded-none border-l-0"
                                        />
                                    </div>
                                </div>
                            ))}
                        </div>
                    </section>
                    <section className="max-w-2xl space-y-4">
                        <div>
                            <h2 className="font-display text-xl">Chat</h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Whether the website offers a chat window, and
                                how it greets a visitor.
                            </p>
                        </div>

                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={form.data.chat_enabled === 1}
                                onChange={(e) =>
                                    form.setData(
                                        'chat_enabled',
                                        e.target.checked ? 1 : 0,
                                    )
                                }
                            />
                            Show the chat window on the website
                        </label>

                        <div className="grid gap-1">
                            <Label htmlFor="chat_greeting">Greeting</Label>
                            <Input
                                id="chat_greeting"
                                value={form.data.chat_greeting}
                                onChange={(e) =>
                                    form.setData(
                                        'chat_greeting',
                                        e.target.value,
                                    )
                                }
                                maxLength={200}
                            />
                            {form.errors.chat_greeting && (
                                <p className="text-sm text-destructive">
                                    {form.errors.chat_greeting}
                                </p>
                            )}
                        </div>
                    </section>

                    {webhooks && (
                        <section className="max-w-2xl space-y-4">
                            <div>
                                <h2 className="font-display text-xl">
                                    Webhooks
                                </h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Send new enquiries, bookings and sales to
                                    another system (a CRM, Zapier, a group chat
                                    bot) as they happen. Each request is signed
                                    with the key below in the X-Clinic-Signature
                                    header. Client phone numbers and emails are
                                    included, so only point this at a system you
                                    control.
                                </p>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="webhook_url">
                                    Send to (https address)
                                </Label>
                                <Input
                                    id="webhook_url"
                                    type="url"
                                    placeholder="https://"
                                    value={form.data.webhook_url ?? ''}
                                    onChange={(e) =>
                                        form.setData(
                                            'webhook_url',
                                            e.target.value,
                                        )
                                    }
                                />
                                {form.errors.webhook_url && (
                                    <p className="text-sm text-destructive">
                                        {form.errors.webhook_url}
                                    </p>
                                )}
                            </div>
                            <fieldset className="space-y-2">
                                <legend className="text-sm font-medium">
                                    Events
                                </legend>
                                {Object.entries(webhooks.options).map(
                                    ([key, label]) => (
                                        <label
                                            key={key}
                                            className="flex items-center gap-2 text-sm"
                                        >
                                            <input
                                                type="checkbox"
                                                checked={events.includes(key)}
                                                onChange={() =>
                                                    form.setData(
                                                        'webhook_events',
                                                        events.includes(key)
                                                            ? events.filter(
                                                                  (e) =>
                                                                      e !== key,
                                                              )
                                                            : [...events, key],
                                                    )
                                                }
                                            />
                                            {label}
                                        </label>
                                    ),
                                )}
                            </fieldset>
                            {webhooks.secret && (
                                <p className="text-sm">
                                    Signing key:{' '}
                                    <code className="border border-border px-1.5 py-0.5 text-xs break-all">
                                        {webhooks.secret}
                                    </code>
                                </p>
                            )}
                            {webhooks.url && (
                                <div className="flex flex-wrap items-center gap-3 text-sm">
                                    <button
                                        type="button"
                                        onClick={() =>
                                            router.post(
                                                settings.webhookTest().url,
                                                {},
                                                { preserveScroll: true },
                                            )
                                        }
                                        className="press h-9 border border-border px-3 hover:bg-mist dark:hover:bg-white/5"
                                    >
                                        Send a test
                                    </button>
                                    <span className="text-muted-foreground">
                                        {webhooks.last
                                            ? `Last sent ${webhooks.last}`
                                            : 'Nothing sent yet.'}
                                    </span>
                                </div>
                            )}
                        </section>
                    )}

                    <Button type="submit" disabled={form.processing}>
                        {form.processing ? (
                            <>
                                <Spinner /> Saving...
                            </>
                        ) : (
                            'Save settings'
                        )}
                    </Button>
                </form>
            </div>
        </>
    );
}

ClinicSettings.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Clinic settings', href: settings.index() },
    ],
};
