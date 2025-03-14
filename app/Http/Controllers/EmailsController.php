<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Exception;

class EmailsController extends Controller
{
  /**
   * Display a listing of the resource.
   *
   * @return \Inertia\Response
   */
  public function index()
  {
    $user_id = Auth::user()->id;
    $emails = EmailTemplate::where('user_id', $user_id)->get();

    return Inertia::render("Customers/EmailEditor", compact('emails'));
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
      $email = $request->toArray();
      $save = EmailTemplate::create($email);
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
    // Validate the request data
    $request->validate([
      'subject' => 'required|string|max:255',
      'content' => 'required|string',
    ]);

    $emailTemplate = EmailTemplate::find($id);

    if (!$emailTemplate) {
      return response()->json(['message' => 'Email template not found'], 404);
    }

    $emailTemplate->subject = $request->input('subject');
    $emailTemplate->content = $request->input('content');
    $emailTemplate->save();

    return response()->json(['message' => 'Email template updated successfully'], 200);
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
      $emailTemplate = EmailTemplate::find($id);
      $emailTemplate->delete();
      return response()->json(['message' => 'Email template deleted successfully'], 200);
    } catch (Exception $e) {
      return response()->json($e->getMessage(), 500);
    }
  }
}
