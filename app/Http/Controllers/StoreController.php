<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReceiptForm;
use App\Http\Requests\StoreForm;
use App\Http\Requests\StoreUpdateForm;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class StoreController extends Controller
{
  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function index()
  {
    $stores = [];

    switch (Auth::user()->role) {
      case "ADMIN":
        $stores = [Auth::user()->store];
        break;
      case "OWNER":
        $stores = Store::all();
        break;
      default:
        abort(403, 'Unauthorized.');
        break;
    }

    return Inertia::render("Stores/Index", [
      "stores" => $stores,
      "userRole" => Auth::user()->role,
    ]);
  }

  /**
   * Show the form for creating a new resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function create()
  {
    return Inertia::render("Stores/CreateEdit");
  }

  /**
   * Store a newly created resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function store(StoreForm $request)
  {
    $count = User::where('email', $request->email)->count();
    if ($count) {
      return response()->json(['response' => 'Email addres already exits!'], 500);
    } else {
      // $logo = $request["logo"]->store("logos");

      // $data = [
      //     "name" => $request->name,
      //     "address" => $request->address,
      //     "price_percent" => $request->price_percent,
      //     "header" => $request->header,
      //     "footer" => $request->footer,
      //     "logo" => $logo,
      //     "email" => $request->email
      // ];

      // $store = Store::create($data);

      $store = Store::create($request->validated());
      $user = User::create([
        "name" => $request->adminname,
        "email" => $request->email,
        "password" => Hash::make($request->password),
        "role"  => 'ADMIN',
        'store_id' => @$store->id,
      ]);
    }
    // if (Auth::user()->role == "OWNER") {
    //     $user->store_id = @$store->id;
    //     $user->save();
    // }

    return response()->json($store, 201);
  }

  /**
   * Display the specified resource.
   *
   * @param  \App\Models\Store  $store
   * @return \Illuminate\Http\Response
   */
  public function show(Store $store)
  {
    //
  }

  /**
   * Show the form for editing the specified resource.
   *
   * @param  \App\Models\Store  $store
   * @return \Illuminate\Http\Response
   */
  public function edit(Store $store)
  {
    return Inertia::render("Stores/CreateEdit", [
      "storeEdit" => $store
    ]);
  }

  public function receiptDetail($id)
  {
    $store = Store::where('id', $id)->first();
    return response()->json(['store' => $store], 200);
  }

  /**
   * Update the specified resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \App\Models\Store  $store
   * @return \Illuminate\Http\Response
   */
  public function update(StoreUpdateForm $request, Store $store)
  {
    $logo = $request->logo;

    $logo = $request->hasFile('logo') ? $request->file('logo')->store("logos") : $store->logo;

    $data = [
      "name" => $request->name,
      "address" => $request->address,
      "header" => $request->header,
      "footer" => $request->footer,
      "logo" => $logo,
      "email" => $request->email
    ];

    $store->update($data);
    // $store->update($request->validated());


    return response()->json($store, 200);
  }

  /**
   * Remove the specified resource from storage.
   *
   * @param  \App\Models\Store  $store
   * @return \Illuminate\Http\Response
   */
  public function destroy(Store $store)
  {
    $store->delete();

    return response()->json("OK", 200);
  }

  /**
   * Lists the Users in that Store
   * @param \App\Models\Store $store
   * @return \Illuminate\Http\Response
   */
  public function listUsers(Store $store)
  {
    $userList = User::all()->merge($store->users);

    $data = [
      "userList" => $userList,
      "store" => $store,
    ];
    
    return response()->json($data);
  }

  /**
   * Updates Store Users
   * @param \Illuminate\Http\Request $request
   * @param \App\Models\Store $store
   * @return \Illuminate\Http\Response
   */
  public function users(Request $request, Store $store)
  {
    $users = collect($request->users);

    foreach ($store->users as $user) {
      $user->store_id = null;
      $user->save();
    }

    foreach ($users as $user) {
      $user = User::find($user["id"]);
      $user->store_id = $store->id;
      $user->save();
    }


    return response()->json("OK");
  }

  /**
   * Saves Store Receipt settings
   * @param \Illuminate\Http\Request $request
   * @param \App\Models\Store $store
   * @return \Illuminate\Http\Response
   */
  public function storeReceiptSettings(ReceiptForm $request, Store $store)
  {
    $settings = $request->validated();
    $has_header = Arr::exists($settings, "header");
    $has_footer = Arr::exists($settings, "footer");
    $has_logo = Arr::exists($settings, "logo");

    if ($has_header) {
      $store->header = $settings["header"];
    }

    if ($has_footer) {
      $store->footer = $settings["footer"];
    }

    if ($has_logo) {
      $logo = $settings["logo"]->store("logos");
      $store->logo = $logo;
    }

    if ($has_header || $has_footer || $has_logo) {
      $store->save();
    }

    return response()->json("OK");
  }

  /**
   * Saves Store Receipt settings
   * @param \Illuminate\Http\Request $request
   * @param \App\Models\Store $store
   * @return \Illuminate\Http\Response
   */
  public function updateStorePercent(Request $request, Store $store)
  {
    $storeCut = $request->input("store_percent");
    $store->price_percent = $storeCut;
    $store->save();

    return response()->json("OK");
  }
}
