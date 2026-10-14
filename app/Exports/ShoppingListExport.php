<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ShoppingListExport implements FromArray, WithHeadings, WithTitle
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $meta
     */
    public function __construct(private array $rows, private array $meta = []) {}

    public function array(): array
    {
        return collect($this->rows)->map(fn (array $row) => [
            $row['name'],
            $row['supplier_name'] ?? 'Fără furnizor',
            $row['unit'],
            number_format((float) $row['needed'], 2, '.', ''),
            number_format((float) $row['in_stock'], 2, '.', ''),
            number_format((float) $row['to_buy'], 2, '.', ''),
            number_format((float) $row['unit_cost'], 2, '.', ''),
            number_format((float) $row['estimated_cost'], 2, '.', ''),
        ])->all();
    }

    public function headings(): array
    {
        return ['Material', 'Furnizor', 'U.M.', 'Necesar', 'In stoc', 'De cumparat', 'Cost unitar', 'Cost estimat'];
    }

    public function title(): string
    {
        return 'Lista de cumparat';
    }
}
