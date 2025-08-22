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
    /* Compact header (DOMPDF-friendly table layout) */
    .invoice-header-table{ width:100%; border-collapse:collapse; margin-bottom:8px; }
    .invoice-header-table td{ vertical-align:middle; }
    .logo-block img{ max-width:140px; display:block; }
    .company-info{ font-size:11px; color:#6b7280; text-align:right; }
    .invoice-meta{ font-size:11px; text-align:right; display:inline-block; padding-left:12px; }
    .invoice-meta .label{ display:block; font-size:9px; text-transform:uppercase; color:#6b7280; }
    .invoice-meta .value{ font-weight:700; color:#0f172a; font-size:13px; }
    /* Inline table to show invoice number and date horizontally (legacy) */
    .invoice-meta-inline{ display:inline-table; vertical-align:middle; margin-left:12px; }
    .invoice-meta-inline td{ padding:0 10px; text-align:left; vertical-align:middle; }
    .invoice-meta-inline .meta-label{ display:block; font-size:9px; color:#6b7280; text-transform:uppercase; }
    .invoice-meta-inline .meta-value{ display:block; font-weight:700; color:#0f172a; font-size:13px; }

    /* Option 2: Boxed Top-Right Badge (Invoice meta) */
    .invoice-badge{ background:#f3f4f6; border:1px solid #e6e6e6; padding:8px 10px; border-radius:6px; text-align:left; display:inline-table; vertical-align:middle; margin-left:12px; }
    .invoice-badge td{ vertical-align:middle; }
    .invoice-badge .badge-number{ font-size:18px; font-weight:700; color:#0f172a; }
    .invoice-badge .badge-date{ font-size:11px; color:#6b7280; margin-top:4px; display:block; }
    .invoice-subtitle{ font-size:12px; color:#6b7280; margin-top:4px; }
    /* Bill To card (compact, DOMPDF-friendly) */
    .bill-card{ background:#fbfbfb; border:1px solid #e6e6e6; padding:8px; border-radius:4px; font-size:12px; }
    .bill-card .bill-label{ font-size:10px; color:#6b7280; text-transform:uppercase; }
    .bill-card .bill-name{ font-weight:700; font-size:13px; margin:0 0 2px 0; line-height:1.15; }
    .bill-card .bill-contact{ color:#374151; font-size:12px; margin-top:4px; line-height:1.2; }
    /* ensure Bill To uses the same vertical rhythm as Bill From */
    .bill-card .bill-to .bill-contact{ margin-top:4px; }
    .bill-card .bill-to .bill-name{ margin:0 0 2px 0; }
    /* normalize header children spacing to match bill-contact */
    .bill-card .bill-header .bill-header-content > * { margin-top:4px; line-height:1.2; display:block; }
    .bill-card .bill-header .bill-header-content > *:first-child { margin-top:0; }
    /* label spacing and header normalization to align names */
    .bill-card .bill-label{ display:block; margin-bottom:4px; }
    .bill-card .bill-header{ margin-top:0; }
    .bill-card .bill-header .bill-header-content{ color:#374151; font-size:12px; margin:0; }
    .bill-card .bill-header .bill-header-content strong,
    .bill-card .bill-header .bill-header-content b{ font-weight:700; color:#0f172a; font-size:13px; }
        /* Prevent price from wrapping and keep it aligned */
        .tbl-price {
            white-space: nowrap;
            text-align: right;
            padding-left: 8px;
            padding-right: 8px;
            border-right: 1px solid #e0e0e0;
        }
        /* Allow issues column to wrap and break words if necessary */
        .tbl-issues {
            white-space: normal;
            word-break: break-word;
            max-width: 320px; /* adjust as needed for your layout */
        }
        /* Ensure device column has reasonable width */
        .tbl-device {
            max-width: 220px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            padding-left: 8px;
            padding-right: 8px;
        }
        /* Add vertical dividers between item table columns */
        .items-table th,
        .items-table td {
            border-left: 1px solid #e0e0e0;
            padding-left: 10px;
        }
        /* Keep first-child padding unless the cell is not .tbl-device */
        .items-table thead th:first-child:not(.tbl-device),
        .items-table tbody td:first-child:not(.tbl-device) {
            border-left: none;
            padding-left: 0;
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
    <table class="invoice-header-table">
        <tr>
            <td style="width:60%;">
                <div style="display:flex; align-items:center;">
                    <div class="logo-block">
                        @if(in_array('logo', $userActiveFields) && isset($logo))
                            <img src="data:image/png;base64,{{ $logo }}" alt="logo" />
                        @else
                            <div style="font-weight:700; font-size:16px;">{{ config('app.name', 'Company') }}</div>
                        @endif
                    </div>
                    <table class="invoice-meta-inline" role="presentation">
                        <tr>
                             @if(in_array('invoice_number', $userActiveFields))
                            <td>
                                <span class="meta-label">Invoice</span>
                                <span class="meta-value">#{{ $sales[0]->id }}</span>
                            </td>
                            @endif
                            @if(in_array('invoice_due', $userActiveFields))
                            <td>
                                <span class="meta-label">Date</span>
                                <span class="meta-value">{{ $sales[0]->created_at->format('F d, Y') }}</span>
                            </td>
                            @endif
                        </tr>
                    </table>
                </div>
                @if(in_array('billing_address', $userActiveFields))
                    <div style="margin-top:8px;">
                        <div class="bill-card">
                            <table class="bill-card-table" role="presentation" style="width:100%; border-collapse:collapse;">
                                <tr>
                                    @if(in_array('header', $userActiveFields) && isset($header))
                                    <td style="width:45%; vertical-align:top; padding-right:2px;">
                                        <div class="bill-label">Bill From</div>
                                            @php
                                                $header_render = $header ?? '';
                                                // wrap the first text node (before any tag) with span.bill-name
                                                $header_render = preg_replace('/^\s*([^<]+)/', '<span class="bill-name">$1</span>', $header_render, 1);
                                            @endphp
                                            <div class="bill-header">
                                                <div class="bill-header-content">{!! $header_render !!}</div>
                                            </div>
                                    </td>
                                    @endif
                                    <td style="width:55%; vertical-align:top; padding-left:2px;" class="bill-to">
                                        <div class="bill-label">Bill To</div>
                                        @if(isset($customer->billing_address))
                                            <div class="bill-name">{{$customer->customer}}</div>
                                            <div class="bill-contact">{{$customer->billing_address}}</div>
                                            <div class="bill-contact">{{$customer->billing_address_city}}, {{$customer->billing_address_state}} {{$customer->billing_address_postal}}</div>
                                            <div class="bill-contact">{{$customer->billing_address_country}}</div>
                                            <div class="bill-contact">{{ $customer->phone[0] ?? '' }}</div>
                                            <div class="bill-contact">{{ $customer->email[0] ?? '' }}</div>
                                        @else
                                            <div class="bill-name">{{$customer}}</div>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                @endif
            </td>
            <td style="width:40%;"></td>
        </tr>
    </table>

    <!-- header moved inside bill-card -->

    <hr />

    <table width="100%" style="border-bottom:1px solid #a9a9a921;">
        <tbody style="margin:20px 0 !important;">
            <tr>
                @if(in_array('billing_address', $userActiveFields))
                <!-- Bill To moved to header -->
                @endif
                <td class="text-right" style="width:45%; padding:20px 0;">
                    <table>
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
    <table class="table table-striped pb-4 items-table">
        <thead>
            <tr>
                @if(in_array('table_device', $userActiveFields))
                    <th class="text-right tbl-device" scope="col">DEVICE</th>
                @endif
                @if(in_array('table_issues', $userActiveFields))
                    <th class="text-right tbl-issues" scope="col">ISSUES</th>
                @endif
                @if(in_array('table_imei', $userActiveFields))
                    <th class="text-right" scope="col">IMEI</th>
                @endif
                @if(in_array('table_price', $userActiveFields))
                    <th class="text-right tbl-price" scope="col">PRICE</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($sales as $sale)
            @foreach($sale->items as $item)
            <tr>
                @if(in_array('table_device', $userActiveFields))
                    <td class="text-right tbl-device">{{ $item["model"] }}</td>
                @endif
                @if(in_array('table_issues', $userActiveFields))
                    <td class="text-right tbl-issues">{{ $item["issues"] }}</td>
                @endif
                @if(in_array('table_imei', $userActiveFields))
                    <td class="text-right">{{ $item["imei"] }}</td>
                @endif
                @if(in_array('table_price', $userActiveFields))
                    <td class="text-right tbl-price">$ {{ number_format($item["selling_price"], 2) }}</td>
                @endif
            </tr>
            @endforeach
            @foreach($returned_items as $item)
            <tr>
                @if(in_array('table_device', $userActiveFields))
                    <td class="text-right tbl-device">[CREDIT]: {{ $item["model"] }}</td>
                @endif
                @if(in_array('table_issues', $userActiveFields))
                    <td class="text-right tbl-issues">{{ $item["issues"] }}</td>
                @endif
                @if(in_array('table_imei', $userActiveFields))
                    <td class="text-right">{{ $item["imei"] }}</td>
                @endif
                @if(in_array('table_price', $userActiveFields))
                    <td class="text-right tbl-price">$ {{ number_format($item["selling_price"], 2) }}</td>
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
