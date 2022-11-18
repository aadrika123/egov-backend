<?php

namespace App\Repository\Property\Concrete;

use App\Models\Property\PropOwnerDtl;
use Exception;
use Illuminate\Http\Request;
use App\Repository\Property\Interfaces\iObjectionRepository;


class ObjectionRepository implements iObjectionRepository
{
    public function ClericalMistake(Request $request)
    {
        $data = $this->getOwnerDetails($request->id);
    }

    //get owner details
    public function getOwnerDetails(Request $request)
    {
        try {
            $ownerDetails = PropOwnerDtl::select('owner_name', 'mobile_no', 'prop_address')
                ->where('prop_properties.holding_no', $request->holdingNo)
                ->join('prop_properties', 'prop_properties.id', '=', 'prop_owner_dtls.property_id')
                ->get();
            return $ownerDetails;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    //save,upload and obbjection number generation
    public function rectification()
}
