<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request): View
    {
        // Coming from "Choose plan" on the pricing page: remember the plan so
        // the new account goes straight to checkout for it after sign-up.
        $selectedPlan = null;
        if ($request->filled('plan')) {
            $selectedPlan = \App\Models\SubscriptionPlan::where('active', true)->find($request->query('plan'));
            if ($selectedPlan) {
                $interval = $request->query('interval') === 'year' ? 'year' : 'month';
                $request->session()->put('url.intended', route('checkout', ['plan' => $selectedPlan->id, 'interval' => $interval]));
            }
        }

        return view('auth.register', ['selectedPlan' => $selectedPlan]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            // Becomes the public /@username URL, so keep it URL-safe.
            'username' => ['required', 'string', 'min:3', 'max:30', 'alpha_dash:ascii', 'unique:'.User::class],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->intended(RouteServiceProvider::HOME);
    }
}
