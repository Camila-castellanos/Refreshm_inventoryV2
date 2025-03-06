<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use App\Models\Prospect;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Exception;

class ProspectController extends Controller
{
  /**
   * Display a listing of the resource.
   *
   * @return \Inertia\Response
   */
  public function index()
  {
    $user_id = Auth::user()->id;
    $prospects = Prospect::where('user_id', $user_id)->get()->toArray();

    return Inertia::render("Customers/Prospects/Index", compact('prospects'));
  }

  /**
   * Show the form for creating a new resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function create()
  {
    //
  }

  /**
   * Store a newly created resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function store(Request $request)
  {
    try {
      $request->merge(['user_id' => Auth::id()]);
      $prospect = $request->toArray();
      $save = Prospect::create($prospect);

      $contact = new Contact();
      $contact->name = $prospect['first_name'] . " " . $prospect['last_name'];
      $contact->email = $prospect['email'];
      $contact->type = 2;
      $contact->user_id = Auth::id();
      $contact->prospect_id = $save->id;
      $contact->save();
      return response()->json($save, 200);
    } catch (Exception $e) {
      return response()->json($e->getMessage(), 500);
    }
  }

  /**
   * Display the specified resource.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function show($id)
  {
    //
  }

  /**
   * Show the form for editing the specified resource.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function edit($id)
  {
    //
  }

  /**
   * Update the specified resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function update(Request $request, $id)
  {
    $request->validate([
      'email' => 'required|email',
      'first_name' => 'required|string|max:255',
      'last_name' => 'required|string|max:255',
      'company_name' => 'required|string|max:255',
      'country' => 'required|string|max:255',
      'phone_number' => 'required|string|max:20', // Adjust max length based on your requirements
      'contact_type' => 'required|string',
    ]);

    $prospect = Prospect::find($id);

    if (!$prospect) {
      return response()->json(['message' => 'Prospect not found'], 404);
    }

    $prospect->email = $request->email;
    $prospect->first_name = $request->first_name;
    $prospect->last_name = $request->last_name;
    $prospect->company_name = $request->company_name;
    $prospect->country = $request->country;
    $prospect->phone_number = $request->phone_number;
    $prospect->contact_type = $request->contact_type;

    $prospect->save();

    return response()->json(['message' => 'Prospect updated successfully'], 200);
  }

  /**
   * Remove the specified resource from storage.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function destroy($id)
  {
    try {
      $prospect = Prospect::find($id);
      $delete = Contact::deleteContactsByProspect($id);
      $prospect->delete();
      return response()->json(['message' => 'Prospect deleted successfully'], 200);
    } catch (Exception $e) {
      return response()->json($e->getMessage(), 500);
    }
  }
}
