<?php

namespace App\Http\Controllers;

use App\Models\ProdigiProduct;
use App\Models\User;
use App\Prints\PrintPricing;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ProdigiProductSettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        $this->ensureDefaultPreferences($user);

        $products = ProdigiProduct::query()->active()->catalogueOrder()->get();
        $pricing = app(PrintPricing::class);

        $pivotRows = $user->prodigiProducts()->get()->keyBy('id');
        $enabledProductIds = $pivotRows
            ->filter(fn ($p) => (bool) ($p->pivot->is_enabled ?? false))
            ->keys()
            ->all();

        // The photographer's own price, or empty to use the catalogue default.
        $pricesByProductId = [];
        foreach ($pivotRows as $productId => $product) {
            $pricesByProductId[(int) $productId] = $product->pivot->retail_price !== null ? (string) $product->pivot->retail_price : '';
        }

        // Products whose cost has risen above the photographer's price.
        $belowCost = $products->filter(fn ($p) => ($pricesByProductId[$p->id] ?? '') !== ''
            && ! $pricing->isSellableAt($p, (float) $pricesByProductId[$p->id]))->pluck('id')->all();

        return view('profile.prodigi-products', [
            'products' => $products,
            'enabledProductIds' => $enabledProductIds,
            'pricesByProductId' => $pricesByProductId,
            'pricing' => $pricing,
            'belowCost' => $belowCost,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled_product_ids' => ['nullable', 'array'],
            'enabled_product_ids.*' => ['integer', 'exists:prodigi_products,id'],
            'retail_prices' => ['nullable', 'array'],
            'retail_prices.*' => ['nullable', 'numeric', 'min:0.50', 'max:9999.99'],
        ]);

        $user = $request->user();
        $this->ensureDefaultPreferences($user);

        $products = ProdigiProduct::query()->active()->get()->keyBy('id');
        $enabledProductIds = collect($validated['enabled_product_ids'] ?? [])->map(fn ($id) => (int) $id)->all();
        $pricing = app(PrintPricing::class);

        // Never below the minimum: the cost, card fees and platform fee.
        $errors = [];
        foreach ($products as $productId => $product) {
            $retailPrice = data_get($validated, 'retail_prices.' . $productId);
            if ($retailPrice !== null && ! $pricing->isSellableAt($product, (float) $retailPrice)) {
                $errors['retail_prices.'.$productId] = "{$product->name} needs a price of at least £".number_format($pricing->minimumFor($product), 2).' to cover printing, delivery and fees.';
            }
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        // Only active products are touched; settings for inactive ones are kept.
        $syncPayload = [];
        foreach ($products as $productId => $product) {
            $retailPrice = data_get($validated, 'retail_prices.' . $productId);
            $syncPayload[$productId] = [
                'is_enabled' => in_array((int) $productId, $enabledProductIds, true),
                'retail_price' => $retailPrice !== null ? round((float) $retailPrice, 2) : null,
            ];
        }

        $user->prodigiProducts()->syncWithoutDetaching($syncPayload);

        return back()->with('success', 'Print products saved. Clients will see the changes straight away.');
    }

    public function listForPortfolio(string $username): JsonResponse
    {
        $user = User::query()->where('username', $username)->firstOrFail();

        $pricing = app(PrintPricing::class);
        $products = $user->enabledProdigiProducts()
            ->select('prodigi_products.*')
            ->catalogueOrder()
            ->get()
            ->map(fn (ProdigiProduct $product) => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'category' => $product->category,
                'description' => $product->description,
                'retail_price' => $pricing->priceFor($product, $product->pivot->retail_price),
                'sellable' => $pricing->isSellableAt($product, $pricing->priceFor($product, $product->pivot->retail_price)),
            ])
            // Never offer a print whose price no longer covers its cost.
            ->filter(fn ($p) => $p['sellable'])
            ->map(fn ($p) => \Illuminate\Support\Arr::except($p, 'sellable'))
            ->values();

        return response()->json([
            'products' => $products,
        ]);
    }

    private function ensureDefaultPreferences(User $user): void
    {
        $catalogIds = ProdigiProduct::query()->active()->pluck('id')->all();
        if (empty($catalogIds)) {
            return;
        }

        // Only add products the user doesn't have a preference for yet.
        // (syncWithoutDetaching would also overwrite existing rows, which
        // reset every price and re-enabled every product each
        // time the page was opened.)
        $existing = $user->prodigiProducts()->pluck('prodigi_products.id')->map(fn ($id) => (int) $id)->all();
        $missing = array_diff(array_map('intval', $catalogIds), $existing);

        if ($missing) {
            $user->prodigiProducts()->attach(
                collect($missing)->mapWithKeys(fn ($id) => [$id => ['is_enabled' => true, 'retail_price' => null]])->all()
            );
        }
    }
}
