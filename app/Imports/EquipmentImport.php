<?php

namespace App\Imports;

use App\Models\Equipment;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EquipmentImport implements ToCollection, WithHeadingRow
{
    public function __construct(
        private readonly int $supplierId,
        private readonly float $markup,
        private readonly array $mapping,
    ) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $name = trim((string) ($row[$this->mapping['name']] ?? ''));
            if ($name === '') {
                continue;
            }

            $cost = $this->number($row[$this->mapping['cost_price']] ?? 0);
            $sale = round($cost * (1 + $this->markup / 100), 2);

            Equipment::updateOrCreate(
                ['supplier_id' => $this->supplierId, 'sku' => $this->value($row, 'sku')],
                [
                    'name' => $name,
                    'category' => $this->category($this->value($row, 'category')),
                    'unit' => in_array($unit = $this->value($row, 'unit'), Equipment::UNITS, true) ? $unit : 'buc',
                    'cost_price' => $cost,
                    'unit_price' => $sale,
                    'markup_percent' => $this->markup,
                    'stock_quantity' => (int) $this->number($row[$this->mapping['stock']] ?? 0),
                    'description' => $this->value($row, 'description'),
                    'is_active' => true,
                ],
            );
        }
    }

    private function value(Collection|array $row, string $field): string
    {
        $column = $this->mapping[$field] ?? null;
        return trim((string) ($column ? ($row[$column] ?? '') : ''));
    }

    private function number(mixed $value): float
    {
        return (float) str_replace(',', '.', preg_replace('/[^\d,.-]/', '', (string) $value));
    }

    private function category(string $value): string
    {
        return in_array($value, array_keys(Equipment::CATEGORIES), true) ? $value : 'altele';
    }
}
