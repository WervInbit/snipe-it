<?php

use App\Models\Location;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $location = Location::withTrashed()->firstOrCreate(
            ['name' => 'Misc/Custom', 'parent_id' => null],
            ['location_type' => Location::determineType(null)]
        );
        if ($location->trashed()) {
            $location->restore();
        }
    }

    public function down(): void
    {
        $location = Location::where('name', 'Misc/Custom')->whereNull('parent_id')->first();
        if ($location) {
            $location->delete();
        }
    }
};
