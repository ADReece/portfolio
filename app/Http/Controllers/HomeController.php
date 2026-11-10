<?php

namespace App\Http\Controllers;

use App\Billing\LaunchOffer;
use App\Models\SubscriptionPlan;
use App\Seo\HomePage;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $plans = SubscriptionPlan::where('active', true)
            ->orderBy('sort_order')
            ->get();
        $offer = app(LaunchOffer::class);
        $page = new HomePage($offer, $plans);

        return view('home', [
            'plans' => $plans,
            'offer' => $offer,
            'page' => $page,
            'starterPlan' => $page->cheapestPaidPlan(),
            'showcases' => config('marketing.showcases'),
        ]);
    }

    /** A sample portfolio linked from the homepage showcases. */
    public function example(string $slug)
    {
        $showcases = config('marketing.showcases');

        return view('examples.show', [
            'slug' => $slug,
            'showcase' => $showcases[$slug],
            'others' => collect($showcases)->except($slug),
        ]);
    }

    public function terms()
    {
        return view('legal.terms');
    }

    public function privacy()
    {
        return view('legal.privacy');
    }

    public function sla()
    {
        return view('legal.sla');
    }

    public function about()
    {
        return view('about');
    }
}

