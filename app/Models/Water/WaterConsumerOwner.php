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

    /* 
    | Get Owner Details According to Filter Conditions
    | @param filterConditions
    | created by: Alok
    */
    public function getOwnerDetails($filterConditions)
    {
        $query = self::select(
            'water_consumer_owners.id',
            'water_consumer_owners.consumer_id',
            'water_consumer_owners.applicant_name',
            'water_consumer_owners.guardian_name',
            'water_consumer_owners.mobile_no',
            'water_consumers.address',
            'water_consumers.category',
            'water_consumers.area_sqft',
            'water_consumers.area_sqmt',
            'water_consumers.consumer_no',
            'water_consumers.holding_no',
            'water_approval_application_details.application_no',
            'water_consumers.saf_no',
            'water_consumers.ulb_id',
            'ulb_masters.ulb_name',

            DB::raw("CASE WHEN water_consumer_owners.status = 'true' THEN 'Active' ELSE 'Inactive' END AS status")
        )
        ->join('water_consumers', 'water_consumers.id', '=', 'water_consumer_owners.consumer_id')
        ->join('water_approval_application_details', 'water_approval_application_details.id', '=', 'water_consumers.id')
        ->leftJoin('ulb_masters', 'ulb_masters.id', '=', 'water_consumers.ulb_id');
        
          // Apply filter conditions
          foreach ($filterConditions as $condition) {
            $query->orWhere($condition[0], $condition[1], $condition[2]); 
        }

        $query->where('water_consumers.ulb_id', '=', 2);
        $query->where('water_consumer_owners.status', '=', true);

        return $query->groupBy(
            'water_consumer_owners.id',
            'water_consumer_owners.consumer_id',
            'water_consumer_owners.applicant_name',
            'water_consumer_owners.guardian_name',            
            'water_consumers.address',
            'water_consumer_owners.mobile_no',
            'water_consumers.consumer_no',
            'water_consumers.holding_no',
            'water_consumers.saf_no',
            'water_consumers.ulb_id',
            'ulb_masters.ulb_name',
            'water_consumers.category',
            'water_consumers.area_sqft',
            'water_consumers.area_sqmt',
            'water_approval_application_details.application_no',
            'water_consumer_owners.status'
        )->get();
    }
}
