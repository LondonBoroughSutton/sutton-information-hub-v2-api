<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class RemoveHistoricUpdateRequestsReportType extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        $historicUpdateRequestsExportReportType = DB::table('report_types')
            ->where('name', '=', 'Historic Update Requests Export')
            ->first();

        DB::table('reports')
            ->where('report_type_id', '=', $historicUpdateRequestsExportReportType->id)
            ->delete();

        DB::table('report_types')
            ->where('name', '=', 'Historic Update Requests Export')
            ->delete();

        // Files are purposely left for historic access/auditing.
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        $now = Date::now();

        DB::table('report_types')->insert([
            'id' => uuid(),
            'name' => 'Historic Update Requests Export',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
