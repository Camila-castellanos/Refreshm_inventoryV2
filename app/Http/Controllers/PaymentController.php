<?php

namespace App\Http\Controllers;

use App\Actions\Sales\AppendItemsToSaleAction;
use App\Http\Requests\InvoiceSentForm;
use App\Http\Requests\RecordPaymentForm;
use App\Mail\InvoiceSent;
use App\Models\CashOnHand;
use App\Models\Customer;
use App\Models\EmailTemplate;
use App\Models\Item;
use App\Models\Payment;
use App\Models\ReturnItems;
use App\Models\Sale;
use App\Models\Scopes\CompanyItemScope;
use App\Models\Storage;
use App\Models\Store;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage as StorageFacade;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function __construct(
        private readonly AppendItemsToSaleAction $appendItemsToSaleAction,
    ) {}

    /**
     * Show Data Listening of Invoices Data
     */
    public function show(Request $request)
    {
        try {
            $user = Auth::user();
            $dataStatus = $request->query('status', 'all');
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');

            // validate valid status or fallback to 'all'
            if (! in_array($dataStatus, ['all', 'paid', 'unpaid'])) {
                $dataStatus = 'all';
            }

            // try to parse dates, fallback to last 2 months if invalid
            try {
                $startDate = Carbon::createFromFormat('Y-m-d', $startDate);
                $endDate = Carbon::createFromFormat('Y-m-d', $endDate);
            } catch (\Exception $e) {
                $startDate = now()->subMonths(2);
                $endDate = now();
            }

            // Unique cache key per user and status (temporarily disabled)
            // $cacheKey = "payments_list_{$user->id}_{$dataStatus}_" . now()->format('Y-m-d_H');

            // Use cache tags if available (Redis/Memcached)
            // $response = Cache::remember($cacheKey, 300, function() use ($user, $dataStatus) {
            //         return $this->getPaymentsData($user->id, $dataStatus);
            // });

            $response = $this->getPaymentsData($dataStatus, $startDate, $endDate);

            $email_templates = EmailTemplate::where('user_id', $user->id)->get();

            $context = [
                'items' => $response ?: [], // Asegurar que sea array
                'data_status' => $dataStatus,
                'email_templates' => $email_templates,
                'data_range' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
            ];

            return Inertia::render('Accounting/Payments', $context);

        } catch (\Throwable $e) {
            Log::error('Error fetching payments data: '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine());

            // No usar response()->json() con Inertia, mejor manejar el error en el frontend
            return Inertia::render('Accounting/Payments', [
                'items' => [],
                'data_status' => 'all',
                'email_templates' => collect(),
                'error' => 'Error loading payments data',
            ]);
        }
    }

    /**
     * Show Invoice For Download as PDF
     */
    public function invoice(Request $request)
    {
        $item = Item::where('id', $request->id)->first();
        $itm_id_pluck = Item::where('sale_id', $item->sale_id)->pluck('sale_id');
        if (is_numeric($item->customer)) {
            $customer = Customer::whereId($item->customer)->select('customer', 'billing_address', 'billing_address_country', 'billing_address_state', 'billing_address_city', 'billing_address_postal', 'email', 'phone')->first();
        } else {
            $customer = Customer::where('customer', $item->customer)->select('customer', 'billing_address', 'billing_address_country', 'billing_address_state', 'billing_address_city', 'billing_address_postal', 'email', 'phone')->first();
        }
        $customer = (object) [
            'customer' => $customer->customer ?? 'N/A',
            'billing_address' => $customer->billing_address ?? 'N/A',
            'billing_address_country' => $customer->billing_address_country ?? 'N/A',
            'billing_address_state' => $customer->billing_address_state ?? 'N/A',
            'billing_address_city' => $customer->billing_address_city ?? 'N/A',
            'billing_address_postal' => $customer->billing_address_postal ?? 'N/A',
            'email' => empty($customer->email) ? ['N/A'] : $customer->email,
            'phone' => empty($customer->phone) ? ['N/A'] : $customer->phone,
        ];
        Log::info('customer final para enviar el receipt,', [$customer]);
        $useId = $item->user_id;
        $user_data = User::where('id', $useId)->first();
        $store_id = $user_data->store_id;
        $user_role = $user_data->role;

        $store = Store::where('id', $store_id)->first();

        $header = null;
        $footer = null;
        $logo = null;

        if ($user_role == 'OWNER') {
            $header = $user_data->invoice_header;
            $footer = $user_data->invoice_footer;
        } else {
            if ($store) {
                $header = $store->header;
                $footer = $store->footer;
            }
        }

        // Logic for Logo Selection (User -> Company -> Store -> Default)
        $logo = null;

        // 1. User Personal Logo
        if (! is_null($user_data->invoice_logo) && StorageFacade::disk('local')->exists($user_data->invoice_logo)) {
            $logo = base64_encode(StorageFacade::disk('local')->get($user_data->invoice_logo));
        }

        // 2. Company Logo (Fallback)
        if (! $logo) {
            // Explicitly load company relationship to be sure
            $user_data->load('company');

            if ($user_data->company) {
                if ($user_data->company->logo) {
                    if (StorageFacade::disk('local')->exists($user_data->company->logo)) {
                        $logo = base64_encode(StorageFacade::disk('local')->get($user_data->company->logo));
                    }
                }
            }
        }

        // 3. Store Logo (Fallback for non-owners or if store has logo)
        if (! $logo && $store && ! is_null($store->logo) && $store->logo != '') {
            if (StorageFacade::disk('local')->exists($store->logo)) {
                $logo = base64_encode(StorageFacade::disk('local')->get($store->logo));
            } elseif (file_exists(storage_path().'/app/'.$store->logo)) {
                $logo = base64_encode(file_get_contents(storage_path().'/app/'.$store->logo));
            }
        }

        if ($item) {
            $sales = Sale::with('items')->whereIn('id', $itm_id_pluck)->get();
            $returned_items_id = ReturnItems::where('sale', $item->sale_id)->pluck('item');
            $returned_items = Item::whereIn('id', $returned_items_id)->get();
            // return view("sale-receipt-invoice",compact("sales", "header", "footer", "logo","customer"));

            $pdf = Pdf::loadView('sale-receipt-invoice', compact('sales', 'header', 'footer', 'logo', 'customer', 'returned_items'))->setOptions(['defaultFont' => 'sans-serif', 'isRemoteEnabled' => 'true'])->setPaper('a6', 'landscape');

            $customer_name = $customer->customer ?? $customer;

            // Busca primero el nombre de la compañía del usuario
            // $user_company = $user_data->company->name ?? null;

            // Si no hay compañía, busca el nombre del store
            // $store_name = $store->name ?? null;

            // Si no hay compañía ni store, usa el nombre del cliente
            $invoice_ref_name = $customer_name;

            $filename = $invoice_ref_name.' Invoice '.'#'.$sales[0]->id.'.pdf';

            return $pdf->stream($filename);
        }
    }

    /**
     * View payment details
     */
    public function view(Request $request, $id)
    {
        try {
            $sale = Sale::with('items')->find($id);

            if (! $sale) {
                return response()->json(['error' => 'Sale not found'], 404);
            }

            $payments = Payment::where('sale_id', $id)->get();

            return Inertia::render('Accounting/PaymentView', [
                'sale' => $sale,
                'payments' => $payments,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error viewing payment: '.$e->getMessage());

            return response()->json(['error' => 'Error loading payment'], 500);
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
            $invoice = $this->invoice($req)->getContent();
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

            // Transition items: sold if fully paid, reserved if partial
            $saleItems = Item::where('sale_id', $item->sale_id)->get();
            foreach ($saleItems as $saleItem) {
                if ($paid == 1) {
                    // Fully paid → preserve original sale date (do not overwrite with payment date)
                    $saleItem->sold = $saleItem->sold
                        ?? $saleItem->partially_sold_at
                        ?? $sale->date
                        ?? $sale->created_at
                        ?? now();
                } else {
                    // Partial payment → keep reserved, ensure sold is cleared
                    $saleItem->sold = null;
                }
                $saleItem->save();
            }

            if ($request->paidPaymentAccount == 'Cash on Hand') {
                $old_cash = CashOnHand::where('user_id', $user->id)->value('balance');
                CashOnHand::where('user_id', $user->id)->update([
                    'balance' => $old_cash + $amount,
                ]);
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

            // Revert items to reserved (sale no longer fully paid, restore position)
            $saleItems = Item::where('sale_id', $request->sale_id)->get();
            foreach ($saleItems as $saleItem) {
                $saleItem->revertToReserved();
            }
        }

        Payment::where('id', $request->id)->delete();

        return response()->json(201);
    }

    public function editPayment(Request $request)
    {
        if (config('app.debug')) {
            Log::info('llego a traer los elementos del sale');
            Log::info([$request->all()]);
        }
        $payment = Payment::where('id', $request->id)->first();
        $oldAmount = $payment->amount_paid;
        $newAmount = $request->paymentAmount;
        $sale = Sale::where('id', $request->sale_id)->first();
        $item = Item::where('sale_id', $sale['id'])->first();
        $user = Auth::user();

        $paid = 0; // default

        if ($oldAmount >= $newAmount) {
            // Decreasing payment: sale becomes unpaid (or less paid)
            $totalAmount = $oldAmount - $newAmount;
            $amountToSubtract = min($totalAmount, $sale['amount_paid']);
            $amount = ($sale['amount_paid'] - $amountToSubtract < 0 ? 0 : $sale['amount_paid'] - $amountToSubtract);
            $balance = ($sale['balance_remaining'] + $amountToSubtract < 0 ? 0 : $sale['balance_remaining'] + $amountToSubtract);
            $totalAmount -= $amountToSubtract;
            $paid = 0;

            Sale::where('id', $request->sale_id)->update([
                'balance_remaining' => ($balance < 0) ? 0 : $balance,
                'amount_paid' => ($amount < 0) ? 0 : $amount,
                'paid' => 0,
                'updated_at' => now(),
            ]);

            // Revert items to reserved (sale no longer fully paid)
            $saleItems = Item::where('sale_id', $request->sale_id)->get();
            foreach ($saleItems as $saleItem) {
                $saleItem->revertToReserved();
            }
        } else {
            // Increasing payment: sale might become fully paid
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

            // Transition items based on new paid status
            $saleItems = Item::where('sale_id', $request->sale_id)->get();
            foreach ($saleItems as $saleItem) {
                if ($paid == 1) {
                    // Fully paid → preserve original sale date (do not overwrite with payment date)
                    $saleItem->sold = $saleItem->sold
                        ?? $saleItem->partially_sold_at
                        ?? $sale->date
                        ?? $sale->created_at
                        ?? now();
                } else {
                    // Still unpaid → keep reserved
                    $saleItem->sold = null;
                }
                $saleItem->save();
            }

            if ($request->paymentAccount == 'Cash on Hand') {
                $old_cash = CashOnHand::where('user_id', $user->id)->value('balance');
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
     */
    public function edit($id): \Inertia\Response
    {
        if (config('app.debug')) {
            Log::info('llego a editar el item');
            Log::info([$id]);
        }
        $user = Auth::user();

        if (Auth::user()->role == ('ADMIN')) {
            $context = [
                'saleId' => $id,
                'saleDate' => Item::where('sale_id', $id)->pluck('sold')->first(),
                'customer' => Item::where('sale_id', $id)->pluck('customer')->first(),
                'items' => Item::where('user_id', $user->id)
                    ->with(['storage:id,name,limit', 'vendor:id,vendor'])
                    ->where('status', Item::STATUS_AVAILABLE)->whereNull('hold')->get(),
            ];
        } elseif (Auth::user()->role == ('USER')) {
            // $usersId = User::where('store_id', @$user->store_id)->pluck('id')->toarray();
            $context = [
                'saleId' => $id,
                'saleDate' => Item::where('sale_id', $id)->pluck('sold')->first(),
                'customer' => Item::where('sale_id', $id)->pluck('customer')->first(),
                'items' => Item::where('user_id', $user->id)->with(['storage:id,name,limit', 'vendor:id,vendor'])->where('status', Item::STATUS_AVAILABLE)->whereNull('hold')->get(),
            ];
        } else {
            $context = [
                'saleId' => $id,
                'saleDate' => Item::where('sale_id', $id)->pluck('sold')->first(),
                'customer' => Item::where('sale_id', $id)->pluck('customer')->first(),
                'items' => Item::where('user_id', $user->id)->with(['storage:id,name,limit', 'vendor:id,vendor'])->where('status', Item::STATUS_AVAILABLE)->whereNull('hold')->get(),
            ];
        }

        return Inertia::render('Accounting/AddItems', $context);
    }

    /**
     * Update the specified expenses in storage.
     */
    public function update(Request $request): \Illuminate\Http\JsonResponse
    {
        $items = $request->input('items');

        $updated = [];
        foreach ($items as $item) {
            $item['amount_paid'] = $item['amount_paid'] > $item['total'] ? $item['total'] : $item['amount_paid'];
            $item['balance_remaining'] = $item['amount_paid'] >= $item['total'] ? 0 : $item['total'] - $item['amount_paid'];
            $total = str_replace(',', '', $item['total']);

            // Determine if the sale is fully paid
            $isFullyPaid = (float) $item['balance_remaining'] <= 0;

            // Update each item via model save so the boot hook handles status, position, and storage_id
            $saleItems = Item::where('sale_id', $item['id'])->get();
            foreach ($saleItems as $saleItem) {
                $saleItem->customer = $item['customer'];

                if ($isFullyPaid) {
                    // Fully paid → boot hook sets status='sold', clears storage_id and position
                    $saleItem->sold = $item['date'];
                } else {
                    // Not fully paid → boot hook sets status='reserved', keeps position
                    $saleItem->sold = null;
                }

                $saleItem->save();
            }

            $paid = $isFullyPaid ? 1 : 0;
            $object = Sale::where('id', $item['id'])->update(['amount_paid' => $item['amount_paid'], 'balance_remaining' => $item['balance_remaining'], 'paid' => $paid, 'total' => $total]);
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
                    // Delete the extra items associated with the sale
                    Item::where('sale_id', $sale->id)
                        ->whereNotIn('type', ['device', 'accessory'])
                        ->delete();

                    // update the normal items to remove the sale_id and sold date using removeSale() to restore location
                    $itemsToReturn = Item::where('sale_id', $sale->id)->get();
                    foreach ($itemsToReturn as $item) {
                        $item->removeSale();
                    }

                    // delete the sale
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
        $sale = Sale::findOrFail($request->sale_id);
        $items = $request->items ?? [];

        $this->appendItemsToSaleAction->execute($sale, $items);

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

    // method to get payments with a search query that were not included in the main load
    public function searchPayments(Request $request)
    {
        try {
            $user = Auth::user();
            $dataStatus = $request->query('status', 'all');
            $search = $request->query('search', '');

            // Validar status
            if (! in_array($dataStatus, ['all', 'paid', 'unpaid'])) {
                $dataStatus = 'all';
            }

            $response = $this->getPaymentsData($dataStatus, null, null, $search);

            return response()->json($response);

        } catch (\Throwable $e) {
            Log::error('Error searching missing payments: '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine());

            return response()->json(['error' => 'Error searching payments'], 500);
        }
    }

    // method to get the simple list of payments
    public function getPaymentsSimpleList(Request $request)
    {
        $search = $request->query('q', '');
        $limit = (int) $request->query('limit', 25);

        // Base query: solo ventas con al menos un ítem (cualquier status: SOLD o RESERVED).
        // El filtro `whereNotNull('sold')` se eliminó porque ocultaba ventas con items
        // únicamente RESERVED (p. ej. AddItemsToSale y AppendIncomingRequestToSale).
        $query = Sale::with('items')
            ->has('items');

        // Si viene término de búsqueda, aplicamos filtro en date o en customer del primer ítem
        if ($search !== '') {
            $query->where(function ($q2) use ($search) {
                $q2->where('date', 'like', "%{$search}%")
                    ->orWhereHas('items', fn ($q3) => $q3->where('customer', 'like', "%{$search}%")
                    );
            });
        }

        // Ejecutamos con orden y límite
        $sales = $query
            ->orderByDesc('date')
            ->take($limit)
            ->get();

        // Mapeamos al formato simple
        $list = $sales->map(function (Sale $sale) {
            $firstItem = $sale->items->first();
            $raw = $firstItem->customer;
            $customer = is_numeric($raw) && ($cust = Customer::find($raw))
                         ? $cust->customer
                         : (string) $raw;

            return [
                'sale_id' => $sale->id,
                'date' => $sale->date,
                'customer' => $customer,
                'total' => $sale->total,
            ];
        });

        return response()->json($list);
    }

    // optimized helper to get payments data
    private function getPaymentsData($dataStatus, $startDate = null, $endDate = null, $search = null)
    {
        // Consulta inicial por ventas (más eficiente)
        $salesQuery = Sale::query()
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()]);
            })
            ->when($dataStatus === 'paid', fn ($q) => $q->where('paid', 1))
            ->when($dataStatus === 'unpaid', fn ($q) => $q->where('paid', 0))
            ->orderByDesc('created_at');

        // optional search filter
        if ($search && $search !== '') {
            $searchableFields = ['date', 'total', 'amount_paid', 'balance_remaining'];
            $salesQuery->where(function ($q2) use ($search, $searchableFields) {
                foreach ($searchableFields as $field) {
                    $q2->orWhere($field, 'like', "%{$search}%");
                }
                $q2->orWhereHas('items', function ($q3) use ($search) {
                    $q3->where('customer', 'like', "%{$search}%");
                });
            });
        }

        $sales = $salesQuery->get();

        if ($sales->isEmpty()) {
            return [];
        }

        // Determine if the current user has org-wide accounting visibility.
        // If so, Item queries must bypass CompanyItemScope (which checks Inventory
        // permissions) so that sold items belonging to other users are not filtered out.
        $authUser = Auth::user();
        $authPerms = $authUser->page_permissions ?? [];
        $hasOrgAccounting = $authUser->role === 'OWNER'
            || (
                isset($authPerms['Accounting'])
                && is_array($authPerms['Accounting'])
                && in_array('View Organization Data', $authPerms['Accounting'])
            );

        // Obtener solo los IDs de ventas
        $saleIds = $sales->pluck('id')->toArray();

        // Verificar qué ventas tienen items (sold O reserved para unpaid)
        $soldItemsQuery = Item::whereIn('sale_id', $saleIds)
            ->whereIn('status', [Item::STATUS_SOLD, Item::STATUS_RESERVED]);
        if ($hasOrgAccounting) {
            $soldItemsQuery->withoutGlobalScope(CompanyItemScope::class);
        }
        $salesWithSoldItems = $soldItemsQuery->pluck('sale_id')->unique()->toArray();

        // Filtrar ventas para mantener solo las que tienen items vendidos
        $validSales = $sales->filter(function ($sale) use ($salesWithSoldItems) {
            return in_array($sale->id, $salesWithSoldItems);
        });

        if ($validSales->isEmpty()) {
            return [];
        }

        // Obtener los primeros items (sold o reserved) para cada venta válida
        // Ordena sold primero (con fecha), reserved al final
        $firstItemsQuery = Item::whereIn('sale_id', $validSales->pluck('id')->toArray())
            ->whereIn('status', [Item::STATUS_SOLD, Item::STATUS_RESERVED])
            ->orderBy('sold', 'desc')
            ->select('id', 'sale_id', 'sold', 'partially_sold_at', 'customer', 'status');
        if ($hasOrgAccounting) {
            $firstItemsQuery->withoutGlobalScope(CompanyItemScope::class);
        }
        $firstItems = $firstItemsQuery->get()->groupBy('sale_id');

        // Obtener todos los customers necesarios de una vez
        $customerNames = $firstItems->pluck('*')->flatten()->pluck('customer')->unique()->filter();
        $customers = Customer::whereIn('customer', $customerNames)
            ->select('customer', 'email', 'credit', 'id')
            ->get()
            ->keyBy('customer');

        // Obtener payments en batch
        $payments = Payment::whereIn('sale_id', $validSales->pluck('id')->toArray())
            ->select('sale_id', 'amount_paid', 'payment_date', 'payment_method', 'payment_account')
            ->get()
            ->groupBy('sale_id');

        $response = [];

        foreach ($validSales as $sale) {
            $itemsForThisSale = $firstItems->get($sale->id);
            if (! $itemsForThisSale || $itemsForThisSale->isEmpty()) {
                continue;
            }

            $firstItem = $itemsForThisSale->first();

            // Procesar payments
            $salePayments = collect($payments->get($sale->id, []))->map(function ($payment) {
                return array_merge($payment->toArray(), [
                    'date' => Carbon::parse($payment->payment_date)->format('F j, Y'),
                    'paid' => '$'.number_format($payment->amount_paid, 2),
                ]);
            })->toArray();

            // Obtener customer info (lógica mejorada)
            $customer = $firstItem->customer;
            $customer_emails = null;
            $customer_credit = 0;
            $customer_id = null;

            // Si el customer es numérico, buscar directamente en la BD por ID
            if (is_numeric($customer)) {
                $customerRecord = Customer::whereId($customer)
                    ->select('customer', 'email', 'credit', 'id')
                    ->first();

                if ($customerRecord) {
                    $customer = $customerRecord->customer;
                    $customer_emails = $customerRecord->email;
                    $customer_credit = $customerRecord->credit;
                    $customer_id = $customerRecord->id;
                } else {
                    $customer = $firstItem->customer; // Mantener el ID si no se encuentra
                }
            } else {
                // Si es texto, buscar en la colección de customers
                $customerRecord = $customers->get($firstItem->customer);
                if ($customerRecord) {
                    $customer = $customerRecord->customer;
                    $customer_emails = $customerRecord->email;
                    $customer_credit = $customerRecord->credit;
                    $customer_id = $customerRecord->id;
                }
            }

            // Obtener returned items (como en original)
            $returned_items = [];
            if ($customer_id) {
                $returns = ReturnItems::where('customer', $customer_id)
                    ->where('requested', 0)
                    ->pluck('item');

                if ($returns->isNotEmpty()) {
                    $returned_items = Item::whereIn('id', $returns)->get()->toArray();
                }
            }

            // Obtener credited items (como en original)
            $credited_items = [];
            $credited = ReturnItems::where('customer', $customer_id)
                ->where('requested', 1)
                ->where('sale', $sale->id)
                ->select('model', 'imei', 'id', 'credit')
                ->get();

            foreach ($credited as $creditedItem) {
                $creditedItem->selling_price = $creditedItem->credit;
                $credited_items[] = $creditedItem;
            }

            // Formatear la respuesta.
            // The primary date source is `sales.date` (the user-picked payment date
            // in the form). This aligns the Payments page with the simpleList
            // endpoint (which already uses `sales.date`). Fallback chain for
            // edge cases: items.sold (when the item is SOLD), partially_sold_at
            // (when the item is RESERVED), then sales.created_at.
            $sold = Carbon::parse($sale->date ?? $firstItem->sold ?? $firstItem->partially_sold_at ?? $sale->created_at);

            // Calculate the correct balance_remaining considering credit and verify data integrity
            $sale_credit = (float) ($sale->credit ?? 0);
            $calculated_balance = max(0, $sale->total - $sale->amount_paid);
            $current_balance = max(0, $sale->balance_remaining);

            // If calculated balance differs from stored balance, update the sale
            if (abs($calculated_balance - $current_balance) > 0.01) { // Using 0.01 for floating point comparison
                $updated_paid_status = ($calculated_balance == 0) ? 1 : 0;

                Sale::where('id', $sale->id)->update([
                    'balance_remaining' => $calculated_balance,
                    'paid' => $updated_paid_status,
                    'updated_at' => now(),
                ]);

                // Use the corrected values in response
                $final_balance = $calculated_balance;
                $final_paid_status = $updated_paid_status;
            } else {
                // Use existing values
                $final_balance = $current_balance;
                $final_paid_status = $sale->paid;
            }

            $response[] = [
                'id' => $firstItem->id,
                'date' => $sold->format('Y-m-d'),
                'customer' => $customer,
                'returned_items' => $returned_items,
                'credited_items' => $credited_items,
                'customer_id' => $customer_id,
                'customer_credit' => (float) $customer_credit,
                'customer_email' => $customer_emails ? $customer_emails : null,
                'credit' => $sale_credit,
                'total' => $sale->total,
                'amount_paid' => max(0, $sale->amount_paid),
                'balance_remaining' => $final_balance,
                'status' => $final_paid_status == 1 ? 'Paid' : 'Unpaid',
                'payments' => $salePayments,
                'sale_id' => $sale->id,
                'payment_method' => $sale->payment_method,
                'payment_account' => $sale->payment_account,
                'tax' => $sale->tax,
                'tax_id' => $sale->tax_id,
                'discount' => $sale->discount,
                'notes' => $sale->notes,
                'sale_date' => $sold->format('d/m/y'),
            ];

            // Liberar memoria
            if (count($response) % 50 === 0) {
                gc_collect_cycles();
            }
        }

        // Ordenar por fecha (como en original)
        usort($response, function ($a, $b) {
            return $b['date'] <=> $a['date'];
        });

        return $response;
    }

    private function resolveCustomerNameFromObject($customer)
    {
        if (! $customer) {
            return null;
        }

        if (! empty($customer->customer)) {
            return $customer->customer;
        }

        if (! empty($customer->first_name) || ! empty($customer->last_name)) {
            return trim($customer->first_name.' '.$customer->last_name);
        }

        return 'Unknown Customer';
    }
}
