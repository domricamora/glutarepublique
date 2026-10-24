import { Link, usePage } from '@inertiajs/react';
import { Bell, Search } from 'lucide-react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { openPalette } from '@/components/admin/command-palette';
import { SidebarTrigger } from '@/components/ui/sidebar';
import notificationRoutes from '@/routes/admin/notifications';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { notificationsUnread } = usePage().props;

    return (
        <header className="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/50 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            <button
                type="button"
                onClick={openPalette}
                className="ml-auto hidden h-9 items-center gap-2 border border-border bg-background px-3 text-sm text-muted-foreground transition-colors duration-150 ease-out hover:border-violet hover:text-foreground sm:flex"
            >
                <Search className="size-4" aria-hidden />
                Search
                <kbd className="ml-2 border border-border px-1.5 py-0.5 text-[11px] tracking-wide">
                    Ctrl K
                </kbd>
            </button>
            <Link
                href={notificationRoutes.index().url}
                aria-label={
                    notificationsUnread
                        ? `Notifications, ${notificationsUnread} unread`
                        : 'Notifications'
                }
                className="relative ml-auto inline-flex size-9 items-center justify-center border border-border bg-background text-muted-foreground transition-colors duration-150 ease-out hover:border-violet hover:text-foreground sm:ml-2"
            >
                <Bell className="size-4" aria-hidden />
                {notificationsUnread > 0 && (
                    <span className="absolute -top-1.5 -right-1.5 min-w-5 bg-plum px-1 text-center text-[11px] leading-5 font-medium text-white tabular-nums">
                        {notificationsUnread > 99 ? '99+' : notificationsUnread}
                    </span>
                )}
            </Link>
        </header>
    );
}
