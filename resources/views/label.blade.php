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
    'barcode'       => 'Barcode',
  ];

try {
  // Retrieve the user's selection or use all by default
  $userFieldsRaw = auth()->user()->printable_tag_fields;
  
  // Ensure it's always an array
  if (!is_array($userFieldsRaw)) {
    $userFieldsRaw = array_keys($fields);
  }
  
  $userFields = $userFieldsRaw;

} catch (\Exception $e) {
  error_log('ERROR retrieving user fields: ' . $e->getMessage());
  $userFields = array_keys($fields);
}

// Filter only the active fields
  $fields = Arr::only($fields, $userFields);

  // count of user active fields (excluding barcode since it doesn't display in the main data section)
  $displayFields = Arr::except($fields, 'barcode');
  $count = count($displayFields);

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

  // Check if barcode field is active in user settings
  $barcodeActive = is_array($userFields) && in_array('barcode', $userFields);

  // Define border style: no border when barcode is active (to maximize space), border when not
  $containerBorder = $barcodeActive ? 'none' : '2px solid #000';



  // Iterate through each field and determine font size based on its length
  $barcodeData = null;
  foreach($fields as $key => $label) {
        $value = match($key) {
      'storage' => (!empty($item->storage->name) && !empty($item->position))
                    ? trim($item->storage->name.' - '.$item->position)
                    : ((!empty($item->location)) ? $item->location : 'N/A'),

      'battery' => isset($item->battery)
          ? (str_ends_with(trim((string)$item->battery), '%')
            ? trim((string)$item->battery)
            : trim((string)$item->battery) . ' %'
          )
          : 'Unknown',
      'vendor' => isset($item->vendor)
         ? trim((string)$item->vendor->vendor)
         : 'N/A',
      'cost' => isset($item->cost)
          ? (str_starts_with(trim((string)$item->cost), '$')
            ? trim((string)$item->cost)
            : '$ ' . trim((string)$item->cost)
          )
          : 'N/A',   
      'selling_price' => isset($item->selling_price)
          ? (str_starts_with(trim((string)$item->selling_price), '$')
            ? trim((string)$item->selling_price)
            : '$ ' . trim((string)$item->selling_price)
          )
          : 'N/A',
      'issues' => isset($item->issues)
          ? trim((string)$item->issues)
          : 'N/A',
      'barcode' => null,

        default   => trim((string)($item->{$key} ?? '')),
      };

      // Store barcode data separately
      if ($key === 'barcode') {
          $rawImei = $item->imei ?? '';
          // Only set barcode data if IMEI is valid (not empty and not N/A)
          if (!empty($rawImei) && $rawImei !== 'N/A') {
              $barcodeData = $rawImei;
          } else {
              $barcodeData = null;
          }
          error_log('Barcode data extracted: ' . ($barcodeData ?? 'NULL'));
      }

      $item->{$key} = $value;
    }

    // Calculate if we need reduced barcode height (many fields OR long issues text)
    // This is done AFTER the foreach so $item->issues has been processed
    $issuesLength = isset($item->issues) ? strlen(trim((string)$item->issues)) : 0;
    $needsReducedBarcode = $count >= 11 || $issuesLength > 16;
    $barcodeHeight = $needsReducedBarcode ? '8mm' : '24mm';

    error_log('$needsReducedBarcode: ' . ($needsReducedBarcode ? 'true' : 'false') . ', $barcodeHeight: ' . $barcodeHeight);


   
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
             width: 100%;
             margin: 0 auto;
             padding: 0;
             border: {{ $containerBorder }};
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
         
           .footer-section {
             flex: 1;
             display: flex;
             flex-direction: column;
             justify-content: center;
             align-items: center;
             /* background-color: yellow; sólo para debug */
             overflow: visible;
             padding-top: 4mm;
             padding-left: 2mm;
             padding-right: 2mm;
             padding-bottom: 1mm;
           }

           .logo_container {
               text-align: center;
               width: 100%;
               margin-bottom: 1mm;
           }
           .logo{
               display: block;
               margin: 0 auto;
               max-width: 90%;
               max-height: 14mm;
               width: auto;
               height: auto;
               object-fit: contain;
           }
           /* Styles for PNG barcode image */
           .barcode_container {
               text-align: center;
               width: 100%;
               margin-bottom: 0;
               margin-top: 2mm;
               overflow: visible;
           }

              .barcode_image {
                  width: 100%;
                  height: 10mm;
                  max-width: 100%;
                  display: block;
                  margin: 0 auto;
                  object-fit: contain;
              }
              .barcode_image.reduced {
                  height: 8mm;
              }
        .labeltag_contact_data {
        width: 100%;
        display: flex;
        height: 20mm;
        border-top: #000 solid 2px;
        }
  .labeltag_contact_data > div {
    width: 50%;           
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
             @if($key !== 'barcode')
             <div>
               <strong>{{ $label }}:</strong>
               <span>
                     {{ $item->{$key} ?? '' }}    
               </span>
             </div>
             @endif
             @endforeach
         </div>
         
         <div class="footer-section">
           <div class="logo_container">
               <img src="data:image/{{ $type }};base64,{{ $image_data }}" class="logo">
           </div>
           @php
               try {
                   // Only show barcode if user enabled it AND we have valid data (IMEI)
                   $shouldShowBarcode = is_array($userFields) && in_array('barcode', $userFields) && !empty($barcodeData);
               } catch (\Exception $e) {
                   error_log('ERROR checking barcode condition: ' . $e->getMessage());
                   $shouldShowBarcode = false;
               }
           @endphp
            @if($shouldShowBarcode)
            <div class="barcode_container">
                @php
                    try {
                        // Force string conversion
                        $barcodeValue = (string)$barcodeData;
                        
                        if (empty($barcodeValue)) {
                            $barcodeValue = 'NO_BARCODE';
                        }
                        
                         // Use Picqer barcode generator as PNG
                         // Width Factor 2: compact bars
                         // Height 50px: balanced height for label space
                         $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
                         $barcodeImage = $generator->getBarcode($barcodeValue, \Picqer\Barcode\BarcodeGeneratorPNG::TYPE_CODE_128, 2, 50, [0, 0, 0]);
                        
                        // Convert to base64
                        $barcodeBase64 = base64_encode($barcodeImage);
                        
                    } catch (\Exception $e) {
                        error_log('ERROR generating barcode: ' . $e->getMessage());
                        $barcodeBase64 = '';
                    }
                @endphp
                @if(!empty($barcodeBase64))
                    <img src="data:image/png;base64,{{ $barcodeBase64 }}" alt="Barcode" class="barcode_image {{ $needsReducedBarcode ? 'reduced' : '' }}">
                @else
                    <div style="border: 1px dashed red; padding: 5px; color: red; font-size: 10px;">
                        [ERROR] Could not generate barcode<br>
                        Value: {{ $barcodeData ?? 'NULL' }}
                    </div>
                @endif
            </div>
            @endif
         </div>
</body>
</html>
