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

</head>

<body class="font-sans antialiased">
    <table class="table" style="font-size: 0.8em;">
        <tr>
            <th>Manufacturer</th>
            <th>Model</th>
            <th>Grade</th>
            <th>IMEI</th>
            <th>Issues</th>
        </tr>
        <tr>
            <td>{{ $item["manufacturer"]  }}</td>
            <td>{{ $item["model"]  }}</td>
            <td>{{ $item["grade"]  }}</td>
            <td>
                {{ $item["imei"]  }}
            </td>
            <td>{{ $item["issues"] }}</td>
        </tr>
        <tr>
            <td>
                {!! DNS2D::getBarcodeHTML(str_pad($item["id"], 10, "0"), "QRCODE", 3, 4) !!}
            </td>
            <td colspan="2">
                {!! DNS1D::getBarcodeHTML(str_pad($item["id"], 10, "0"), "MSI") !!}
            </td>
        </tr>
    </table>
</body>

</html>