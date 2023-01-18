<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PropOwner extends Model
{
    use HasFactory;

    //owner details by propertyId
    public function getOwnerDetails($request)
    {
        return PropOwner::select(
            'prop_owners.owner_name as name',
            'prop_owners.mobile_no as mobileNo',
            'prop_properties.prop_address as address'
        )
            ->join('prop_properties', 'prop_properties.id', '=', 'prop_owners.property_id')
            ->where('prop_owners.property_id', $request->propId)
            ->get();
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
                'owner_name',
                'mobile_no'
            )
            ->orderByDesc('id')
            ->first();
    }
}
