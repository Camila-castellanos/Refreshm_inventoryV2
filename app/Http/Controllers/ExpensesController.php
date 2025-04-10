<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseForm;
use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\CashOnHand;
use App\Http\Requests\ExpenseExcelForm;
use App\Imports\ExpensesImport;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Maatwebsite\Excel\Facades\Excel;

class ExpensesController extends Controller
{
  /**
   * Show Data Listening of Expenses Data
   */
  public function show()
  {
    $user = Auth::user();

    $context = [
      'items' => Expense::where("user_id", $user->id)->orderBy('id', 'desc')->get(),
    ];

    return Inertia::render("Accounting/Expenses", $context);
  }


  /**
   * Show the form for creating a new Expenses.
   *
   * @return \Inertia\Response
   */
  public function create(): \Inertia\Response
  {

    return Inertia::render("Accounting/ExpenseCreateEdit");
  }

  /**
   * Store a newly created expenses in storage.
   *
   * @param ExpenseForm $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function store(ExpenseForm $request): \Illuminate\Http\JsonResponse
  {
    $items = $request->validated();
    $user = Auth::user();
    $amount = 0;

    

    $created = [];
    foreach ($items["items"] as $item) {
      $item['user_id'] = Auth::user()->id;
      $amount += $item['total'];
      $created[] = Expense::create($item);
    }

    $old_amount = CashOnHand::where('user_id', $user->id)->value("balance");
    CashOnHand::where('user_id', $user->id)->update([
      "balance" => $old_amount - $amount,
      "updated_at" => now(),
    ]);

    return response()->json($created, 201);
  }

  /**
   * Show the form for editing the specified expenses.
   *
   * @param  \App\Models\Expense  $item
   * @return \Inertia\Response
   */
  public function edit($item): \Inertia\Response
  {
    $ids = base64_decode(urldecode($item));
    $ids = explode(";", $ids);
    $items = Expense::whereIn("id", $ids)->get();
    return Inertia::render('Accounting/ExpenseCreateEdit', [
      "editing" => $items
    ]);
  }

  /**
   * Update the specified expenses in storage.
   *
   * @param  Request  $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function update(Request $request): \Illuminate\Http\JsonResponse
  {
    $items = $request->input("items");

    $updated = [];
    foreach ($items as $item) {
      $item['user_id'] = Auth::user()->id;
      $object = Expense::updateOrCreate(['id' => @$item["id"]], $item);
      $updated[] = $object;
    }

    return response()->json($updated);
  }

  /**
   * Deletes multiple Expenses.
   *
   * @param Request $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function obliterate(Request $request): \Illuminate\Http\JsonResponse
  {
    $items = collect($request->input())->pluck("id");
    $deleted = Expense::whereIn('id', $items->toArray())->delete();
    return response()->json($deleted);
  }

  public function excelCreate()
  {
    return Inertia::render("Accounting/ExpenseExcel");
  }

  public function excelStore(ExpenseExcelForm $request)
  {
    $check = $request->validated();
    Excel::import(new ExpensesImport, $check['file']);

    return response()->json('done', 201);
  }

  public function excelDemoDownload()
  {
    $filePath = public_path("excelDemo/Expensedemoexcels.xlsx");
    return Response::download($filePath);
  }
}
