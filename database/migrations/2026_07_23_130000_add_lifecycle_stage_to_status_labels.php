<?php

use App\Models\Statuslabel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('status_labels', 'lifecycle_stage')) {
            Schema::table('status_labels', function (Blueprint $table): void {
                $table->string('lifecycle_stage', 32)->nullable()->after('archived')->index();
            });
        }

        $stageByLegacyName = [
            'ready for sale' => Statuslabel::LIFECYCLE_READY_FOR_SALE,
            'for sale' => Statuslabel::LIFECYCLE_READY_FOR_SALE,
            'ready to sell' => Statuslabel::LIFECYCLE_READY_FOR_SALE,
            'sold' => Statuslabel::LIFECYCLE_SOLD,
            'sold to customer' => Statuslabel::LIFECYCLE_SOLD,
            'sold / shipped' => Statuslabel::LIFECYCLE_SOLD,
            'broken / parts' => Statuslabel::LIFECYCLE_BROKEN_PARTS,
            'broken/parts' => Statuslabel::LIFECYCLE_BROKEN_PARTS,
            'broken/spare parts' => Statuslabel::LIFECYCLE_BROKEN_PARTS,
            'broken / for parts' => Statuslabel::LIFECYCLE_BROKEN_PARTS,
            'broken - not fixable' => Statuslabel::LIFECYCLE_BROKEN_PARTS,
            'returned / rma' => Statuslabel::LIFECYCLE_RETURNED,
            'returned - pending' => Statuslabel::LIFECYCLE_RETURNED,
            'returned – pending' => Statuslabel::LIFECYCLE_RETURNED,
            'pending destruction' => Statuslabel::LIFECYCLE_DESTRUCTION_PENDING,
            'destruction pending' => Statuslabel::LIFECYCLE_DESTRUCTION_PENDING,
            'destroyed' => Statuslabel::LIFECYCLE_DESTROYED,
            'destroyed / recycled' => Statuslabel::LIFECYCLE_DESTROYED,
        ];

        DB::table('status_labels')
            ->select(['id', 'name', 'lifecycle_stage'])
            ->orderBy('id')
            ->get()
            ->each(function (object $status) use ($stageByLegacyName): void {
                if ($status->lifecycle_stage !== null && $status->lifecycle_stage !== '') {
                    return;
                }

                $legacyName = mb_strtolower(trim((string) $status->name));
                $stage = $stageByLegacyName[$legacyName] ?? null;

                if ($stage !== null) {
                    DB::table('status_labels')
                        ->where('id', $status->id)
                        ->update(['lifecycle_stage' => $stage]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('status_labels', 'lifecycle_stage')) {
            Schema::table('status_labels', function (Blueprint $table): void {
                $table->dropColumn('lifecycle_stage');
            });
        }
    }
};
