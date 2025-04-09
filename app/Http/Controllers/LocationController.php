<?php

namespace App\Http\Controllers;

use App\Http\Requests\LocationForm;
use App\Models\Location;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LocationController extends Controller
{
  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function index(Store $store)
  {
    return Inertia::render('Locations/Index', [
      'locations' => $store->locations,
      "store" => $store
    ]);
  }

  /**
   * Show the form for creating a new resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function create(Store $store)
  {
    return Inertia::render('Locations/CreateEdit', [
      'store' => $store
    ]);
  }

  /**
   * Store a newly created resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function store(Store $store, LocationForm $request)
  {
    $location = $store->locations()->create($request->validated());
    return response()->json($location, 201);
  }

  /**
   * Display the specified resource.
   *
   * @param  \App\Models\Location  $location
   * @return \Illuminate\Http\Response
   */
  public function show(Location $location)
  {
    //
  }

  /**
   * Show the form for editing the specified resource.
   *
   * @param  \App\Models\Location  $location
   * @return \Illuminate\Http\Response
   */
  public function edit(Location $location)
  {
    return Inertia::render('Locations/CreateEdit', [
      "location" => $location,
      'store' => $location->store,
    ]);
  }

  /**
   * Update the specified resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \App\Models\Location  $location
   * @return \Illuminate\Http\Response
   */
  public function update(LocationForm $request, Location $location)
  {
    if ($location->update($request->validated())) {
      return response()->json($location, 200);
    }
    return response()->json('ERROR', 500);
  }

  /**
   * Remove the specified resource from storage.
   *
   * @param  \App\Models\Location  $location
   * @return \Illuminate\Http\Response
   */
  public function destroy(Location $location)
  {
    if ($location->delete()) {
      return response()->json('OK', 200);
    }
    return response()->json('ERROR', 500);
  }

  /**
   * Lists the Users in that Location
   * @param \App\Models\Location $location
   * @return \Illuminate\Http\Response
   */
  public function listUsers(Location $location)
  {
    $data = [
      "userList" => $location->store->users,
      "location" => $location,
      'store' => $location->store,
    ];

    return response()->json($data);
  }

  /**
   * Updates Location Users
   * @param \Illuminate\Http\Request $request
   * @param \App\Models\Location $location
   * @return \Illuminate\Http\Response
   */
  public function users(Request $request, Location $location)
  {
    $users = collect($request->users);

    foreach ($location->users as $user) {
      $user->location_id = null;
      $user->save();
    }

    foreach ($users as $user) {
      $user = User::find($user["id"]);
      $user->location_id = $location->id;
      $user->save();
    }

    return response()->json("OK");
  }

  /**
   * Redirect the user to the right index
   * @return \Illuminate\Http\Response
   */
  public function userLocations()
  {
    if (Auth::user()->role == 'ADMIN') {
      $store = Auth::user()->store;
      if ($store == null ) {
        return Inertia::render('Locations/NoLocation');
      } 
      return redirect()->route("stores.locations.index", $store);
    }
    abort(403, 'Unauthorized.');
  }
}
