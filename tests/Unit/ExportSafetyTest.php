<?php

namespace Tests\Unit;

use App\Exports\RowsExport;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\TestCase;
class ExportSafetyTest extends TestCase
{
    public function test_sap_identifiers_and_formula_text_remain_strings()
    {
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $export = new RowsExport([], []);
        $export->bindValue($sheet->getCell('A1'), '0010');
        $export->bindValue($sheet->getCell('B1'), '=1+1');
        $this->assertSame('0010', $sheet->getCell('A1')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('B1')->getDataType());
    }
    public function test_csv_formula_injection_is_escaped()
    {
        $export = new RowsExport([['0010', '=1+1', '@SUM(A1)', -4, '-4.20']], [], true);
        $this->assertSame([['0010', "'=1+1", "'@SUM(A1)", -4, '-4.20']], $export->array());
    }
}
