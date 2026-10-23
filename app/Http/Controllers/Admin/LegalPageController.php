<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The privacy policy, terms and data privacy notice. Edited as plain text in
 * sections and rendered as paragraphs, never as HTML, so nothing typed here
 * can put markup on the public site.
 */
class LegalPageController extends Controller
{
    public function index(Request $request): Response
    {
        $pages = Page::orderBy('id')->get(['id', 'title', 'slug', 'summary', 'sections', 'reviewed_on']);

        return Inertia::render('admin/pages/index', [
            'pages' => $pages->map(fn (Page $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'summary' => $p->summary,
                'sections' => $p->sections,
                'reviewed_on' => $p->reviewed_on?->toDateString(),
            ]),
            'selected' => $request->integer('page') ?: $pages->first()?->id,
        ]);
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'summary' => ['nullable', 'string', 'max:255'],
            'sections' => ['required', 'array', 'min:1', 'max:40'],
            'sections.*.heading' => ['required', 'string', 'max:160'],
            'sections.*.paragraphs' => ['required', 'array', 'min:1'],
            'sections.*.paragraphs.*' => ['required', 'string', 'max:5000'],
        ]);

        $page->update([
            'title' => $data['title'],
            'summary' => $data['summary'] ?? null,
            'sections' => array_map(fn (array $s) => [
                'heading' => trim($s['heading']),
                'paragraphs' => array_values(array_map('trim', $s['paragraphs'])),
            ], array_values($data['sections'])),
            'reviewed_on' => now()->toDateString(),
        ]);

        AuditLog::record('settings.page.update', "Edited the {$page->title} page", $page);

        return back()->with('success', "{$page->title} saved and published.");
    }
}
