<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;
use App\Models\CustomField;
use Illuminate\Support\Facades\Auth;

class CustomFieldsController extends Controller
{

  public function store(Request $request)
  {
    try {
      $user = Auth::user();
      $customField = CustomField::create([
        'text' => $request->text,
        'value' => $request->value,
        'type' => $request->type,
        'active' => $request->active,
        'user_id' => $user->id,
      ]);
      return response()->json($customField, 200);
    } catch (Exception $e) {
      return response()->json($e->getMessage(), 500);
    }
  }

  public function update($id, Request $request)
  {
    try {
      $update = CustomField::where('id', $id)->update([
        'text' => $request->text,
        'type' => $request->type,
      ]);
      dump($update);
      return response()->json($update, 200);
    } catch (Exception $e) {
      return response()->json($e->getMessage(), 500);
    }
  }

  public function updateActive($id, Request $request)
  {
    try {
      $customField = CustomField::where('id', $id)->first();
      $customField->active = $request->active;
      $updated = $customField->save();
      return response()->json($updated, 200);
    } catch (Exception $e) {
      return response()->json($e->getMessage(), 500);
    }
  }

  public function destroy($id)
  {
    try {
      $delete = CustomField::where('id', $id)->delete();
      return response()->json($delete, 200);
    } catch (Exception $e) {
      return response()->json($e->getMessage(), 500);
    }
  }
}
