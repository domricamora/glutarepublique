<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Media\UploadPhoto;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Promotion;
use App\Models\Treatment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Promotions and packages on the website's Promotions page (5+1 drip
 * packages, seasonal offers). Plain text only, like the other page editors.
 */
class PromotionController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('admin/promotions/index', [
            'promotions' => Promotion::orderBy('sort')->orderBy('id')->get()->map(fn (Promotion $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'summary' => $p->summary,
                'description' => $p->description,
                'details' => $p->details ?? [],
                'badge' => $p->badge,
                'ends_on' => $p->ends_on?->toDateString(),
                'treatment_id' => $p->treatment_id,
                'image' => $p->image,
                'is_active' => $p->is_active,
            ]),
            'treatments' => Treatment::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'selected' => $request->query('promotion', 'first'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $promotion = Promotion::create($data + [
            'slug' => $this->slug($data['title']),
            'sort' => (int) Promotion::max('sort') + 1,
        ]);
        $this->photo($request, $promotion);
        AuditLog::record('settings.promotion.create', "Added the promotion {$promotion->title}", $promotion);

        return to_route('admin.promotions.index', ['promotion' => $promotion->id])->with('success', "{$promotion->title} saved.");
    }

    public function update(Request $request, Promotion $promotion): RedirectResponse
    {
        $promotion->update($this->validated($request));
        $this->photo($request, $promotion);
        AuditLog::record('settings.promotion.update', "Edited the promotion {$promotion->title}", $promotion);

        return back()->with('success', "{$promotion->title} saved.");
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'summary' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'details' => ['nullable', 'string', 'max:5000'],
            'badge' => ['nullable', 'string', 'max:40'],
            'ends_on' => ['nullable', 'date'],
            'treatment_id' => ['nullable', 'integer', Rule::exists('treatments', 'id')->where('organization_id', $request->user()->organization_id)],
            'is_active' => ['required', 'boolean'],
            'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:'.UploadPhoto::uploadLimitKilobytes()],
        ]);

        // One line per detail, the way the form shows them.
        $data['details'] = array_values(array_filter(array_map('trim', explode("\n", (string) ($data['details'] ?? '')))));
        unset($data['photo']);

        return $data;
    }

    private function photo(Request $request, Promotion $promotion): void
    {
        if ($request->hasFile('photo')) {
            $promotion->update(['image' => (new UploadPhoto('promotions'))($request->file('photo'), $promotion->title, $promotion->getRawOriginal('image'))]);
        }
    }

    private function slug(string $title): string
    {
        $base = Str::slug($title) ?: 'promotion';
        $slug = $base;
        for ($i = 2; Promotion::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
