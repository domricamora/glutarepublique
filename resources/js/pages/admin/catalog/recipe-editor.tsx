import { useForm } from '@inertiajs/react';
import { Check, Plus, Trash2, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { money } from '@/lib/pos';
import catalog from '@/routes/admin/catalog';

export type RecipeProduct = {
    id: number;
    name: string;
    sku: string | null;
    cost: number;
};

export type StaffOption = {
    id: number;
    name: string;
    title: string;
    branches: string;
};

type Line = { product_id: number | ''; quantity: number };

/**
 * One service's recipe and staff. The recipe is what a single session takes
 * off the branch's shelf when the service is rung up; the staff are who may
 * perform it, and none ticked means anyone at the branch.
 */
export function RecipeEditor({
    service,
    products,
    staff,
    onClose,
}: {
    service: {
        id: number;
        name: string;
        price: number;
        promo_price: number | null;
        recipe: { product_id: number; quantity: number }[];
        staff_ids: number[];
    };
    products: RecipeProduct[];
    staff: StaffOption[];
    onClose: () => void;
}) {
    const form = useForm<{ products: Line[]; staff: number[] }>({
        products: service.recipe.map((line) => ({ ...line })),
        staff: [...service.staff_ids],
    });

    const lines = form.data.products;
    const setLine = (i: number, line: Partial<Line>) =>
        form.setData(
            'products',
            lines.map((l, j) => (j === i ? { ...l, ...line } : l)),
        );

    const cost = lines.reduce((sum, l) => {
        const product = products.find((p) => p.id === l.product_id);

        return sum + (product ? product.cost * (Number(l.quantity) || 0) : 0);
    }, 0);
    const price = service.promo_price || service.price;
    const margin = price > 0 ? ((price - cost) / price) * 100 : null;

    const toggleStaff = (id: number) =>
        form.setData(
            'staff',
            form.data.staff.includes(id)
                ? form.data.staff.filter((s) => s !== id)
                : [...form.data.staff, id],
        );

    const save = () => {
        // A half-filled line (no product chosen) is dropped rather than refused.
        form.transform((data) => ({
            ...data,
            products: data.products.filter((l) => l.product_id !== ''),
        }));
        form.put(catalog.services.recipe(service.id).url, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    const errors = Object.values(form.errors);

    return (
        <div className="grid gap-8 bg-muted/40 p-5 lg:grid-cols-2">
            <section>
                <h3 className="text-sm font-semibold">
                    Products used per session
                </h3>
                <p className="mt-1 text-xs text-muted-foreground">
                    Taken off the branch's shelf when {service.name} is rung up.
                </p>

                {products.length === 0 ? (
                    <p className="mt-4 text-sm text-muted-foreground">
                        No products in the catalogue yet. Add them under the
                        Products tab first.
                    </p>
                ) : (
                    <ul className="mt-4 grid gap-2">
                        {lines.map((line, i) => (
                            <li
                                key={i}
                                className="grid grid-cols-[1fr_5.5rem_auto] items-center gap-2"
                            >
                                <select
                                    aria-label="Product"
                                    value={line.product_id}
                                    onChange={(e) =>
                                        setLine(i, {
                                            product_id: e.target.value
                                                ? Number(e.target.value)
                                                : '',
                                        })
                                    }
                                    className="h-9 border border-border bg-background px-2 text-sm"
                                >
                                    <option value="">Choose a product</option>
                                    {products.map((p) => (
                                        <option
                                            key={p.id}
                                            value={p.id}
                                            // Once per recipe: set the units instead of adding it twice.
                                            disabled={lines.some(
                                                (l, j) =>
                                                    j !== i &&
                                                    l.product_id === p.id,
                                            )}
                                        >
                                            {p.name}
                                            {p.sku ? ` (${p.sku})` : ''}
                                        </option>
                                    ))}
                                </select>
                                <Input
                                    aria-label="Units per session"
                                    type="number"
                                    min={1}
                                    value={line.quantity}
                                    onChange={(e) =>
                                        setLine(i, {
                                            quantity: Number(e.target.value),
                                        })
                                    }
                                    className="h-9 text-right tabular-nums"
                                />
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    aria-label="Remove product"
                                    onClick={() =>
                                        form.setData(
                                            'products',
                                            lines.filter((_, j) => j !== i),
                                        )
                                    }
                                >
                                    <Trash2 className="h-4 w-4 text-destructive" />
                                </Button>
                            </li>
                        ))}
                        <li>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                onClick={() =>
                                    form.setData('products', [
                                        ...lines,
                                        { product_id: '', quantity: 1 },
                                    ])
                                }
                            >
                                <Plus className="h-3 w-3" />
                                Add product
                            </Button>
                        </li>
                    </ul>
                )}

                <dl className="mt-5 grid grid-cols-2 gap-3 border-t border-border pt-4 text-sm tabular-nums">
                    <div>
                        <dt className="text-xs text-muted-foreground">
                            Cost per session
                        </dt>
                        <dd className="mt-1 font-medium">{money(cost)}</dd>
                    </div>
                    <div>
                        <dt className="text-xs text-muted-foreground">
                            Gross margin
                        </dt>
                        <dd className="mt-1 font-medium">
                            {margin === null
                                ? 'Set a price first'
                                : `${margin.toFixed(1)}%`}
                        </dd>
                    </div>
                </dl>
            </section>

            <section>
                <h3 className="text-sm font-semibold">
                    Staff who can perform it
                </h3>
                <p className="mt-1 text-xs text-muted-foreground">
                    Online and desk booking only offer these people. Leave all
                    unticked to let anyone at the branch take it.
                </p>
                <ul className="mt-4 grid gap-1 sm:grid-cols-2">
                    {staff.map((s) => (
                        <li key={s.id}>
                            <label className="flex cursor-pointer items-start gap-2 py-1.5 text-sm">
                                <input
                                    type="checkbox"
                                    checked={form.data.staff.includes(s.id)}
                                    onChange={() => toggleStaff(s.id)}
                                    className="mt-1"
                                />
                                <span>
                                    {s.name}
                                    <span className="block text-xs text-muted-foreground">
                                        {s.title}
                                        {s.branches ? `, ${s.branches}` : ''}
                                    </span>
                                </span>
                            </label>
                        </li>
                    ))}
                </ul>
            </section>

            <div className="flex flex-wrap items-center gap-2 lg:col-span-2">
                <Button
                    type="button"
                    size="sm"
                    disabled={form.processing}
                    onClick={save}
                    className="bg-plum text-white hover:bg-plum-deep"
                >
                    {form.processing ? (
                        <Spinner />
                    ) : (
                        <Check className="h-3 w-3" />
                    )}
                    Save recipe and staff
                </Button>
                <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    onClick={onClose}
                >
                    <X className="h-3 w-3" />
                    Cancel
                </Button>
                {errors.length > 0 && (
                    <p className="text-sm text-destructive">{errors[0]}</p>
                )}
            </div>
        </div>
    );
}
