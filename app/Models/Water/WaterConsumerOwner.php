<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WaterConsumerOwner extends Model
{
    use HasFactory;
    protected $connection = 'pgsql_water';

    /**
     * | Get Consumer Details According to ConsumerId
     * | @param ConsumerId
     * | @return list / List of owners
     */
    public function getConsumerOwner($consumerId)
    {
        return WaterConsumerOwner::where('status', true)
            ->where('consumer_id', $consumerId);
    }

    public function ownerByApplication($consumerId)
    {
        return WaterConsumerOwner::select(
            'water_consumer_owners.applicant_name as owner_name',
            'guardian_name',
            'mobile_no',
            'email',
            'city',
            'district'
        )
            // ->join('water_applications', 'water_applications.id', '=', 'water_applicants.application_id')
            ->where('water_consumer_owners.consumer_id', $consumerId)
            ->where('water_consumer_owners.status', 1);
    }
}
