{{-- filepath: resources/views/labels.blade.php --}}
<!doctype html>
<html>
<head>
  <style>
    /* Ajusta márgenes si hace falta */
     @page {
  size: 101.6mm 50.8mm portrait;
  margin: 1.5mm;
}
    body { margin: 0; padding: 0; }
  </style>
</head>
<body>
  @foreach($records as $item)
    {{-- Reutiliza tu partial de etiqueta --}}
    @include('label', ['item' => $item])
    @if (! $loop->last)
      <div style="page-break-after: always;"></div>
    @endif
  @endforeach
</body>
</html>