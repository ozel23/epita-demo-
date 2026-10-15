<?php

namespace App\Imports;

use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
class Iw47Import implements ToArray, WithChunkReading, WithHeadingRow
{
    public $rows = [];
    public function array(array $array)
    {
        if (count($this->rows) + count($array) > 10000) {
            throw ValidationException::withMessages(['file' => 'Maksimal 10.000 baris per batch.']);
        }
        $this->rows = array_merge($this->rows, $array);
    }
    public function chunkSize(): int
    {
        return 500;
    }
}
