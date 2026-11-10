<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RefreshProdigiProduct;
use App\Models\ProdigiImport;
use App\Models\ProdigiProduct;
use App\Prints\CatalogueImporter;
use App\Prints\CatalogueRefresher;
use App\Prints\PrintPricing;
use App\Service\ProdigiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
            // Bulk adds still running, or finished in the last day.
            'imports' => ProdigiImport::query()
                ->where(fn ($q) => $q->whereNull('finished_at')->orWhere('finished_at', '>', now()->subDay()))
                ->latest('id')->limit(5)->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.print-catalogue.create', $this->createData(null, ProdigiService::configured()));
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

        return view('admin.print-catalogue.create', $this->createData($lookup, true));
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
     * Adds several SKUs at once, pasted from Prodigi's dashboard or catalogue.
     * Each is looked up in the queue and added inactive with Prodigi's
     * description as its name and a guessed category. Existing SKUs are skipped.
     */
    public function storeMany(Request $request, CatalogueImporter $importer): RedirectResponse
    {
        $request->validate(['skus' => ['required', 'string', 'max:100000']]);
        $this->requireProdigi();

        $entries = CatalogueImporter::skusFromText($request->input('skus'));
        if (! $entries) {
            throw ValidationException::withMessages(['skus' => 'Paste one or more Prodigi SKUs, one per line.']);
        }

        return $this->queued($importer->queue($entries, 'paste', 'Pasted SKUs'));
    }

    /** Imports SKUs from a CSV, e.g. a price sheet downloaded from Prodigi's dashboard. */
    public function import(Request $request, CatalogueImporter $importer): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:10240']]);
        $this->requireProdigi('file');

        $entries = CatalogueImporter::skusFromCsv($request->file('file')->getRealPath());
        if (! $entries) {
            throw ValidationException::withMessages(['file' => 'No SKUs found. The sheet needs a column headed "SKU", or cells like GLOBAL-FAP-16X24.']);
        }

        return $this->queued($importer->queue($entries, 'csv', Str::limit($request->file('file')->getClientOriginalName(), 200)));
    }

    /** Tries known Prodigi SKU patterns and adds the ones that exist. */
    public function discover(Request $request, CatalogueImporter $importer): RedirectResponse
    {
        $families = array_keys(config('prints.discovery.families'));
        $data = $request->validate([
            'families' => ['required', 'array', 'min:1'],
            'families.*' => ['string', Rule::in($families)],
        ], ['families.required' => 'Choose at least one product family.']);
        $this->requireProdigi('families');

        $entries = CatalogueImporter::discoveryCandidates($data['families']);

        return $this->queued($importer->queue($entries, 'discover', 'Discover: '.implode(', ', $data['families'])));
    }

    private function queued(ProdigiImport $import): RedirectResponse
    {
        $new = $import->total - $import->skipped;
        $minutes = max(1, (int) ceil($new / max(1, (int) config('prints.import_per_minute'))));

        return redirect()->route('admin.print-catalogue.index', ['status' => 'inactive'])->with('success', $new
            ? "Checking {$new} SKU".($new === 1 ? '' : 's')." with Prodigi (about {$minutes} min). New products are added as inactive; progress is shown below."
                .($import->skipped ? " {$import->skipped} already in the catalogue." : '')
            : 'Every one of those SKUs is already in the catalogue.');
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

    /** Queues a refresh of every product (too many to do within one request). */
    public function refreshAll(): RedirectResponse
    {
        $this->requireProdigi();
        $gap = 60 / max(1, (int) config('prints.import_per_minute'));
        $ids = ProdigiProduct::query()->orderBy('id')->pluck('id');
        foreach ($ids->values() as $i => $id) {
            RefreshProdigiProduct::dispatch((int) $id)->delay(now()->addMilliseconds((int) round($i * $gap * 1000)));
        }

        return back()->with('success', 'Refreshing '.$ids->count().' products from Prodigi in the background. Products whose refresh fails show the reason.');
    }

    public function toggle(ProdigiProduct $product): RedirectResponse
    {
        $product->update(['is_active' => ! $product->is_active]);

        return back()->with('success', $product->is_active
            ? "{$product->name} is now offered to photographers."
            : "{$product->name} is hidden from photographers and clients. Past orders are unaffected.");
    }

    private function createData(?array $lookup, bool $prodigiReady): array
    {
        return [
            'lookup' => $lookup,
            'prodigiReady' => $prodigiReady,
            'families' => config('prints.discovery.families'),
            'candidatesPerFamily' => count(CatalogueImporter::discoveryCandidates([array_key_first(config('prints.discovery.families'))])),
        ];
    }

    private function validatedSku(Request $request): string
    {
        $request->validate(['sku' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9][A-Za-z0-9._\-]*$/']]);

        return strtoupper(trim($request->input('sku')));
    }

    private function requireProdigi(string $field = 'sku'): void
    {
        if (! ProdigiService::configured()) {
            throw ValidationException::withMessages([$field => 'Prodigi isn\'t configured: set PRODIGI_API_KEY.']);
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
