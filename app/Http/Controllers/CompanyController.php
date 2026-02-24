<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage as StorageFacade;

class CompanyController extends Controller
{
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
