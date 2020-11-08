<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLocalAuthoritiesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('local_authorities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('alt_name')->nullable();
            $table->char('code', 10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('local_authorities');
    }
}
