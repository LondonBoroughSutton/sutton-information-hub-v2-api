<?php

namespace App\Models;

use App\Models\Mutators\ServiceCriterionMutators;
use App\Models\Relationships\ServiceCriterionRelationships;
use App\Models\Scopes\ServiceCriterionScopes;

class ServiceCriterion extends Model
{
    use ServiceCriterionMutators;
    use ServiceCriterionRelationships;
    use ServiceCriterionScopes;

    const EMPLOYMENT_FIELD_OPTIONS = [
        'Employed full-time',
        'Employed part-time',
        'Full-time student',
        'In employment',
        'Long term sick or disabled',
        'Looking after home or family',
        'Not in employment',
        'Other',
        'Retired',
        'Self employed',
        'Severe long term sick or disabled',
        'Student (including full time students)',
    ];
    const BENEFIT_FIELD_OPTIONS = [
        'Housing Benefits and Council Tax Benefits',
        'Council Tax Benefits',
        'Council Tax Benefits only',
        'Employment and Support Allowance or incapacity benefit',
        'Housing Benefits',
        'Housing Benefits only',
        'Jobseeker\'s Allowance',
        'Lone Parent',
    ];
    const AGE_GROUP_FIELD_OPTIONS = [
        'Under 16',
        '16 to 24',
        '25 to 64',
        '65 to 84',
        '65 and over',
        '85 and over',
    ];
    const GENDER_FIELD_OPTIONS = [
        'Female',
        'Male',
        'Non-binary',
        'Other',
        'Transgender',
    ];
    const DISABILITIES_FIELD_OPTIONS = [
        'Autistic',
        'Blind or partially sighted',
        'Deaf blind',
        'Learning disability',
        'Physically disabled',
        'Severely or profoundly deaf',
        'Wheelchair user',
        'Without speech',
    ];
}
