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
        size: A4 portrait; /* pdf size and orientation */
        margin: 10mm; /* margin */
    }
    </style>
</head>

<body class="font-sans antialiased">
    @isset($logo)
    <div class="row">
        <div class="col-12">
            <img src="data:image/png;base64,{{ $logo }}" alt="" width="100%" style="max-width: 200px;" />
        </div>
    </div>
    @endif

    @isset($header)

    <div class="row">
        <div class="col-12">
            {!! $header !!}
        </div>
    </div>
    @endif
    <hr />

    <table width="100%" style="border-bottom:1px solid #a9a9a921;">
        <tbody style="margin:20px 0 !important;">
            <tr>
                <td class="text-left" style="width:70%;padding:20px 0; ">
                    <p class="mb-1"><strong>Bill To</strong></p>
                    @if(isset($customer->billing_address))
                        <p class="mb-2">{{$customer->customer}}</p>
                        <p style="margin: 0">{{$customer->billing_address}}</p>
                        <p style="margin: 0">{{$customer->billing_address_city}}, {{$customer->billing_address_state}}</p>
                        <p class="mb-2">{{$customer->billing_address_country}}</p>
                        <p style="margin: 0">{{$customer->phone[0]}}</p>
                        <p>{{$customer->email[0]}}</p>
                    @else
                        <p class="mb-2">{{$customer}}</p>
                    @endif
                </td>
                <td class="text-right" style="width:30%;padding:20px 0;">
                    <table>
                        <tr>
                            <td>
                                <strong>Invoice Number:</strong>
                            </td>
                            <td class="text-left">
                                <span>
                                    {{$sale->id}}</span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong>Invoice Due:</strong>
                            </td>
                            <td class="text-left">
                                {{$sale->created_at->format('F d, Y')}}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong>Payment Due:</strong>
                            </td>
                            <td class="text-left">
                                {{$sale->created_at->format('F d, Y')}}
                            </td>
                        </tr>
                        <tr style="background-color:#a9a9a921; border-radius:3px;">
                            <td>
                                <strong>Amount Due:</strong>
                            </td>
                            <td class="text-left">
                                $ {{number_format($sale->total, 2)}}
                            </td>
                        </tr>
                    </table>
                    {{-- <p class="mb-1 row">  </p>
                    <p class="mb-1 row"><strong class="col-6"></strong> <span class="col-6"></span> </p>
                    <p class="mb-1 row"><strong class="col-6"></strong> <span class="col-6">{{$sale->created_at->format('F d, Y')}}</span> </p>
                    <p class="mb-0 row" style="background-color:#a9a9a921; border-radius:3px;">
                        <strong class="col-6"></strong> <span class="col-6"> </span>
                    </p> --}}
                </td>
            </tr>
        </tbody>
    </table>

    <hr />
    <table class="table pb-4 table-striped">
        <thead>
            <tr>
                <th class="text-right" scope="col">DEVICE</th>
                <th class="text-right" scope="col">ISSUES</th>
                <th class="text-right" scope="col">IMEI</th>
                <th class="text-right" scope="col">PRICE</th>
            </tr>
        </thead>
        <tbody>
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
        </tbody>
    </table>
    <div class="row">
        <div class="col-span-4 offset-md-8">
            <table class="table pb-4 table-striped">
                <tr>
                    <td class="text-right">
                        <b>SUBTOTAL:</b>
                        $ {{ number_format($sale->subtotal, 2)  }}
                    </td>
                </tr>
                <tr>
                    <td class="text-right">
                        <b>DISCOUNT:</b>
                        $ {{ number_format($sale->discount, 2)  }}
                    </td>
                </tr>
                <tr>
                    <td class="text-right">
                        <b>TAX:</b>
                        $ {{ number_format($sale->flatTax, 2)  }}
                    </td>
                </tr>
                <tr>
                    <td class="text-right">
                        <b>TOTAL:</b>
                        $ {{ number_format($sale->total, 2)  }}
                    </td>
                </tr>
            </table>
        </div>
    </div>

    @isset($footer)
    <div class="row">
        <div class="col-12">
            {!! $footer !!}
        </div>
    </div>
    @endif
</body>

</html>
