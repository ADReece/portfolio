<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;

class AdminSettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->groupBy('group');

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'nullable|string|max:500',
        ]);

        foreach ($validated['settings'] as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        \App\Support\Currency::flush();

        return redirect()->back()->with('success', 'Settings updated successfully!');
    }

    public function plansIndex()
    {
        $plans = SubscriptionPlan::orderBy('sort_order')->get();
        return view('admin.plans.index', compact('plans'));
    }

    public function plansCreate()
    {
        return view('admin.plans.create');
    }

    public function plansStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:subscription_plans,slug',
            'stripe_product_id' => 'nullable|string|max:255',
            'stripe_price_id' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'annual_price' => 'nullable|numeric|min:0',
            'annual_stripe_price_id' => 'nullable|string|max:255',
            'annual_discount_percent' => 'nullable|integer|min:0|max:100',
            'photo_limit' => 'nullable|integer|min:0',
            'collection_limit' => 'nullable|integer|min:0',
            'private_collections' => 'boolean',
            'watermarking' => 'boolean',
            'selling' => 'boolean',
            'video_upload' => 'boolean',
            'custom_templates' => 'boolean',
            'features' => 'nullable|array',
            'features.*' => 'string|max:255',
            'active' => 'boolean',
            'recommended' => 'boolean',
            'sort_order' => 'required|integer|min:0',
        ]);

        $validated['features'] = $validated['features'] ?? [];

        SubscriptionPlan::create($validated);

        return redirect()->route('admin.plans.index')->with('success', 'Subscription plan created successfully!');
    }

    public function plansEdit(SubscriptionPlan $plan)
    {
        return view('admin.plans.edit', compact('plan'));
    }

    public function plansUpdate(Request $request, SubscriptionPlan $plan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:subscription_plans,slug,' . $plan->id,
            'stripe_product_id' => 'nullable|string|max:255',
            'stripe_price_id' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'annual_price' => 'nullable|numeric|min:0',
            'annual_stripe_price_id' => 'nullable|string|max:255',
            'annual_discount_percent' => 'nullable|integer|min:0|max:100',
            'photo_limit' => 'nullable|integer|min:0',
            'collection_limit' => 'nullable|integer|min:0',
            'private_collections' => 'boolean',
            'watermarking' => 'boolean',
            'selling' => 'boolean',
            'video_upload' => 'boolean',
            'custom_templates' => 'boolean',
            'features' => 'nullable|array',
            'features.*' => 'string|max:255',
            'active' => 'boolean',
            'recommended' => 'boolean',
            'sort_order' => 'required|integer|min:0',
        ]);

        // A form with every feature removed sends no features[] at all, so an
        // absent list means "none", not "leave unchanged".
        $validated['features'] = $validated['features'] ?? [];

        $plan->update($validated);

        return redirect()->route('admin.plans.index')->with('success', 'Subscription plan updated successfully!');
    }

    public function plansDestroy(SubscriptionPlan $plan)
    {
        if ($plan->users()->count() > 0) {
            return redirect()->back()->with('error', 'Cannot delete plan with active subscribers!');
        }

        $plan->delete();

        return redirect()->route('admin.plans.index')->with('success', 'Subscription plan deleted successfully!');
    }
}
