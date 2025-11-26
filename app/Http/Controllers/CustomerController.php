<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerForm;
use App\Http\Requests\MarketingEmailForm;
use Illuminate\Support\Facades\Mail;
use Parsedown;
use App\Mail\MarketingEmail;
use App\Models\Customer;
use App\Models\Item;
use App\Models\MailList;
use App\Models\Sale;
use App\Models\Contact;
use App\Models\EmailTemplate;
use App\Models\Prospect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Cache key único por usuario para evitar conflictos
        $user = Auth::user();
        $cacheKey = "customers_index_user_{$user->id}";
        
        // Cache por 20 minutos (1200 segundos)
        $customers = Cache::remember($cacheKey, 1200, function () {
            $startDate = now()->subYear()->startOfDay();
            $endDate = now()->endOfDay();

            // Approach compatible with strict MySQL - using subqueries
            return Customer::select([
                    'customers.id', 
                    'customers.customer', 
                    'customers.first_name', 
                    'customers.last_name', 
                    'customers.email', 
                    'customers.phone', 
                    'customers.phone_optional',
                    'customers.credit',
                    'customers.account_number',
                    'customers.website',
                    'customers.notes',
                    'customers.currency',
                    'customers.billing_address',
                    'customers.billing_address_optional',
                    'customers.billing_address_country',
                    'customers.billing_address_state',
                    'customers.billing_address_city',
                    'customers.billing_address_postal',
                    'customers.ship_name',
                    'customers.shipping_address',
                    'customers.shipping_address_optional',
                    'customers.shipping_address_country',
                    'customers.shipping_address_state',
                    'customers.shipping_address_city',
                    'customers.shipping_address_postal',
                    'customers.shipping_phone',
                    'customers.delivery_instructions'
                ])
                ->selectSub(function($query) use ($startDate, $endDate) {
                    $query->selectRaw('COALESCE(SUM(items.selling_price + (items.selling_price * COALESCE(sales.tax, 0) / 100)), 0)')
                          ->from('items')
                          ->join('sales', 'items.sale_id', '=', 'sales.id')
                          ->whereRaw('(items.customer = customers.customer OR items.customer = customers.id)')
                          ->whereBetween('sales.created_at', [$startDate, $endDate]);
                }, 'revenue')
                ->selectSub(function($query) use ($startDate, $endDate) {
                    $query->selectRaw('COALESCE(SUM((items.selling_price + (items.selling_price * COALESCE(sales.tax, 0) / 100)) - COALESCE(items.cost, 0)), 0)')
                          ->from('items')
                          ->join('sales', 'items.sale_id', '=', 'sales.id')
                          ->whereRaw('(items.customer = customers.customer OR items.customer = customers.id)')
                          ->whereBetween('sales.created_at', [$startDate, $endDate]);
                }, 'profit')
                ->selectSub(function($query) use ($startDate, $endDate) {
                    $query->selectRaw('COALESCE(SUM(DISTINCT sales.balance_remaining), 0)')
                          ->from('sales')
                          ->whereExists(function($subQuery) {
                              $subQuery->selectRaw('1')
                                       ->from('items')
                                       ->whereRaw('items.sale_id = sales.id')
                                       ->whereRaw('(items.customer = customers.customer OR items.customer = customers.id)');
                          })
                          ->whereBetween('sales.created_at', [$startDate, $endDate]);
                }, 'balance')
                ->get()
                ->map(function ($customer) {
                    // Calculate margin
                    $revenue = (float) $customer->revenue;
                    $profit = (float) $customer->profit;
                    $customer->margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

                    // Ensure non-negative values
                    $customer->revenue = max(0, $revenue);
                    $customer->profit = max(0, $profit);
                    $customer->margin = max(0, $customer->margin);
                    $customer->balance = max(0, (float) $customer->balance);
                    $customer->credit = (float) $customer->credit;

                    // Process names efficiently
                    if (is_array($customer->first_name)) {
                        $names = [];
                        foreach ($customer->first_name as $key => $fname) {
                            $lastName = $customer->last_name[$key] ?? '';
                            $fullName = trim("$fname $lastName");
                            if ($fullName !== '') {
                                $names[] = $fullName;
                            }
                        }
                        $customer->name = implode(', ', $names);
                    } else {
                        $customer->name = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
                    }

                    // Process contact info
                    $customer->email = is_array($customer->email) ? implode(", ", $customer->email) : ($customer->email ?? '');
                    $customer->phone = is_array($customer->phone) ? implode(", ", $customer->phone) : ($customer->phone ?? '');

                    return $customer;
                });
        });
        
        return Inertia::render('Customers/Index', compact('customers'));
    }

    public function mailingList()
    {
        return Inertia::render('Customers/MailList');
    }

    public function emailEditor()
    {
        return Inertia::render('Customers/EmailEditor');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return Inertia::render('Customers/CreateEdit');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CustomerForm $request)
    {
        $form = $request->validated();
        
        // Log the incoming data
        \Log::info('Customer Store - Incoming Request Data:', [
            'form' => $form,
            'all_request' => $request->all()
        ]);

        $personal_phone_optional = $form["personal_phone_optional"] ?? [];
        foreach ($personal_phone_optional as $key => $value) {
            if ($value == null) {
                $personal_phone_optional[$key] = [];
            }
        }
        $customer = new Customer();
        $customer->customer = $form["customer_name"];
        $customer->user_id = Auth::id();
        $customer->company_id = Auth::user()->company_id;
        $customer->first_name = $form["first_name"] ?? null;
        $customer->last_name = $form["last_name"] ?? null;
        $customer->email = $form["email"] ?? null;
        $customer->phone = $form["personal_phone"] ?? null;
        $customer->phone_optional = $personal_phone_optional;
        $customer->account_number = $form["accnumber"] ?? null;
        $customer->website = $form["website"] ?? null;
        $customer->notes = $form["note"] ?? null;
        $customer->currency = $form["billing_currency"] ?? 'CAD';
        $customer->billing_address = $form["billing_address"] ?? null;
        $customer->billing_address_optional = $form["billing_address_optional"] ?? [];
        $customer->billing_address_country = $form["billing_country"] ?? null;
        $customer->billing_address_state = $form["billing_state"] ?? null;
        $customer->billing_address_city = $form["billing_city"] ?? null;
        $customer->billing_address_postal = $form["billing_postal_code"] ?? null;
        $customer->ship_name = $form["shipto"] ?? null;
        $customer->shipping_address = $form["shipping_address"] ?? null;
        $customer->shipping_address_optional = $form["shipping_address_optional"] ?? [];
        $customer->shipping_address_country = $form["shipping_country"] ?? null;
        $customer->shipping_address_state = $form["shipping_state"] ?? null;
        $customer->shipping_address_city = $form["shipping_city"] ?? null;
        $customer->shipping_address_postal = $form["shipping_postal_code"] ?? null;
        $customer->shipping_phone = $form["shipping_phone"] ?? null;
        $customer->delivery_instructions = $form["shipping_delivery_instructions"] ?? null;
        $customer->credit = $form["credit"] ?? 0;
        
        // Log before saving
        \Log::info('Customer Before Save:', $customer->toArray());
        
        $customer->save();
        
        // Log after saving
        \Log::info('Customer After Save:', $customer->toArray());

        if (is_array($customer->email) && count($customer->email) > 0) {
            $contact = new Contact();
            $contact->name = $form["customer_name"];
            $contact->email = $customer->email[0];
            $contact->type = 1;
            $contact->user_id = Auth::user()->id;
            $contact->customer_id = $customer->id;
            $contact->save();
        }

        return response()->json($customer, 201);
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
    public function edit(Customer $customer)
    {
        return Inertia::render('Customers/CreateEdit', [
            "customerEdit" => $customer
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(CustomerForm $request, Customer $customer)
    {
        try {
            $form = $request->validated();
            
            // Log incoming data
            \Log::info('Customer Update - Incoming Request Data:', [
                'form' => $form,
                'all_request' => $request->all()
            ]);
            
            $personal_phone_optional = $form["personal_phone_optional"] ?? [];
            foreach ($personal_phone_optional as $key => $value) {
                if ($value == null) {
                    $personal_phone_optional[$key] = [];
                }
            }
            $customer_data = array(
                'customer' => $form["customer_name"],
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id,
                'first_name' => $form["first_name"] ?? null,
                'last_name' => $form["last_name"] ?? null,
                'email' => $form["email"] ?? null,
                'phone' => $form["personal_phone"] ?? null,
                'phone_optional' => $personal_phone_optional,
                'account_number' => $form["accnumber"] ?? null,
                'website' => $form["website"] ?? null,
                'notes' => $form["note"] ?? null,
                'currency' => $form["billing_currency"] ?? 'CAD',
                'credit' => $form["credit"] ?? 0,
                'billing_address' => $form["billing_address"] ?? null,
                'billing_address_optional' => $form["billing_address_optional"] ?? [],
                'billing_address_country' => $form["billing_country"] ?? null,
                'billing_address_state' => $form["billing_state"] ?? null,
                'billing_address_city' => $form["billing_city"] ?? null,
                'billing_address_postal' => $form["billing_postal_code"] ?? null,
                'ship_name' => $form["shipto"] ?? null,
                'shipping_address' => $form["shipping_address"] ?? null,
                'shipping_address_optional' => $form["shipping_address_optional"] ?? [],
                'shipping_address_country' => $form["shipping_country"] ?? null,
                'shipping_address_state' => $form["shipping_state"] ?? null,
                'shipping_address_city' => $form["shipping_city"] ?? null,
                'shipping_address_postal' => $form["shipping_postal_code"] ?? null,
                'shipping_phone' => $form["shipping_phone"] ?? null,
                'delivery_instructions' => $form["shipping_delivery_instructions"] ?? null
            );
            
            // Log the data array before update
            \Log::info('Customer Update - Data to Update:', $customer_data);

            if ($customer->update($customer_data)) {
                // Log after update
                \Log::info('Customer After Update:', $customer->fresh()->toArray());
                
                // Invalidar cache de customers
                $user = Auth::user();
                Cache::forget("customers_index_user_{$user->id}");
                
                return response()->json($customer, 200);
            } else {
                \Log::error('Customer Update Failed - No rows updated');
                return response()->json('', 500);
            }
        } catch (Exception $e) {
            \Log::error('Customer Update Error:', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Customer $customer)
    {
        if ($customer->delete()) {
            Contact::deleteContactsByCustomer($customer->id);
            
            // Invalidar cache de customers
            $user = Auth::user();
            Cache::forget("customers_index_user_{$user->id}");
            
            return response()->json('OK', 200);
        } else {
            return response()->json('', 500);
        }
    }

    public function customersList(Request $request)
    {
        $customer = $request->search;
        $customers = Customer::where(function ($query) use ($customer) {
            $query->where('customer', 'LIKE', '%' . $customer . '%');
            $query->orWhere('first_name', 'LIKE', '%' . $customer . '%');
            $query->orWhere('last_name', 'LIKE', '%' . $customer . '%');
        })->select('id', 'customer', 'first_name', 'last_name', 'credit')->get();
        foreach ($customers as $customer) {
        // Check if the arrays have values before accessing them
        $firstName = is_array($customer->first_name) && !empty($customer->first_name) ? $customer->first_name[0] : '';
        $lastName = is_array($customer->last_name) && !empty($customer->last_name) ? $customer->last_name[0] : '';
        $customer->customer_name = $firstName . ($firstName && $lastName ? " " : "") . $lastName;
    }
        return response()->json($customers, 200);
    }

    public function datewise(Request $request)
    {
        try {
            $startDate = $request->startDate;
            $endDate = $request->endDate;
            $start = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
            $end = Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay();

            // Optimized query using subqueries like the index method
            $customers = Customer::select([
                    'customers.id', 
                    'customers.customer', 
                    'customers.first_name', 
                    'customers.last_name', 
                    'customers.email', 
                    'customers.phone', 
                    'customers.phone_optional',
                    'customers.credit',
                    'customers.account_number',
                    'customers.website',
                    'customers.notes',
                    'customers.currency',
                    'customers.billing_address',
                    'customers.billing_address_optional',
                    'customers.billing_address_country',
                    'customers.billing_address_state',
                    'customers.billing_address_city',
                    'customers.billing_address_postal',
                    'customers.ship_name',
                    'customers.shipping_address',
                    'customers.shipping_address_optional',
                    'customers.shipping_address_country',
                    'customers.shipping_address_state',
                    'customers.shipping_address_city',
                    'customers.shipping_address_postal',
                    'customers.shipping_phone',
                    'customers.delivery_instructions'
                ])
                ->selectSub(function($query) use ($start, $end) {
                    $query->selectRaw('COALESCE(SUM(items.selling_price + (items.selling_price * COALESCE(sales.tax, 0) / 100)), 0)')
                          ->from('items')
                          ->join('sales', 'items.sale_id', '=', 'sales.id')
                          ->whereRaw('(items.customer = customers.customer OR items.customer = customers.id)')
                          ->whereBetween('sales.created_at', [$start, $end]);
                }, 'revenue')
                ->selectSub(function($query) use ($start, $end) {
                    $query->selectRaw('COALESCE(SUM((items.selling_price + (items.selling_price * COALESCE(sales.tax, 0) / 100)) - COALESCE(items.cost, 0)), 0)')
                          ->from('items')
                          ->join('sales', 'items.sale_id', '=', 'sales.id')
                          ->whereRaw('(items.customer = customers.customer OR items.customer = customers.id)')
                          ->whereBetween('sales.created_at', [$start, $end]);
                }, 'profit')
                ->selectSub(function($query) use ($start, $end) {
                    $query->selectRaw('COALESCE(SUM(DISTINCT sales.balance_remaining), 0)')
                          ->from('sales')
                          ->whereExists(function($subQuery) {
                              $subQuery->selectRaw('1')
                                       ->from('items')
                                       ->whereRaw('items.sale_id = sales.id')
                                       ->whereRaw('(items.customer = customers.customer OR items.customer = customers.id)');
                          })
                          ->whereBetween('sales.created_at', [$start, $end]);
                }, 'balance')
                ->get()
                ->map(function ($customer) {
                    // Calculate margin
                    $revenue = (float) $customer->revenue;
                    $profit = (float) $customer->profit;
                    $customer->margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

                    // Ensure non-negative values
                    $customer->revenue = max(0, $revenue);
                    $customer->profit = max(0, $profit);
                    $customer->margin = max(0, $customer->margin);
                    $customer->balance = max(0, (float) $customer->balance);
                    $customer->credit = (float) $customer->credit;

                    // Process names efficiently
                    if (is_array($customer->first_name)) {
                        $names = [];
                        foreach ($customer->first_name as $key => $fname) {
                            $lastName = $customer->last_name[$key] ?? '';
                            $fullName = trim("$fname $lastName");
                            if ($fullName !== '') {
                                $names[] = $fullName;
                            }
                        }
                        $customer->name = implode(', ', $names);
                    } else {
                        $customer->name = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
                    }

                    // Process contact info
                    $customer->email = is_array($customer->email) ? implode(", ", $customer->email) : ($customer->email ?? '');
                    $customer->phone = is_array($customer->phone) ? implode(", ", $customer->phone) : ($customer->phone ?? '');

                    return $customer;
                });
        
            return response()->json($customers, 200);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    public function marketingEmail(Request $request)
    {
        $selectedContacts = $request->input('selectedContacts');
        if ($selectedContacts) {
            $selectedContacts = json_decode($selectedContacts, true);
        }

        $customers = Customer::where('user_id', Auth::id())->where('email', '!=', '[null]')->get();
        $contacts = Contact::where('user_id', Auth::user()->id)->get();
        $mail_lists = MailList::where('user_id', Auth::id())->get();
        $email_templates = EmailTemplate::where('user_id', Auth::id())->get();

        return Inertia::render('Customers/EmailSender', [
            "customers" => $customers,
            "contacts" => $contacts,
            "email_templates" => $email_templates,
            "mail_lists" => $mail_lists,
            "selectedContacts" => $selectedContacts,
        ]);
    }


    public function sendMarketingEmail(MarketingEmailForm $request)
    {
        try {
            $validatedData = $request->validated();
            $destiny = json_decode($validatedData['destiny'], true);
            $subject = $validatedData['subject'];
            $content = $validatedData['content'];

            $user = Auth::user();
            foreach ($destiny as $recipient) {
                $names = $recipient['name'];
                $emails = $recipient['email'];

                if (is_array($names)) {
                    $n = 0;
                    foreach ($names as $name) {
                        $mail = new MarketingEmail($subject, $content, $name, $user);

                        if ($request->hasFile('attachments')) {
                            foreach ($request->file('attachments') as $attachment) {
                                $mail->attach($attachment->getRealPath(), [
                                    'as' => $attachment->getClientOriginalName(),
                                    'mime' => $attachment->getClientMimeType(),
                                ]);
                            }
                        }

                        $email = $emails[$n];
                        Mail::to($email)->send($mail);
                        $n++;
                    }
                } else {
                    $mail = new MarketingEmail($subject, $content, $names, $user);

                    if ($request->hasFile('attachments')) {
                        foreach ($request->file('attachments') as $attachment) {
                            $mail->attach($attachment->getRealPath(), [
                                'as' => $attachment->getClientOriginalName(),
                                'mime' => $attachment->getClientMimeType(),
                            ]);
                        }
                    }
                    Mail::to($emails)->send($mail);
                }
            }

            return response()->json($mail, 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getByName(Request $request, $name)
    {
        $customer = Customer::where('customer', $name)->first();

        if ($customer) {
            return response()->json($customer, 200);
        } else {
            return response()->json(['error' => 'Customer not found'], 404);
        }
    }
}
