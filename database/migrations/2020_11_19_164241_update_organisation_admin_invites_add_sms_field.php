<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateOrganisationAdminInvitesAddSmsField extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('organisation_admin_invites', function (Blueprint $table) {
            $table->string('sms')->after('email')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('organisation_admin_invites', function (Blueprint $table) {
            $table->dropColumn('sms');
        });
    }
}
