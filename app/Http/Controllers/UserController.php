<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserForm;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Exception;

class UserController extends Controller
{
  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function index(Request $request)
{
    $users = [];
    $filter = $request->query('filter', 'own'); // obtain the filter from the query string, default to 'own'
    
    switch (Auth::user()->role) {
        case "ADMIN":
            $store = Auth::user()->store;
            $users = @$store->users;
            break;
        case "OWNER":
            if ($filter === 'own') {
                //  the owner wants to see only their own users
                $store = Auth::user()->store;
                $users = $store ? $store->users : collect([]);
            } else if ($filter === 'all') {
                // The owner wants to see all users (default behavior)
                $users = User::all();
            }
            break;
        default:
            abort(403, 'Unauthorized.');
            break;
    }
    
    return Inertia::render('Users/Index', [
        "users" => $users,
    ]);
}

  /**
   * Show the form for creating a new resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function create()
  {
    return Inertia::render('Users/CreateEdit');
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
      "name" => $form["name"],
      "email" => $form["email"],
      "password" => Hash::make($form["password"]),
    ]);
    if (Auth::user()->role == "ADMIN") {
      $user->store_id = @Auth::user()->store->id;
      $user->save();
    }
    return response()->json($user, 201);
  }

  /**
   * Display the specified resource.
   *
   * @param  \App\Models\User  $user
   * @return \Illuminate\Http\Response
   */
  public function show(User $user)
  {
    //
  }

  /**
   * Show the form for editing the specified resource.
   *
   * @param  \App\Models\User  $user
   * @return \Illuminate\Http\Response
   */
  public function edit(User $user)
  {
    return Inertia::render('Users/CreateEdit', [
      "userEdit" => $user
    ]);
  }

  /**
   * Update the specified resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \App\Models\User  $user
   * @return \Illuminate\Http\Response
   */
  public function update(UserForm $request, User $user)
  {
    $form = $request->validated();
    $form["password"] = Hash::make($form["password"]);
    if ($user->update($form)) {
      return response()->json($user, 200);
    } else {
      return response()->json('', 500);
    }
  }

  /**
   * Remove the specified resource from storage.
   *
   * @param  \App\Models\User  $user
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
    return Inertia::render('Users/Roles', [
      "userEdit" => $user,
      "currentUserRole" => Auth::user()->role,
    ]);
  }

  public function updateRole(Request $request, User $user)
  {
    $newRole = $request->role;
    $user->role = $newRole;
    $user->save();
    return response()->json("OK");
  }

  public function ownerInvoiceUpdate(Request $request, User $user)
  {
    $header = $request->header ?: NULL;
    $footer = $request->footer ?: NULL;
    $logo = $user->invoice_logo;
    if ($request->logo != "null") {
      $logo = $request["logo"]->store("logos");
    }

    $user->invoice_header = $header;
    $user->invoice_footer = $footer;
    $user->invoice_logo = $logo;
    $user->save();
    return response()->json("OK");
  }

  public function updateHeaders(User $user, Request $request)
  {
    try {
      if ($request->tab == "sold") {
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
            'fields'   => 'required|array',
        ]);

        $user = Auth::user();
        // Store directly as an array (cast to JSON in the column)
        $user->printable_tag_fields = $data['fields'];
        $user->save();

        return response()->json([
            'success' => true,
            'fields'  => $user->printable_tag_fields,
        ], 200);
    }
}
