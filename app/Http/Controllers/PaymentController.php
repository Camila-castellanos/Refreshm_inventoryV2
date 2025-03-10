<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceSentForm;
use App\Models\Customer;
use App\Models\CashOnHand;
use App\Models\CustomField;
use App\Http\Requests\RecordPaymentForm;
use App\Mail\InvoiceSent;
use Illuminate\Support\Facades\Mail;
use App\Models\ReturnItems;
use Exception;
use Illuminate\Http\Request;
use DateTime;
use App\Models\Item;
use App\Models\Storage;
use App\Models\Sale;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Store;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use App\Models\EmailTemplate;
use Barryvdh\DomPDF\Facade\Pdf;

class PaymentController extends Controller
{
  /**
   * Show Data Listening of Invoices Data
   */
  public function show(Request $request)
  {
    try {
      $user = Auth::user();
      $salesPluck = Item::whereNotNull('sold')->where('user_id', $user->id)->whereNotNull('sale_id')->pluck('sale_id')->unique();

      $dataStatus = 'all';
      $dataStatus = ($request->query('status') == 'paid' || $request->query('status') == 'unpaid') ? $request->query('status') : 'all';

      $response = [];

      foreach ($salesPluck as $salePluck) {
        $item = Item::where('sale_id', $salePluck)->whereNotNull('sold')->first();

        $sale = Sale::where('id', $salePluck)->first();
        if ($dataStatus == 'unpaid')
          $sale = Sale::where('id', $salePluck)->where('paid', 0)->first();
        if ($dataStatus == 'paid')
          $sale = Sale::where('id', $salePluck)->where('paid', 1)->first();

        if ($sale !== null) {
          $payments = [];
          $total = $sale->total;
          $saleCredit = $sale->credit;
          $payment = Payment::where('sale_id', $sale->id)->get()->map(function ($payment) {
            $formattedDate = date('F j, Y', strtotime($payment->payment_date));
            $paid = "$" . number_format($payment->amount_paid, 2);
            return array_merge($payment->toArray(), ['date' => $formattedDate, 'paid' => $paid]);
          });
          $payment->credit = $saleCredit;
          if ($payment->isNotEmpty()) {
            foreach ($payment as $record) {
              $payments[] = $record;
            }
          }

          $credit = 0;
          $sold = new DateTime($item->sold);
          $customer = $item->customer;
          $customer_emails = null;
          if (is_numeric($item->customer)) {
            $customers = Customer::whereId($item->customer)->select('customer', 'billing_address', 'billing_address_country', 'billing_address_state', 'billing_address_city', 'billing_address_postal', 'email', 'phone', 'credit')->first();
            if ($customers) {
              $customer = $customers->customer;
              $customer_emails = $customers->email;
              $credit = $customers->credit;
            } else {
              $customer = $item->customer;
              $credit = 0;
            }
          }
          $customer_id = $item->customer;


          $returned_items = [];
          $credited_items = [];
          $returns = ReturnItems::where("customer", $customer_id)->where("requested", 0)->get();
          foreach ($returns as $return) {
            $returned_items[] = Item::whereId($return->item)->first();
          }

          $credited = ReturnItems::where("customer", $customer_id)->where("requested", 1)->where('sale', $sale->id)->select('model', 'imei', 'id', 'credit')->get();
          foreach ($credited as $creditedItem) {
            $creditedItem->selling_price = $creditedItem->credit;
            $credited_items[] = $creditedItem;
          }

          $i["id"] = $item->id;
          $i["date"] = $sold->format("Y-m-d");
          $i["customer"] = $customer;
          $i["returned_items"] = $returned_items;
          $i["credited_items"] = $credited_items;
          $i["customer_id"] = $customer_id;
          $i["customer_credit"] = $credit;
          $i["customer_email"] = $customer_emails;
          $i["credit"] = $sale->credit;
          $i["total"] = $total;
          $i["amount_paid"] = $sale->amount_paid < 0 ? 0 : $sale->amount_paid;
          $i["balance_remaining"] = $sale->balance_remaining < 0 ? 0 : $sale->balance_remaining;
          $i["status"] = $sale->paid == 1 ? "Paid" : 'Unpaid';
          $i["payments"] = $payments;
          $i["sale_id"] = $sale->id;
          $i["payment_method"] = $sale->payment_method;
          $i["payment_account"] = $sale->payment_account;
          $i["tax"] = $sale->tax;
          $i["tax_id"] = $sale->tax_id;
          $i["discount"] = $sale->discount;
          $i["notes"] = $sale->notes;
          $i["sale_date"] = $sold->format("d/m/y");
          $response[] = $i;
        }
      }

      usort($response, function ($a, $b) {
        return $b['date'] <=> $a['date'];
      });

      $email_templates = EmailTemplate::where('user_id', Auth::user()->id)->get();
      $context = [
        'items' => $response,
        'data_status' => $dataStatus,
        'email_templates' => $email_templates,
      ];
      return Inertia::render("Accounting/Payments", $context);
    } catch (Exception $e) {
      return response()->json($e->getMessage(), 400);
    }
  }

  /**
   * Show Invoice For Download as PDF
   */
  public function invoice(Request $request)
  {
    $item = Item::where('id', $request->id)->first();
    $itm_id_pluck = Item::where('sale_id', $item->sale_id)->pluck('sale_id');

    $customer = $item->customer;
    if (is_numeric($item->customer)) {
      $customer = Customer::whereId($item->customer)->select('customer', 'billing_address', 'billing_address_country', 'billing_address_state', 'billing_address_city', 'billing_address_postal', 'email', 'phone')->first();
      if ($customer)
        $customer = $customer;
      else
        $customer = $item->customer;
    }

    $useId = $item->user_id;
    $user_data = User::where('id', $useId)->first();
    $store_id = $user_data->store_id;
    $user_role = $user_data->role;

    $store = Store::where('id', $store_id)->first();

    $header = null;
    $footer = null;
    $logo = null;

    if ($user_role == "OWNER") {
      $header = $user_data->invoice_header;
      $footer = $user_data->invoice_footer;
      if (!is_null($user_data->invoice_logo)) {
        $logo = base64_encode(file_get_contents(storage_path() . "/app/" . $user_data->invoice_logo));
      } else {
        $logo = base64_encode(file_get_contents(public_path() . "/img/_REFRESHMOBILE.png"));
      }
    } else {

      if ($store) {
        $header = $store->header;
        $footer = $store->footer;
        if (!is_null($store->logo) && $store->logo != "") {
          $logo = base64_encode(file_get_contents(storage_path() . "/app/" . $store->logo));
        } else {
          $logo = base64_encode(file_get_contents(public_path() . "/img/_REFRESHMOBILE.png"));
        }
      }
    }


    if ($item) {
      $sales = Sale::with('items')->whereIn('id', $itm_id_pluck)->get();
      $returned_items_id = ReturnItems::where('sale', $item->sale_id)->pluck('item');
      $returned_items = Item::whereIn('id', $returned_items_id)->get();
      // return view("sale-receipt-invoice",compact("sales", "header", "footer", "logo","customer"));

      $pdf = Pdf::loadView("sale-receipt-invoice", compact("sales", "header", "footer", "logo", "customer", "returned_items"))->setOptions(["defaultFont" => "sans-serif", "isRemoteEnabled" => "true"])->setPaper("a6", "landscape");



      $filename = $customer . " Invoice " . $sales[0]->id . ".pdf";

      return $pdf->stream($filename);
    }
  }

  /**
   * Send an email with the invoice attached
   */
  public function sendInvoice(InvoiceSentForm $request)
  {
    try {
      $customer = Customer::find($request->customer_id);
      $emails = $customer->email;
      $subject = $request->subject;
      $message = $request->message;
      $req = new Request;
      $req->merge(['id' => $request->id]);
      $invoice = $this->invoice($req);
      $invoiceSent = new InvoiceSent($subject, $message);
      $invoiceSent->attachData($invoice, 'invoice.pdf', [
        'mime' => 'application/pdf',
      ]);

      foreach ($emails as $email) {
        $mail = Mail::to($email)->send($invoiceSent);
      }
      return response()->json($mail, 201);
    } catch (Exception $e) {
      return response()->json($e->getMessage(), 404);
    }
  }

  /**
   * Payment unpaid to paid change
   */
  public function paid(RecordPaymentForm $request)
  {
    $request->validated();
    $user = Auth::user();
    $totalAmount = $request->amount;
    $sale = Sale::where('id', $request->sale_id)->first();
    $item = Item::where('sale_id', $request->sale_id)->first();
    if ($item && $totalAmount > 0) {
      $amt = $totalAmount + $sale['amount_paid'];
      $amount = $amt;
      $balance = $sale['total'] - $amt;
      $amount = min($sale['total'], $amount);
      $balance = max(0, $balance);
      $paid = $balance == 0 ? 1 : 0;
      $totalAmount -= $totalAmount > ($sale['total'] - $sale['amount_paid']) ? ($sale['total'] - $sale['amount_paid']) : $totalAmount;

      Sale::where('id', $item->sale_id)->update([
        'paid' => $paid,
        'balance_remaining' => round($balance, 2),
        'amount_paid' => round($amount, 2),
        'payment_method' => $request->paidPaymentMethod,
        'payment_account' => $request->paidPaymentAccount,
        'notes' => $request->paidNotes,
        'updated_at' => now(),
      ]);

      if ($request->paidPaymentAccount == "Cash on Hand") {
        $old_cash = CashOnHand::where('user_id', $user->id)->value("balance");
        CashOnHand::where('user_id', $user->id)->update([
          'balance' => $old_cash + $amount,
        ]);
      }
    }

    Payment::insert([
      'sale_id' => $item->sale_id,
      'amount_paid' => $request->amount,
      'balance_remaining' => round($balance, 2),
      'payment_method' => $request->paidPaymentMethod,
      'payment_account' => $request->paidPaymentAccount,
      'payment_date' => $request->paidDate,
      'created_at' => now(),
      'updated_at' => now(),
    ]);
  }

  public function removePayment(Request $request)
  {
    $payment = Payment::where('id', $request->id)->first();
    $totalAmount = $payment->amount_paid;

    $item = Item::where('sale_id', $request->sale_id)->first();
    $sale = Sale::where('id', $request->sale_id)->first();

    if ($item && $totalAmount > 0) {
      $amountToSubtract = min($totalAmount, $sale->amount_paid); // Calculate the amount to subtract from the sale
      $amount = ($sale->amount_paid - $amountToSubtract < 0 ? 0 : $sale->amount_paid - $amountToSubtract);
      $balance = ($sale->balance_remaining + $amountToSubtract < 0 ? 0 : $sale->balance_remaining + $amountToSubtract); // Subtract the amount from the sale
      $amount = max(0, $amount);
      $balance = min($sale->total, $balance);
      $totalAmount -= $amountToSubtract; // Subtract the amount from the payment

      Sale::where('id', $item->sale_id)->update([
        'balance_remaining' => round($balance, 2),
        'amount_paid' => round($amount, 2),
        'paid' => 0,
        'updated_at' => now(),
      ]);
    }

    Payment::where('id', $request->id)->delete();

    return response()->json(201);
  }

  public function editPayment(Request $request)
  {
    $payment = Payment::where('id', $request->id)->first();
    $oldAmount = $payment->amount_paid;
    $newAmount = $request->paymentAmount;
    $sale = Sale::where('id', $request->sale_id)->first();
    $item = Item::where('sale_id', $sale['id'])->first();
    $user = Auth::user();

    if ($oldAmount >= $newAmount) {
      $totalAmount = $oldAmount - $newAmount;
      $amountToSubtract = min($totalAmount, $sale['amount_paid']); // Calculate the amount to subtract from the sale
      $amount = ($sale['amount_paid'] - $amountToSubtract < 0 ? 0 : $sale['amount_paid'] - $amountToSubtract);
      $balance = ($sale['balance_remaining'] + $amountToSubtract < 0 ? 0 : $sale['balance_remaining'] + $amountToSubtract); // Subtract the amount from the sale
      $totalAmount -= $amountToSubtract; // Subtract the amount from the payment

      Sale::where('id', $request->sale_id)->update([
        'balance_remaining' => ($balance < 0) ? 0 : $balance,
        'amount_paid' => ($amount < 0) ? 0 : $amount,
        'paid' => 0,
        'updated_at' => now(),
      ]);
    } else {
      $totalAmount = $newAmount - $oldAmount;
      $item = Item::where('sale_id', $sale['id'])->first();
      $amt = $totalAmount + $sale['amount_paid'];
      $amount = $amt >= $sale['total'] ? $sale['total'] : $amt;
      $balance = $amt >= $sale['total'] ? 0 : $sale['total'] - $amt;
      $paid = $balance == 0 ? 1 : 0;
      $totalAmount -= $totalAmount > ($sale['total'] - $sale['amount_paid']) ? ($sale['total'] - $sale['amount_paid']) : $totalAmount;

      Sale::where('id', $request->sale_id)->update([
        'paid' => $paid,
        'balance_remaining' => ($balance < 0 ? 0 : $balance),
        'amount_paid' => ($amount < 0 ? 0 : $amount),
        'payment_method' => $request->paymentMethod,
        'payment_account' => $request->paymentAccount,
        'notes' => $request->paidNotes,
        'updated_at' => now(),
      ]);

      if ($request->paymentAccount == "Cash on Hand") {
        $old_cash = CashOnHand::where('user_id', $user->id)->value("balance");
        CashOnHand::where('user_id', $user->id)->update([
          'balance' => $old_cash + $amount,
        ]);
      }
    }

    Payment::where('id', $request->id)->update([
      'amount_paid' => $request->paymentAmount,
      'balance_remaining' => $balance,
      'payment_method' => $request->paymentMethod,
      'payment_account' => $request->paymentAccount,
      'payment_date' => $request->paymentDate,
      'updated_at' => now(),
    ]);

    return response()->json(201);
  }

  /**
   * Show the form for editing the specified expenses.
   *
   * @param  \App\Models\Expense  $item
   * @return \Inertia\Response
   */
  public function edit($id): \Inertia\Response
  {
    $user = Auth::user();

    if (Auth::user()->role == ('ADMIN')) {
      $context = [
        'saleId' => $id,
        'saleDate' => Item::where("sale_id", $id)->pluck('sold')->first(),
        'customer' => Item::where("sale_id", $id)->pluck('customer')->first(),
        'items' => Item::where("user_id", $user->id)
          ->with(['storage:id,name,limit', 'vendor:id,vendor'])
          ->whereNull("sold")->whereNull("hold")->get(),
      ];
    } else if (Auth::user()->role == ('USER')) {
      //$usersId = User::where('store_id', @$user->store_id)->pluck('id')->toarray();
      $context = [
        'saleId' => $id,
        'saleDate' => Item::where("sale_id", $id)->pluck('sold')->first(),
        'customer' => Item::where("sale_id", $id)->pluck('sold')->first(),
        'items' => Item::where("user_id", $user->id)->with(['storage:id,name,limit', 'vendor:id,vendor'])->whereNull("sold")->whereNull("hold")->get(),
      ];
    } else {
      $context = [
        'saleId' => $id,
        'saleDate' => Item::where("sale_id", $id)->pluck('sold')->first(),
        'customer' => Item::where("sale_id", $id)->pluck('customer')->first(),
        'items' => Item::where("user_id", $user->id)->with(['storage:id,name,limit', 'vendor:id,vendor'])->whereNull("sold")->whereNull("hold")->get(),
      ];
    }

    return Inertia::render('Accounting/PaymentEdit', $context);
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
      $item["amount_paid"] = $item["amount_paid"] > $item['total'] ? $item["total"] : $item["amount_paid"];
      $item["balance_remaining"] = $item["amount_paid"] >= $item['total'] ? 0 : $item['total'] - $item["amount_paid"];
      $total = str_replace(",", "", $item['total']);
      $update_item = Item::where('sale_id', $item['id'])->update(
        [
          'sold' => $item['date'],
          'customer' => $item['customer'],
        ]
      );
      $paid = $item["amount_paid"] < 1 ? 0 : 1;
      $object = Sale::where('id', $item["id"])->update(['amount_paid' => $item["amount_paid"], 'balance_remaining' => $item["balance_remaining"], 'paid' => $paid, 'total' => $total]);
      $updated[] = $object;
    }

    return response()->json($updated);
  }

  public function delete(Request $request)
  {
    try {
      foreach ($request->invoices as $invoice) {
        $sale = Sale::find($invoice['sale_id']);

        if ($sale) {
          Item::where('sale_id', $sale->id)->update(["sold" => null, "sale_id" => null]);
          $sale->delete();
        }
      }

      return response()->json($request->invoices, 200);
    } catch (Exception $e) {
      return response()->json($e->getMessage(), 404);
    }
  }


  public function addNewItems(Request $request)
  {
    $items = $request->items;

    $sale = Sale::find($request->sale_id);
    $tax = intval($sale->tax) / 100;
    $discount = $sale->discount;
    $balance = $sale->balance_remaining;
    $finalTotal = $sale->total;
    $finaFlatTax = $sale->flatTax;
    $finalSubTotal = $sale->subtotal;

    foreach ($items as $item) {
      $itemData = Item::find($item['id']);
      Item::where('id', $item['id'])->update([
        'sold' => $request->sale_date,
        'selling_price' => $item['selling_price'],
        'customer' => $request->customer,
        'profit' => $item['selling_price'] - $itemData->cost,
        'sale_id' => $request->sale_id,
        'sold_position' => $item['position'],
        'sold_storage_id' => $item['storage_id'],
        'sold_storage_name' => Storage::find($item['storage_id'])?->name,
        'position' => null,
        'storage_id' => null
      ]);

      $subtotal = $item['selling_price'] - $discount;
      $flatTax = ($subtotal * $tax);
      $total = ($subtotal + $flatTax);
      $finalSubTotal += $subtotal;
      $finaFlatTax += $flatTax;
      $finalTotal += $total;
      $balance += $total;
    }

    $paid = 0;
    if ($sale->amount_paid >= $finalTotal) {
      $paid = 1;
    }

    Sale::where('id', $request->sale_id)->update([
      'flatTax' => $finaFlatTax,
      'subtotal' => $finalSubTotal,
      'total' => $finalTotal,
      'balance_remaining' => $balance,
      'paid' => $paid,
    ]);

    return response()->json(200);
  }

  public function amountPaidBalancingSet()
  {
    $sale = Sale::all();
    foreach ($sale as $s) {
      if ($s->paid == 1) {
        Sale::where('id', $s->id)->update(['amount_paid' => $s->total, 'balance_remaining' => 0]);
      } else {
        Sale::where('id', $s->id)->update(['amount_paid' => 0, 'balance_remaining' => $s->total]);
      }
    }
    return 'success';
  }
}
