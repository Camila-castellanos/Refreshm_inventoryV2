<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\User; // Import the User model
use Illuminate\Support\Collection; // Import Collection for type hinting
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
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

        $flashData = function () use ($request) {
            return [
                'success' => $request->session()->get('success'),
                'error'   => $request->session()->get('error'),
            ];
        };

        return array_merge($parentShare, [
            'layout' => fn () => $request->is('inventory*') ? 'InventoryLayout' : null,
            'auth' => $authData,
            'flash' => $flashData,
        ]);
    }

    /**
     * Helper function to format user data for sharing, including a list of shops.
     *
     * @param \App\Models\User $user The authenticated user instance.
     * @return array<string, mixed>
     */
    protected function getUserAuthData(User $user): array
    {
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
            'shops' => $shopsArray, // Now an array of shop objects [{id: 1, name: 'Shop A'}, ...]
            // 'company_id' => $company?->id, // Optional: Include company ID if needed
        ];
    }
}