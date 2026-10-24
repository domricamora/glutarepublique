<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Media\UploadPhoto;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Specialist;
use App\Models\Treatment;
use App\Models\TreatmentCategory;
use App\Support\BranchAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The product catalogue (plan.md §19, §21). This is the same row the counter
 * sells from and the same row the shelves are counted against, so a price or a
 * name changed here is changed everywhere at once: the register picks it up on
 * its next load, and receipts already printed keep the price they were sold at.
 */
class CatalogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:60'],
            'category' => ['nullable', 'string', 'max:60'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'all'])],
            'tab' => ['nullable', Rule::in(['products', 'services'])],
        ]);

        $branch = BranchAccess::single($request, $request->integer('branch') ?: null);

        $products = Product::with('stocks')
            ->when($filters['q'] ?? null, fn (Builder $q, $term) => $q->where(fn (Builder $w) => $w
                ->where('name', 'like', "%{$term}%")
                ->orWhere('sku', 'like', "%{$term}%")))
            ->when($filters['category'] ?? null, fn (Builder $q, $c) => $q->where('category', $c))
            ->when(($filters['status'] ?? 'all') === 'active', fn (Builder $q) => $q->where('is_active', true))
            ->when(($filters['status'] ?? 'all') === 'inactive', fn (Builder $q) => $q->where('is_active', false))
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'category' => $p->category,
                'description' => $p->description,
                'price' => $p->price,
                'cost' => $p->cost,
                'image' => $p->image,
                'is_active' => $p->is_active,
                'on_hand' => $p->onHandAt($branch),
                'margin' => $p->price > 0 ? round(($p->price - $p->cost) / $p->price * 100, 1) : null,
            ]);

        return Inertia::render('admin/catalog/index', [
            'tab' => $filters['tab'] ?? 'products',
            'products' => $products->values(),
            'services' => Treatment::withCount(['saleItems', 'appointments'])->with(['products:id,name,cost', 'specialists:id'])
                ->when($filters['q'] ?? null, fn (Builder $q, $term) => $q->where('name', 'like', "%{$term}%"))
                ->when(($filters['status'] ?? 'all') === 'active', fn (Builder $q) => $q->where('is_active', true))
                ->when(($filters['status'] ?? 'all') === 'inactive', fn (Builder $q) => $q->where('is_active', false))
                ->orderBy('sort')
                ->orderBy('name')
                ->get()
                ->map(fn (Treatment $t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'summary' => $t->summary,
                    'description' => $t->description,
                    'price' => $t->price,
                    'promo_price' => $t->promo_price,
                    'duration_minutes' => (int) $t->duration_minutes,
                    'category_id' => $t->treatment_category_id,
                    'category' => $t->category?->name,
                    'subcategory' => $t->subcategory,
                    'recipe' => $t->products->map(fn (Product $p) => ['product_id' => $p->id, 'quantity' => (int) $p->pivot->quantity])->values(),
                    'recipe_cost' => $t->recipeCost(),
                    'staff_ids' => $t->specialists->pluck('id'),
                    'is_active' => $t->is_active,
                    'sold' => $t->sale_items_count,
                    'booked' => $t->appointments_count,
                    // The cast makes this a full url, which is what the photo
                    // control needs to show what is on the site right now.
                    'image' => $t->image,
                ]),
            'service_categories' => TreatmentCategory::orderBy('name')->get(['id', 'name']),
            // What a service's recipe and staff can be chosen from.
            'recipe_products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'cost']),
            'staff' => Specialist::where('is_active', true)->orderBy('sort')->with('branches:id,name')->get(['id', 'name', 'title'])
                ->map(fn (Specialist $s) => ['id' => $s->id, 'name' => $s->name, 'title' => $s->title, 'branches' => $s->branches->pluck('name')->join(', ')]),
            'categories' => Product::orderBy('category')->distinct()->pluck('category')->filter()->values(),
            'query' => $filters['q'] ?? '',
            'category' => $filters['category'] ?? '',
            'status' => $filters['status'] ?? 'all',
            'branch' => ['id' => $branch->id, 'name' => $branch->name],
            'branches' => Branch::where('is_active', true)->orderBy('id')->get(['id', 'name']),
            'active' => $products->where('is_active', true)->count(),
            'out_of_stock' => $products->where('is_active', true)->where('on_hand', '<=', 0)->count(),
        ]);
    }

    /**
     * Deletes a service. Receipts keep its name, price and cost (they are
     * snapshots), and its bookings keep its name; its recipe and staff list go
     * with it. Retiring (switching it off the menu) is still there for a
     * service that may come back.
     */
    public function destroyService(Request $request, Treatment $treatment): RedirectResponse
    {
        $sold = $treatment->saleItems()->count();
        $booked = $treatment->appointments()->count();

        // Older bookings may predate the saved name.
        $treatment->appointments()->whereNull('service_name')->update(['service_name' => $treatment->name]);
        $treatment->delete();
        AuditLog::record('catalog.service.delete', "Deleted the service {$treatment->name} ({$sold} sold, {$booked} booked)");

        return back()->with('success', $sold + $booked > 0
            ? sprintf('%s deleted. Its %s and %s keep its name.', $treatment->name, $this->plural($sold, 'sale', 'sales'), $this->plural($booked, 'booking', 'bookings'))
            : $treatment->name.' deleted.');
    }

    /**
     * Deletes a product. Receipts keep its name, price and cost. Its stock lots,
     * shelf counts and stock ledger go with it, and it comes off every recipe.
     */
    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $onHand = (int) $product->stocks()->sum('on_hand');
        $recipes = $product->belongsToMany(Treatment::class, 'treatment_products')->count();

        $product->delete();
        AuditLog::record('catalog.product.delete', "Deleted the product {$product->name} ({$onHand} on the shelves, on {$recipes} recipes)");

        return back()->with('success', trim(sprintf(
            '%s deleted.%s%s',
            $product->name,
            $onHand > 0 ? " {$onHand} units on the shelves were removed with it." : '',
            $recipes > 0 ? ' It was taken off '.$this->plural($recipes, 'recipe', 'recipes').'.' : '',
        )));
    }

    /**
     * A service's recipe and staff: the products one session takes off the
     * shelf when it is rung up, and the people who may perform it (nobody
     * ticked means anyone at the branch). Receipts already sold keep their cost.
     */
    public function saveRecipe(Request $request, Treatment $treatment): RedirectResponse
    {
        $organization = $treatment->organization_id;
        $data = $request->validate([
            'products' => ['present', 'array', 'max:30'],
            'products.*.product_id' => ['required', 'integer', 'distinct', Rule::exists('products', 'id')->where('organization_id', $organization)],
            'products.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'staff' => ['present', 'array'],
            'staff.*' => ['integer', 'distinct', Rule::exists('specialists', 'id')->where('organization_id', $organization)],
        ], [
            'products.*.product_id.distinct' => 'Each product goes on the recipe once; set its quantity instead.',
            'products.*.quantity.min' => 'A recipe line needs at least 1 unit.',
        ]);

        $treatment->products()->sync(collect($data['products'])->mapWithKeys(fn (array $line) => [$line['product_id'] => ['quantity' => $line['quantity']]]));
        $treatment->specialists()->sync($data['staff']);

        return back()->with('success', $treatment->name.' recipe and staff saved.');
    }

    /** Adds a service to the menu. */
    public function storeService(Request $request): RedirectResponse
    {
        $data = $this->validateService($request);

        Treatment::create($data + [
            'slug' => $this->uniqueSlug($data['name']),
            'is_active' => true,
        ]);

        return back()->with('success', $data['name'].' added to the menu.');
    }

    /** Edits a service, price and promo included. Receipts keep their own price. */
    public function updateService(Request $request, Treatment $treatment): RedirectResponse
    {
        $data = $this->validateService($request);
        $data['treatment_category_id'] ??= $treatment->treatment_category_id;
        // The edit row has no sessions field, so a blank one keeps the menu's text ("Buy 5, get 1 free").
        $data['recommended_sessions'] ??= $treatment->recommended_sessions;

        $treatment->update($data);

        return back()->with('success', $treatment->name.' updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateService(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'summary' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:5000'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:600'],
            'price' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'promo_price' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            // A service without a category has nowhere to sit on the menu.
            'treatment_category_id' => ['required', 'integer', Rule::exists('treatment_categories', 'id')],
            // The heading a service sits under within its category, e.g. "Botox Procedures".
            'subcategory' => ['nullable', 'string', 'max:80'],
            'recommended_sessions' => ['nullable', 'integer', 'min:1', 'max:60'],
            'preparation' => ['nullable', 'string', 'max:1000'],
            'aftercare' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'price.required' => 'A price is what the counter will charge.',
            'summary.required' => 'The short line that sells it on the website.',
            'description.required' => 'A client reads this before they book.',
        ]);
    }

    private function plural(int $count, string $one, string $many): string
    {
        return $count.' '.($count === 1 ? $one : $many);
    }

    /**
     * A product picture, stored as WebP wherever it came from (plan.md 50).
     */
    public function productPhoto(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:'.UploadPhoto::uploadLimitKilobytes()],
        ], [
            'photo.max' => 'Keep the photo under '.(round(UploadPhoto::uploadLimitKilobytes() / 1024).'MB').'.',
            'photo.required' => 'That photo was too large for this server to receive.',
            'photo.mimes' => 'A photo has to be a JPG, PNG or WebP.',
        ]);

        $path = (new UploadPhoto('products'))($data['photo'], $product->name, $product->imagePath());
        $product->update(['image' => $path]);

        return response()->json(['path' => $path, 'url' => asset(ltrim($path, '/'))]);
    }

    public function removeProductPhoto(Product $product): JsonResponse
    {
        (new UploadPhoto('products'))->discard($product->imagePath());
        $product->update(['image' => null]);

        return response()->json(['path' => null, 'url' => null]);
    }

    /** Adds an item to the catalogue. It holds no stock until it is received. */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateProduct($request);

        $product = Product::create($data + [
            'slug' => $this->uniqueSlug($data['name']),
            'is_active' => true,
        ]);

        return back()->with('success', $product->name.' added. Receive stock before selling it.');
    }

    /** Edits an item, including its price. Receipts already printed keep theirs. */
    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validateProduct($request, $product);

        $product->update($data);

        return back()->with('success', $product->name.' updated.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateProduct(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'sku' => [
                'nullable', 'string', 'max:40',
                Rule::unique('products', 'sku')
                    ->where(fn ($q) => $q->where('organization_id', $product?->organization_id ?? Organization::current()?->id))
                    ->ignore($product?->id),
            ],
            'category' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'cost' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'price.required' => 'A price is what the counter will charge.',
            'cost.required' => 'A cost is what the shelves are valued at.',
        ]);
    }

    /** Names stay unique so links and receipts stay readable. */
    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $suffix = 2;

        while (Product::withoutGlobalScopes()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * A treatment picture, stored as WebP like every other upload.
     *
     * A treatment page with no photograph is the first thing a clinic owner
     * notices, and this was the one image on the site with no way to change it
     * from the office.
     */
    public function servicePhoto(Request $request, Treatment $treatment): JsonResponse
    {
        $data = $request->validate([
            'photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:'.UploadPhoto::uploadLimitKilobytes()],
        ], [
            'photo.max' => 'Keep the photo under '.(round(UploadPhoto::uploadLimitKilobytes() / 1024).'MB').'.',
            'photo.required' => 'That photo was too large for this server to receive.',
            'photo.mimes' => 'A photo has to be a JPG, PNG or WebP.',
        ]);

        $path = (new UploadPhoto('treatments'))($data['photo'], $treatment->name, $treatment->imagePath());
        $treatment->update(['image' => $path]);

        return response()->json(['path' => $path, 'url' => asset(ltrim($path, '/'))]);
    }

    public function removeServicePhoto(Treatment $treatment): JsonResponse
    {
        (new UploadPhoto('treatments'))->discard($treatment->imagePath());
        $treatment->update(['image' => null]);

        return response()->json(['path' => null, 'url' => null]);
    }
}
