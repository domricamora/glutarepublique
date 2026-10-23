import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { Photo } from '@/pages/admin/promotions/index';
import { dashboard } from '@/routes';
import postRoutes from '@/routes/admin/posts';
import blog from '@/routes/blog';

type Post = {
    id: number;
    title: string;
    slug: string;
    category: string;
    excerpt: string;
    body: string;
    takeaways: string[];
    image: string | null;
    author_name: string;
    is_published: boolean;
    published_on: string | null;
};

type Props = { posts: Post[]; categories: string[]; selected: string };

export default function PostsIndex({ posts, categories, selected }: Props) {
    const current =
        selected === 'new'
            ? null
            : (posts.find((p) => String(p.id) === selected) ??
              posts[0] ??
              null);

    return (
        <>
            <Head title="Journal" />
            <div className="flex flex-1 flex-col gap-8 px-4 py-6 md:px-8 md:py-8">
                <header>
                    <h1 className="font-display text-3xl md:text-4xl">
                        Journal
                    </h1>
                    <p className="mt-2 max-w-2xl text-muted-foreground">
                        The articles on the website&apos;s Journal. Plain text:
                        leave a blank line between paragraphs. Keep claims
                        modest and send medical questions to a consultation.
                    </p>
                </header>

                <nav className="flex flex-wrap gap-2" aria-label="Posts">
                    <Link
                        href={postRoutes.index({ query: { post: 'new' } })}
                        preserveScroll
                        className={cn(
                            'border border-dashed px-3 py-1.5 text-sm',
                            current === null
                                ? 'border-foreground'
                                : 'border-border text-muted-foreground hover:text-foreground',
                        )}
                    >
                        + New post
                    </Link>
                    {posts.map((p) => (
                        <Link
                            key={p.id}
                            href={postRoutes.index({ query: { post: p.id } })}
                            preserveScroll
                            className={cn(
                                'border px-3 py-1.5 text-sm',
                                p.id === current?.id
                                    ? 'border-foreground'
                                    : 'border-border text-muted-foreground hover:text-foreground',
                                !p.is_published && 'line-through',
                            )}
                        >
                            {p.title}
                        </Link>
                    ))}
                </nav>

                <Editor
                    key={current?.id ?? 'new'}
                    post={current}
                    categories={categories}
                />
            </div>
        </>
    );
}

function Editor({
    post,
    categories,
}: {
    post: Post | null;
    categories: string[];
}) {
    const form = useForm<{
        title: string;
        category: string;
        excerpt: string;
        body: string;
        takeaways: string;
        author_name: string;
        is_published: boolean;
        published_on: string;
        photo: File | null;
    }>({
        title: post?.title ?? '',
        category: post?.category ?? categories[0] ?? '',
        excerpt: post?.excerpt ?? '',
        body: post?.body ?? '',
        takeaways: (post?.takeaways ?? []).join('\n'),
        author_name: post?.author_name ?? '',
        is_published: post?.is_published ?? true,
        published_on:
            post?.published_on ?? new Date().toISOString().slice(0, 10),
        photo: null,
    });
    const errors = form.errors as Record<string, string>;

    const field = (
        key: 'title' | 'excerpt' | 'author_name' | 'published_on',
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
                required={key !== 'published_on'}
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
                    post
                        ? postRoutes.update(post.id).url
                        : postRoutes.store().url,
                    { preserveScroll: true, forceFormData: true },
                );
            }}
            className="grid max-w-5xl gap-8 lg:grid-cols-[1fr_18rem]"
        >
            <div className="space-y-5">
                {post?.is_published && (
                    <p className="text-sm break-all text-muted-foreground">
                        Live at{' '}
                        <a
                            href={blog.show(post.slug).url}
                            target="_blank"
                            rel="noreferrer"
                            className="underline underline-offset-4"
                        >
                            {blog.show(post.slug).url}
                        </a>
                    </p>
                )}
                {field('title', 'Title')}
                <div className="grid gap-5 sm:grid-cols-2">
                    <div className="grid gap-1">
                        <Label htmlFor="category">Category</Label>
                        <Input
                            id="category"
                            list="categories"
                            value={form.data.category}
                            onChange={(e) =>
                                form.setData('category', e.target.value)
                            }
                            required
                        />
                        <datalist id="categories">
                            {categories.map((c) => (
                                <option key={c} value={c} />
                            ))}
                        </datalist>
                    </div>
                    {field('author_name', 'Author')}
                </div>
                {field('excerpt', 'Excerpt (shown on the Journal list)')}
                <div className="grid gap-1">
                    <Label htmlFor="body">Article</Label>
                    <Textarea
                        id="body"
                        rows={16}
                        value={form.data.body}
                        onChange={(e) => form.setData('body', e.target.value)}
                        required
                    />
                    {errors.body && (
                        <p className="text-sm text-destructive">
                            {errors.body}
                        </p>
                    )}
                </div>
                <div className="grid gap-1">
                    <Label htmlFor="takeaways">Key takeaways</Label>
                    <Textarea
                        id="takeaways"
                        rows={4}
                        value={form.data.takeaways}
                        onChange={(e) =>
                            form.setData('takeaways', e.target.value)
                        }
                    />
                    <p className="text-xs text-muted-foreground">
                        One per line. Optional.
                    </p>
                </div>
                <div className="grid gap-5 sm:grid-cols-2">
                    {field('published_on', 'Publish date', 'date')}
                    <label className="flex items-center gap-2 self-end pb-2 text-sm">
                        <input
                            type="checkbox"
                            checked={form.data.is_published}
                            onChange={(e) =>
                                form.setData('is_published', e.target.checked)
                            }
                        />
                        Published on the website
                    </label>
                </div>
                <Button type="submit" disabled={form.processing}>
                    {post ? 'Save' : 'Add post'}
                </Button>
            </div>

            <Photo
                current={post?.image ?? null}
                file={form.data.photo}
                onPick={(f) => form.setData('photo', f)}
                error={errors.photo}
            />
        </form>
    );
}

PostsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Journal', href: postRoutes.index() },
    ],
};
