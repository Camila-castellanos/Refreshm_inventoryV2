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
    $subtotal = 0;
    $discount = 0;
    $flatTax = 0;
    $total = 0;
    $credit = 0;
    @endphp
    <div class="row m-0">
        <div class="col-12">
            <table width="100%">
                <tbody style="margin:20px 0 !important;">
                    <tr>
                        <td class="text-left" style="width:30%;padding:20px 0;">
                            @isset($logo)
                                <img src="data:image/png;base64,{{ $logo }}" alt="" width="100%" style="max-width: 200px;" />
                            @endisset
                        </td>
                        <td class="text-right" style="width:70%;padding:20px 0;"><h1>INVOICE</h1></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    @isset($header)
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
                <td class="text-right" style="width:45%; padding:20px 0;">
                    <table>
                        <tr>
                            <td>
                                <strong>Invoice Number:</strong>
                            </td>
                            <td class="text-left">
                                <span>{{$sales[0]->id}}</span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong>Invoice Due:</strong>
                            </td>
                            <td class="text-left">
                                {{$sales[0]->created_at->format('F d, Y')}}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong>Payment Due:</strong>
                            </td>
                            <td class="text-left">
                                {{$sales[0]->created_at->format('F d, Y')}}
                            </td>
                        </tr>
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
                    </table>
                </td>
            </tr>
        </tbody>
    </table>

    <hr />
    <table class="table table-striped pb-4">
        <thead>
            <tr>
                <th class="text-right" scope="col">DEVICE</th>
                <th class="text-right" scope="col">ISSUES</th>
                <th class="text-right" scope="col">IMEI</th>
                <th class="text-right" scope="col">PRICE</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sales as $sale)
            @foreach($sale->items as $item)
            <tr>
                <td class="text-right">
                    {{ $item["model"]  }}
                </td>
                <td class="text-right">
                    {{ $item["issues"]  }}
                </td>
                <td class="text-right">
                    {{ $item["imei"]  }}
                </td>
                <td class="text-right">
                    $ {{ number_format($item["selling_price"], 2)  }}
                </td>
            </tr>
            @endforeach
            @foreach($returned_items as $item)
            <tr>
                <td  class="text-right">
                    [CREDIT]: {{ $item["model"]  }}
                </td>
                <td class="text-right">
                    {{ $item["issues"]  }}
                </td>
                <td class="text-right">
                    {{ $item["imei"]  }}
                </td>
                <td class="text-right">
                    $ {{ number_format($item["selling_price"], 2)  }}
                </td>
                @php
                    $credit += $item["selling_price"];
                @endphp
            </tr>
            @endforeach
            @php
                $subtotal += $sale->subtotal + $credit;
                $discount += $sale->discount;
                $flatTax += $sale->flatTax;
                $discount += $sale->discount;
                $total += $sale->total;
            @endphp
            @endforeach
        </tbody>
    </table>
    <div class="row m-0">
        <div class="col-span-4 offset-md-8">
            <table class="table table-striped pb-4">
                <tr>
                    <td class="text-right">
                        <b>SUBTOTAL:</b>
                        $ {{ number_format($subtotal, 2)  }}
                    </td>
                </tr>
                <tr>
                    <td class="text-right">
                        <b>TAX:</b>
                        $ {{ number_format($flatTax, 2)  }}
                    </td>
                </tr>
                <tr>
                    <td class="text-right">
                        <b>TOTAL:</b>
                        $ {{ number_format($total, 2)  }}
                    </td>
                </tr>
                @if($sale->credit > 0)
                <tr>
                    <td class="text-right">
                        <b>(CREDIT:)</b>
                        <span class="text-red-500">- $ {{ number_format($sale->credit + ($sale->credit * $sale->tax/100), 2) }}</span>
                    </td>
                </tr>
                @endif
            </table>
        </div>
    </div>

    @isset($footer)
    <div class="row m-0">
        <div class="col-12">
            {!! $footer !!}
        </div>
    </div>
    @endif
</body>

</html>
