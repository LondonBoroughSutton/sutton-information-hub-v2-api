<?php

namespace App\Providers;

use App\Models\Collection;
use App\Models\CollectionTaxonomy;
use App\Models\File;
use App\Models\Location;
use App\Models\Organisation;
use App\Models\OrganisationAdminInvite;
use App\Models\PendingOrganisationAdmin;
use App\Models\Referral;
use App\Models\Report;
use App\Models\Service;
use App\Models\ServiceLocation;
use App\Models\ServiceTaxonomy;
use App\Models\Taxonomy;
use App\Models\User;
use App\Observers\CollectionObserver;
use App\Observers\CollectionTaxonomyObserver;
use App\Observers\FileObserver;
use App\Observers\LocationObserver;
use App\Observers\OrganisationAdminInviteObserver;
use App\Observers\OrganisationObserver;
use App\Observers\PendingOrganisationAdminObserver;
use App\Observers\ReferralObserver;
use App\Observers\ReportObserver;
use App\Observers\ServiceLocationObserver;
use App\Observers\ServiceObserver;
use App\Observers\ServiceTaxonomyObserver;
use App\Observers\TaxonomyObserver;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class ModelServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot()
    {
        Collection::observe(CollectionObserver::class);
        CollectionTaxonomy::observe(CollectionTaxonomyObserver::class);
        File::observe(FileObserver::class);
        Location::observe(LocationObserver::class);
        OrganisationAdminInvite::observe(OrganisationAdminInviteObserver::class);
        Organisation::observe(OrganisationObserver::class);
        PendingOrganisationAdmin::observe(PendingOrganisationAdminObserver::class);
        Referral::observe(ReferralObserver::class);
        Report::observe(ReportObserver::class);
        ServiceLocation::observe(ServiceLocationObserver::class);
        Service::observe(ServiceObserver::class);
        ServiceTaxonomy::observe(ServiceTaxonomyObserver::class);
        Taxonomy::observe(TaxonomyObserver::class);

        Relation::morphMap([
            'services' => Service::class,
            'organisations' => Organisation::class,
            'referrals' => Referral::class,
            'users' => User::class,
        ]);
    }

    /**
     * Register services.
     */
    public function register()
    {
        //
    }
}
