<?php
return [
  // todos los campos disponibles para la plantilla de invoice
  'available_fields' => [
    'logo',
    'header',
    'billing_address',
    'invoice_number',
    'invoice_due',
    'payment_due',
    'amount_due',
    'items',
    'table_device',
    'table_issues',
    'table_imei',
    'table_price',
    'subtotal',
    'tax',
    'total',
    'credit',
    'footer',
  ],

  // por defecto, qué campos mostrar si el usuario no ha personalizado
  'default_fields' => [
    'logo',
    'header',
    'billing_address',
    'invoice_number',
    'invoice_due',
    'payment_due',
    'amount_due',
    'items',
    'table_device',
    'table_issues',
    'table_imei',
    'table_price',
    'subtotal',
    'tax',
    'total',
    'credit',
    'footer',
  ],
];