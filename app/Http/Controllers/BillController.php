<?php

namespace App\Http\Controllers;

use App\Http\Requests\BillForm;
use Illuminate\Http\Request;
use App\Models\Bill;
use App\Models\PaymentsBills;
use App\Models\CashOnHand;
use App\Http\Requests\BillExcelForm;
use App\Imports\BillsImport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;
use Maatwebsite\Excel\Excel;

class BillController extends Controller
{
  /**
   * Show Data Listening of Bills Data
   */
  public function show(Request $request)
  {
    $user = Auth::user();

    $dataStatus = 'all';
    $dataStatus = ($request->query('status') == 'paid' || $request->query('status') == 'unpaid') ? $request->query('status') : 'all';

    $bills = Bill::where("user_id", $user->id);

    if ($dataStatus != 'all') {
      if ($dataStatus == 'unpaid') {
        $bills->where('status', 0);
      } else {
        $bills->where('status', 1);
      }
    }

    $bills = $bills->get();
    foreach ($bills as $bill) {
      $payments = PaymentsBills::where('bill_id', $bill->id)->get()->map(function ($payment) {
        $formattedDate = date('F j, Y', strtotime($payment->payment_date));
        $paid = "$" . number_format($payment->amount_paid, 2);
        return array_merge($payment->toArray(), ['date' => $formattedDate, 'paid' => $paid]);
      });
      if ($payments != null) {
        $bill['payments'] = $payments;
      }
    }

    $context = [
      'items' => $bills,
      'dataStatus' => $dataStatus
    ];

    return Inertia::render("Accounting/BillsShow", $context);
  }


  /**
   * Show the form for creating a new Bills.
   *
   * @return \Inertia\Response
   */
  public function create(): \Inertia\Response
  {

    return Inertia::render("Accounting/BillsCreateEdit");
  }

  /**
   * Store a newly created bills in storage.
   *
   * @param BillForm $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function store(BillForm $request): \Illuminate\Http\JsonResponse
  {
    $items = $request->validated();
    $user = Auth::user();
    $amount = 0;
    $created = [];
    foreach ($items["items"] as $item) {
      $item['user_id'] = Auth::user()->id;
      $item['amount_paid'] = 0;
      $item['balance_remaining'] = $item['total'];
      $item['flat_tax'] = $item['subtotal'] * ($item['tax'] / 100);
      $item['status'] = 0;
      if ($item['status'] == 1) {
        $item['amount_paid'] = $item['total'];
        $item['balance_remaining'] = 0;
        $amount += $item['amount_paid'];
      }
      $created[] = Bill::create($item);
    }

    $old_amount = CashOnHand::where('user_id', $user->id)->value("balance");
    CashOnHand::where('user_id', $user->id)->update([
      "balance" => $old_amount - $amount,
      "updated_at" => now(),
    ]);

    return response()->json($created, 201);
  }

  /**
   * Show the form for editing the specified bills.
   *
   * @param  \App\Models\Bill  $item
   * @return \Inertia\Response
   */
  public function edit($item): \Inertia\Response
  {
    $ids = base64_decode(urldecode($item));
    $ids = explode(";", $ids);
    $items = Bill::whereIn("id", $ids)->get();
    return Inertia::render('Accounting/BillsCreateEdit', [
      "editing" => $items
    ]);
  }

  /**
   * Update the specified bills in storage.
   *
   * @param  Request  $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function update(Request $request): \Illuminate\Http\JsonResponse
  {
    $items = $request->input("items");

    $updated = [];
    $amount = 0;
    foreach ($items as $item) {
      $item['user_id'] = Auth::user()->id;
      $item['amount_paid'] = 0;
      $item['balance_remaining'] = $item['total'];
      $item['flat_tax'] = $item['subtotal'] * ($item['tax'] / 100);
      if ($item['status'] == 1) {
        $item['amount_paid'] = $item['total'];
        $item['balance_remaining'] = 0;
        $amount += $item['amount_paid'];
      }
      $object = Bill::updateOrCreate(['id' => @$item["id"]], $item);
      $updated[] = $object;
    }

    return response()->json($updated);
  }

  /**
   * Deletes multiple Bills.
   *
   * @param Request $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function obliterate(Request $request): \Illuminate\Http\JsonResponse
  {
    $items = collect($request->input())->pluck("id");
    $deleted = Bill::whereIn('id', $items->toArray())->delete();
    return response()->json($deleted);
  }

  public function excelCreate()
  {
    return Inertia::render("Accounting/BillsExcel");
  }

  public function excelStore(BillExcelForm $request)
  {
    $check = $request->validated();
    // dd($check['file']);
    Excel::import(new BillsImport, $check['file']);

    return response()->json('done', 201);
  }

  public function excelDemoDownload()
  {
    $filePath = public_path("excelDemo/Billdemoexcels.xlsx");
    return Response::download($filePath);
  }

  public function recordPayment(Request $request)
  {
    $user = Auth::user();
    $bill = Bill::where('id', $request->id)->first();
    $paid = 0;
    $total = $request->amount + $bill->amount_paid;

    if ($total >= $bill->total) {
      $total = $bill->total;
    }

    $balance = $bill->balance_remaining - $request->amount;
    if ($balance <= 0) {
      $balance = 0;
      $paid = 1;
    }

    $update = $bill->update([
      'amount_paid' => $total,
      'balance_remaining' => $balance,
      'status' => $paid,
    ]);

    PaymentsBills::insert([
      'bill_id' => $request->id,
      'amount_paid' => $request->amount,
      'balance_remaining' => $balance,
      'payment_method' => $request->payment_method,
      'payment_account' => $request->payment_account,
      'payment_date' => $request->payment_date,
      'created_at' => now(),
      'updated_at' => now(),
    ]);

    $cash_on_hands = CashOnHand::where('user_id', $user->id)->first();
    $remaining = $cash_on_hands['balance'] - $request->amount;
    $update = CashOnHand::where('user_id', $user->id)->update([
      'balance' => $remaining,
    ]);

    return response()->json($update);
  }

  public function editPayment(Request $request)
  {
    $user = Auth::user();
    $payment = PaymentsBills::where('id', $request->id)->first();
    $bill = Bill::where('id', $payment->bill_id)->first();
    $cash_on_hand = CashOnHand::where('user_id', $user->id)->first();

    $total = $bill->amount_paid;
    $balance = $bill->balance_remaining;
    $diff = 0;
    if ($request->payment_amount < $payment->amount_paid) {
      $diff = $payment->amount_paid - $request->payment_amount;
      $total -= $diff;
      $total = $total <= 0 ? 0 : $total;
      $balance += $diff;
      $balance = $balance >= $bill->total ? $bill->total : $balance;

      CashOnHand::where('user_id', $user->id)->update([
        'balance' => $cash_on_hand->balance + $diff,
      ]);
    } else if ($request->payment_amount > $payment->amount_paid) {
      $diff = $request->payment_amount - $payment->amount_paid;
      $total += $diff;
      $total = $total >= $bill->total ? $bill->total : $total;
      $balance -= $diff;
      $balance = $balance <= 0 ? 0 : $balance;

      CashOnHand::where('user_id', $user->id)->update([
        'balance' => $cash_on_hand->balance - $diff,
      ]);
    }

    $paid = 0;
    if ($total == $bill->total) {
      $paid = 1;
    }

    $bill->update([
      'amount_paid' => $total,
      'balance_remaining' => $balance,
      'status' => $paid,
      'updated_at' => now(),
    ]);

    $update = $payment->update([
      'amount_paid' => $request->payment_amount,
      'balance_remaining' => $balance,
      'payment_method' => $request->payment_method,
      'payment_account' => $request->payment_account,
      'payment_date' => $request->payment_date,
      'updated_at' => now(),
    ]);

    return response()->json($update);
  }

  public function removePayment(Request $request)
  {
    $user = Auth::user();
    $payment = PaymentsBills::where('id', $request->id)->first();
    $bill = Bill::where('id', $payment->bill_id)->first();
    $cash_on_hand = CashOnHand::where('user_id', $user->id)->first();

    $paid = 0;
    $total = $bill->amount_paid - $request->payment_amount;
    if ($total <= 0) {
      $total = 0;
    }

    $balance = $bill->balance_remaing + $request->payment_amount;
    if ($balance >= $bill->total) {
      $balance = $bill->total;
    }

    $bill->update([
      'amount_paid' => $total,
      'balance_remaining' => $balance,
      'status' => $paid,
    ]);

    $remaining = $cash_on_hand->balance + $request->payment_amount;
    CashOnHand::where('user_id', $user->id)->update(['balance' => $remaining]);

    $delete = $payment->delete();

    return response()->json($delete);
  }
}
