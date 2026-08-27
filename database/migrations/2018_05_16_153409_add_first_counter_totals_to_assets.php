<?php

use Illuminate\Database\Migrations\Migration;

class AddFirstCounterTotalsToAssets extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Checkout, check-in, and request counters are retained as historical
        // data in this fork and must not be rebuilt from retired workflows.
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
