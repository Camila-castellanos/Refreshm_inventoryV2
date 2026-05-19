<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeEmail;
use App\Models\Customer;
use App\Services\MagicLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CustomerAuthController extends Controller
{
    public function __construct(
        private MagicLinkService $magicLinkService
    ) {}

    /**
     * Show register form.
     */
    public function showRegister()
    {
        return Inertia::render('PublicStore/Account/Register');
    }

    /**
     * Register a new customer.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:customers,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $customer = Customer::withoutGlobalScopes()->create([
            'customer' => $validated['name'],
            'first_name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
            'user_id' => 1, // System user for portal customers
        ]);

        Mail::to($customer->email)->send(new WelcomeEmail(
            $customer,
            config('app.url').'/publicstore/account/dashboard'
        ));

        \Illuminate\Support\Facades\Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->route('publicstore.account.dashboard');
    }

    /**
     * Show login form.
     */
    public function showLogin()
    {
        return Inertia::render('PublicStore/Account/Login');
    }

    /**
     * Handle login request - sends magic link if no password, error if has password.
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $customer = Customer::withoutGlobalScopes()
            ->where('email', $validated['email'])
            ->first();

        // Security: don't reveal if email exists
        if (! $customer) {
            return back()->with('success', 'If your email is registered, you will receive a link.');
        }

        // Customer already has password - reject magic link flow
        if ($customer->hasPassword()) {
            return back()->withErrors([
                'email' => 'This account already has a password. Use the login form.',
            ]);
        }

        // Generate and send magic link
        $token = $this->magicLinkService->generateToken($customer);
        $magicLinkUrl = config('app.url').'/publicstore/account/magic-link/'.$token;

        $this->magicLinkService->sendMagicLink($customer, $magicLinkUrl);

        return back()->with('success', 'Check your email for the link.');
    }

    /**
     * Show the set password page (for magic link verification).
     */
    public function showSetPassword(string $token)
    {
        $customer = $this->magicLinkService->validateToken($token);

        if (! $customer) {
            // Can't return JSON if it's a page load, better to return Inertia with error prop
            return Inertia::render('PublicStore/Account/SetPassword', [
                'token' => $token,
                'email' => null,
                'error' => 'The link has expired or is invalid. Request a new one.',
            ]);
        }

        return Inertia::render('PublicStore/Account/SetPassword', [
            'token' => $token,
            'email' => $customer->email,
        ]);
    }

    /**
     * Set password from magic link token.
     */
    public function setPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $customer = $this->magicLinkService->validateToken($validated['token']);

        if (! $customer) {
            throw ValidationException::withMessages([
                'token' => ['The link has expired. Request a new one.'],
            ]);
        }

        $customer->password = Hash::make($validated['password']);
        $customer->email_verified_at = now();
        $customer->save();

        // Consume the token
        $this->magicLinkService->consumeToken($customer);

        \Illuminate\Support\Facades\Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->route('publicstore.account.dashboard');
    }

    /**
     * Logout the customer.
     */
    public function logout(Request $request)
    {
        \Illuminate\Support\Facades\Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('publicstore.account.login.show');
    }
}

