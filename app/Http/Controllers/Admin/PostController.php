<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Media\UploadPhoto;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The journal (blog). Written as plain text with a blank line between
 * paragraphs, rendered as paragraphs, never as HTML.
 */
class PostController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('admin/posts/index', [
            'posts' => Post::latest('published_at')->latest('id')->get()->map(fn (Post $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'category' => $p->category,
                'excerpt' => $p->excerpt,
                'body' => $p->body,
                'takeaways' => $p->takeaways ?? [],
                'image' => $p->image,
                'author_name' => $p->author_name,
                'is_published' => $p->is_published,
                'published_on' => $p->published_at?->toDateString(),
            ]),
            'categories' => Post::distinct()->orderBy('category')->pluck('category'),
            'selected' => $request->query('post', 'first'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $post = Post::create($this->validated($request) + ['slug' => $this->slug($request->string('title')->value())]);
        $this->photo($request, $post);
        AuditLog::record('settings.post.create', "Added the journal post {$post->title}", $post);

        return to_route('admin.posts.index', ['post' => $post->id])->with('success', "{$post->title} saved.");
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $post->update($this->validated($request));
        $this->photo($request, $post);
        AuditLog::record('settings.post.update', "Edited the journal post {$post->title}", $post);

        return back()->with('success', "{$post->title} saved.");
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'category' => ['required', 'string', 'max:40'],
            'excerpt' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:30000'],
            'takeaways' => ['nullable', 'string', 'max:3000'],
            'author_name' => ['required', 'string', 'max:120'],
            'is_published' => ['required', 'boolean'],
            'published_on' => ['nullable', 'date'],
            'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:'.UploadPhoto::uploadLimitKilobytes()],
        ]);

        return [
            'title' => $data['title'],
            'category' => trim($data['category']),
            'excerpt' => $data['excerpt'],
            'body' => $data['body'],
            'takeaways' => array_values(array_filter(array_map('trim', explode("\n", (string) ($data['takeaways'] ?? ''))))),
            'author_name' => $data['author_name'],
            'is_published' => $data['is_published'],
            'published_at' => $data['published_on'] ?? now()->toDateString(),
            // About 200 words a minute.
            'read_minutes' => max(1, (int) ceil(str_word_count(strip_tags($data['body'])) / 200)),
        ];
    }

    private function photo(Request $request, Post $post): void
    {
        if ($request->hasFile('photo')) {
            $post->update(['image' => (new UploadPhoto('journal'))($request->file('photo'), $post->title, $post->getRawOriginal('image'))]);
        }
    }

    private function slug(string $title): string
    {
        $base = Str::slug($title) ?: 'post';
        $slug = $base;
        for ($i = 2; Post::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
