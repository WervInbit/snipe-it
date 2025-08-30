<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Location;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class AssetSeeder extends Seeder
{
    private $adminuser;
    private $locationIds;
    private $supplierIds;

    /**
     * Seed the assets table, respecting foreign keys.
     *
     * @return void
     */
    public function run()
    {
        /*
         * Safely clear assets and their dependent tables.
         * MySQL will not allow TRUNCATE on a table referenced by a foreign key,
         * so we disable FK checks, delete children, delete parents,
         * then reset the auto‑increment counters.
         */
        Schema::disableForeignKeyConstraints();

        // Delete children that reference assets
        DB::table('asset_tests')->delete();
        DB::table('asset_logs')->delete();
        DB::table('checkout_requests')->delete();

        // Delete the assets themselves
        Asset::query()->delete();

        // Reset auto‑increment counters on parent and child tables
        DB::statement('ALTER TABLE asset_tests AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE asset_logs AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE checkout_requests AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE assets AUTO_INCREMENT = 1');

        // Re‑enable foreign key checks
        Schema::enableForeignKeyConstraints();

        // Ensure required reference data is present
        $this->ensureLocationsSeeded();
        $this->ensureSuppliersSeeded();

        // Look up or create the initial admin user
        $this->adminuser = User::where('permissions->superuser', '1')->first()
            ?? User::factory()->firstAdmin()->create();
        $this->locationIds = Location::all()->pluck('id');
        $this->supplierIds = Supplier::all()->pluck('id');

        // Seed a fixed set of assets (no company scoping)
        $created = Asset::factory()
            ->count(40)
            ->state(new Sequence(fn() => [
                'rtd_location_id' => $this->locationIds->random(),
                'supplier_id'     => $this->supplierIds->random(),
                'created_by'      => $this->adminuser->id,
            ]))
            ->create();
        // Generate QR labels for each created asset (best-effort)
        try {
            $qr = app(\App\Services\QrLabelService::class);
            $created->each(function (Asset $asset) use ($qr) {
                try { $qr->generate($asset); } catch (\Throwable $e) { /* ignore */ }
            });
        } catch (\Throwable $e) { /* ignore */ }

        /*
         * Remove any stray files left in storage after seeding.
         * In the unpatched version, this loop was followed by a call to
         * DB::table('checkout_requests')->truncate(), which is no longer needed:contentReference[oaicite:0]{index=0}.
         */
        $del_files = Storage::files('assets');
        foreach ($del_files as $del_file) {
            Log::debug('Deleting: ' . $del_file);
            try {
                Storage::disk('public')->delete('assets' . '/' . $del_file);
            } catch (\Exception $e) {
                Log::debug($e);
            }
        }
    }

    private function ensureLocationsSeeded(): void
    {
        if (! Location::count()) {
            $this->call(LocationSeeder::class);
        }
    }

    private function ensureSuppliersSeeded(): void
    {
        if (! Supplier::count()) {
            $this->call(SupplierSeeder::class);
        }
    }

    // No companies needed for demo

    private function getState(): callable
    {
        return fn($sequence) => [
            'rtd_location_id' => $this->locationIds->random(),
            'supplier_id'     => $this->supplierIds->random(),
            'created_by'      => $this->adminuser->id,
        ];
    }
}
