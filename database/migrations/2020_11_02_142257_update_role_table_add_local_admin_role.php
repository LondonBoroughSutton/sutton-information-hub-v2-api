<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class UpdateRoleTableAddLocalAdminRole extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $now = Date::now();

        DB::table('roles')->insert([
            'id' => uuid(),
            'name' => 'Local Admin',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('roles')->where('name', 'Local Admin')->delete();
    }
}
