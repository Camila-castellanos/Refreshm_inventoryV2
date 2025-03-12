<?php

namespace App\Imports;

use App\Models\CustomField;
use App\Models\Item;
use Exception;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\Auth;

class ItemsImport implements ToModel, WithHeadingRow
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
      if (!empty($row['model']) && !empty($row['date'])) {
        $row['user_id'] = Auth::user()->id;
        $item = new Item($row);
        $vendor = explode('#', $item->supplier);
        $customValues = [];
        foreach ($row as $key => $value) {
          $parts = explode('_field_', $key);
          if (count($parts) > 1) {
            $valuePattern = $parts[0];
            $idPattern = $parts[1];
            $result = $row[$key];
            $field = CustomField::where('id', $idPattern)->first();
            $customValue = [
              "id" => $idPattern,
              "text" => $field->text,
              "type" => $field->type,
              "slug" => $field->value,
              "value" => $result,
            ];
            $customValues[] = $customValue;
          }
        }

        $jsonCustomValues = json_encode($customValues);

        if (count($vendor) > 1) {
          $item->supplier = $vendor[0];
          $item->vendor_id = $vendor[1];
        }

        $item->custom_values = $jsonCustomValues;

        return $item;
      }
      return null;
    } catch (Exception $e) {
      return response()->json($e->getMessage(), 500);
    }
  }
}
