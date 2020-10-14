<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateSettingsTableCmsDefaultValue extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::table('settings')
            ->where('key', '=', 'cms')
            ->update([
                'value' => DB::raw(
                    <<<'EOT'
JSON_SET(
    `value`,
    "$.frontend.providers",JSON_OBJECT("title",`value`->>"$.frontend.get_involved.title","content",`value`->>"$.frontend.get_involved.content"),
    "$.frontend.supporters",JSON_OBJECT("title","Title","content","Content"),
    "$.frontend.funders",JSON_OBJECT("title","Title","content","Content")
)
EOT
                ),
            ]);
        DB::table('settings')
            ->where('key', '=', 'cms')
            ->update([
                'value' => DB::raw('JSON_REMOVE(`value`,"$.frontend.get_involved", "$.frontend.about.video_url")'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::table('settings')
            ->where('key', '=', 'cms')
            ->update([
                'value' => DB::raw(
                    <<<'EOT'
JSON_SET(
    `value`,
    "$.frontend.get_involved",JSON_OBJECT("title",`value`->>"$.frontend.providers.title","content",`value`->>"$.frontend.providers.content"),
    "$.frontend.about.video_url","Video URL")
)
EOT
                ),
            ]);

        DB::table('settings')
            ->where('key', '=', 'cms')
            ->update([
                'value' => DB::raw('JSON_REMOVE(`value`,"$.frontend.providers", "$.frontend.supporters","$.frontend.funders")'),
            ]);
    }
}
