<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Models\MailList;
use App\Models\Contact;
use App\Models\EmailTemplate;
use App\Http\Requests\EmailForm;
use Illuminate\Support\Facades\Mail;
use App\Mail\MarketingEmail;

class MailListController extends Controller
{
  /**
   * Display a listing of the resource.
   *
   * @return \Inertia\Response;
   */
  public function index()
  {
    $user_id = Auth::user()->id;
    $contacts = Contact::where('user_id', Auth::id())->get()->toArray();
    $email_templates = EmailTemplate::where('user_id', Auth::id())->get()->toArray();
    $mailing_lists = MailList::all()->toArray();

    return Inertia::render("Customers/MailList", [
      "contacts" => $contacts,
      "email_templates" => $email_templates,
      "mailing_lists" => $mailing_lists,
    ]);
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
      $user = Auth::user();
      $mail_list = MailList::create([
        "title" => $request->title,
        "names" => $request->names,
        "emails" => $request->emails,
        "user_id" => $user->id,
      ]);
      return response()->json($mail_list, 200);
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
    try {
      $request->validate([
        'emails' => 'required',
        'names' => 'required',
        'title' => 'required',
      ]);

      $mailing_list = MailList::find($id);

      if (!$mailing_list) {
        return response()->json(['message' => 'Mailing List not found'], 404);
      }

      $mailing_list->names = $request->input('names');
      $mailing_list->emails = $request->input('emails');
      $mailing_list->title = $request->input('title');
      $mailing_list = $mailing_list->save();

      return response()->json($mailing_list, 200);
    } catch (Exception $e) {
      return response()->json($e->getMessage(), 500);
    }
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
      $mailing_list = MailList::find($id);
      $mailing_list->delete();
      return response()->json(["message" => "Mailing List was deleted!"], 200);
    } catch (Exception $e) {
      return response()->json($e->getMessage(), 500);
    }
  }

  public function send(EmailForm $request)
  {
    $validatedData = $request->validated();
    $contacts = $validatedData['contacts']; // Lista de emails
    $subject = $validatedData['subject'];
    $content = $validatedData['content'];
    $user = Auth::user();

    if (!$user) {
      return response()->json(['error' => 'User not authenticated'], 401);
    }

    // Obtener los nombres y emails de la base de datos
    $contactData = Contact::whereIn('email', $contacts)->get(['name', 'email']);

    // Convertir a un array asociativo [email => name]
    $contactsMap = $contactData->pluck('name', 'email')->toArray();

    foreach ($contacts as $email) {
      $name = $contactsMap[$email] ?? 'Valued Customer'; // Nombre por defecto si no se encuentra en la BD

      try {
        $mail = new MarketingEmail($subject, $content, $name, $user);
        Mail::to($email)->send($mail);
      } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
        continue;
      }
    }

    return response()->json(['message' => 'Emails sent successfully'], 200);
  }
}
