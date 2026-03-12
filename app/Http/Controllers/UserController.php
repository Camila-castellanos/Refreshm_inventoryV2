<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserForm;
use App\Models\Tab;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $authUser = Auth::user();
        $filter = $request->query('filter', 'own');

        switch ($authUser->role) {
            case 'OWNER':
                if ($filter === 'all') {
                    // The owner (Super Admin) wants to see all users in the system
                    $users = User::all();
                } else {
                    // Filter by owner's company
                    $users = User::where('company_id', $authUser->company_id)->get();
                }
                break;

            case 'ADMIN':
                // ADMINs ONLY see users from their own company, regardless of filters
                $users = User::where('company_id', $authUser->company_id)->get();
                break;

            default:
                abort(403, 'Unauthorized.');
                break;
        }

        return Inertia::render('Users/Index', [
            'users' => $users,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return Inertia::render('Users/Index');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserForm $request)
    {
        $form = $request->validated();
        $user = User::create([
            'name' => $form['name'],
            'email' => $form['email'],
            'role' => 'ADMIN',
            'password' => Hash::make($form['password']),
        ]);
        // safeguard to avoid user association with store
        $global_user = $request->global_user ?? false;
        if ($global_user) {
            return response()->json($user, 201);
        }
        // user association with store
        if (Auth::user()->role == 'ADMIN' || Auth::user()->role == 'OWNER') {
            $user->store_id = Auth::user()->store_id;
            $user->company_id = Auth::user()->company_id;
            $user->save();

            return response()->json($user, 201);
        }

        return response()->json($user, 201);
    }

    /**
     * Update authenticated user's timezone.
     *
     * @return \Illuminate\Http\Response
     */
    public function updateTimezone(Request $request)
    {
        $request->validate([
            'timezone' => 'required|timezone',
        ]);
        // Persist new timezone for authenticated user
        User::where('id', Auth::id())->update([
            'timezone' => $request->timezone,
        ]);

        return back();
    }

    /**
     * Fetch authenticated user's timezone.
     *
     * @return \Illuminate\Http\Response
     */
    public function getTimezone()
    {
        $tz = Auth::user()->timezone;

        return response()->json(['timezone' => $tz], 200);
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        return Inertia::render('Users/Index', [
            'userEdit' => $user,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(UserForm $request, User $user)
    {
        $form = $request->validated();
        $form['password'] = Hash::make($form['password']);
        if ($user->update($form)) {
            return response()->json($user, 200);
        } else {
            return response()->json('', 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        if ($user->delete()) {
            return response()->json('OK', 200);
        } else {
            return response()->json('', 500);
        }
    }

    public function changeRole(User $user)
    {
        return Inertia::render('Users/Index', [
            'userEdit' => $user,
            'currentUserRole' => Auth::user()->role,
        ]);
    }

    public function updateRole(Request $request, User $user)
    {
        $newRole = $request->role;
        $user->role = $newRole;
        $user->save();

        return response()->json('OK');
    }

    public function ownerInvoiceUpdate(Request $request, User $user)
    {
        $header = $request->header ?: null;
        $footer = $request->footer ?: null;
        $logo = $user->invoice_logo;
        if ($request->logo != 'null') {
            $logo = $request['logo']->store('logos');
        }

        $user->invoice_header = $header;
        $user->invoice_footer = $footer;
        $user->invoice_logo = $logo;
        $user->save();

        return response()->json('OK');
    }

    public function updateHeaders(User $user, Request $request)
    {
        try {
            if ($request->tab == 'sold') {
                $user->sold_headers = json_encode($request->fields);
                $save = $user->save();
            } else {
                $user->headers = json_encode($request->fields);
                $save = $user->save();
            }

            return response()->json($save, 200);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    // Get the printable tag fields for the current user
    public function getPrintableTagFields()
    {
        try {
            $user = Auth::user();
            // as JSON the column may already be an array or a JSON string
            $fields = is_array($user->printable_tag_fields)
                ? $user->printable_tag_fields
                : json_decode($user->printable_tag_fields, true) ?? [];

            return response()->json($fields, 200);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Could not retrieve printable tag fields',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updatePrintableTagFields(Request $request)
    {
        // Validate that we received an array of allowed keys
        $data = $request->validate([
            'fields' => 'required|array',
        ]);

        $user = Auth::user();
        // Store directly as an array (cast to JSON in the column)
        $user->printable_tag_fields = $data['fields'];
        $user->save();

        return response()->json([
            'success' => true,
            'fields' => $user->printable_tag_fields,
        ], 200);
    }

    /**
     * Get printable invoice fields for the authenticated user.
     */
    public function getPrintableInvoiceFields()
    {
        try {
            $user = Auth::user();
            $fields = is_array($user->printable_invoice_fields)
                ? $user->printable_invoice_fields
                : json_decode($user->printable_invoice_fields, true) ?? [];

            return response()->json($fields, 200);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Could not retrieve printable invoice fields',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update printable invoice fields for the authenticated user.
     */
    public function updatePrintableInvoiceFields(Request $request)
    {
        $data = $request->validate([
            'fields' => 'required|array',
        ]);

        $user = Auth::user();
        $user->printable_invoice_fields = $data['fields'];
        $user->save();

        return response()->json([
            'success' => true,
            'fields' => $user->printable_invoice_fields,
        ], 200);
    }

    /**
     * Return tabs that belong to the authenticated user.
     *
     * @return \Illuminate\Http\Response
     */
    public function userTabs(Request $request)
    {
        try {
            $user = Auth::user();
            if (! $user) {
                return response()->json(['tabs' => []], 200);
            }

            $tabs = Tab::where('user_id', $user->id)
                ->orderBy('order', 'asc')
                ->get();

            return response()->json(['tabs' => $tabs], 200);
        } catch (Exception $e) {
            Log::error('UserController@userTabs error: '.$e->getMessage());

            return response()->json(['error' => 'Could not retrieve tabs'], 500);
        }
    }

    /**
     * Update a custom tab name.
     *
     * @return \Illuminate\Http\Response
     */
    public function updateTabName(Request $request)
    {
        try {
            $user = Auth::user();

            $data = $request->validate([
                'tab_id' => 'required|exists:tabs,id',
                'name' => 'required|string|max:255',
            ]);

            $tab = Tab::where('id', $data['tab_id'])
                ->where('user_id', $user->id)
                ->first();

            if (! $tab) {
                return response()->json(['error' => 'Tab not found'], 404);
            }

            $tab->update(['name' => $data['name']]);

            return response()->json([
                'success' => true,
                'tab' => $tab,
            ], 200);
        } catch (Exception $e) {
            Log::error('UserController@updateTabName error: '.$e->getMessage());

            return response()->json(['error' => 'Could not update tab name'], 500);
        }
    }

    /**
     * Get the authenticated user's invoice logo.
     */
    public function getInvoiceLogo(Request $request)
    {
        $user = Auth::user();

        // 1. Check Personal User Logo
        if ($user->invoice_logo && Storage::disk('local')->exists($user->invoice_logo)) {
            return response()->file(Storage::disk('local')->path($user->invoice_logo));
        }

        // If strict mode is requested, stop here if no personal logo
        if ($request->has('strict')) {
            return response()->json(['url' => null], 404);
        }

        // 2. Check Company Logo (Fallback)
        $company = $user->company;
        if ($company && $company->logo && Storage::disk('local')->exists($company->logo)) {
            return response()->file(Storage::disk('local')->path($company->logo));
        }

        // 3. Check Store Logo (Fallback)
        $store = $user->store;
        if ($store && $store->logo) {
            if (Storage::disk('local')->exists($store->logo)) {
                return response()->file(Storage::disk('local')->path($store->logo));
            } elseif (file_exists(storage_path('app/'.$store->logo))) {
                return response()->file(storage_path('app/'.$store->logo));
            }
        }

        // 4. No default logo
        return response()->json(['url' => null], 404);
    }

    /**
     * Update the authenticated user's invoice logo.
     */
    public function updateInvoiceLogo(Request $request)
    {
        $request->validate([
            'photo' => ['nullable', 'mimes:jpg,jpeg,png,png', 'max:1024'],
        ]);

        $user = Auth::user();

        if ($request->hasFile('photo')) {
            // Delete old logo if exists
            if ($user->invoice_logo && Storage::disk('local')->exists($user->invoice_logo)) {
                Storage::disk('local')->delete($user->invoice_logo);
            }

            // Store new logo
            $path = $request->file('photo')->store('logos', 'local');

            // Update user record
            $user->forceFill([
                'invoice_logo' => $path,
            ])->save();
        }

        return back(303);
    }

    /**
     * Delete the authenticated user's invoice logo.
     */
    public function deleteInvoiceLogo()
    {
        $user = Auth::user();

        if ($user->invoice_logo && Storage::disk('local')->exists($user->invoice_logo)) {
            Storage::disk('local')->delete($user->invoice_logo);
        }

        $user->forceFill([
            'invoice_logo' => null,
        ])->save();

        return back(303);
    }
}
