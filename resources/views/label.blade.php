@php
// image logo encoding
  $image_path = public_path('images/swiftstock_logo.jpeg');
  $type = pathinfo($image_path, PATHINFO_EXTENSION);
  $image_data = base64_encode(file_get_contents($image_path));

  //avoid null id on new items not saved yet
  $itemId = is_array($item)
    ? ($item['id'] ?? '')
    : ($item->id   ?? '');

// Define the fields that can be displayed and their labels
  $fields = [
    'date'          => 'Date',
    'vendor'        => 'Vendor',
    'manufacturer'  => 'Manufacturer',
    'model'         => 'Model',
    'colour'        => 'Colour',
    'battery'       => 'Battery',
    'grade'         => 'Grade',
    'issues'        => 'Issues',
    'imei'          => 'IMEI',
    'cost'          => 'Cost',
    'selling_price' => 'Selling Price',
    'storage'      => 'Location',
  ];

// Retrieve the user's selection or use all by default
  $userFields = auth()->user()->printable_tag_fields ?? array_keys($fields);

// Filter only the active fields
  $fields = Arr::only($fields, $userFields);

  // count of user active fields
  $count = count($fields);

  // define logo postion depending on the number of fields
  $logoTopPosition = match($count) {
    1 => '35%',
    2 => '30%',
    3 => '28%',
    4 => '26%',
    5 => '24%',
    6 => '20%',
    default => '15%',
  };
// define padding between fields depending on the number of fields
  $fieldPadding   = match(true) {
    $count >= 11 => '1mm',
    $count == 10 => '1.2mm',
    $count <= 3 => '3mm',
    $count <= 5 => '2.5mm',
    default     => '2mm',
  };
// define font size depending on the number of fields
 $baseFontSize = match(true) {
    $count <= 3 => '5mm',
    $count <= 5 => '4mm',
    default     => '3.5mm',
  };

// define the logo width depending on the number of fields
  $logoWidth = match(true) {
    $count == 12 && strlen($item['issues'] ?? '') > 17 => '35%',
    $count == 12 => '50%',
    $count == 11 => '60%',
    $count == 10 => '80%',
    $count == 9 => '55%',
    $count == 8 => '60%',
    $count ==  7 => '70%',
    $count == 6 => '80%',
    $count <= 5 => '90%',
    default     => '60%',
  };  

  // define the logo margin depending on the number of fields
  $logoMargin = match(true) {
    $count == 12 => '1mm',
    $count == 11 => '2.5mm',
    $count == 10 => '2mm',
    $count == 8 => '4mm',
    $count == 7 => '8mm',
    $count == 6 => '10mm',
    $count == 5 => '10mm',
    $count == 4 => '13mm',
    $count == 3 => '15mm',
    $count == 2 => '20mm',
    $count == 1 => '25mm',
    default     => '1mm',
  };
// Iterate through each field and determine font size based on its length
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
    'vendor' => isset($item->vendor)
       ? trim((string)$item->vendor->vendor)
       : 'N/A',

      default   => trim((string)($item[$key] ?? '')),
    };

    $item[$key]    = $value;
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
            font-size: {{ $baseFontSize }};
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
            padding: {{ $fieldPadding }} 0px;
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
            height: auto;
            margin-top: {{$logoMargin}};  
        }
        .logo{
            display: block;
            margin: 0 auto;
            width: {{ $logoWidth }};
            object-fit: contain;
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
              <span>
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