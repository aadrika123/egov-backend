<?php

namespace App\Http\Controllers;

use App\Models\MPropBuildingRentalconst;
use App\Models\MPropForgeryType;
use App\Models\MPropRentalValue;
use App\Models\MPropVacanatRentalrate;
use App\Models\MPropVacantRentalrate;
use App\Models\PropApartmentdtl;
use App\Models\Property\MPropBuildingRentalrate;
use App\Models\Property\RefPropTransferMode;
use App\Models\RefPropBuildingRenatlRate;
use App\Models\RefPropConstructionType;
use App\Models\RefPropFloor;
use App\Models\RefPropGbbuildingusagetype;
use App\Models\RefPropGbpropusagetype;
use App\Models\RefPropObjectionType;
use App\Models\RefPropOccupancyFactor;
use App\Models\RefPropOccupancyType;
use App\Models\RefPropOwnershipType;
use App\Models\RefPropPenaltyType;
use App\Models\RefPropRebateType;
use App\Models\RefPropRoadType;
use App\Models\RefPropType;
use App\Models\RefPropUsageType;
use Illuminate\Http\Request;

/**
 * | Creation of Reference APIs
 * | Created By- Tannu Verma
 * | Created On- 24-05-2023 
 * | Serial No. - 21
 * | Status-Closed
 */

/**
 * | Functions for creation of Reference APIs
 * 1. listBuildingRentalconst()
 * 2. listPropForgeryType()
 * 3. listPropRentalValue()
 * 4. listPropApartmentdtl()
 * 5. listBropBuildingRentalrate()
 * 6. listPropVacantRentalrate()
 * 7. listPropConstructiontype()
 * 8. listPropFloor()
 * 9. listPropgbBuildingUsagetype()
 * 10. listPropgbPropUsagetype()
 * 11. listPropObjectiontype()
 * 12. listPropOccupancyFactor()
 * 13. listPropOccupancytype()
 * 14. listPropOwnershiptype()
 * 15. listPropPenaltytype()
 * 16. listPropRebatetype()
 * 17. listPropRoadtype()
 * 18. listPropTransfermode()
 * 19. listPropType()
 * 20. listPropUsagetype()
 */


class ReferenceController extends Controller
{
    /** 
     * 1. listBuildingRentalconst()
     *    Display List for Building Rental Const
     */
    public function listBuildingRentalconst(Request $request)
    {
        try {
            $m_buildingRentalconst = MPropBuildingRentalconst::where('status', 1)
                ->get();

            return responseMsgs(true, 'Building Rental Const Retrieved Successfully', $m_buildingRentalconst, "012101", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (\Exception $e) {
            return responseMsgs(false, $e->getMessage(), "",                                                  "012101", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }

    /** 
     * 2. listPropForgeryType()
     *    Display List for Property Forgery type
     */

    public function listPropForgeryType(Request $request)
    {
        try {
            $m_propforgerytype = MPropForgeryType::where('status', 1)
                ->get();

            return responseMsgs(true, 'Forgery type Retrieved Successfully', $m_propforgerytype, "012102", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (\Exception $e) {
            return responseMsgs(false, $e->getMessage(), "",                                     "012102", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }


    /** 
     * 3. listPropRentalValue()
     *    Display List for Property rental Value
     */

    public function listPropRentalValue(Request $request)
    {
        try {
            $m_proprentalvalue = MPropRentalValue::where('status', 1)
                ->get();

            return responseMsgs(true, 'Rental Value Retrieved Successfully', $m_proprentalvalue, "012103", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (\Exception $e) {
            return responseMsgs(false, $e->getMessage(), "",                                     "012103", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }


    /** 
     * 4. listPropApartmentdtl()
     *    Display List for Apartment Detail
     */

    public function listPropApartmentdtl(Request $request)
    {
        try {
            $m_propapartmentdtl = PropApartmentDtl::where('status', 1)
                ->get();

            return responseMsgs(true, 'Apartment details Retrieved Successfully', $m_propapartmentdtl, "012104", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (\Exception $e) {
            return responseMsgs(false, $e->getMessage(), "",                                           "012104", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }


    /** 
     * 5. listPropBuildingRentalrate()
     *    Display List for Building Rental Rate
     */

    public function listPropBuildingRentalrate(Request $request)
    {
        try {
            $m_propbuildingrentalrate = MPropBuildingRentalrate::where('status', 1)
                ->get();

            return responseMsgs(true, 'Building Rental Rate Retrieved Successfully', $m_propbuildingrentalrate, "012105", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (\Exception $e) {
            return responseMsgs(false, $e->getMessage(), "",                                                    "012105", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }


    /** 
     * 6. listPropVacantRentalrate()
     *    Display List for Vacant Rental Rate
     */

    public function listPropVacantRentalrate(Request $request)
    {
        try {
            $status = $request->input('status', 1); // Status filter, default is 1

            $m_propvacantrentalrate = MPropVacantRentalrate::where('status', $status)
                ->get();

            return responseMsgs(true, 'Vacant Rental Rate Retrieved Successfully', $m_propvacantrentalrate,  "012106", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (\Exception $e) {
            return responseMsgs(false, $e->getMessage(), "",                                                 "012106", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }


    /** 
     * 7. listPropConstructiontype()
     *    Display List for Property Construction Type
     */

    public function listPropConstructiontype(Request $request)
    {
        try {
            $m_propconstructiontype = RefPropConstructionType::where('status', 1)
                ->get();

            return responseMsgs(true,  'Construction Type Retrieved Successfully', $m_propconstructiontype, "012107", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (\Exception $e) {
            return responseMsgs(false, $e->getMessage(), "",                                                "012107", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }


    /** 
     * 8. listPropFloor()
     *    Display List for Property Floor
     */

    public function listPropFloor(Request $request)
    {
        try {
            $m_propfloor = RefPropFloor::where('status', 1)
                ->orderby('id')
                ->get();

            return responseMsgs(true, 'Floor Type Retrieved Successfully', $m_propfloor, "012108", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (\Exception $e) {
            return responseMsgs(false, $e->getMessage(), "",                             "012108", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }


    /** 
     * 9. listPropgbBuildingUsagetype()
     *    Display List for Property GB Building Usage Type
     */

    public function listPropgbBuildingUsagetype(Request $request)
    {
        try {
            $m_propgbbuildingusagetype = RefPropGbbuildingusagetype::where('status', 1)
                ->get();

            return responseMsgs(true, 'GB Building Usage Type Retrieved Successfully', $m_propgbbuildingusagetype, "012109", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (\Exception $e) {
            return responseMsgs(false, $e->getMessage(), "",                                                       "012109", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }


    /** 
     * 10. listPropgbPropUsagetype()
     *    Display List for Property Usage Type
     */

    public function listPropgbPropUsagetype(Request $request)
    {
        try {

            $m_propgbpropusagetype = RefPropGbpropusagetype::where('status', 1)
                ->get();

            return responseMsgs(true, 'GB Property Usage Type Retrieved Successfully', $m_propgbpropusagetype, "012110", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (\Exception $e) {
            return responseMsgs(false, $e->getMessage(), "",                                                   "012110", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }


    /** 
     * 11. listPropObjectiontype()
     *    Display List for Property Objection Type
     */

    public function listpropobjectiontype(Request $request)
    {
        try {
            $m_propobjectiontype = RefPropObjectionType::where('status', 1)
                ->get();

            return responseMsgs(true, 'Property Objection Type Retrieved Successfully', $m_propobjectiontype, "012111", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (\Exception $e) {
            return responseMsgs(false, $e->getMessage(), "",                                                  "012111", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }


    /** 
     * 12. listPropOccupancyFactor()
     *    Display List for Property Occupancy Factor
     */

    public function listPropOccupancyFactor(Request $request)
    {
        try {
            $m_propoccupancyfactor = RefPropOccupancyFactor::where('status', 1)
                ->get();

            return responseMsgs(true, 'Property Occupancy Factor Retrieved Successfully', $m_propoccupancyfactor, "012112", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (\Exception $e) {
            return responseMsgs(false, $e->getMessage(), "",                                                       "012112", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }


    /** 
     * 13. listPropOccupancytype()
     *    Display List for Property Occupancy Type
     */

    public function listPropOccupancytype(Request $request)
    {
        try {
            $m_propoccupancytype = RefPropOccupancyType::where('status', 1)
                ->get();

            return responseMsgs(true, 'Property Occupancy Type Retrieved Successfully', $m_propoccupancytype, "012113", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (\Exception $e) {
            return responseMsgs(false, $e->getMessage(), "",                                                  "012113", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }


    /** 
     * 14. listPropOwnershiptype()
     *    Display List for Property Ownership Type
     */

    public function listPropOwnershiptype(Request $request)
    {
        try {
            $m_propownershiptype = RefPropOwnershipType::where('status', 1)
                ->get();

            return responseMsgs(true, 'Property Ownership Type Retrieved Successfully', $m_propownershiptype, "012114", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (\Exception $e) {
            return responseMsgs(false, $e->getMessage(), "",                                                  "012114", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }

    /** 
     * 15. listPropPenaltytype()
     *    Display List for Property Penalty Type
     */

    public function listPropPenaltytype(Request $request)
    {
        try {
            $m_proppenaltytype = RefPropPenaltyType::where('status', 1)
                ->get();

            return responseMsgs(true, 'Property Penalty Type Retrieved Successfully', $m_proppenaltytype, "012115", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (\Exception $e) {
            return responseMsgs(false, $e->getMessage(), "",                                              "012115", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }

    /** 
     * 16. listPropRebatetype()
     *    Display List for Property Rebate Type
     */

    public function listPropRebatetype(Request $request)
    {
        try {
            $m_proprebatetype = RefPropRebateType::where('status', 1)
                ->get();

            return responseMsgs(true,  'Property Rebate Type Retrieved Successfully', $m_proprebatetype, "012116", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (\Exception $e) {
            return responseMsgs(false, $e->getMessage(), "",                                             "012116", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }


    /** 
     * 17. listPropRoadtype()
     *    Display List for Property Road Type
     */

    public function listPropRoadtype(Request $request)
    {
        try {
            $m_proproadtype = RefPropRoadType::where('status', 1)
                ->get();

            return responseMsgs(true, 'Property Road Type Retrieved Successfully', $m_proproadtype, "012117", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (\Exception $e) {
            return responseMsgs(false, $e->getMessage(), "",                                        "012117", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }



    /** 
     * 18. listPropTransfermode()
     *    Display List for Property Transfer Mode
     */

    public function listPropTransfermode(Request $request)
    {
        try {
            $m_proptransfermode = RefPropTransferMode::where('status', 1)
                ->get();

            return responseMsgs(true, 'Property Transfer Mode Retrieved Successfully', $m_proptransfermode, "012118", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (\Exception $e) {
            return responseMsgs(false, $e->getMessage(), "",                                                "012118", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }


    /** 
     * 19. listProptype()
     *    Display List for Property Type
     */

    public function listProptype(Request $request)
    {
        try {
            $m_proptype = RefPropType::where('status', 1)
                ->get();

            return responseMsgs(true, 'Property Type Retrieved Successfully', $m_proptype, "012119", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (\Exception $e) {
            return responseMsgs(false, $e->getMessage(), "",                               "012119", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }


    /** 
     * 20. listPropUsagetype()
     *    Display List for Property Usage Type
     */

    public function listPropUsagetype(Request $request)
    {
        try {
            $m_propusagetype = RefPropUsageType::where('status', 1)
                ->get();

            if (!$m_propusagetype) {
                return responseMsgs(false, "Property Usage Type not find", '');
            }

            return responseMsgs(true, 'Property Usage Type Retrieved Successfully',  $m_propusagetype,  "012120", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (\Exception $e) {
            return responseMsgs(false, $e->getMessage(), "",                                            "012120", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }
}
