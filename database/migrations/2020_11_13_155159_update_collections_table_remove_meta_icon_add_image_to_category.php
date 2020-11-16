<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateCollectionsTableRemoveMetaIconAddImageToCategory extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::table('collections')
            ->where('type', '=', 'category')
            ->update([
                'meta' => DB::raw('JSON_REMOVE(`meta`,"$.icon")'),
            ]);
        DB::table('collections')
            ->where('type', '=', 'category')
            ->update([
                'meta' => DB::raw('JSON_SET(`meta`,"$.image_file_id", NULL)'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::table('collections')
            ->where('type', '=', 'category')
            ->update([
                'meta' => DB::raw('JSON_REMOVE(`meta`,"$.image_file_id")'),
            ]);
        DB::table('collections')
            ->where('type', '=', 'category')
            ->update([
                'meta' => DB::raw('JSON_SET(`meta`,"$.icon", NULL)'),
            ]);
    }
}
