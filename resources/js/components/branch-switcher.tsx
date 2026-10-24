import { router, usePage } from '@inertiajs/react';
import { MapPin } from 'lucide-react';
import branchRoutes from '@/routes/admin/branch';

/**
 * Which branch the office screens show. Someone allowed to see every branch
 * picks one here (or all of them); everyone else works at their own branch,
 * shown as a label. The server enforces the same rule (App\Support\BranchAccess).
 */
export default function BranchSwitcher() {
    const context = usePage().props.branchContext;

    if (!context || context.options.length === 0) {
        return null;
    }

    if (!context.canSwitch) {
        const home = context.options.find((b) => b.id === context.home);

        return home ? (
            <p className="flex items-center gap-2 px-2 text-xs text-sidebar-foreground/80">
                <MapPin className="size-3.5 shrink-0" />
                {home.name}
            </p>
        ) : null;
    }

    return (
        <label className="grid gap-1 px-2 text-xs text-sidebar-foreground/70">
            Branch
            <select
                value={context.current ?? ''}
                onChange={(e) =>
                    router.post(
                        branchRoutes.choose().url,
                        {
                            branch_id: e.target.value
                                ? Number(e.target.value)
                                : null,
                        },
                        { preserveScroll: true },
                    )
                }
                className="h-9 w-full border border-sidebar-border bg-sidebar px-2 text-sm text-sidebar-foreground"
            >
                <option value="">All branches</option>
                {context.options.map((b) => (
                    <option key={b.id} value={b.id}>
                        {b.city ? `${b.city}, ${b.name}` : b.name}
                    </option>
                ))}
            </select>
        </label>
    );
}
