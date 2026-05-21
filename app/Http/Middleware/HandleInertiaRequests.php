<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection; // Import the User model
use Inertia\Middleware; // Import Collection for type hinting

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $parentShare = parent::share($request);

        $authData = fn () => [
            'user' => $request->user() ? $this->getUserAuthData($request->user()) : null,
        ];

        $customerAuthData = fn () => [
            'user' => auth('customer')->check()
                ? $this->getUserAuthData(auth('customer')->user())
                : null,
        ];

        $flashData = function () use ($request) {
            return [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'activation_success' => $request->session()->get('activation_success'),
            ];
        };

        return array_merge($parentShare, [
            'layout' => fn () => $request->is('inventory*') ? 'InventoryLayout' : null,
            'auth' => $authData,
            'customer_auth' => $customerAuthData,
            'permissions_defaults' => function () use ($request) {
                $user = $request->user();
                if (! $user) {
                    return config('permissions.user_defaults');
                }
                $configKey = ($user->role === 'ADMIN' || $user->role === 'OWNER') ? 'permissions.defaults' : 'permissions.user_defaults';

                return config($configKey);
            },
            'permissions_structure' => config('permissions.structure'),
            'flash' => $flashData,
        ]);
    }

    /**
     * Helper function to format user data for sharing, including a list of shops.
     *
     * @param  \App\Models\User|\App\Models\Customer  $user  The authenticated user instance.
     * @return array<string, mixed>
     */
    protected function getUserAuthData($user): array
    {
        // If user is actually a Customer (from portal), return minimal data plus preferences
        if ($user instanceof \App\Models\Customer) {
            return [
                'id' => $user->id,
                'email' => $user->email,
                'type' => 'customer',
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'default_store' => $user->default_store,
                'default_shipping' => $user->default_shipping,
                'notes' => $user->notes,
            ];
        }

        // Eager load company and its shops efficiently
        $user->loadMissing(['company.shops']);

        $company = $user->company;

        // Get the collection of shops, defaulting to null if no company
        $shopsCollection = $company?->shops;

        // Map the shops collection to an array of simple objects (id, name)
        // If $shopsCollection is null or empty, default to an empty array using '?? []'
        $shopsArray = $shopsCollection?->map(function ($shop) {
            // Return only the essential data needed globally
            return [
                'id' => $shop->id,
                'name' => $shop->name,
                'slug' => $shop->slug,
            ];
        })->all() ?? []; // ->all() converts collection to array, ?? [] handles null collection

        // Return the standard user data PLUS the company name and the array of shops
        return [
            // Standard user attributes
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            // Add any other user fields you need globally

            // --- UPDATED ---
            'companyName' => $company?->name, // Company name remains
            'role' => $user->role, // User role
            'page_permissions' => $user->page_permissions,
            'shops' => $shopsArray, // Now an array of shop objects [{id: 1, name: 'Shop A'}, ...]
            // 'company_id' => $company?->id, // Optional: Include company ID if needed
        ];
    }
}
