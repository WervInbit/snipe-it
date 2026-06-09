<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class ProductionSupplierSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->suppliers() as $data) {
            $name = trim((string) ($data['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $data['name'] = $name;

            /** @var Supplier $supplier */
            $supplier = Supplier::withTrashed()->firstOrNew(['name' => $name]);
            $supplier->fill($data);

            if ($supplier->trashed()) {
                $supplier->restore();
            }

            $supplier->save();
        }
    }

    /**
     * Add real production suppliers here only. Demo/example suppliers belong
     * in SupplierSeeder or another explicit demo/local seeder.
     *
     * @return array<int,array<string,string|null>>
     */
    private function suppliers(): array
    {
        return [
        ];
    }
}
