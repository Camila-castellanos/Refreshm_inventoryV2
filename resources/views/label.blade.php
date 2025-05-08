@php
// image logo encoding
  $image_path = public_path('images/swiftstock_logo.jpeg');
  $type = pathinfo($image_path, PATHINFO_EXTENSION);
  $image_data = base64_encode(file_get_contents($image_path));

  //avoid null id on new items not saved yet
  $itemId = is_array($item)
    ? ($item['id'] ?? '')
    : ($item->id   ?? '');
  // Definimos los campos a mostrar y sus etiquetas
  $fields = [
    'manufacturer' => 'Manufacturer',
    'model'        => 'Model',
    'storage'      => 'Storage',
    'colour'       => 'Colour',
    'battery'      => 'Battery Health',
    'imei'         => 'IMEI',
  ];

  // Recorremos cada campo y decidimos tamaño de fuente según longitud
  $fontSizes = [];
  foreach($fields as $key => $label) {
        if ($key === 'storage') {
            $value = trim((string)(
                ($item->storage->name ?? 'N/A')
                . ' - '
                . ($item->position ?? 'N/A')
            ));
        } else {
            $value = trim((string)($item[$key] ?? ''));
        }
      $fontSizes[$key] = mb_strlen($value) > 17 ? '2.5mm' : '3.8mm';
  }
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
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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
            @foreach($fields as $key => $label)
            <div>
              <strong>{{ $label }}:</strong>
              <span style="font-size: {{ $fontSizes[$key] }}">
                @if($key === 'storage')
                     {{ $item->storage->name ?? 'N/A' }} - {{ $item->position ?? 'N/A' }}
                @else
                    {{ $item[$key] ?? '' }}    
                @endif 
              </span>
            </div>
            @endforeach
        </div>
        <div class="logo_container">
            <img src="data:image/{{ $type }};base64,{{ $image_data }}" class="logo">
        </div>
        <div class="labeltag_contact_data">
                <div>Sign up for a<br>swiftstock account</div>
                <div>{!! DNS2D::getBarcodeHTML(
                        str_pad($itemId, 10, '0'),
                        'QRCODE', 3, 3
)                   !!}</div>
        </div>
    </div>
</body>
</html>