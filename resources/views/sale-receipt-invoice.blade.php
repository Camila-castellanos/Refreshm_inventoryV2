<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">


    <!-- Styles -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <style>
        @page {
            size: A4 portrait;
        }
    </style>
</head>

<body class="font-sans antialiased" style="font-size: 14px;">

    @php
        // Fetch user-specific printable invoice active fields or default config
        $defaults = config('invoice.default_fields');
        $userActiveFields = auth()->user()->printable_invoice_fields ?? $defaults;

        $subtotal = 0;
        $discount = 0;
        $flatTax = 0;
        $total = 0;
        $credit = 0;
    @endphp
    @php
        // Calculate summary totals upfront
        $subtotal = 0;
        $flatTax = 0;
        $total = 0;
        $credit = 0;
        foreach ($sales as $sale) {
            $subtotal += $sale->subtotal;
            $flatTax  += $sale->flatTax;
            $total    += $sale->total;
        }
        foreach ($returned_items as $item) {
            $credit += $item['selling_price'];
        }
    @endphp
    <div class="row m-0">
        <div class="col-12">
            <table width="100%">
                <tbody style="margin:20px 0 !important;">
                    <tr>
                        {{-- Logo cell --}}
                        @if(in_array('logo', $userActiveFields) && isset($logo))
                        <td class="text-left" style="width:30%;padding:20px 0;">
                            <img src="data:image/png;base64,{{ $logo }}" alt="" width="100%" style="max-width: 200px;" />
                        </td>
                        @endif
                        {{-- Invoice title always visible --}}
                        <td class="text-right" style="width:70%;padding:20px 0;"><h1>INVOICE</h1></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    @if(in_array('header', $userActiveFields) && isset($header))
        <div class="text-right row m-0">
            <div class="col-12">
                {!! $header !!}
            </div>
        </div>
    @endisset

    <hr />

    <table width="100%" style="border-bottom:1px solid #a9a9a921;">
        <tbody style="margin:20px 0 !important;">
            <tr>
                @if(in_array('billing_address', $userActiveFields))
                <td class="text-left" style="width:70%;padding:20px 0; ">
                    <p class="mb-1"><strong>Bill To</strong></p>
                    @if(isset($customer->billing_address))
                        <p class="mb-2">{{$customer->customer}}</p>
                        <p style="margin: 0">{{$customer->billing_address}}</p>
                        <p style="margin: 0">{{$customer->billing_address_city}}, {{$customer->billing_address_state}}, {{$customer->billing_address_postal}}</p>
                        <p class="mb-2">{{$customer->billing_address_country}}</p>
                        <p style="margin: 0">{{$customer->phone[0]}}</p>
                        <p>{{$customer->email[0]}}</p>
                    @else
                        <p class="mb-2">{{$customer}}</p>
                    @endif
                </td>
                @endif
                <td class="text-right" style="width:45%; padding:20px 0;">
                    <table>
                        @if(in_array('invoice_number', $userActiveFields))
                        <tr>
                            <td><strong>Invoice Number:</strong></td>
                            <td class="text-left"><span>{{$sales[0]->id}}</span></td>
                        </tr>
                        @endif
                        @if(in_array('invoice_due', $userActiveFields))
                        <tr>
                            <td><strong>Invoice Due:</strong></td>
                            <td class="text-left">{{$sales[0]->created_at->format('F d, Y')}}</td>
                        </tr>
                        @endif
                        @if(in_array('payment_due', $userActiveFields))
                        <tr>
                            <td><strong>Payment Due:</strong></td>
                            <td class="text-left">{{$sales[0]->created_at->format('F d, Y')}}</td>
                        </tr>
                        @endif
                        @if(in_array('amount_due', $userActiveFields))
                        <tr style="background-color:#a9a9a921; border-radius:3px;">
                            <td>
                                <strong>Amount Due:</strong>
                            </td>
                            <td class="text-left">
                                @php
                                    $salestotal = array_sum(array_column($sales->toarray(),'total'));
                                    $salestotal -= (array_sum(array_column($sales->toarray(), 'credit')) + (array_sum(array_column($sales->toarray(), 'credit')) * $sales[0]->tax/100)) ;
                                @endphp
                                $ {{number_format($salestotal, 2)}}
                            </td>
                        </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </tbody>
    </table>

    @if(
        in_array('billing_address', $userActiveFields) ||
        in_array('invoice_number',   $userActiveFields) ||
        in_array('invoice_due',      $userActiveFields) ||
        in_array('payment_due',      $userActiveFields) ||
        in_array('amount_due',       $userActiveFields)
    )
        <hr />
    @endif
    @if(in_array('items', $userActiveFields))
    <table class="table table-striped pb-4">
        <thead>
            <tr>
                @if(in_array('table_device', $userActiveFields))
                    <th class="text-right" scope="col">DEVICE</th>
                @endif
                @if(in_array('table_issues', $userActiveFields))
                    <th class="text-right" scope="col">ISSUES</th>
                @endif
                @if(in_array('table_imei', $userActiveFields))
                    <th class="text-right" scope="col">IMEI</th>
                @endif
                @if(in_array('table_price', $userActiveFields))
                    <th class="text-right" scope="col">PRICE</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($sales as $sale)
            @foreach($sale->items as $item)
            <tr>
                @if(in_array('table_device', $userActiveFields))
                    <td class="text-right">{{ $item["model"] }}</td>
                @endif
                @if(in_array('table_issues', $userActiveFields))
                    <td class="text-right">{{ $item["issues"] }}</td>
                @endif
                @if(in_array('table_imei', $userActiveFields))
                    <td class="text-right">{{ $item["imei"] }}</td>
                @endif
                @if(in_array('table_price', $userActiveFields))
                    <td class="text-right">$ {{ number_format($item["selling_price"], 2) }}</td>
                @endif
            </tr>
            @endforeach
            @foreach($returned_items as $item)
            <tr>
                @if(in_array('table_device', $userActiveFields))
                    <td class="text-right">[CREDIT]: {{ $item["model"] }}</td>
                @endif
                @if(in_array('table_issues', $userActiveFields))
                    <td class="text-right">{{ $item["issues"] }}</td>
                @endif
                @if(in_array('table_imei', $userActiveFields))
                    <td class="text-right">{{ $item["imei"] }}</td>
                @endif
                @if(in_array('table_price', $userActiveFields))
                    <td class="text-right">$ {{ number_format($item["selling_price"], 2) }}</td>
                @endif
                @php
                    $credit += $item["selling_price"];
                @endphp
            </tr>
            @endforeach
            @endforeach
        </tbody>
    </table>
    @endif
    <div class="row m-0">
        <div class="col-span-4 offset-md-8">
            <table class="table table-striped pb-4">
                @if(in_array('subtotal', $userActiveFields))
                <tr>
                    <td class="text-right">
                        <b>SUBTOTAL:</b> $ {{ number_format($subtotal, 2) }}
                    </td>
                </tr>
                @endif
                @if(in_array('tax', $userActiveFields))
                <tr>
                    <td class="text-right">
                        <b>TAX:</b> $ {{ number_format($flatTax, 2) }}
                    </td>
                </tr>
                @endif
                @if(in_array('total', $userActiveFields))
                <tr>
                    <td class="text-right">
                        <b>TOTAL:</b> $ {{ number_format($total, 2) }}
                    </td>
                </tr>
                @endif
                @if(in_array('credit', $userActiveFields) && $credit > 0)
                <tr>
                    <td class="text-right">
                        <b>(CREDIT:)</b>
                        <span class="text-red-500">- $ {{ number_format($credit + ($credit * ($sales[0]->tax ?? 0) / 100), 2) }}</span>
                    </td>
                </tr>
                @endif
            </table>
        </div>
    </div>

    @if(in_array('footer', $userActiveFields) && isset($footer))
    <div class="row m-0">
        <div class="col-12">
            {!! $footer !!}
        </div>
    </div>
    @endif
</body>

</html>
