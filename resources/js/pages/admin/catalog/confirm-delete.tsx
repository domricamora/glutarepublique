import { router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';

/**
 * A delete button that asks first, in place: "Delete? Yes / No", with a line
 * on what deleting keeps. Used for services and products in the catalogue.
 */
export function ConfirmDelete({
    name,
    url,
    note,
}: {
    name: string;
    url: string;
    note: string;
}) {
    const [asking, setAsking] = useState(false);
    const [busy, setBusy] = useState(false);

    if (!asking) {
        return (
            <Button
                type="button"
                size="sm"
                variant="ghost"
                onClick={() => setAsking(true)}
                aria-label={`Delete ${name}`}
            >
                <Trash2 className="h-4 w-4 text-destructive" />
            </Button>
        );
    }

    return (
        <span className="inline-flex flex-col items-end gap-1 text-left">
            <span className="inline-flex items-center gap-1">
                <span className="text-sm">Delete {name}?</span>
                <Button
                    type="button"
                    size="sm"
                    variant="destructive"
                    disabled={busy}
                    onClick={() => {
                        setBusy(true);
                        router.delete(url, {
                            preserveScroll: true,
                            onFinish: () => setBusy(false),
                        });
                    }}
                >
                    Yes, delete
                </Button>
                <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    onClick={() => setAsking(false)}
                >
                    No
                </Button>
            </span>
            <span className="max-w-xs text-xs text-muted-foreground">
                {note}
            </span>
        </span>
    );
}
