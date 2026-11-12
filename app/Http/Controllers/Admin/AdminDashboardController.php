<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Carbon;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // The public demo account isn't a customer.
        $totalUsers = User::where('is_demo', false)->count();
        $newUsers30 = User::where('is_demo', false)->where('created_at', '>=', now()->subDays(30))->count();
        // Count only subscriptions that are paid up (active or trialing, including
        // cancelled ones still inside their paid period). Failed payments are
        // shown separately as past due.
        $current = fn ($q) => $q->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()));
        $activeSubscribers = User::whereHas('subscriptions', fn ($q) => $current($q->whereIn('stripe_status', ['active', 'trialing'])))->count();
        $pastDueSubscribers = User::whereHas('subscriptions', fn ($q) => $current($q->where('stripe_status', 'past_due')))
            ->whereDoesntHave('subscriptions', fn ($q) => $current($q->whereIn('stripe_status', ['active', 'trialing'])))
            ->count();
        $realOrders = fn () => Order::whereHas('user', fn ($q) => $q->where('is_demo', false));
        $ordersLast30 = $realOrders()->where('created_at', '>=', now()->subDays(30))->count();
        $gmvLast30 = $realOrders()->where('status','completed')->where('created_at','>=',now()->subDays(30))->sum('total');

        return view('admin.dashboard', compact('totalUsers','newUsers30','activeSubscribers','pastDueSubscribers','ordersLast30','gmvLast30'));
    }
}

