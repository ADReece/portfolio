<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProdigiProduct;
use App\Prints\CatalogueRefresher;
use App\Prints\PrintPricing;
use App\Service\ProdigiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Admin → Print catalogue: which Prodigi products Portfolio offers, at what
 * default and minimum prices. Products are added by SKU; details and a UK cost
 * quote come from Prodigi (free calls that place no orders).
 */
class AdminPrintCatalogueController extends Controller
{
    public function index(Request $request, PrintPricing $pricing): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $products = ProdigiProduct::query()
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('sku', 'like', '%'.$term.'%')->orWhere('name', 'like', '%'.$term.'%')))
            ->when($filters['category'] ?? null, fn ($q, $c) => $q->where('category', $c))
            ->when(($filters['status'] ?? null) === 'active', fn ($q) => $q->where('is_active', true))
            ->when(($filters['status'] ?? null) === 'inactive', fn ($q) => $q->where('is_active', false))
            ->withCount(['users as enabled_count' => fn ($q) => $q->where('prodigi_product_user.is_enabled', true)])
            ->catalogueOrder()
            ->get();

        // Photographers whose own price no longer covers the cost.
        $belowCost = $products->mapWithKeys(function (ProdigiProduct $p) use ($pricing) {
            $min = $pricing->minimumFor($p);

            return [$p->id => $min === null ? 0 : $p->users()
                ->wherePivot('is_enabled', true)
                ->wherePivotNotNull('retail_price')
                ->wherePivot('retail_price', '<', $min)
                ->count()];
        });

        return view('admin.print-catalogue.index', [
            'products' => $products,
            'belowCost' => $belowCost,
            'pricing' => $pricing,
            'filters' => $filters,
            'categories' => ProdigiProduct::query()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
            'prodigiReady' => ProdigiService::configured(),
            'environment' => config('services.prodigi.environment'),
        ]);
    }

    public function create(): View
    {
        return view('admin.print-catalogue.create', ['lookup' => null, 'prodigiReady' => ProdigiService::configured()]);
    }

    /** Looks a SKU up on Prodigi and shows what it is before saving. */
    public function lookup(Request $request): View|RedirectResponse
    {
        $sku = $this->validatedSku($request);
        $this->requireProdigi();
        if ($existing = ProdigiProduct::where('sku', $sku)->first()) {
            return redirect()->route('admin.print-catalogue.edit', $existing)->with('info', "{$sku} is already in the catalogue.");
        }

        $lookup = $this->fetch($sku);

        return view('admin.print-catalogue.create', ['lookup' => $lookup, 'prodigiReady' => true]);
    }

    public function store(Request $request, CatalogueRefresher $refresher): RedirectResponse
    {
        $sku = $this->validatedSku($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:50'],
        ]);
        $this->requireProdigi();
        if (ProdigiProduct::where('sku', $sku)->exists()) {
            throw ValidationException::withMessages(['sku' => "{$sku} is already in the catalogue."]);
        }

        // Fetched again here, rather than trusting the preview form.
        $lookup = $this->fetch($sku);
        $product = ProdigiProduct::create([
            'sku' => $lookup['sku'],
            'name' => $data['name'],
            'category' => $data['category'],
            'description' => $lookup['description'],
            'size_label' => $lookup['size_label'],
            'raw_payload' => $lookup['raw'],
            'sort_order' => (int) ProdigiProduct::max('sort_order') + 10,
            // New products start hidden so the admin can check price and options first.
            'is_active' => false,
        ]);
        $refresher->refresh($product);

        return redirect()->route('admin.print-catalogue.edit', $product)->with(
            $product->refresh_error ? 'error' : 'success',
            $product->refresh_error
                ? "Added {$product->sku}, but the cost quote failed: {$product->refresh_error}"
                : "Added {$product->sku}. Check the price and options, then make it active."
        );
    }

    /**
     * Adds several SKUs at once (pasted from Prodigi's dashboard or catalogue).
     * Prodigi's API can't list an account's products, so they're added by SKU.
     * Each is looked up, added inactive with Prodigi's description as its name
     * and a guessed category, and its cost quoted. Existing SKUs are skipped.
     */
    public function storeMany(Request $request, CatalogueRefresher $refresher): RedirectResponse
    {
        $request->validate(['skus' => ['required', 'string', 'max:5000']]);
        $this->requireProdigi();

        $skus = collect(preg_split('/[\s,;]+/', strtoupper($request->input('skus'))))
            ->map(fn ($s) => trim($s))
            ->filter(fn ($s) => preg_match('/^[A-Z0-9][A-Z0-9._\-]*$/', $s))
            ->unique()->values();
        if ($skus->isEmpty()) {
            throw ValidationException::withMessages(['skus' => 'Paste one or more Prodigi SKUs, one per line.']);
        }
        if ($skus->count() > 25) {
            throw ValidationException::withMessages(['skus' => 'Add up to 25 SKUs at a time.']);
        }

        $added = $skipped = $notFound = $failed = [];
        $sort = (int) ProdigiProduct::max('sort_order');
        foreach ($skus as $sku) {
            if (ProdigiProduct::where('sku', $sku)->exists()) {
                $skipped[] = $sku;

                continue;
            }
            try {
                $lookup = $refresher->lookup($sku);
            } catch (\Throwable $e) {
                $failed[] = $sku;

                continue;
            }
            if (! $lookup) {
                $notFound[] = $sku;

                continue;
            }
            $product = ProdigiProduct::create([
                'sku' => $lookup['sku'],
                'name' => \Illuminate\Support\Str::limit($lookup['description'] ?: $lookup['sku'], 250, ''),
                'category' => ProdigiProduct::guessCategory($lookup['sku'], $lookup['description']),
                'description' => $lookup['description'],
                'size_label' => $lookup['size_label'],
                'raw_payload' => $lookup['raw'],
                'sort_order' => $sort += 10,
                'is_active' => false,
            ]);
            $refresher->refresh($product);
            $added[] = $sku;
        }

        $parts = array_filter([
            $added ? 'Added '.count($added).' (inactive): '.implode(', ', $added).'.' : null,
            $skipped ? 'Already in the catalogue: '.implode(', ', $skipped).'.' : null,
            $notFound ? 'Not found on Prodigi: '.implode(', ', $notFound).'.' : null,
            $failed ? 'Couldn\'t reach Prodigi for: '.implode(', ', $failed).'. Try again.' : null,
        ]);

        return redirect()->route('admin.print-catalogue.index', ['status' => $added ? 'inactive' : null])
            ->with(($notFound || $failed) ? 'error' : 'success', implode(' ', $parts));
    }

    public function edit(ProdigiProduct $product, PrintPricing $pricing): View
    {
        return view('admin.print-catalogue.edit', [
            'product' => $product,
            'pricing' => $pricing,
            'categories' => collect(ProdigiProduct::CATEGORIES)->merge(ProdigiProduct::whereNotNull('category')->pluck('category'))->unique()->values(),
        ]);
    }

    public function update(Request $request, ProdigiProduct $product, PrintPricing $pricing): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', 'string', 'max:50'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
            'default_retail_price' => ['nullable', 'numeric', 'min:0.50', 'max:9999.99'],
            'is_active' => ['boolean'],
            'allowed_attributes' => ['nullable', 'array'],
            'allowed_attributes.*' => ['array'],
            'allowed_attributes.*.*' => ['string', 'max:100'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_image' => ['boolean'],
        ]);

        $minimum = $pricing->minimumFor($product);
        if (isset($data['default_retail_price']) && $minimum !== null && (float) $data['default_retail_price'] < $minimum) {
            throw ValidationException::withMessages(['default_retail_price' => 'The default price must be at least £'.number_format($minimum, 2).' to cover the print, delivery, card fees and the platform fee.']);
        }

        // Only options Prodigi actually offers; every attribute needs at least one.
        $offered = $product->prodigiAttributes();
        $allowed = [];
        foreach ($offered as $name => $values) {
            $chosen = array_values(array_intersect($values, $data['allowed_attributes'][$name] ?? []));
            if ($chosen === []) {
                throw ValidationException::withMessages(["allowed_attributes.{$name}" => 'Offer at least one '.ProdigiProduct::labelAttribute($name).' option.']);
            }
            $allowed[$name] = $chosen;
        }

        if ($request->hasFile('image')) {
            $old = $product->image_path;
            $product->image_path = $request->file('image')->store('catalogue', 's3');
            if ($old) {
                Storage::disk('s3')->delete($old);
            }
        } elseif ($request->boolean('remove_image') && $product->image_path) {
            Storage::disk('s3')->delete($product->image_path);
            $product->image_path = null;
        }

        $product->fill([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'],
            'sort_order' => $data['sort_order'],
            'default_retail_price' => $data['default_retail_price'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'allowed_attributes' => $allowed ?: null,
        ])->save();

        return redirect()->route('admin.print-catalogue.edit', $product)->with('success', 'Saved.');
    }

    public function refresh(ProdigiProduct $product, CatalogueRefresher $refresher): RedirectResponse
    {
        $this->requireProdigi();
        $refresher->refresh($product);

        return back()->with(
            $product->refresh_error ? 'error' : 'success',
            $product->refresh_error ? "Refresh failed for {$product->sku}: {$product->refresh_error}" : "Refreshed {$product->sku} from Prodigi."
        );
    }

    public function refreshAll(CatalogueRefresher $refresher): RedirectResponse
    {
        $this->requireProdigi();
        $failed = ProdigiProduct::query()->orderBy('id')->get()
            ->map(fn ($p) => $refresher->refresh($p))
            ->filter(fn ($p) => $p->refresh_error);

        return back()->with(
            $failed->isEmpty() ? 'success' : 'error',
            $failed->isEmpty() ? 'Refreshed every product from Prodigi.' : 'Some products failed to refresh: '.$failed->pluck('sku')->implode(', ')
        );
    }

    public function toggle(ProdigiProduct $product): RedirectResponse
    {
        $product->update(['is_active' => ! $product->is_active]);

        return back()->with('success', $product->is_active
            ? "{$product->name} is now offered to photographers."
            : "{$product->name} is hidden from photographers and clients. Past orders are unaffected.");
    }

    private function validatedSku(Request $request): string
    {
        $request->validate(['sku' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9][A-Za-z0-9._\-]*$/']]);

        return strtoupper(trim($request->input('sku')));
    }

    private function requireProdigi(): void
    {
        if (! ProdigiService::configured()) {
            throw ValidationException::withMessages(['sku' => 'Prodigi isn\'t configured: set PRODIGI_API_KEY.']);
        }
    }

    private function fetch(string $sku): array
    {
        try {
            $lookup = app(CatalogueRefresher::class)->lookup($sku);
        } catch (\Throwable $e) {
            throw ValidationException::withMessages(['sku' => 'Couldn\'t reach Prodigi: '.$e->getMessage()]);
        }
        if (! $lookup) {
            throw ValidationException::withMessages(['sku' => "Prodigi doesn't have a product with SKU {$sku}."]);
        }

        return $lookup;
    }
}
