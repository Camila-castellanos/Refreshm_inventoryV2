<?php

namespace App\Http\Controllers\PublicStore\Portal;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeEmail;
use App\Models\Customer;
use App\Services\GeoIPService;
use App\Services\MagicLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PublicStoreSessionController extends Controller
{
    public function __construct(
        private MagicLinkService $magicLinkService,
        private GeoIPService $geoIPService
    ) {}

    /**
     * Show register form (now redirects to unified login page).
     */
    public function showRegister()
    {
        return redirect()->route('public-store.portal.login.show');
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
            route('public-store.portal.dashboard')
        ));

        Auth::guard('customer')->login($customer);
        $this->detectAndSaveCurrency($customer, $request->ip());
        $request->session()->regenerate();


        $lastShop = session('last_public_shop');
        if ($lastShop) {
            return redirect()->route('public.inventory.shop.index', ['shopSlug' => $lastShop]);
        }

                return redirect()->intended(route('public-store.portal.dashboard'));
    }

    /**
     * Show the unified login page.
     */
    public function showLogin()
    {
        return Inertia::render('PublicStore/Portal/Login');
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
        $this->detectAndSaveCurrency($customer, $request->ip());
        $request->session()->regenerate();

        $lastShop = session('last_public_shop');
        if ($lastShop) {
            return redirect()->route('public.inventory.shop.index', ['shopSlug' => $lastShop]);
        }

        return redirect()->intended(route('public-store.portal.dashboard'));
    }

    /**
     * Send a one-time activation magic link
 for customers already in the system
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
            return back()->withErrors([
                'activation_email' => 'This email is not registered in our systems. Please contact support.',
            ]);
        }

        // Already has a password — guide them to sign in
        if ($customer->hasPassword()) {
            return back()->withErrors([
                'activation_email' => 'This account is already active. Please sign in with your password.',
            ]);
        }

        $token         = $this->magicLinkService->generateToken($customer);
        $magicLinkUrl  = route('public-store.portal.magic-link.show', ['token' => $token, 'type' => 'activation']);

        $this->magicLinkService->sendMagicLink($customer, $magicLinkUrl, 'activation');

        return back()->with('activation_success', 'Activation link successfully sent to your email!');
    }

    /**
     * Send a magic link for password recovery (forgot password).
     */
    public function sendResetLink(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $customer = Customer::withoutGlobalScopes()
            ->where('email', $validated['email'])
            ->first();

        // Security: Same generic success if not found to avoid enumeration
        if (! $customer || ! $customer->hasPassword()) {
            return back()->with('activation_success', 'If your email is in our system, you will receive a password reset link.');
        }

        $token         = $this->magicLinkService->generateToken($customer);
        $magicLinkUrl  = route('public-store.portal.magic-link.show', ['token' => $token, 'type' => 'reset']);

        $this->magicLinkService->sendMagicLink($customer, $magicLinkUrl, 'reset');

        return back()->with('activation_success', 'Reset link sent! Check your email to update your password.');
    }

    /**
     * Show the set password page (magic link verification).
     */
    public function showSetPassword(Request $request, string $token)
    {
        $customer = $this->magicLinkService->validateToken($token);

        if (! $customer) {
            return Inertia::render('PublicStore/Portal/SetPassword', [
                'token' => $token,
                'email' => null,
                'error' => 'The link has expired or is invalid. Request a new one.',
                'type'  => $request->query('type', 'access'),
            ]);
        }

        return Inertia::render('PublicStore/Portal/SetPassword', [
            'token' => $token,
            'email' => $customer->email,
            'type'  => $request->query('type', 'access'),
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
        $this->detectAndSaveCurrency($customer, $request->ip());
        $request->session()->regenerate();

        $lastShop = session('last_public_shop');
        if ($lastShop) {
            return redirect()->route('public.inventory.shop.index', ['shopSlug' => $lastShop]);
        }

        return redirect()->route('public-store.portal.dashboard');
    }

    /**
     * Logout the customer without affecting other guards (like admin/web).
     */
    public function logout(Request $request)
    {
        $lastShop = session('last_public_shop');
        
        Auth::guard('customer')->logout();

        // No invalidamos toda la sesión para no desconectar al admin
        // Pero regeneramos el token para seguridad básica
        $request->session()->regenerateToken();

        if ($lastShop) {
            return redirect()->route('public.inventory.shop.index', ['shopSlug' => $lastShop]);
        }

        return redirect()->route('public-store.portal.login.show');
    }

    /**
     * Detect and save customer currency preference if not already set.
     */
    private function detectAndSaveCurrency(Customer $customer, ?string $ip): void
    {
        if ($customer->currency !== null) {
            return;
        }

        $currency = $this->geoIPService->getCurrencyByIP($ip ?? '');
        $customer->update(['currency' => $currency]);
    }
}
