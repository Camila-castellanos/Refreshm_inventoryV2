<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $contact = Contact::create([
                'name' => $request->name,
                'email' => $request->email,
                'user_id' => $user->id,
                'type' => 'custom',
            ]);

            return response()->json($contact, 200);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }
}
