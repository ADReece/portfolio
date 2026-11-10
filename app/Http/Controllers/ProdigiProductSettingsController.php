<?php

namespace App\Http\Controllers;

use App\Models\ProdigiProduct;
use App\Models\User;
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

        $products = ProdigiProduct::query()
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $pivotRows = $user->prodigiProducts()->get()->keyBy('id');
        $enabledProductIds = $pivotRows
            ->filter(fn ($p) => (bool) ($p->pivot->is_enabled ?? false))
            ->keys()
            ->all();

        $pricesByProductId = [];
        foreach ($pivotRows as $productId => $product) {
            $pricesByProductId[(int) $productId] = (string) ($product->pivot->retail_price ?? '29.99');
        }

        return view('profile.prodigi-products', [
            'products' => $products,
            'enabledProductIds' => $enabledProductIds,
            'pricesByProductId' => $pricesByProductId,
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

        $allProductIds = ProdigiProduct::query()->where('is_active', true)->pluck('id')->all();
        $enabledProductIds = collect($validated['enabled_product_ids'] ?? [])->map(fn ($id) => (int) $id)->all();

        $syncPayload = [];
        foreach ($allProductIds as $productId) {
            $retailPrice = data_get($validated, 'retail_prices.' . $productId);
            $syncPayload[$productId] = [
                'is_enabled' => in_array((int) $productId, $enabledProductIds, true),
                'retail_price' => $retailPrice !== null ? (float) $retailPrice : 29.99,
            ];
        }

        $user->prodigiProducts()->syncWithoutDetaching($syncPayload);

        return back()->with('success', 'Print products saved. Clients will see the changes straight away.');
    }

    public function listForPortfolio(string $username): JsonResponse
    {
        $user = User::query()->where('username', $username)->firstOrFail();

        $products = $user->enabledProdigiProducts()
            ->select(['prodigi_products.id', 'prodigi_products.sku', 'prodigi_products.name', 'prodigi_products.category', 'prodigi_products.description'])
            ->orderBy('prodigi_products.category')
            ->orderBy('prodigi_products.name')
            ->get();

        $products = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'category' => $product->category,
                'description' => $product->description,
                'retail_price' => (float) ($product->pivot->retail_price ?? 29.99),
            ];
        });

        return response()->json([
            'products' => $products,
        ]);
    }

    private function ensureDefaultPreferences(User $user): void
    {
        $catalogIds = ProdigiProduct::query()->where('is_active', true)->pluck('id')->all();
        if (empty($catalogIds)) {
            return;
        }

        // Only add products the user doesn't have a preference for yet.
        // (syncWithoutDetaching would also overwrite existing rows, which
        // reset every price to 29.99 and re-enabled every product each
        // time the page was opened.)
        $existing = $user->prodigiProducts()->pluck('prodigi_products.id')->map(fn ($id) => (int) $id)->all();
        $missing = array_diff(array_map('intval', $catalogIds), $existing);

        if ($missing) {
            $user->prodigiProducts()->attach(
                collect($missing)->mapWithKeys(fn ($id) => [$id => ['is_enabled' => true, 'retail_price' => 29.99]])->all()
            );
        }
    }
}
