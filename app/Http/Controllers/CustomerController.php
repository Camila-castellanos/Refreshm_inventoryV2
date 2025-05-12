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
        $customers = Customer::whereUserId(Auth::user()->id)->get();
        foreach ($customers as $customer) {
            $items = Item::whereCustomer($customer->customer)->whereUserId(Auth::user()->id)->get();
            $sale_pks = $items->map(function ($item) {
                return $item->sale_id;
            })->toArray();

            $total = [];
            $profit = [];
            $balance = [];
            $sales = Sale::whereIn("id", $sale_pks)
                ->whereBetween('created_at', [now()->subYear()->startOfDay(), now()->endOfDay()])
                ->with('items') 
                ->get();
            foreach ($sales as $sale) {
                $tax = intval($sale->tax) / 100;
                $balance[] = $sale->balance_remaining;
                foreach ($sale->items as $item) {
                    if (isset($item)) {
                        $selling_price = $item->selling_price + $item->selling_price * $tax;
                        $total[] = $selling_price;
                        $profit[] = (float) $selling_price - (float) $item->cost;
                    }
                }
            }
            $total = array_sum($total);
            $profit = array_sum($profit);
            if ($profit != 0) {
                $margin = ($profit / $total) * 100;
            } else {
                $margin = 0;
            }

            $balance = array_sum($balance);
            $customer->revenue = $total < 0 ? 0 : $total;
            $customer->profit = $profit < 0 ? 0 : $profit;
            $customer->margin = $margin < 0 ? 0 : $margin;
            $customer->balance = $balance < 0 ? 0 : $balance;
            $customer->credit = (float) $customer->credit;

            if (is_array($customer->first_name)) {
    foreach ($customer->first_name as $key => $fname) {
        $last = $customer->last_name[$key] ?? '';       // si no existe, cadena vacía
        $full = trim("$fname $last");
        if ($full !== '') {
            $customer->name .= $full . ', ';
        }
    }
}

            $customer->email = implode(", ", $customer->email);
            $customer->phone = implode(", ", $customer->phone);
        }
        
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

        $personal_phone_optional = $request->personal_phone_optional;
        foreach ($personal_phone_optional as $key => $value) {
            if ($value == null) {
                $personal_phone_optional[$key] = [];
            }
        }
        $customer = new Customer();
        $customer->customer = $form["customer_name"];
        $customer->user_id = Auth::id();
        $customer->first_name = $form["first_name"];
        $customer->last_name = $form["last_name"];
        $customer->email = $form["email"];
        $customer->phone = $form["personal_phone"];
        $customer->phone_optional = $personal_phone_optional;
        $customer->account_number = $request->accnumber;
        $customer->website = $request->website;
        $customer->notes = $request->note;
        $customer->currency = $request->billing_currency;
        $customer->billing_address = $request->billing_address;
        $customer->billing_address_optional = $request->billing_address_optional;
        $customer->billing_address_country = $request->billing_country;
        $customer->billing_address_state = $request->billing_state;
        $customer->billing_address_city = $request->billing_city;
        $customer->billing_address_postal = $request->billing_postal_code;
        $customer->ship_name = $request->shipto;
        $customer->shipping_address = $request->shipping_address;
        $customer->shipping_address_optional = $request->shipping_address_optional;
        $customer->shipping_address_country = $request->shipping_country;
        $customer->shipping_address_state = $request->shipping_state;
        $customer->shipping_address_city = $request->shipping_city;
        $customer->shipping_address_postal = $request->shipping_postal_code;
        $customer->shipping_phone = $request->shipping_phone;
        $customer->delivery_instructions = $request->shipping_delivery_instructions;
        $customer->credit = $request->credit;
        $customer->save();

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
            $personal_phone_optional = $request->personal_phone_optional;
            foreach ($personal_phone_optional as $key => $value) {
                if ($value == null) {
                    $personal_phone_optional[$key] = [];
                }
            }
            $customer_data = array(
                'customer' => $form["customer_name"],
                'user_id' => Auth::id(),
                'first_name' => $form["first_name"],
                'last_name' => $form["last_name"],
                'email' => $form["email"],
                'phone' => $form["personal_phone"],
                'phone_optional' => $personal_phone_optional,
                'account_number' => $request->accnumber,
                'website' => $request->website,
                'notes' => $request->note,
                'currency' => $request->billing_currency,
                'credit' => $form["credit"],
                'billing_address' => $request->billing_address,
                'billing_address_optional' => $request->billing_address_optional,
                'billing_address_country' => $request->billing_country,
                'billing_address_state' => $request->billing_state,
                'billing_address_city' => $request->billing_city,
                'billing_address_postal' => $request->billing_postal_code,
                'ship_name' => $request->shipto,
                'shipping_address' => $request->shipping_address,
                'shipping_address_optional' => $request->shipping_address_optional,
                'shipping_address_country' => $request->shipping_country,
                'shipping_address_state' => $request->shipping_state,
                'shipping_address_city' => $request->shipping_city,
                'shipping_address_postal' => $request->shipping_postal_code,
                'shipping_phone' => $request->shipping_phone,
                'delivery_instructions' => $request->shipping_delivery_instructions
            );

            if ($customer->update($customer_data)) {
                return response()->json($customer, 200);
            } else {
                return response()->json('', 500);
            }
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
    public function destroy(Customer $customer)
    {
        if ($customer->delete()) {
            Contact::deleteContactsByCustomer($customer->id);
            return response()->json('OK', 200);
        } else {
            return response()->json('', 500);
        }
    }

    public function customersList(Request $request)
    {
        $customer = $request->search;
        $customers = Customer::whereUserId(Auth::id())->where(function ($query) use ($customer) {
            $query->where('customer', 'LIKE', '%' . $customer . '%');
            $query->orWhere('first_name', 'LIKE', '%' . $customer . '%');
            $query->orWhere('last_name', 'LIKE', '%' . $customer . '%');
        })->select('id', 'customer', 'first_name', 'last_name', 'credit')->get();
        Log::info($customers);
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
            $customers = Customer::whereUserId(Auth::id())->get();
            $startDate = $request->startDate;
            $endDate = $request->endDate;

            $start = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
            $end = Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay();

            foreach ($customers as $customer) {
                $items = Item::whereCustomer($customer->id)->whereUserId(Auth::id())->get();
                $sale_pks = $items->map(function ($item) {
                    return $item->sale_id;
                })->toArray();

                $total = [];
                $profit = [];
                $balance = [];
                $sales = Sale::whereIn("id", $sale_pks)->whereDate('date', '>=', $start)->whereDate('date', '<=', $end)->get();
                foreach ($sales as $sale) {
                    $tax = intval($sale->tax) / 100;
                    $balance[] = $sale->balance_remaining;
                    foreach ($sale->items as $item) {
                        if (isset($item)) {
                            $selling_price = $item->selling_price + $item->selling_price * $tax;
                            $total[] = $selling_price;
                            $profit[] = (float) $selling_price - (float) $item->cost;
                        }
                    }
                }
                $total = array_sum($total);
                $profit = array_sum($profit);
                if ($profit != 0) {
                    $margin = ($profit / $total) * 100;
                } else {
                    $margin = 0;
                }

                $balance = array_sum($balance);
                $customer->revenue = $total < 0 ? 0 : $total;
                $customer->profit = $profit < 0 ? 0 : $profit;
                $customer->margin = $margin < 0 ? 0 : $margin;
                $customer->balance = $balance < 0 ? 0 : $balance;
                $customer->credit = (float) $customer->credit;

                       if (is_array($customer->first_name)) {
    foreach ($customer->first_name as $key => $fname) {
        $last = $customer->last_name[$key] ?? '';       // si no existe, cadena vacía
        $full = trim("$fname $last");
        if ($full !== '') {
            $customer->name .= $full . ', ';
        }
    }
}

                $customer->email = implode(", ", $customer->email);
                $customer->phone = implode(", ", $customer->phone);
            }
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
}
