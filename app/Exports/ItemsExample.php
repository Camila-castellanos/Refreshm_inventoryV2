<?php

namespace App\Exports;

use App\Models\Vendor;
use App\Models\CustomField;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Response;

class ItemsExample implements FromCollection, WithHeadings, WithEvents
{
  /**
   * @return \Illuminate\Support\Collection
   */

  // public function headings(): array
  // {
  //     return [
  //         'date',
  //         'supplier',
  //         'manufacturer',
  //         'model',
  //         'colour',
  //         'battery',
  //         'grade',
  //         'issues',
  //         'cost',
  //         'imei',
  //         'selling_price'
  //     ];
  // }

  // public function __construct()
  // {
  //     $status=['active','pending','disabled'];
  //     $departments=['Account','Admin','Ict','Sales'];
  //     $selects=[  //selects should have column_name and options
  //         ['columns_name'=>'D','options'=>$departments],
  //         ['columns_name'=>'E','options'=>$status],
  //     ];
  //     $this->selects=$selects;
  //     $this->row_count=50;//number of rows that will have the dropdown
  //     $this->column_count=5;//number of columns to be auto sized
  // }

  protected $selects;
  protected $row_count;
  protected $column_count;
  protected $vendors;
  public function __construct(array $vendors)
  {
    $selects = [
      ['columns_name' => 'B', 'options' => $vendors],
    ];
    $this->selects = $selects;
    $this->row_count = 50;
    $this->column_count = 1;
    $this->vendors = $vendors;
  }

  /**
   * @return \Illuminate\Support\Collection
   */
  public function collection()
  {
    return collect([]);
  }
  public function headings(): array
  {
    $user = Auth::user();
    $headers = [
      'date',
      'supplier',
      'manufacturer',
      'model',
      'colour',
      'battery',
      'grade',
      'issues',
      'cost',
      'imei',
      'selling_price',

    ];
    $values = CustomField::where('user_id', $user->id)->get();
    foreach ($values as $value) {
      $header = strtolower($value->text) . '_' . $value->id . '_field_' . $value->id;
      $header = str_replace(' ', '_', $header);
      array_push($headers, $header);
    }

    return $headers;
  }

  /**
   * @return array
   */
  public function registerEvents(): array
  {

    return [
      AfterSheet::class => function (AfterSheet $event) {
        $row_count = $this->row_count;
        $column_count = $this->column_count;
        $vendors = $event->sheet->getParent()->createSheet();
        $vendors->setTitle('Vendors');

        for ($i = 0; $i < count($this->vendors); $i++) {
          $n = $i + 1;
          $vendors->getCell("A{$n}")->setValue($this->vendors[$i]);
        }
        $veryHidden = 'veryHidden';
        $vendors->setSheetState($veryHidden);

        foreach ($this->selects as $select) {
          $drop_column = $select['columns_name'];
          $options = $select['options'];
          $event->sheet->setTitle('Items');
          $validation = $event->sheet->getCell("{$drop_column}2")->getDataValidation();
          $validation->setType(DataValidation::TYPE_LIST);
          $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
          $validation->setAllowBlank(false);
          $validation->setShowInputMessage(true);
          $validation->setShowErrorMessage(true);
          $validation->setShowDropDown(true);
          $validation->setErrorTitle('Input error');
          $validation->setError('Value is not in list.');
          $validation->setPromptTitle('Pick from list');
          $validation->setPrompt('Please pick a value from the drop-down list.');
          $validation->setFormula1('=\'Vendors\'!$A$1:$A$500');

          for ($i = 2; $i <= $row_count; $i++) {
            $event->sheet->getCell("{$drop_column}{$i}")->setDataValidation(clone $validation);
          }

          for ($i = 1; $i <= $column_count; $i++) {
            $column = Coordinate::stringFromColumnIndex($i);
            $event->sheet->getColumnDimension($column)->setAutoSize(true);
          }
        }
      },
    ];
  }

  protected function vendorsSheet()
  {
    $sheet = new Spreadsheet();
    $sheet = $sheet->createSheet();
    $sheet = $sheet->setTitle("Vendors");
    return $sheet;
  }
}
