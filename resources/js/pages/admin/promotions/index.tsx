import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { dashboard, promotions as promotionsPage } from '@/routes';
import promotionRoutes from '@/routes/admin/promotions';

type Promotion = {
    id: number;
    title: string;
    slug: string;
    summary: string;
    description: string;
    details: string[];
    badge: string | null;
    ends_on: string | null;
    treatment_id: number | null;
    image: string | null;
    is_active: boolean;
};

type Props = {
    promotions: Promotion[];
    treatments: { id: number; name: string }[];
    selected: string;
};

export default function PromotionsIndex({
    promotions,
    treatments,
    selected,
}: Props) {
    const current =
        selected === 'new'
            ? null
            : (promotions.find((p) => String(p.id) === selected) ??
              promotions.find((p) => p.is_active) ??
              promotions[0] ??
              null);

    return (
        <>
            <Head title="Promotions & packages" />
            <div className="flex flex-1 flex-col gap-8 px-4 py-6 md:px-8 md:py-8">
                <header>
                    <h1 className="font-display text-3xl md:text-4xl">
                        Promotions & packages
                    </h1>
                    <p className="mt-2 max-w-2xl text-muted-foreground">
                        Offers and session packages on the website&apos;s
                        Promotions page. Saving publishes straight away. Keep
                        the wording modest: no promised results.
                    </p>
                </header>

                <nav className="flex flex-wrap gap-2" aria-label="Promotions">
                    {promotions.map((p) => (
                        <Link
                            key={p.id}
                            href={promotionRoutes.index({
                                query: { promotion: p.id },
                            })}
                            preserveScroll
                            className={cn(
                                'border px-3 py-1.5 text-sm',
                                p.id === current?.id
                                    ? 'border-foreground'
                                    : 'border-border text-muted-foreground hover:text-foreground',
                                !p.is_active && 'line-through',
                            )}
                        >
                            {p.title}
                        </Link>
                    ))}
                    <Link
                        href={promotionRoutes.index({
                            query: { promotion: 'new' },
                        })}
                        preserveScroll
                        className={cn(
                            'border border-dashed px-3 py-1.5 text-sm',
                            current === null
                                ? 'border-foreground'
                                : 'border-border text-muted-foreground hover:text-foreground',
                        )}
                    >
                        + New promotion or package
                    </Link>
                </nav>

                <Editor
                    key={current?.id ?? 'new'}
                    promotion={current}
                    treatments={treatments}
                />
            </div>
        </>
    );
}

function Editor({
    promotion,
    treatments,
}: {
    promotion: Promotion | null;
    treatments: Props['treatments'];
}) {
    const form = useForm<{
        title: string;
        summary: string;
        description: string;
        details: string;
        badge: string;
        ends_on: string;
        treatment_id: string;
        is_active: boolean;
        photo: File | null;
    }>({
        title: promotion?.title ?? '',
        summary: promotion?.summary ?? '',
        description: promotion?.description ?? '',
        details: (promotion?.details ?? []).join('\n'),
        badge: promotion?.badge ?? '',
        ends_on: promotion?.ends_on ?? '',
        treatment_id: promotion?.treatment_id
            ? String(promotion.treatment_id)
            : '',
        is_active: promotion?.is_active ?? true,
        photo: null,
    });
    const errors = form.errors as Record<string, string>;

    const field = (
        key: 'title' | 'summary' | 'badge' | 'ends_on',
        label: string,
        type = 'text',
    ) => (
        <div className="grid gap-1">
            <Label htmlFor={key}>{label}</Label>
            <Input
                id={key}
                type={type}
                value={form.data[key]}
                onChange={(e) => form.setData(key, e.target.value)}
                required={key === 'title' || key === 'summary'}
            />
            {errors[key] && (
                <p className="text-sm text-destructive">{errors[key]}</p>
            )}
        </div>
    );

    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                form.post(
                    promotion
                        ? promotionRoutes.update(promotion.id).url
                        : promotionRoutes.store().url,
                    { preserveScroll: true, forceFormData: true },
                );
            }}
            className="grid max-w-5xl gap-8 lg:grid-cols-[1fr_18rem]"
        >
            <div className="space-y-5">
                {promotion && (
                    <p className="text-sm text-muted-foreground">
                        Shown on{' '}
                        <a
                            href={promotionsPage().url}
                            target="_blank"
                            rel="noreferrer"
                            className="underline underline-offset-4"
                        >
                            the Promotions page
                        </a>
                        {!promotion.is_active && ' (hidden right now)'}
                    </p>
                )}
                {field('title', 'Title')}
                {field('summary', 'One-line summary')}
                <div className="grid gap-1">
                    <Label htmlFor="description">Description</Label>
                    <Textarea
                        id="description"
                        rows={5}
                        value={form.data.description}
                        onChange={(e) =>
                            form.setData('description', e.target.value)
                        }
                        required
                    />
                    {errors.description && (
                        <p className="text-sm text-destructive">
                            {errors.description}
                        </p>
                    )}
                </div>
                <div className="grid gap-1">
                    <Label htmlFor="details">Package lines and prices</Label>
                    <Textarea
                        id="details"
                        rows={6}
                        value={form.data.details}
                        onChange={(e) =>
                            form.setData('details', e.target.value)
                        }
                        placeholder={
                            'Bronze: 3,699 pesos for 5+1\nGold: 7,299 pesos for 5+1'
                        }
                    />
                    <p className="text-xs text-muted-foreground">
                        One line each, shown as a list.
                    </p>
                </div>
                <div className="grid gap-5 sm:grid-cols-2">
                    {field('badge', 'Badge (optional, e.g. Best value)')}
                    {field('ends_on', 'Ends on (optional)', 'date')}
                </div>
                <div className="grid gap-1">
                    <Label htmlFor="treatment">Linked treatment</Label>
                    <select
                        id="treatment"
                        value={form.data.treatment_id}
                        onChange={(e) =>
                            form.setData('treatment_id', e.target.value)
                        }
                        className="h-9 border border-input bg-transparent px-3 text-sm"
                    >
                        <option value="">None</option>
                        {treatments.map((t) => (
                            <option key={t.id} value={t.id}>
                                {t.name}
                            </option>
                        ))}
                    </select>
                    <p className="text-xs text-muted-foreground">
                        Adds a "Book" link to that treatment.
                    </p>
                </div>
                <label className="flex items-center gap-2 text-sm">
                    <input
                        type="checkbox"
                        checked={form.data.is_active}
                        onChange={(e) =>
                            form.setData('is_active', e.target.checked)
                        }
                    />
                    Show on the website
                </label>
                <Button type="submit" disabled={form.processing}>
                    {promotion ? 'Save and publish' : 'Add promotion'}
                </Button>
            </div>

            <Photo
                current={promotion?.image ?? null}
                file={form.data.photo}
                onPick={(f) => form.setData('photo', f)}
                error={errors.photo}
            />
        </form>
    );
}

export function Photo({
    current,
    file,
    onPick,
    error,
}: {
    current: string | null;
    file: File | null;
    onPick: (file: File | null) => void;
    error?: string;
}) {
    const preview = file ? URL.createObjectURL(file) : current;

    return (
        <div className="space-y-2">
            <Label htmlFor="photo">Photo</Label>
            {preview ? (
                <img
                    src={preview}
                    alt=""
                    className="aspect-[4/3] w-full border border-border object-cover"
                />
            ) : (
                <div className="flex aspect-[4/3] w-full items-center justify-center border border-dashed border-border text-sm text-muted-foreground">
                    No photo yet
                </div>
            )}
            <Input
                id="photo"
                type="file"
                accept="image/jpeg,image/png,image/webp"
                onChange={(e) => onPick(e.target.files?.[0] ?? null)}
            />
            <p className="text-xs text-muted-foreground">
                JPG, PNG or WebP. Saved with the rest of the form.
            </p>
            {error && <p className="text-sm text-destructive">{error}</p>}
        </div>
    );
}

PromotionsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Promotions & packages', href: promotionRoutes.index() },
    ],
};
