<?php

namespace App\Imports;

use App\Models\Bill;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\Auth;

class BillsImport implements ToModel, WithHeadingRow
{
  use Importable;
  /**
   * @param array $row
   *
   * @return \Illuminate\Database\Eloquent\Model|null
   */
  public function model(array $row)
  {
    // dd($row);
    $row['user_id'] = Auth::user()->id;
    $row['total'] = (int)$row['total'];
    $row['amount_paid'] = (int)$row['amount_paid'];
    return new Bill($row);
  }
}
