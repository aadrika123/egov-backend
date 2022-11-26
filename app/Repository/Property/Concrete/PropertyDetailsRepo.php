<?php

namespace App\Repository\Property\Concrete;

use App\Models\Property\PropProperty;
use App\Repository\Property\Interfaces\iPropertyDetailsRepo;
use Exception;

class PropertyDetailsRepo implements iPropertyDetailsRepo
{
    /**
     * | Created On-26-11-2022 
     * | Created By-Sam kerkettta
     * --------------------------------------------------------------------------------------
     * | Controller regarding with Propery Module (Property Details)
     */




    /**
     * |--------------------------------------- filtring the details of Property / returning-----------------------------------------------
     * | @param request
     * | @var requestDetails
     */
    public function getFilterProperty($request)
    {
        try {
            $requestDetails = $request->filteredBy;
            switch ($requestDetails) {
                case ("HoldingNo"): {
                        $filterByHolding = $this->searchByHolding($request);
                        if (empty($filterByHolding['0'])) {
                            return responseMsg(false, "Data Not Found!", $request->search);
                        }
                        return responseMsg(true, "Data According to Holding!", $filterByHolding);
                    }
                case ("OwnerDetail"): {
                        $filterByOwner = $this->searchByOwner($request);
                        if (empty($filterByOwner['0'])) {
                            return responseMsg(false, "Data Not Found!", $request->search);
                        }
                        return responseMsg(true, "Data According to Owner!", $filterByOwner);
                    }
                case ("Address"): {
                        $filterByAddress = $this->searchByAddress($request);
                        if (empty($filterByAddress['0'])) {
                            return responseMsg(false, "Data Not Found!", $request->search);
                        }
                        return responseMsg(true, "Data According to Address!", $filterByAddress);
                    }
                default:
                    return responseMsg(false,"Not a Valid Entry for Filtration Error Retry!","");
            }
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }

    /**
     * |-------------------------------- Called Function for the prop Details 1.1 -----------------------------------------------
     * | @param request
     * | Searching the details of the property according to holdingNo 
     */
    public function searchByHolding($request)
    {
        return PropProperty::select(
            'prop_properties.new_holding_no AS holdingNo',
            'prop_properties.prop_address AS address',
            'corr_address AS correspondingAddress',
            'owner_name AS ownerName',
            'mobile_no AS mobileNo'
        )
            ->join('prop_owners', 'prop_owners.property_id', '=', 'prop_properties.id')
            ->where('new_holding_no', $request->search)
            ->get();
    }

    /**
     * |-------------------------------- Called Function for the prop Details 1.2 -----------------------------------------------
     * | @param request
     * | Searching the detais of the property according to owner details ie. owners Name/ owners Mobile name / Pan no / addhar card
     */
    public function searchByOwner($request)
    {
        if (($request->wardId) == 0) {
            return PropProperty::select(
                'prop_properties.new_holding_no AS holdingNo',
                'prop_properties.prop_address AS address',
                'corr_address AS correspondingAddress',
                'owner_name AS ownerName',
                'mobile_no AS mobileNo'
            )
                ->join('prop_owners', 'prop_owners.property_id', '=', 'prop_properties.id')
                ->where('owner_name', $request->search)
                ->orwhere('mobile_no', $request->search)
                ->orwhere('pan_no', $request->search)
                ->orwhere('aadhar_no', $request->search)
                ->get();
        }
        return PropProperty::select(
            'prop_properties.new_holding_no AS holdingNo',
            'prop_properties.prop_address AS address',
            'corr_address AS correspondingAddress',
            'owner_name AS ownerName',
            'mobile_no AS mobileNo'
        )
            ->join('prop_owners', 'prop_owners.property_id', '=', 'prop_properties.id')
            ->where('ward_mstr_id', $request->wardId)
            ->where('owner_name', $request->search)
            ->orwhere('mobile_no', $request->search)
            ->orwhere('pan_no', $request->search)
            ->orwhere('aadhar_no', $request->search)
            ->get();
    }

    /**
     * |-------------------------------- Called Function for the prop Details 1.2 -----------------------------------------------
     * | @param request
     * | searching the details of the owners according to addresss 
     */
    public function searchByAddress($request)
    {
        // return $request->wardId;
        if (($request->wardId) == 0) {
            return ("this");
            return PropProperty::select(
                'prop_properties.new_holding_no AS holdingNo',
                'prop_properties.prop_address AS address',
                'corr_address AS correspondingAddress',
                'owner_name AS ownerName',
                'mobile_no AS mobileNo'
            )
                ->join('prop_owners', 'prop_owners.property_id', '=', 'prop_properties.id')
                ->where('prop_properties.prop_address', $request->search)
                ->get();
        }
        return PropProperty::select(
            'prop_properties.new_holding_no AS holdingNo',
            'prop_properties.prop_address AS address',
            'corr_address AS correspondingAddress',
            'owner_name AS ownerName',
            'mobile_no AS mobileNo'
        )
            ->join('prop_owners', 'prop_owners.property_id', '=', 'prop_properties.id')
            ->where('prop_properties.ward_mstr_id', $request->wardId)
            ->where('prop_properties.prop_address', $request->search)
            ->get();
    }
}
