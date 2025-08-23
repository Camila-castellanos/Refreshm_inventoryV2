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

    /* Small due card that sits to the right of the bill-card */
    .due-card{ background: #ffffff; border:none; padding:8px 10px; border-radius:6px; width:170px; text-align:left; display:inline-table; vertical-align:top; }
    .due-card .label{ font-size:10px; color:#6b7280; text-transform:uppercase; }
    .due-card .value{ font-size:16px; font-weight:700; color:#0f172a; margin-top:4px; }
    .due-card .sub{ font-size:11px; color:#6b7280; margin-top:6px; }
    /* Totals card (placed under due-card) */
    .totals-card{ background:#fbfbfb; border:none; padding:8px 10px; border-radius:6px; margin-top:8px; }
    .totals-card .line{ font-size:12px; color:#374151; margin-top:6px; }
    .totals-card .line .label{ color:#6b7280; text-transform:uppercase; font-size:10px; }
    .totals-card .line .value{ float:right; font-weight:700; }
    /* Footer card (placed under totals) */
    .footer-card{ background:#fbfbfb; border:none; padding:10px; border-radius:6px; margin-top:8px; font-size:12px; color:#374151; }
    .footer-card .content{ font-size:12px; color:#374151; }
    .invoice-subtitle{ font-size:12px; color:#6b7280; margin-top:4px; }
    /* Bill To card (compact, DOMPDF-friendly) */
    .bill-card{ background:#fbfbfb; border:none; padding:8px; border-radius:4px; font-size:12px; }
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
    .bill-card-table {
        padding-bottom: 25px;
    }
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
    /* Items table card styling (modernized) */
    .items-card{ background:#ffffff; border-radius:8px; padding:6px; margin-top:12px; box-shadow: 0 1px 2px rgba(15,23,42,0.04); border:1px solid #eef2f6; }
    .items-card .items-table{ width:100%; border-collapse:separate; border-spacing:0; table-layout:fixed; font-size:13px; }
    .items-card .items-table thead th{ background:#f8fafc; color:#0f172a; font-weight:700; padding:10px 12px; text-align:left; border-bottom:1px solid #e6eef6; }
    .items-card .items-table tbody td{ background:#ffffff; padding:10px 12px; vertical-align:middle; color:#334155; }
    .items-card .items-table tbody tr + tr td{ border-top:1px solid #f1f5f9; }
    .items-card .items-table th.tbl-price, .items-card .items-table td.tbl-price{ width:120px; text-align:right; white-space:nowrap; }
    .items-card .items-table th.tbl-issues, .items-card .items-table td.tbl-issues{ width:40%; }
    .items-card .items-table th.tbl-device, .items-card .items-table td.tbl-device{ width:30%; }
    /* Zebra rows for readability */
    .items-card .items-table tbody tr:nth-child(odd) td{ background:#ffffff; }
    .items-card .items-table tbody tr:nth-child(even) td{ background:#fbfdff; }
    /* Subtle rounded corners for the table */
    .items-card .items-table thead th:first-child{ border-top-left-radius:6px; }
    .items-card .items-table thead th:last-child{ border-top-right-radius:6px; }
    .items-card .items-table tbody tr:last-child td:first-child{ border-bottom-left-radius:6px; }
    .items-card .items-table tbody tr:last-child td:last-child{ border-bottom-right-radius:6px; }
    /* Ellipsis for device column to keep layout neat */
    .items-card .items-table td.tbl-device{ overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    /* Make numeric columns bold and right aligned */
    .items-card .items-table td.tbl-price{ font-weight:700; color:#0f172a; }
    /* Responsive tweak for small widths when rendering PDF */
    @media print, screen and (max-width:800px) {
        .items-card .items-table th, .items-card .items-table td { padding:8px 8px; font-size:12px; }
    }

    #items-table{
        background-color: #fbfbfb !important;
        margin-bottom:4px;
        border-bottom:1px solid #e0e0e0;
    }
    /* reduce bottom padding inside the items table cells to tighten spacing */
    #items-table tbody td{ padding-bottom:8px; }
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
    @php
        // Precompute a display-friendly sales total used in the header due-card
        $salestotal = array_sum(array_column($sales->toArray(), 'total'));
        $salestotal -= (array_sum(array_column($sales->toArray(), 'credit')) + (array_sum(array_column($sales->toArray(), 'credit')) * ($sales[0]->tax ?? 0) / 100));
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
            <td style="width:40%; vertical-align:top; padding-left:12px;">
                @if(in_array('payment_due', $userActiveFields) || in_array('amount_due', $userActiveFields))
                    <table class="bill-card due-card" role="presentation" style="width:100%; border-collapse:collapse;">
                        <tr><td>
                            @if(in_array('payment_due', $userActiveFields))
                                <div class="label">Payment Due</div>
                                <div class="value">{{ $sales[0]->created_at->format('F d, Y') }}</div>
                            @endif
                            @if(in_array('amount_due', $userActiveFields))
                                <div class="sub"><strong>Amount Due:</strong> $ {{ number_format($salestotal,2) }}</div>
                            @endif
                        </td></tr>
                    </table>
                    @if(in_array('subtotal', $userActiveFields) || in_array('tax', $userActiveFields) || in_array('total', $userActiveFields))
                        <table class="totals-card" role="presentation" style="width:100%; border-collapse:collapse;">
                            <tr><td>
                                @if(in_array('subtotal', $userActiveFields))
                                    <div class="line"><span class="label">Subtotal</span><span class="value">$ {{ number_format($subtotal,2) }}</span></div>
                                @endif
                                @if(in_array('tax', $userActiveFields))
                                    <div class="line"><span class="label">Tax</span><span class="value">$ {{ number_format($flatTax,2) }}</span></div>
                                @endif
                                @if(in_array('total', $userActiveFields))
                                    <div class="line"><span class="label">Total</span><span class="value">$ {{ number_format($total,2) }}</span></div>
                                @endif
                            </td></tr>
                        </table>
                    @endif
                @endif
                @if(in_array('footer', $userActiveFields) && isset($footer))
                    @php
                        // Preserve line breaks: convert <br> and block tags to newlines first
                        $tmp = $footer;
                        // Normalize different br variants
                        $tmp = preg_replace('#<(br|br\s*/)>#i', "\n", $tmp);
                        // Treat span boundaries as line breaks: </span><span...> or </span> ... <span -> newline
                        $tmp = preg_replace('#</span\s*>\s*<span[^>]*>#i', "\n", $tmp);
                        // Also convert any closing or opening span tags to newline if isolated
                        $tmp = preg_replace('#</?span[^>]*>#i', "\n", $tmp);
                        // Convert block-level tags to newlines (p, div, li, h1..h6)
                        $tmp = preg_replace('#</?(p|div|li|h[1-6])[^>]*>#i', "\n", $tmp);
                        // Now strip remaining tags
                        $footer_text = strip_tags($tmp);
                        // decode HTML entities
                        $footer_text = html_entity_decode($footer_text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        // Replace non-breaking spaces and NBSP variants with regular space
                        $footer_text = str_replace(["\xC2\xA0", "\xA0", chr(160), '&nbsp;'], ' ', $footer_text);
                        // Remove zero-width / invisible Unicode characters
                        $footer_text = preg_replace('/[\x{200B}\x{200C}\x{200D}\x{200E}\x{200F}\x{00AD}]/u', '', $footer_text);
                        // Split into lines, trim each line, remove empty lines at ends and collapse multiple blank lines into single
                        $lines = preg_split('/\r?\n/', $footer_text);
                        $cleanLines = array();
                        foreach ($lines as $line) {
                            $l = preg_replace('/\s+/', ' ', $line);
                            $l = trim($l);
                            $cleanLines[] = $l;
                        }
                        // Remove leading/trailing empty lines
                        while (count($cleanLines) && $cleanLines[0] === '') array_shift($cleanLines);
                        while (count($cleanLines) && end($cleanLines) === '') array_pop($cleanLines);
                        // Collapse multiple consecutive empty lines to a single empty line
                        $finalLines = array();
                        $prevEmpty = false;
                        foreach ($cleanLines as $l) {
                            $isEmpty = ($l === '');
                            if ($isEmpty && $prevEmpty) continue;
                            $finalLines[] = $l;
                            $prevEmpty = $isEmpty;
                        }
                        $footer_text = implode("\n", $finalLines);
                    @endphp
                    <table class="bill-card footer-card" role="presentation" style="width:100%; border-collapse:collapse;">
                        <tr><td>
                            <div class="content">{!! nl2br(e($footer_text)) !!}</div>
                        </td></tr>
                    </table>
                @endif
            </td>
        </tr>
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
    <table class="table items-table" id="items-table">
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

</body>

</html>
