@php
// image logo encoding
  $image_path = public_path('images/swiftstock_logo.jpeg');
  $type = pathinfo($image_path, PATHINFO_EXTENSION);
  $image_data = base64_encode(file_get_contents($image_path));

  //avoid null id on new items not saved yet
  $itemId = is_array($item)
    ? ($item['id'] ?? '')
    : ($item->id   ?? '');

// Define the fields to display and their labels
  $fields = [
    'manufacturer' => 'Manufacturer',
    'model'        => 'Model',
    'storage'      => 'Storage',
    'colour'       => 'Colour',
    'battery'      => 'Battery Health',
    'imei'         => 'IMEI',
  ];

// Retrieve the user's selection or use all by default
  $userFields = auth()->user()->printable_tag_fields ?? array_keys($fields);

// Filter only the active fields
  $fields = Arr::only($fields, $userFields);

  // define logo postion depending on the number of fields
  switch (count($fields)) {
        case 1:
            $logoTopPosition = '35%';
            break;
        case 2:
            $logoTopPosition = '30%';
            break;
        case 3:
            $logoTopPosition = '28%';
            break;
        case 4:
            $logoTopPosition = '26%';
            break;
        case 5:
            $logoTopPosition = '24%';
            break;
        case 6:
            $logoTopPosition = '20%';
            break;
        default:
            $logoTopPosition = '50%';
            break;
    }

// Iterate through each field and determine font size based on its length
  $fontSizes = [];
  foreach($fields as $key => $label) {
        $value = match($key) {
      'storage' => (!empty($item->storage->name) && !empty($item->position))
                    ? trim($item->storage->name.' - '.$item->position)
                    : 'N/A',

    'battery' => isset($item->battery)
        ? (str_ends_with(trim((string)$item->battery), '%')
          ? trim((string)$item->battery)
          : trim((string)$item->battery) . ' %'
        )
        : 'Unknown',

      default   => trim((string)($item[$key] ?? '')),
    };

    $item[$key]    = $value;
    $fontSizes[$key] = mb_strlen($value) > 20 ? '3mm' : '3.8mm';
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
            height: 100%;
            width: 100%;
        }
        .labeltag_container {
            display: flex;
            flex-direction: column;
            width: 98%;
            margin: 0 auto;
            padding: 0;
            border: 2px solid #000;
            font-size: 3.8mm;
            height: 97mm;
            box-sizing: border-box;
            /* background-color: blue; sólo para debug */
        }

        .labeltag_main_data {
            display: flex;
            flex-direction: column;
            width: 100%;
            margin: 0 auto;
            border-bottom: #000 solid 2px;
            /* background-color: green; sólo para debug */
        }
        .labeltag_main_data div {
            padding-top: 5px;
            padding-bottom: 10px;
            border-bottom: 1px solid #000;
            width: 95%;
            margin: auto;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .labeltag_main_data div:last-child {
            border-bottom: none;
        }
        .logo_container{
            text-align: center;
            position: relative;
        }
        .logo{
            max-width: 90%;
            object-fit: contain;
            position: relative;
            top: {{$logoTopPosition}};
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
                    {{ $item[$key] ?? '' }}    
              </span>
            </div>
            @endforeach
        </div>
        <div class="logo_container">
            <img src="data:image/{{ $type }};base64,{{ $image_data }}" class="logo">
        </div>
        <!-- <div class="labeltag_contact_data">
                <div>Sign up for a<br>swiftstock account</div>
                <div>{!! DNS2D::getBarcodeHTML(
                        str_pad($itemId, 10, '0'),
                        'QRCODE', 3, 3
)                   !!}</div>
        </div> -->
    </div>
</body>
</html>