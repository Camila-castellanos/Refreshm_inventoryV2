<?php

namespace App\Imports;

use App\Models\Expense;
use App\Models\Tax;
use Exception;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\Auth;

class ExpensesImport implements ToModel, WithHeadingRow
{

  use Importable;
  /**
   * @param array $row
   *
   * @return \Illuminate\Database\Eloquent\Model|null
   */
  public function model(array $row)
  {
    try {
      // Validate required fields
      if (empty($row['category']) || empty($row['subtotal']) || empty($row['tax_id'])) {
        Log::error('Missing required field in row: ' . json_encode($row));
        return null; // Skip this row
      }

      // Set user ID
      $row['user_id'] = Auth::user()->id;

      // Get tax percentage and calculate tax
      $taxPercentage = Tax::whereId($row['tax_id'])->pluck('percentage')->first();
      if ($taxPercentage === null) {
        Log::error('Tax ID not found: ' . $row['tax_id']);
        return null; // Skip this row
      }

      $tax = number_format($taxPercentage, 2);
      $row['tax'] = $tax;

      // Calculate total
      $row['total'] = $row['subtotal'] * (1 + $row['tax'] / 100);

      // Create new Expense model instance
      $item = new Expense($row);
      return $item;
    } catch (Exception $e) {
      Log::error('Error importing row: ' . $e->getMessage());
      return null; // Skip this row
    }
  }
}
