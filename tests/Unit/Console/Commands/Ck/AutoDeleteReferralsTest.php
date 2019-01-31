<?php

namespace Tests\Unit\Console\Commands\Ck;

use App\Console\Commands\Ck\AutoDeleteReferralsCommand;
use App\Models\Referral;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class AutoDeleteReferralsTest extends TestCase
{
    public function test_auto_delete_works()
    {
        $newReferral = factory(Referral::class)->create([
            'email' => 'test@example.com',
            'referee_email' => 'test@example.com',
            'status' => Referral::STATUS_COMPLETED,
            'created_at' => today(),
            'updated_at' => today(),
        ]);

        $dueForDeletionReferral = factory(Referral::class)->create([
            'email' => 'test@example.com',
            'referee_email' => 'test@example.com',
            'status' => Referral::STATUS_COMPLETED,
            'created_at' => today()->subMonths(Referral::AUTO_DELETE_MONTHS),
            'updated_at' => today()->subMonths(Referral::AUTO_DELETE_MONTHS),
        ]);

        Artisan::call(AutoDeleteReferralsCommand::class);

        $this->assertDatabaseHas($newReferral->getTable(), ['id' => $newReferral->id]);
        $this->assertDatabaseMissing($dueForDeletionReferral->getTable(), ['id' => $dueForDeletionReferral->id]);
    }

    public function test_old_incompleted_referrals_are_not_deleted()
    {
        $dueForDeletionReferral = factory(Referral::class)->create([
            'email' => 'test@example.com',
            'referee_email' => 'test@example.com',
            'status' => Referral::STATUS_INCOMPLETED,
            'created_at' => today()->subMonths(Referral::AUTO_DELETE_MONTHS),
            'updated_at' => today()->subMonths(Referral::AUTO_DELETE_MONTHS),
        ]);

        Artisan::call(AutoDeleteReferralsCommand::class);

        $this->assertDatabaseHas($dueForDeletionReferral->getTable(), ['id' => $dueForDeletionReferral->id]);
    }
}
