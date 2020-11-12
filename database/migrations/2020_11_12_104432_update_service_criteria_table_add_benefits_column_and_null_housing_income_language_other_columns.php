<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateServiceCriteriaTableAddBenefitsColumnAndNullHousingIncomeLanguageOtherColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('service_criteria', function (Blueprint $table) {
            $table->string('benefits')->after('gender')->nullable();
        });

        DB::table('service_criteria')
            ->update([
                'housing' => null,
                'income' => null,
                'language' => null,
                'other' => null,
            ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('service_criteria', function (Blueprint $table) {
            $table->dropColumn('benefits');
        });
    }
}
