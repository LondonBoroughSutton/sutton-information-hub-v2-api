<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUsersTableAddFieldsForLocalAdmin extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('employer_name')->after('password')->nullable();
            $table->uuid('location_id')->nullable()->after('employer_name');
            $table->foreign('location_id')->references('id')->on('locations');
            $table->uuid('local_authority_id')->nullable()->after('location_id');
            $table->foreign('local_authority_id')->references('id')->on('local_authorities');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['local_authority_id']);
            $table->dropColumn('local_authority_id');
            $table->dropForeign(['location_id']);
            $table->dropColumn('location_id');
            $table->dropColumn('employer_name');
        });
    }
}
