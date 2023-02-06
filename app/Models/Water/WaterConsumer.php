<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WaterConsumer extends Model
{
    use HasFactory;

    /**
     * | get the water consumer detaials by consumr No
     * | @param consumerNo
     * | @var 
     * | @return 
     */
    public function getDetailByConsumerNo($key, $refNo)
    {
        return WaterConsumer::select(
            'water_consumers.consumer_no',
            'water_consumers.ward_id',
            'water_consumers.address',
            'water_consumers.holding_no',
            'water_consumers.saf_no',
            'ulb_ward_masters.ward_name',
            DB::raw("string_agg(water_consumer_owners.applicant_name,',') as ownerName"),
            DB::raw("string_agg(water_consumer_owners.mobile_no::VARCHAR,',') as mobileNo"),
            DB::raw("string_agg(water_consumer_owners.father_name,',') as FatherName"),
        )
            ->join('water_consumer_owners', 'water_consumer_owners.consumer_id', '=', 'water_consumers.id')
            ->leftJoin('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'water_consumers.ward_id')
            ->where('water_consumers.' . $key, 'LIKE', '%' . $refNo . '%')
            ->where('consumer_status', true)
            ->where('ulb_ward_masters.status', true)
            ->groupBy('water_consumers.saf_no', 'water_consumers.holding_no', 'water_consumers.address', 'water_consumers.id', 'water_consumer_owners.consumer_id', 'water_consumers.consumer_no', 'water_consumers.ward_id', 'ulb_ward_masters.ward_name')
            ->get();
    }


    /**
     * | get the water consumer detaials by Owner details
     * | @param consumerNo
     * | @var 
     * | @return 
     */
    public function getDetailByOwnerDetails($key, $refVal)
    {
        return WaterConsumer::select(
            'water_consumers.consumer_no',
            'water_consumers.ward_id',
            'water_consumers.address',
            'water_consumers.holding_no',
            'water_consumers.saf_no',
            'ulb_ward_masters.ward_name',
            'water_consumer_owners.applicant_name as ownerName',
            'water_consumer_owners.mobile_no as mobileNo',
            'water_consumer_owners.father_name as FatherName',
        )
            ->join('water_consumer_owners', 'water_consumer_owners.consumer_id', '=', 'water_consumers.id')
            ->leftJoin('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'water_consumers.ward_id')
            ->where('water_consumer_owners.' . $key, 'LIKE', '%' . $refVal . '%')
            ->where('consumer_status', true)
            ->where('ulb_ward_masters.status', true)
            ->get();
    }
}
