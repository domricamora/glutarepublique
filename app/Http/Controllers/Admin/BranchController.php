<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\ClinicSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Branches: address, contact and opening hours. The hours saved here are the
 * ones the website shows, the open or closed badge reads and booking offers
 * slots inside, so there is only one schedule to keep right.
 */
class BranchController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/branches/index', [
            'branches' => Branch::orderBy('id')->withCount(['employees as staff_count' => fn ($q) => $q->where('status', '!=', 'resigned')])
                ->get(['id', 'name', 'slug', 'address', 'city', 'phone', 'email', 'hours', 'map_url', 'is_active', 'opens_on',
                    'online_booking', 'receipt_name', 'receipt_tin', 'receipt_address', 'receipt_footer', 'discount_limit', 'low_stock_level']),
            // What an empty branch setting falls back to.
            'defaults' => ['discount_limit' => (float) ClinicSetting::get('approvals.discount_limit', 10)],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $branch = Branch::create($data + ['slug' => $this->slug($data['name'])]);
        AuditLog::record('settings.branch.create', "Added the {$branch->name} branch", $branch);

        return back()->with('success', "{$branch->name} added. Assign its staff in Staff so it can take bookings.");
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $branch->update($this->validated($request));
        AuditLog::record('settings.branch.update', "Updated the {$branch->name} branch", $branch);

        return back()->with('success', "Saved {$branch->name}.");
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:190'],
            'map_url' => ['nullable', 'url:https', 'max:500'],
            'is_active' => ['required', 'boolean'],
            'opens_on' => ['nullable', 'date'],
            'hours' => ['required', 'array', 'size:7'],
            'hours.*' => ['nullable', 'array', 'size:2'],
            'hours.*.*' => ['required', 'date_format:H:i'],
            'online_booking' => ['sometimes', 'boolean'],
            'receipt_name' => ['nullable', 'string', 'max:120'],
            'receipt_tin' => ['nullable', 'string', 'max:40'],
            'receipt_address' => ['nullable', 'string', 'max:255'],
            'receipt_footer' => ['nullable', 'string', 'max:255'],
            'discount_limit' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'low_stock_level' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);

        $hours = [];
        foreach (range(0, 6) as $day) {
            $slot = $data['hours'][$day] ?? null;
            if ($slot && $slot[0] >= $slot[1]) {
                throw ValidationException::withMessages(["hours.$day" => 'Closing time must be after opening time.']);
            }
            $hours[] = $slot ? [$slot[0], $slot[1]] : null;
        }

        return ['hours' => $hours] + $data;
    }

    private function slug(string $name): string
    {
        $base = Str::slug($name) ?: 'branch';
        $slug = $base;
        for ($i = 2; Branch::where('slug', $slug)->exists(); $i++) {
            $slug = "$base-$i";
        }

        return $slug;
    }
}
