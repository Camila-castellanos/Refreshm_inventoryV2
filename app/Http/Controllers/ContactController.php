<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Exception;

class ContactController extends Controller
{
  public function store(Request $request)
  {
    try {
      $user = Auth::user();
      $contact = Contact::create([
        "name" => $request->name,
        "email" => $request->email,
        "user_id" => $user->id,
        "type" => 3,
      ]);
      return response()->json($contact, 200);
    } catch (Exception $e) {
      return response()->json($e->getMessage(), 500);
    }
  }
}
