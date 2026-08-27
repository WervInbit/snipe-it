<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('saml_nonces')
            ->select('nonce')
            ->groupBy('nonce')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('nonce')
            ->each(function (string $nonce): void {
                $keepId = DB::table('saml_nonces')
                    ->where('nonce', $nonce)
                    ->min('id');

                DB::table('saml_nonces')
                    ->where('nonce', $nonce)
                    ->where('id', '<>', $keepId)
                    ->delete();
            });

        Schema::table('saml_nonces', function (Blueprint $table) {
            $table->dropIndex(['nonce']);
            $table->unique('nonce');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saml_nonces', function (Blueprint $table) {
            $table->dropUnique(['nonce']);
            $table->index('nonce');
        });
    }
};
