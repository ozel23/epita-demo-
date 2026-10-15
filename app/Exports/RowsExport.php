<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
/** Preserve SAP identifiers with leading zeroes and never interpret user text as formulas. */
class RowsExport extends DefaultValueBinder implements FromArray, WithCustomValueBinder, WithHeadings, WithStrictNullComparison
{
    private $rows;
    private $headers;
    private $csvSafe;
    public function __construct(array $rows, array $headers, bool $csvSafe = false)
    {
        $this->rows = $rows;
        $this->headers = $headers;
        $this->csvSafe = $csvSafe;
    }
    public function array(): array
    {
        return array_map(function ($row) {
            return array_map(function ($value) {
                if ($this->csvSafe && is_string($value) && preg_match('/^[=+@-]/', $value) && !is_numeric($value)) {
                    return "'" . $value;
                }
                return $value;
            }, array_values($row));
        }, $this->rows);
    }
    public function headings(): array
    {
        return $this->headers;
    }
    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }
        return parent::bindValue($cell, $value);
    }
}
