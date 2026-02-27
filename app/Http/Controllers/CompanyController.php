<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage as StorageFacade;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CompanyController extends Controller
{
    /**
     * Show the company settings screen.
     */
    public function show()
    {
        $user = Auth::user();

        // Ensure only OWNER can access
        if ($user->role !== 'OWNER') {
            abort(403, 'Unauthorized action.');
        }

        $company = $user->company;

        if (! $company) {
            abort(404, 'Company not found.');
        }

        return Inertia::render('Company/Show', [
            'company' => $company,
            'members' => $company->users()->get()->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'role' => $member->role,
                    'profile_photo_url' => $member->profile_photo_url,
                    'page_permissions' => $member->page_permissions,
                ];
            }),
        ]);
    }

    /**
     * Update the company's name.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'OWNER') {
            abort(403, 'Unauthorized action.');
        }

        $company = $user->company;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('companies')->ignore($company->id)],
        ]);

        $company->forceFill([
            'name' => $validated['name'],
        ])->save();

        return back(303);
    }

    /**
     * Add a new member to the company.
     */
    public function storeMember(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'OWNER') {
            abort(403, 'Unauthorized action.');
        }

        $company = $user->company;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:ADMIN,USER'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'company_id' => $company->id,
            'store_id' => $user->store_id, // Assign to same store/location by default for now, or make it selectable
        ]);

        return back(303);
    }

    /**
     * Update a member's role.
     */
    public function updateMemberRole(Request $request, $memberId)
    {
        $user = Auth::user();

        if ($user->role !== 'OWNER') {
            abort(403, 'Unauthorized action.');
        }

        $member = User::where('id', $memberId)->where('company_id', $user->company_id)->firstOrFail();

        // Prevent modifying self via this method (although owner shouldn't be in the list as editable member usually)
        if ($member->id === $user->id) {
            abort(403, 'Cannot modify your own role here.');
        }

        $validated = $request->validate([
            'role' => ['required', 'in:ADMIN,USER'],
        ]);

        $member->forceFill([
            'role' => $validated['role'],
        ])->save();

        return back(303);
    }

    /**
     * Update a member's permissions.
     */
    public function updateMemberPermissions(Request $request, $memberId)
    {
        $user = Auth::user();

        if ($user->role !== 'OWNER') {
            abort(403, 'Unauthorized action.');
        }

        $member = User::where('id', $memberId)->where('company_id', $user->company_id)->firstOrFail();

        // Prevent modifying self permissions (Owner should have full access anyway)
        if ($member->id === $user->id) {
            abort(403, 'Cannot modify your own permissions here.');
        }

        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
        ]);

        $member->forceFill([
            'page_permissions' => $validated['permissions'] ?? [],
        ])->save();

        return back(303);
    }

    /**
     * Remove a member from the company.
     */
    public function removeMember($memberId)
    {
        $user = Auth::user();

        if ($user->role !== 'OWNER') {
            abort(403, 'Unauthorized action.');
        }

        $member = User::where('id', $memberId)->where('company_id', $user->company_id)->firstOrFail();

        if ($member->id === $user->id) {
            abort(403, 'Cannot remove yourself.');
        }

        $member->delete();

        return back(303);
    }

    /**
     * Get the authenticated user's company logo.
     */
    public function getLogo()
    {
        $user = Auth::user();
        $company = $user->company;

        if (! $company || ! $company->logo) {
            return response()->json(['url' => null]);
        }

        // Check if file exists on 'local' disk
        if (StorageFacade::disk('local')->exists($company->logo)) {
            return response()->file(StorageFacade::disk('local')->path($company->logo));
        }

        return response()->json(['url' => null], 404);
    }

    /**
     * Update the authenticated user's company logo (Owner only).
     */
    public function updateLogo(Request $request)
    {
        $request->validate([
            'photo' => ['nullable', 'mimes:jpg,jpeg,png,png', 'max:1024'],
        ]);

        $user = Auth::user();

        // Ensure only OWNER can update company logo
        if ($user->role !== 'OWNER') {
            abort(403, 'Unauthorized action.');
        }

        $company = $user->company;

        if (! $company) {
            abort(404, 'Company not found.');
        }

        if ($request->hasFile('photo')) {
            // Delete old logo if exists
            if ($company->logo && StorageFacade::disk('local')->exists($company->logo)) {
                StorageFacade::disk('local')->delete($company->logo);
            }

            // Store new logo in 'company-logos' directory within 'local' disk
            $path = $request->file('photo')->store('company-logos', 'local');

            // Update company record
            $company->logo = $path;
            $company->save();
        }

        return back(303);
    }

    /**
     * Delete the authenticated user's company logo (Owner only).
     */
    public function deleteLogo()
    {
        $user = Auth::user();

        // Ensure only OWNER can delete company logo
        if ($user->role !== 'OWNER') {
            abort(403, 'Unauthorized action.');
        }

        $company = $user->company;

        if (! $company) {
            abort(404, 'Company not found.');
        }

        if ($company->logo && StorageFacade::disk('local')->exists($company->logo)) {
            StorageFacade::disk('local')->delete($company->logo);
        }

        $company->logo = null;
        $company->save();

        return back(303);
    }
}
