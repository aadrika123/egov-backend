<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropOwner extends Model
{
    use HasFactory;

    //owner details by propertyId
    public function getOwnerDetails($request)
    {
        return PropOwner::select(
            'owner_name as name',
            'mobile_no as mobileNo',
            'prop_address as address'
        )
            ->join('prop_properties', 'prop_properties.id', '=', 'prop_owners.property_id')
            ->where('prop_properties.id', $request->propId)
            ->first();
    }
}
