<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeEmail;
use App\Models\Customer;
use App\Services\MagicLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
     * Show register form (now redirects to unified login page).
     */
    public function showRegister()
    {
        return redirect()->route('publicstore.account.login.show');
    }

    /**
     * Register a new customer with email + password.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:customers,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $customer = Customer::withoutGlobalScopes()->create([
            'customer'          => $validated['name'],
            'first_name'        => $validated['name'],
            'email'             => $validated['email'],
            'password'          => Hash::make($validated['password']),
            'email_verified_at' => now(),
            'user_id'           => 1,
        ]);

        Mail::to($customer->email)->send(new WelcomeEmail(
            $customer,
            config('app.url') . '/publicstore/account/dashboard'
        ));

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->route('publicstore.account.dashboard');
    }

    /**
     * Show the unified login page.
     */
    public function showLogin()
    {
        return Inertia::render('PublicStore/Account/Login');
    }

    /**
     * Authenticate with email + password.
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $customer = Customer::withoutGlobalScopes()
            ->where('email', $validated['email'])
            ->first();

        // Customer doesn't exist or has no password set yet (not activated)
        if (! $customer || ! $customer->hasPassword()) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        if (! Hash::check($validated['password'], $customer->password)) {
            throw ValidationException::withMessages([
                'password' => ['Incorrect password.'],
            ]);
        }

        Auth::guard('customer')->login($customer, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('publicstore.account.dashboard'));
    }

    /**
     * Send a one-time activation magic link for customers already in the system
     * but who have not yet set a password.
     */
    public function sendActivationLink(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $customer = Customer::withoutGlobalScopes()
            ->where('email', $validated['email'])
            ->first();

        // Always return the same success message — don't reveal if email exists
        if (! $customer) {
            return back()->with('activation_success', 'If your email is in our system, you will receive an activation link.');
        }

        // Already has a password — guide them to sign in
        if ($customer->hasPassword()) {
            return back()->withErrors([
                'activation_email' => 'This account is already active. Please sign in with your password.',
            ]);
        }

        $token         = $this->magicLinkService->generateToken($customer);
        $magicLinkUrl  = config('app.url') . '/publicstore/account/magic-link/' . $token;

        $this->magicLinkService->sendMagicLink($customer, $magicLinkUrl);

        return back()->with('activation_success', 'Activation link sent! Check your email to set your password.');
    }

    /**
     * Show the set password page (magic link verification).
     */
    public function showSetPassword(string $token)
    {
        $customer = $this->magicLinkService->validateToken($token);

        if (! $customer) {
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
     * Set password from magic link token and log the customer in.
     */
    public function setPassword(Request $request)
    {
        $validated = $request->validate([
            'token'    => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $customer = $this->magicLinkService->validateToken($validated['token']);

        if (! $customer) {
            throw ValidationException::withMessages([
                'token' => ['The link has expired. Request a new one.'],
            ]);
        }

        $customer->password          = Hash::make($validated['password']);
        $customer->email_verified_at = now();
        $customer->save();

        $this->magicLinkService->consumeToken($customer);

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->route('publicstore.account.dashboard');
    }

    /**
     * Logout the customer.
     */
    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('publicstore.account.login.show');
    }
}
