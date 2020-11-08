<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateOrganisationsMakeDescriptionNullable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('organisations', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
        });

        // If migrating after rollback, set default to null.
        DB::table('organisations')
            ->where('description', '=', '')
            ->update(['description' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Set default value for null.
        DB::table('organisations')
            ->whereNull('description')
            ->update(['description' => '']);

        Schema::table('organisations', function (Blueprint $table) {
            $table->text('description')->nullable(false)->change();
        });
    }
}
