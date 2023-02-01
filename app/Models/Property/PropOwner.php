<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PropOwner extends Model
{
    use HasFactory;

    //owner details by ownerId
    public function getOwnerDetail($request)
    {
        return PropOwner::select(
            'prop_owners.id',
            'prop_owners.owner_name',
            'prop_owners.mobile_no',
            'corr_address',
            'corr_city',
            'corr_dist',
            'corr_pin_code',
            'corr_state'
        )
            ->join('prop_properties', 'prop_properties.id', '=', 'prop_owners.property_id')
            ->where('prop_owners.id', $request->ownerId)
            ->first();
    }

    // Get Owners by Property Id
    public function getOwnersByPropId($propertyId)
    {
        return DB::table('prop_owners')
            ->where('property_id', $propertyId)
            ->get();
    }

    /**
     * | Get The first Owner by Property Id
     */
    public function getOwnerByPropId($propId)
    {
        return PropOwner::where('property_id', $propId)
            ->select(
                'owner_name as ownerName',
                'mobile_no as mobileNo',
                'guardian_name as guardianName',
                'email',
                'gender',
                'is_armed_force',
                'is_specially_abled'
            )
            ->orderBy('id')
            ->get();
    }

}
