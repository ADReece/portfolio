<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * The photographer's home: what to do next, how things are going, and
     * recent work. Uses local data only (no Stripe API calls), so it's fast.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user()->load('subscriptionPlan');
        $plan = $user->subscriptionPlan;

        $collectionsCount = $user->collections()->count();
        $publishedCount = $user->collections()->where('status', 'Published')->count();
        $photosCount = $user->photos()->count();
        $sellsPrints = $user->hasFeature('selling');
        $hasPrintProducts = $sellsPrints && $user->enabledProdigiProducts()->exists();
        $canCustomize = $user->is_admin || $user->isFeatureOverrideActive() || $user->hasFeature('upload_logo');

        // Getting started: the steps from sign-up to a first shared gallery,
        // then selling. Steps the plan doesn't include are left out.
        $steps = collect([
            [
                'title' => 'Create your first collection',
                'description' => 'A collection holds the photos from one shoot or event.',
                'done' => $collectionsCount > 0,
                'href' => route('collections.create'),
                'action' => 'Create collection',
            ],
            [
                'title' => 'Upload photos',
                'description' => 'Add photos to a collection. You can organise them into sets.',
                'done' => $photosCount > 0,
                'href' => route('collections.index'),
                'action' => 'Go to collections',
            ],
            [
                'title' => 'Publish and share a collection',
                'description' => 'Publishing gives you a link to send to your client.',
                'done' => $publishedCount > 0,
                'href' => route('collections.index'),
                'action' => 'Go to collections',
            ],
            $canCustomize ? [
                'title' => 'Make your portfolio your own',
                'description' => 'Add your logo and choose colours and fonts.',
                'done' => (bool) ($user->logo_path || $user->portfolio_accent_color),
                'href' => route('profile.customize'),
                'action' => 'Customize portfolio',
            ] : null,
            $sellsPrints ? [
                'title' => 'Start selling prints',
                'description' => 'Choose print products and connect Stripe to get paid.',
                'done' => $hasPrintProducts && $user->stripe_connect_enabled,
                'href' => $hasPrintProducts ? route('connect.stripe') : route('profile.prodigi-products.edit'),
                'action' => $hasPrintProducts ? 'Connect Stripe' : 'Choose print products',
            ] : null,
        ])->filter()->values();

        $orders = $user->orders()->where('status', 'completed')->where('created_at', '>=', now()->subDays(30));

        $subscription = $user->subscription('default');

        return view('dashboard', [
            'user' => $user,
            'plan' => $plan,
            'steps' => $steps,
            'stats' => [
                'collections' => $collectionsCount,
                'published' => $publishedCount,
                'photos' => $photosCount,
                'photoLimit' => ($user->isFeatureOverrideActive() || $user->is_admin) ? null : $plan?->photo_limit,
                'orders30' => $sellsPrints ? (clone $orders)->count() : null,
                'earnings30' => $sellsPrints ? (float) (clone $orders)->sum('photographer_amount') : null,
            ],
            'recentCollections' => $user->collections()
                ->withCount(['sets', 'photos'])
                ->with('coverPhoto')
                ->latest('updated_at')
                ->take(4)
                ->get(),
            'subscription' => $subscription,
            'isNewUser' => $user->created_at?->gt(now()->subDay()) ?? false,
        ]);
    }
}
