<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MakePhoneColumnNullableOnUsersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->change();
        });

        // If migrating after rollback, set default to null.
        DB::table('users')
            ->where('phone', '=', '00000000000')
            ->update(['phone' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Set default value for null.
        DB::table('users')
            ->whereNull('phone')
            ->update(['phone' => '00000000000']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable(false)->change();
        });
    }
}
