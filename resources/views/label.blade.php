@php
// image logo encoding
  $image_path = public_path('images/swiftstock_logo.jpeg');
  $type = pathinfo($image_path, PATHINFO_EXTENSION);
  $image_data = base64_encode(file_get_contents($image_path));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <title>Item Label</title>
    <style>
       @page {
  size: 101.6mm 50.8mm portrait;
  margin: 1.5mm;
}
    body {
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            box-sizing: border-box;
        }
        .labeltag_container {
            width: 98%;
            margin: 0 auto;
            padding: 0;
            border: 2px solid #000;
            font-size: 3.8mm;
        }

        .labeltag_main_data {
            display: flex;
            flex-direction: column;
            width: 100%;
            margin: 0 auto;
            border-bottom: #000 solid 2px;
        }
        .labeltag_main_data div {
            padding-top: 5px;
            padding-bottom: 10px;
            border-bottom: 1px solid #000;
            width: 95%;
            margin: auto;
        }
        .labeltag_main_data div:last-child {
            border-bottom: none;
        }

        .logo_container {
            display: flex;
            width: 100%;
            padding: 10px 0;
            text-align: center;
        }
        .logo{
            width: 35mm;
        }
        .labeltag_contact_data {
        width: 100%;
        display: flex;
        height: 20mm;
        border-top: #000 solid 2px;
        }
  .labeltag_contact_data > div {
    width: 50%;           /* dos columnas iguales */
    float: left;
    padding: 5px;
    box-sizing: border-box;
    padding-top: 8px;
  }
    .labeltag_contact_data > div:last-child {
        border-left: #000 solid 2px;
    }
    </style>
</head>
<body>
    <div class="labeltag_container">
        <div class="labeltag_main_data">
            <div>
                <strong>Manufacturer:</strong>
                <span>{{ $item['manufacturer'] ?? '' }}</span>
            </div>
            <div>
                <strong>Model:</strong>
                <span>{{ $item['model'] ?? '' }}</span>
            </div>
            <div>
            <strong>Storage:</strong>
            <span>{{ $item['storage'] ?? '' }}</span>
        </div>
        <div>
            <strong>Colour:</strong>
            <span>{{ $item['colour'] ?? '' }}</span>
        </div>
        <div>
            <strong>Battery Health:</strong>
            <span>{{ $item['battery'] ?? '' }}</span>
        </div>
        <div>
            <strong>IMEI:</strong>
            <span>{{ $item['imei'] ?? '' }}</span>
        </div>
        </div>
        <div class="logo_container">
            <img src="data:image/{{ $type }};base64,{{ $image_data }}" class="logo">
        </div>
        <div class="labeltag_contact_data">
                <div>Sign up for a<br>swiftstock account</div>
                <div>{!! DNS2D::getBarcodeHTML(str_pad($item['id'],10,'0'), 'QRCODE', 3, 3) !!}</div>
        </div>
    </div>
</body>
</html>