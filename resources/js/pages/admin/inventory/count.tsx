import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Search } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import inventory from '@/routes/admin/inventory';

type Props = {
    branch: { id: number; name: string };
    branches: { id: number; name: string }[];
    products: {
        id: number;
        name: string;
        sku: string | null;
        category: string | null;
        on_hand: number;
    }[];
};

/**
 * A count session: the whole shelf on one sheet. Staff type what they count,
 * the difference shows as they go, and only the rows that were counted are
 * saved.
 */
export default function InventoryCount({ branch, branches, products }: Props) {
    const form = useForm<{
        branch_id: number;
        counts: Record<number, number | null>;
        note: string;
    }>({ branch_id: branch.id, counts: {}, note: '' });
    const [search, setSearch] = useState('');

    const rows = useMemo(() => {
        const term = search.trim().toLowerCase();

        return products.filter(
            (p) =>
                term === '' ||
                p.name.toLowerCase().includes(term) ||
                (p.sku ?? '').toLowerCase().includes(term),
        );
    }, [products, search]);

    const counted = Object.entries(form.data.counts).filter(
        ([, v]) => v !== null,
    );
    const off = counted.filter(
        ([id, v]) => v !== products.find((p) => p.id === Number(id))?.on_hand,
    ).length;

    const setCount = (id: number, value: string) =>
        form.setData('counts', {
            ...form.data.counts,
            [id]: value === '' ? null : Math.max(0, Number(value)),
        });

    return (
        <>
            <Head title="Count stock" />
            <div className="flex flex-1 flex-col gap-6 px-4 py-6 md:px-8 md:py-8">
                <header className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <Link
                            href={
                                inventory.index({
                                    query: { branch: branch.id },
                                }).url
                            }
                            className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Inventory
                        </Link>
                        <h1 className="mt-2 font-display text-3xl md:text-4xl">
                            Count stock at {branch.name}
                        </h1>
                        <p className="mt-1 max-w-2xl text-sm text-muted-foreground">
                            Type what is on the shelf. Rows left blank are not
                            changed. Anything that differs from the records is
                            corrected as one stocktake when you save.
                        </p>
                    </div>
                    {branches.length > 1 && (
                        <div className="flex flex-wrap">
                            {branches.map((b) => (
                                <button
                                    key={b.id}
                                    type="button"
                                    onClick={() =>
                                        router.get(
                                            inventory.count({
                                                query: { branch: b.id },
                                            }).url,
                                        )
                                    }
                                    aria-pressed={b.id === branch.id}
                                    className={cn(
                                        'press -ml-px h-10 border px-3 text-sm first:ml-0',
                                        b.id === branch.id
                                            ? 'relative border-plum bg-lilac/60 text-plum'
                                            : 'border-border bg-background hover:bg-mist dark:hover:bg-white/5',
                                    )}
                                >
                                    {b.name}
                                </button>
                            ))}
                        </div>
                    )}
                </header>

                <div className="relative">
                    <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Find a product"
                        aria-label="Find a product"
                        className="h-10 pl-9"
                    />
                </div>

                {products.length === 0 ? (
                    <p className="border border-border px-4 py-10 text-center text-sm text-muted-foreground">
                        No products in the catalogue yet. Add them under
                        Catalogue first.
                    </p>
                ) : (
                    <div className="border border-border">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-border text-left text-muted-foreground">
                                    <th className="px-4 py-3 font-normal">
                                        Product
                                    </th>
                                    <th className="w-24 px-4 py-3 text-right font-normal">
                                        Records
                                    </th>
                                    <th className="w-28 px-4 py-3 text-right font-normal">
                                        Counted
                                    </th>
                                    <th className="hidden w-24 px-4 py-3 text-right font-normal sm:table-cell">
                                        Difference
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.map((p) => {
                                    const value = form.data.counts[p.id];
                                    const diff =
                                        value === undefined || value === null
                                            ? null
                                            : value - p.on_hand;

                                    return (
                                        <tr
                                            key={p.id}
                                            className="border-b border-border last:border-0"
                                        >
                                            <td className="px-4 py-2">
                                                {p.name}
                                                <span className="block text-xs text-muted-foreground">
                                                    {[p.sku, p.category]
                                                        .filter(Boolean)
                                                        .join(', ')}
                                                </span>
                                            </td>
                                            <td className="px-4 py-2 text-right tabular-nums">
                                                {p.on_hand}
                                            </td>
                                            <td className="px-4 py-2">
                                                <Input
                                                    type="number"
                                                    min={0}
                                                    inputMode="numeric"
                                                    aria-label={`Counted ${p.name}`}
                                                    value={value ?? ''}
                                                    onChange={(e) =>
                                                        setCount(
                                                            p.id,
                                                            e.target.value,
                                                        )
                                                    }
                                                    className="h-9 text-right tabular-nums"
                                                />
                                            </td>
                                            <td
                                                className={cn(
                                                    'hidden px-4 py-2 text-right tabular-nums sm:table-cell',
                                                    diff !== null &&
                                                        diff < 0 &&
                                                        'text-destructive',
                                                )}
                                            >
                                                {diff === null
                                                    ? ''
                                                    : diff === 0
                                                      ? 'Matches'
                                                      : `${diff > 0 ? '+' : ''}${diff}`}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}

                <div className="flex flex-wrap items-end gap-3 border-t border-border pt-4">
                    <div className="min-w-60 flex-1">
                        <Input
                            value={form.data.note}
                            onChange={(e) =>
                                form.setData('note', e.target.value)
                            }
                            placeholder="Note for the stocktake (optional)"
                            aria-label="Note"
                            className="h-10"
                        />
                    </div>
                    <p className="text-sm text-muted-foreground">
                        {counted.length} counted, {off} to correct
                    </p>
                    <Button
                        type="button"
                        disabled={form.processing || counted.length === 0}
                        onClick={() => form.post(inventory.count.store().url)}
                        className="bg-plum text-white hover:bg-plum-deep"
                    >
                        {form.processing && <Spinner />}
                        Save count
                    </Button>
                </div>
                {Object.values(form.errors)[0] && (
                    <p className="text-sm text-destructive">
                        {Object.values(form.errors)[0]}
                    </p>
                )}
            </div>
        </>
    );
}
