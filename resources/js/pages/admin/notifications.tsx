import { Head, Link, router } from '@inertiajs/react';
import { ago } from '@/lib/admin';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import notificationRoutes from '@/routes/admin/notifications';

type Props = {
    notifications: {
        id: string;
        title: string;
        body: string;
        event: string;
        read: boolean;
        created_at: string;
    }[];
};

/** Alerts for the signed-in person: new enquiries, online bookings, stock to count. */
export default function Notifications({ notifications }: Props) {
    const unread = notifications.filter((n) => !n.read).length;

    return (
        <>
            <Head title="Notifications" />
            <div className="flex flex-1 flex-col gap-6 px-4 py-6 md:px-8 md:py-8">
                <header className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h1 className="font-display text-3xl md:text-4xl">
                            Notifications
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            New enquiries, online bookings and stock to count,
                            for the work you do.
                        </p>
                    </div>
                    {unread > 0 && (
                        <button
                            type="button"
                            onClick={() =>
                                router.post(
                                    notificationRoutes.read().url,
                                    {},
                                    { preserveScroll: true },
                                )
                            }
                            className="press h-10 border border-border px-4 text-sm hover:bg-mist dark:hover:bg-white/5"
                        >
                            Mark all as read
                        </button>
                    )}
                </header>

                {notifications.length === 0 ? (
                    <p className="border border-border px-4 py-10 text-center text-sm text-muted-foreground">
                        Nothing yet. Enquiries and online bookings appear here
                        as they come in.
                    </p>
                ) : (
                    <ul className="divide-y divide-border border-y border-border">
                        {notifications.map((n) => (
                            <li key={n.id}>
                                <Link
                                    href={notificationRoutes.open(n.id).url}
                                    className="flex items-start gap-3 py-3 hover:bg-mist dark:hover:bg-white/5"
                                >
                                    <span
                                        aria-hidden
                                        className={cn(
                                            'mt-1.5 size-2 shrink-0',
                                            n.read
                                                ? 'bg-transparent'
                                                : 'bg-plum',
                                        )}
                                    />
                                    <span className="min-w-0 flex-1 text-sm">
                                        <span
                                            className={cn(
                                                'block',
                                                !n.read && 'font-medium',
                                            )}
                                        >
                                            {n.title}
                                            {!n.read && (
                                                <span className="sr-only">
                                                    {' '}
                                                    (unread)
                                                </span>
                                            )}
                                        </span>
                                        <span className="block text-muted-foreground">
                                            {n.body}
                                        </span>
                                    </span>
                                    <span className="shrink-0 text-xs text-muted-foreground">
                                        {ago(n.created_at)}
                                    </span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}

Notifications.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Notifications', href: notificationRoutes.index() },
    ],
};
