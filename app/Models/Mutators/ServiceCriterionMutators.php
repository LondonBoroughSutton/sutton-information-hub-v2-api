<?php

namespace App\Models\Mutators;

trait ServiceCriterionMutators
{
    /**
     * Get the Employment attribute as an array
     *
     * @param String $value
     * @return Array
     **/
    public function getEmploymentAttribute($value)
    {
        return $value ? explode('|', $value) : null;
    }

    /**
     * Set the Employment attribute as a string
     *
     * @param Array $value
     * @return null
     **/
    public function setEmploymentAttribute($value)
    {
        $this->attributes['employment'] = implode('|', (array) $value);
    }

    /**
     * Get the Benefits attribute as an array
     *
     * @param String $value
     * @return Array
     **/
    public function getBenefitsAttribute($value)
    {
        return $value ? explode('|', $value) : null;
    }

    /**
     * Set the Benefits attribute as a string
     *
     * @param Array $value
     * @return null
     **/
    public function setBenefitsAttribute($value)
    {
        $this->attributes['benefits'] = implode('|', (array) $value);
    }

    /**
     * Get the AgeGroup attribute as an array
     *
     * @param String $value
     * @return Array
     **/
    public function getAgeGroupAttribute($value)
    {
        return $value ? explode('|', $value) : null;
    }

    /**
     * Set the AgeGroup attribute as a string
     *
     * @param Array $value
     * @return null
     **/
    public function setAgeGroupAttribute($value)
    {
        $this->attributes['age_group'] = implode('|', (array) $value);
    }

    /**
     * Get the Gender attribute as an array
     *
     * @param String $value
     * @return Array
     **/
    public function getGenderAttribute($value)
    {
        return $value ? explode('|', $value) : null;
    }

    /**
     * Set the Gender attribute as a string
     *
     * @param Array $value
     * @return null
     **/
    public function setGenderAttribute($value)
    {
        $this->attributes['gender'] = implode('|', (array) $value);
    }

    /**
     * Get the Disability attribute as an array
     *
     * @param String $value
     * @return Array
     **/
    public function getDisabilityAttribute($value)
    {
        return $value ? explode('|', $value) : null;
    }

    /**
     * Set the Disability attribute as a string
     *
     * @param Array $value
     * @return null
     **/
    public function setDisabilityAttribute($value)
    {
        $this->attributes['disability'] = implode('|', (array) $value);
    }
}
