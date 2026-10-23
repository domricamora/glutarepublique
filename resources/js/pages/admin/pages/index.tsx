import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { dashboard, legal } from '@/routes';
import pageRoutes from '@/routes/admin/pages';

type Section = { heading: string; paragraphs: string[] };

type Page = {
    id: number;
    title: string;
    slug: string;
    summary: string | null;
    sections: Section[];
    reviewed_on: string | null;
};

type Props = { pages: Page[]; selected: number | null };

export default function PagesIndex({ pages, selected }: Props) {
    const page = pages.find((p) => p.id === selected) ?? pages[0];

    return (
        <>
            <Head title="Website pages" />
            <div className="flex flex-1 flex-col gap-8 px-4 py-6 md:px-8 md:py-8">
                <header>
                    <h1 className="font-display text-3xl md:text-4xl">
                        Website pages
                    </h1>
                    <p className="mt-2 max-w-2xl text-muted-foreground">
                        The privacy policy, terms of use and data privacy
                        notice. Saving publishes straight away and sets the
                        reviewed date. Have your lawyer check the wording.
                    </p>
                </header>

                <nav className="flex flex-wrap gap-2" aria-label="Pages">
                    {pages.map((p) => (
                        <Link
                            key={p.id}
                            href={pageRoutes.index({ query: { page: p.id } })}
                            preserveScroll
                            className={cn(
                                'border px-3 py-1.5 text-sm',
                                p.id === page?.id
                                    ? 'border-foreground'
                                    : 'border-border text-muted-foreground hover:text-foreground',
                            )}
                        >
                            {p.title}
                        </Link>
                    ))}
                </nav>

                {page ? (
                    <Editor key={page.id} page={page} />
                ) : (
                    <p className="text-muted-foreground">No pages yet.</p>
                )}
            </div>
        </>
    );
}

function Editor({ page }: { page: Page }) {
    // Paragraphs are edited as one box per section, split on blank lines.
    const form = useForm({
        title: page.title,
        summary: page.summary ?? '',
        sections: page.sections.map((s) => ({
            heading: s.heading,
            body: s.paragraphs.join('\n\n'),
        })),
    });

    const setSection = (
        i: number,
        patch: Partial<{ heading: string; body: string }>,
    ) =>
        form.setData(
            'sections',
            form.data.sections.map((s, j) =>
                j === i ? { ...s, ...patch } : s,
            ),
        );

    const errors = form.errors as Record<string, string>;
    const url = legal(page.slug).url;

    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                form.transform((data) => ({
                    ...data,
                    sections: data.sections.map((s) => ({
                        heading: s.heading,
                        paragraphs: s.body
                            .split(/\n\s*\n/)
                            .map((p) => p.trim())
                            .filter(Boolean),
                    })),
                }));
                form.patch(pageRoutes.update(page.id).url, {
                    preserveScroll: true,
                });
            }}
            className="max-w-3xl space-y-6"
        >
            <p className="text-sm break-all text-muted-foreground">
                Live at{' '}
                <a
                    href={url}
                    target="_blank"
                    rel="noreferrer"
                    className="underline underline-offset-4"
                >
                    {url}
                </a>
                {page.reviewed_on && ` · last reviewed ${page.reviewed_on}`}
            </p>
            <div className="grid gap-1">
                <Label htmlFor="title">Title</Label>
                <Input
                    id="title"
                    value={form.data.title}
                    onChange={(e) => form.setData('title', e.target.value)}
                    required
                />
            </div>
            <div className="grid gap-1">
                <Label htmlFor="summary">Summary</Label>
                <Input
                    id="summary"
                    value={form.data.summary}
                    onChange={(e) => form.setData('summary', e.target.value)}
                    maxLength={255}
                />
            </div>

            {form.data.sections.map((section, i) => (
                <fieldset
                    key={i}
                    className="space-y-2 border-t border-border pt-5"
                >
                    <div className="flex items-end gap-2">
                        <div className="grid flex-1 gap-1">
                            <Label htmlFor={`heading-${i}`}>
                                Section {i + 1} heading
                            </Label>
                            <Input
                                id={`heading-${i}`}
                                value={section.heading}
                                onChange={(e) =>
                                    setSection(i, { heading: e.target.value })
                                }
                                required
                            />
                        </div>
                        <Button
                            type="button"
                            variant="ghost"
                            disabled={form.data.sections.length === 1}
                            onClick={() =>
                                form.setData(
                                    'sections',
                                    form.data.sections.filter(
                                        (_, j) => j !== i,
                                    ),
                                )
                            }
                        >
                            Remove
                        </Button>
                    </div>
                    <Textarea
                        aria-label={`Section ${i + 1} text`}
                        value={section.body}
                        onChange={(e) =>
                            setSection(i, { body: e.target.value })
                        }
                        rows={Math.min(14, 3 + section.body.split('\n').length)}
                        required
                    />
                    <p className="text-xs text-muted-foreground">
                        Leave a blank line between paragraphs.
                    </p>
                    {(errors[`sections.${i}.heading`] ||
                        errors[`sections.${i}.paragraphs`]) && (
                        <p className="text-sm text-destructive">
                            {errors[`sections.${i}.heading`] ??
                                errors[`sections.${i}.paragraphs`]}
                        </p>
                    )}
                </fieldset>
            ))}

            <div className="flex flex-wrap gap-2">
                <Button
                    type="button"
                    variant="outline"
                    onClick={() =>
                        form.setData('sections', [
                            ...form.data.sections,
                            { heading: '', body: '' },
                        ])
                    }
                >
                    Add a section
                </Button>
                <Button type="submit" disabled={form.processing}>
                    Save and publish
                </Button>
            </div>
        </form>
    );
}

PagesIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Website pages', href: pageRoutes.index() },
    ],
};
