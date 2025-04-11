<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class InvitationController extends Controller
{
     public function index(Request $request) {
        
        $encodedCompanyName = $request->query('company');

        if (!Auth::check()) {
            return redirect()->route('register', ['company' => $encodedCompanyName]);
        }

        return Inertia::render('Invitation/Index');
    }

    public function accept(Request $request) {

        $user = Auth::user();

        $companyName = $request->validate([
            "companyName" => ['required', 'string', 'max:255', 'exists:companies,name'],
        ]);

        $company = Company::where('name', $companyName)->firstOrFail();

        $user->update([
            'company_id' => $company->id
        ]);


        return Redirect::route('profile.show')->with('success', 'Invitation accepted successfully! Welcome aboard.');
    }
}
