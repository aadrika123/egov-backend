<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\MicroServices\DocUpload;
use App\Models\AboutUsDetail;
use App\Models\ActRule;
use App\Models\Administrative;
use App\Models\Announcement;
use App\Models\CitizenDesk;
use App\Models\CitizenDeskDescription;
use App\Models\Contact;
use App\Models\Department;
use App\Models\ImportantLink;
use App\Models\ImportantNotice;
use App\Models\MAsset;
use App\Models\MEService;
use App\Models\MMobileAppLink;
use App\Models\MNewsEvent;
use App\Models\MScheme;
use App\Models\Property\MPropRoadType;
use App\Models\RefPropFloor;
use App\Models\RefPropConstructionType;
use App\Models\RefPropGbbuildingusagetype;
use App\Models\RefPropGbpropusagetype;
use App\Models\RefPropObjectionType;
use App\Models\RefPropOccupancyFactor;
use App\Models\RefPropOccupancyType;
use App\Models\RefPropOwnershipType;
use App\Models\RefPropPenaltyType;
use App\Models\RefPropRoadType;
use App\Models\RefPropType;
use App\Models\RefPropRebateType;
use App\Models\RefPropTransferMode;
use App\Models\RefPropUsageType;
use App\Models\MSlider;
use App\Models\MWhat;
use App\Models\Property\MPropForgeryType;
use App\Models\Property\MPropCvRate;
use App\Models\Property\MCapitalValueRate;
use App\Models\Property\MPropBuildingRentalconst;
use App\Models\Property\MPropBuildingRentalrate;
use App\Models\Property\MPropMultiFactor;
use App\Models\Property\MPropRentalValue;
use App\Models\Property\MPropVacantRentalrate;
use App\Models\QuickLink;
use App\Models\UsefulLink;
use App\Models\UserManualHeading;
use App\Models\UserManualHeadingDescription;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;







use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;
use Sabberworm\CSS\Property\Import;

class MasterReferenceController extends Controller
{
    /**
     * | Adds a new construction type to the reference data.
     */
    public function createConstructionType(Request $req)
    {
        try {
            $req->validate([
                'constructionType' => 'required'
            ]);

            $create = new RefPropConstructionType();
            $create->addConstructionType($req);

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    public function updateConstructionType(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'constructionType' => 'required',
            ]);
            $update = new RefPropConstructionType();
            $list  = $update->updateConstructionType($req);

            return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Updates an existing construction type in the reference data.
     */
    public function constructiontypebyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new RefPropConstructionType();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "ConstructionType List By Id", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all construction types from the reference data.
    */
    public function allConstructiontypelist(Request $req)
    {
        try {
            $list = new RefPropConstructionType();
            $masters = $list->listConstructionType();

            return responseMsgs(true, "All ConstructionType List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
    * | Deletes or updates the status of a construction type based on the provided ID and status.
    */
    public function deleteConstructionType(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required|int'
            ]);
            $delete = new RefPropConstructionType();
            $message = $delete->deleteConstructionType($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Creates a new floor type with the provided name.
     */

    public function createFloorType(Request $req)
    {
        try {
            $req->validate([
                'floorName' => 'required'
            ]);

            $create = new RefPropFloor();
            $create->addFloorType($req);

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

     /**
     * | Updates an existing floor type based on the given ID and new name.
     */
    public function updateFloorType(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'floorName' => 'required',
            ]);
            $update = new RefPropFloor();
            $list  = $update->updatefloorType($req);

            return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
    * | Retrieves floor type details by the specified ID.
     */
    public function floortypebyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new RefPropFloor();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "FloorType List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

     /**
     * | Returns a list of all available floor types.
     */
    public function allFloortypelist(Request $req)
    {
        try {
            $list = new RefPropFloor();
            $masters = $list->listFloorType();

            return responseMsgs(true, "All FloorType List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Deletes or updates the status of a floor type based on the provided ID and status.
     */
    public function deleteFloorType(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required|int'
            ]);
            $delete = new RefPropFloor();
            $message = $delete->deletefloorType($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Creates a new GB building usage type with the provided building type name.
     */

    public function createGbBuildingType(Request $req)
    {
        try {
            $req->validate([
                'buildingType' => 'required'
            ]);

            $create = new RefPropGbbuildingusagetype();
            $create->addGbBuildingType($req);

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
    * | Updates an existing GB building usage type by ID with the new building type name.
    */
    public function updateGbBuildingType(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'buildingType' => 'required',
            ]);
            $update = new RefPropGbbuildingusagetype();
            $list  = $update->updateGbBuildingType($req);

            return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
    * | Retrieves details of a GB building usage type by its ID.
     */
    public function GbBuildingtypebyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new RefPropGbbuildingusagetype();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "Gb Building Type List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
    * | Retrieves the list of all GB building usage types.
     */
    public function allGbBuildingtypelist(Request $req)
    {
        try {
            $list = new RefPropGbbuildingusagetype();
            $masters = $list->listGbBuildingType();

            return responseMsgs(true, "All Gb Building Type List List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
    * | Soft deletes or updates the status of a GB building usage type by ID. 
     */
    public function deleteGbBuildingType(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required|int'
            ]);
            $delete = new RefPropGbbuildingusagetype();
            $message = $delete->deleteGbBuildingType($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | create a new GB Property Usage Type.
     */

    public function createGbPropUsageType(Request $req)
    {
        try {
            $req->validate([
                'propUsageType' => 'required'
            ]);
            $create = new RefPropGbpropusagetype();
            $create->addGbPropUsageType($req);

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

     /**
     * | update an existing GB Property Usage Type by ID.   
     */
    public function updateGbPropUsageType(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'propUsageType' => 'required',
            ]);
            $update = new RefPropGbpropusagetype();
            $list  = $update->updateGbPropUsageType($req);

            return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a GB Property Usage Type by its ID.
     */
    public function GbPropUsagetypebyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new RefPropGbpropusagetype();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "GbPropUsageType List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all GB Property Usage Types.
     */
    public function allGbPropUsagetypelist(Request $req)
    {
        try {
            $list = new RefPropGbpropusagetype();
            $masters = $list->listGbPropUsageType();

            return responseMsgs(true, "All GbPropUsageType List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | delete or deactivate a GB Property Usage Type by ID.
     */
    public function deleteGbPropUsageType(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required|int'
            ]);
            $delete = new RefPropGbpropusagetype();
            $message = $delete->deleteGbPropUsageType($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * |Gb Prop Objection Type Crud
     */

    public function createObjectionType(Request $req)
    {
        try {
            $req->validate([
                'Type' => 'required'
            ]);
            $create = new RefPropObjectionType();
            $create->addObjectionType($req);

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | update an existing GB Objection Type by ID.
     */
    public function updateObjectionType(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'Type' => 'required',
            ]);
            $update = new RefPropObjectionType();
            $list  = $update->updateObjectionType($req);

            return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a GB Objection Type by its ID.
     */
    public function ObjectiontypebyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new RefPropObjectionType();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "ObjectionType List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all GB Objection Types.
     */
    public function allObjectiontypelist(Request $req)
    {
        try {
            $list = new RefPropObjectionType();
            $masters = $list->listObjectionType();

            return responseMsgs(true, "All ObjectionType List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Deletes or updates the status of a GB Objection Type by ID.
     */
    public function deleteObjectionType(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required|int'
            ]);
            $delete = new RefPropObjectionType();
            $message = $delete->deleteObjectionType($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * |Occupancy Factor Crud
     */

    public function createOccupancyFactor(Request $req)
    {
        try {
            $req->validate([
                'multFactor' => 'required',
                'occupancyName' => 'required'
            ]);
            $create = new RefPropOccupancyFactor();
            $create->addOccupancyFactor($req);

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | update an existing occupancy factor by ID.
     */
    public function updateOccupancyFactor(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'multFactor' => 'required',
                'occupancyName' => 'required'
            ]);
            $update = new RefPropOccupancyFactor();
            $list  = $update->updateOccupancyFactor($req);

            return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves an occupancy factor by its ID.
     */
    public function OccupancyFactorbyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new RefPropOccupancyFactor();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "OccupancyFactor List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all occupancy factors.
     */
    public function allOccupancyFactorlist(Request $req)
    {
        try {
            $list = new RefPropOccupancyFactor();
            $masters = $list->listOccupancyFactor();

            return responseMsgs(true, "All Occupancy factor List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Deletes or updates the status of an occupancy factor by ID.
     */
    public function deleteOccupancyFactor(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required|int'
            ]);
            $delete = new RefPropOccupancyFactor();
            $message = $delete->deleteOccupancyFactor($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * |Occupancy Type Crud
     */

    public function createOccupancyType(Request $req)
    {
        try {
            $req->validate([
                'occupancyType' => 'required',

            ]);
            $create = new RefPropOccupancyType();
            $create->addOccupancytype($req);

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | update an existing occupancy type by ID.
     */
    public function updateOccupancyType(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'occupancyType' => 'required'
            ]);
            $update = new RefPropOccupancyType();
            $list  = $update->updateOccupancytype($req);

            return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves an occupancy type by its ID.
     */
    public function OccupancyTypebyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new RefPropOccupancyType();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "OccupancyType List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all occupancy types.
     */
    public function allOccupancyTypelist(Request $req)
    {
        try {
            $list = new RefPropOccupancyType();
            $masters = $list->listOccupancytype();

            return responseMsgs(true, "All Occupancy type List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Deletes or updates the status of an occupancy type by ID.
     */
    public function deleteOccupancyType(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required|int'
            ]);
            $delete = new RefPropOccupancyType();
            $message = $delete->deleteOccupancytype($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * |Prop Ownership Type Crud
     */

    public function createOwnershipType(Request $req)
    {
        try {
            $req->validate([
                'ownershipType' => 'required',

            ]);
            $create = new RefPropOwnershipType();
            $create->addOwnershiptype($req);

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | update an existing ownership type by ID.
     */
    public function updateOwnershipType(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'ownershipType' => 'required'
            ]);
            $update = new RefPropOwnershipType();
            $list  = $update->updateOwnershiptype($req);

            return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves an ownership type by its ID.
     */
    public function OwnershipTypebyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new RefPropOwnershipType();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "OwnershipType List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all ownership types.
     */
    public function allOwnershipTypelist(Request $req)
    {
        try {
            $list = new RefPropOwnershipType();
            $masters = $list->listOwnershiptype();

            return responseMsgs(true, "All Ownership type List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Deletes or updates the status of an ownership type by ID.
     */
    public function deleteOwnershipType(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required|int'
            ]);
            $delete = new RefPropOwnershipType();
            $message = $delete->deleteOwnershiptype($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * |Prop Road Type Crud
     */

    public function createRoadType(Request $req)
    {
        try {
            $req->validate([
                'roadType' => 'required',

            ]);
            $create = new RefPropRoadType();
            $create->addroadtype($req);

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | update an existing road type by ID.
     */
    public function updateroadType(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'roadType' => 'required'
            ]);
            $update = new RefPropRoadType();
            $list  = $update->updateroadtype($req);

            return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a road type by its ID.
     */
    public function roadTypebyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new RefPropRoadType();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "RoadType List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all road types.
     */
    public function allroadTypelist(Request $req)
    {
        try {
            $list = new RefPropRoadType();
            $masters = $list->listroadtype();

            return responseMsgs(true, "All Road type List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Deletes or updates the status of a road type by ID.
     */
    public function deleteroadType(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required|int'
            ]);
            $delete = new RefPropRoadType();
            $message = $delete->deleteroadtype($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * |Prop Type Crud
     */

    public function createPropertyType(Request $req)
    {
        try {
            $req->validate([
                'propertyType' => 'required',

            ]);
            $create = new RefPropType();
            $create->addpropertytype($req);

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | update an existing property type by ID.
     */
    public function updatePropertyType(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'propertyType' => 'required'
            ]);
            $update = new RefPropType();
            $list  = $update->updatepropertytype($req);

            return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a property type by its ID.
     */
    public function propertyTypebyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new RefPropType();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "PropertyType List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all property types.
     */
    public function allpropertyTypelist(Request $req)
    {
        try {
            $list = new RefPropType();
            $masters = $list->listpropertytype();

            return responseMsgs(true, "All Property type List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Deletes or updates the status of a property type by ID.
     */
    public function deletepropertyType(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required|int'
            ]);
            $delete = new RefPropType();
            $message = $delete->deletepropertytype($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * |Prop Transfer Mode Crud
     */

    public function createPropTransferMode(Request $req)
    {
        try {
            $req->validate([
                'transferMode' => 'required',

            ]);
            $create = new RefPropTransferMode();
            $create->addproptransfermode($req);

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | update an existing property transfer mode by ID.
     */
    public function updateTransferMode(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'transferMode' => 'required'
            ]);
            $update = new RefPropTransferMode();
            $list  = $update->updateproptransfermode($req);

            return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a property transfer mode by its ID.
     */
    public function TransferModebyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new RefPropTransferMode();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "PropertyTransfer List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all property transfer modes.
     */
    public function allTransferModelist(Request $req)
    {
        try {
            $list = new RefPropTransferMode();
            $masters = $list->listproptransfermode();

            return responseMsgs(true, "All Property transfer List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Deletes or updates the status of a property transfer mode by ID.
     */
    public function deleteTransferMode(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required|int'
            ]);
            $delete = new RefPropTransferMode();
            $message = $delete->deleteproptransfermode($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * |Prop Usage Type Crud
     */

    public function createPropUsageType(Request $req)
    {
        try {
            $req->validate([
                'usageType' => 'required',
                'usageCode' => 'required',

            ]);
            $create = new RefPropUsageType();
            $create->addpropusagetype($req);

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | update an existing property usage type by ID.
     */
    public function updateUsageType(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'usageType' => 'required',
                'usageCode' => 'required'

            ]);
            $update = new RefPropUsageType();
            $list  = $update->updatepropusagetype($req);

            return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a property usage type by its ID.
     */
    public function PropUsageTypebyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new RefPropUsageType();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "PropertyUsage Type List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all property usage types.
     */
    public function allPropUsageTypelist(Request $req)
    {
        try {
            $list = new RefPropUsageType();
            $masters = $list->listpropusagetype();

            return responseMsgs(true, "All Property Usage Type List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Deletes or updates the status of a property usage type by ID.
     */
    public function deletePropUsageType(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required|int'
            ]);
            $delete = new RefPropUsageType();
            $message = $delete->deletepropusagetype($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * |Prop Rebate Type Crud
     */

    public function createRebateType(Request $req)
    {
        try {
            $req->validate([
                'rebateType' => 'required',

            ]);
            $create = new RefPropRebateType();
            $create->addrebatetype($req);

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | update an existing rebate type by ID.
     */
    public function updateRebateType(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'rebateType' => 'required'
            ]);
            $update = new RefPropRebateType();
            $list  = $update->updaterebatetype($req);

            return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a rebate type by its ID.
     */
    public function RebateTypebyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new RefPropRebateType();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else

                return responseMsgs(true, "RebateType List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all rebate types.
     */
    public function allRebateTypelist(Request $req)
    {
        try {
            $list = new RefPropRebateType();
            $masters = $list->listrebatetype();

            return responseMsgs(true, "All Rebate type List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Deletes or updates the status of a rebate type by ID.
     */
    public function deleteRebateType(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required|int'
            ]);
            $delete = new RefPropRebateType();
            $message = $delete->deletepropertytype($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * |Prop Penalty Type Crud
     */

    public function createPenaltyType(Request $req)
    {
        try {
            $req->validate([
                'penaltyType' => 'required',

            ]);
            $create = new RefPropPenaltyType();
            $create->addpenaltytype($req);
            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | update an existing penalty type by ID.
     */
    public function updatePenaltyType(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'penaltyType' => 'required'
            ]);
            $update = new RefPropPenaltyType();
            $list  = $update->updatepenaltytype($req);

            return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a penalty type by its ID.
     */
    public function PenaltyTypebyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new RefPropPenaltyType();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else

                return responseMsgs(true, "PenaltyType List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all penalty types.
     */
    public function allPenaltyTypelist(Request $req)
    {
        try {
            $list = new RefPropPenaltyType();
            $masters = $list->listpenaltytype();

            return responseMsgs(true, "All Penalty type List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Deletes or updates the status of a penalty type by ID.
     */
    public function deletePenaltyType(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required|int'
            ]);
            $delete = new RefPropPenaltyType();
            $message = $delete->deletepenaltytype($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * |Forgery Type Crud
     */

    public function createForgeryType(Request $req)
    {
        try {
            $req->validate([
                'Forgerytype' => 'required',

            ]);
            $create = new MPropForgeryType();
            $create->addForgeryType($req);

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | update an existing forgery type by ID.
     */
    public function updateForgeryType(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'Forgerytype' => 'required'
            ]);
            $update = new MPropForgeryType();
            $list  = $update->updateForgeryType($req);

            return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a forgery type by its ID.
     */
    public function ForgeryTypebyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new MPropForgeryType();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "Forgery Type List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all forgery types.
     */
    public function allForgeryTypelist(Request $req)
    {
        try {
            $list = new MPropForgeryType();
            $masters = $list->listForgeryType();

            return responseMsgs(true, "All Forgery type List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Deletes or updates the status of a forgery type by ID.
     */
    public function deleteForgeryType(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required|boolean'
            ]);
            $delete = new MPropForgeryType();
            $message = $delete->deleteForgeryType($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * |M-Capital-Value-Rate
     */
    public function MCapitalValurRateById(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new MCapitalValueRate();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "Capital Value Rate List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Retrieves a list of all capital value rates.
     */
    public function allMCapitalValurRateList(Request $req)
    {
        try {
            $list = new MCapitalValueRate();
            $masters = $list->listCapitalValueRate();

            return responseMsgs(true, "All Capital Value Rate List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * |M-Prop-Building-rentalconsts
     */
    public function MPropBuildingRentalconstsById(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new MPropBuildingRentalconst();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "Building Rental Const List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Retrieves a list of all building rental constants.
     */
    public function allMPropBuildingRentalconstsList(Request $req)
    {
        try {
            $list = new MPropBuildingRentalconst();
            $masters = $list->listMPropBuildingRenConst();

            return responseMsgs(true, "All Building Rental Const List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * |M-Prop-Building-rentalrates
     */
    public function MPropBuildingRentalRatesById(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new MPropBuildingRentalrate();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "Building Rental Rate List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Retrieves a list of all building rental rates.
     */
    public function allMPropBuildingRentalRatesList(Request $req)
    {
        try {
            $list = new MPropBuildingRentalrate();
            $masters = $list->listMPropBuildingRentRate();

            return responseMsgs(true, "All Building Rental Rate List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | M-Prop-Capital-Value-Rates
     * | Retrieves a property capital value rate by its ID.
     */
    public function MPropCvRatesById(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new MPropCvRate();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "Prop Cv Rate List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Retrieves a list of all property capital value rates.
     */
    public function allMPropCvRatesList(Request $req)
    {
        try {
            $list = new MPropCvRate();
            $masters = $list->listMPropCvRate();

            return responseMsgs(true, "All Prop Cv Rate List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * |M-Prop-Multi-Factors 
     * | Retrieves a property multi-factor by its ID.
     */
    public function MPropMultiFactorById(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new MPropMultiFactor();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "Prop Multi Factor List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Retrieves a list of all property multi-factors.
     */
    public function allMPropMultiFactorList(Request $req)
    {
        try {
            $list = new MPropMultiFactor();
            $masters = $list->listMPropMultiFactor();

            return responseMsgs(true, "All Prop Multi Factor List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * |M-Prop-Rental-Value
     */
    public function MPropRentalValueById(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new MPropRentalValue();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "Prop Rental Value List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Retrieves a list of all property rental values.
     */
    public function allMPropRentalValueList(Request $req)
    {
        try {
            $list = new MPropRentalValue();
            $masters = $list->listMPropRentalValue();

            return responseMsgs(true, "All Prop Rental value List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }


    /**
     * |M-Prop-Road-type
     */
    public function MPropRoadTypeById(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new MPropRoadType();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "Prop Road Type List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Retrieves a list of all property road types.
     */
    public function allMPropRoadTypeList(Request $req)
    {
        try {
            $list = new MPropRoadType();
            $masters = $list->listMPropRoadType();

            return responseMsgs(true, "All Prop Road Type List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * |M-Prop-vacant-rentalrate
     */
    public function MPropVacantRentalrateById(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new MPropVacantRentalrate();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "Prop Vacant Rental Rate List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Retrieves a list of all property vacant rental rates.
     */
    public function allMPropVacantRentalrateList(Request $req)
    {
        try {
            $list = new MPropVacantRentalrate();
            $masters = $list->listMPropVacantRetlRate();

            return responseMsgs(true, "All Vacant Rental Rate List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * |M-Slider
     */

    public function createSlider(Request $req)
    {
        try {
            $req->validate([
                'sliderName' => 'nullable',
                'sliderImage' => 'required|mimes:pdf,jpeg,png,jpg'
            ]);
            $req->merge(["document" => $req->sliderImage]);
            $docUpload = new DocUpload;
            $data = $docUpload->checkDoc($req);
            if (!$data["status"]) {
                throw new Exception("Document Not uploaded");
            }
            $req->merge($data["data"]);
            $create = new MSlider();
            if (!$create->addSlider($req)) {
                throw new Exception("data not stored");
            }

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | update an existing slider by ID.
     */
    public function updateSlider(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'sliderName' => 'required',
                'sliderImage' => 'required'
            ]);
            $req->merge(["document" => $req->sliderImage]);
            $docUpload = new DocUpload;
            $data = $docUpload->checkDoc($req);
            if (!$data["status"]) {
                throw new Exception("Document Not uploaded");
            }
            $req->merge($data["data"]);
            $update = new MSlider();
            $list  = $update->updateSlider($req);

            return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a slider by its ID.
     */
    public function allSliderList(Request $req)
    {
        try {
            $list = new MSlider();
            $docUpload = new DocUpload;
            $masters = $list->listSlider()->map(function ($val) use ($docUpload) {
                $url = $docUpload->getSingleDocUrl($val);
                $val->is_suspended = $val->status;
                $val->slider_image_url = $url["doc_path"] ?? null;
                return $val;
            });

            return responseMsgs(true, "All Slider List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Retrieves a list of all sliders for version 1.
     */
    public function allSliderListv1(Request $req)
    {
        $list = new MSlider();
        $docUpload = new DocUpload;
        $masters = $list->listDash()->map(function ($val) use ($docUpload) {
            $url = $docUpload->getSingleDocUrl($val);
            $val->is_suspended = $val->status;
            $val->slider_image_url = $url["doc_path"] ?? null;
            return $val;
        });
        return $masters;
    }

    /* 
     * | Deletes or updates the status of a slider by ID.
     */
    public function deleteSlider(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required'
            ]);
            $delete = new MSlider();
            $message = $delete->deleteSlider($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a slider by its ID.
     */
    public function sliderById(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $list = new MSlider();
            $message = $list->getById($req);
            $docUpload = new DocUpload();
            $url = $docUpload->getSingleDocUrl($message);
            $message->is_suspended = $message->status;
            $message->slider_image_url = $url["doc_path"] ?? null;
            return responseMsgs(true, "Slider Details", $message, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * |M-Assets
     */
    //Assets crud with DMS
    public function addAssetesv3(Request $req)
    {
        $req->validate([
            'key' => 'required',
            "assetName" => "required",
            'assetFile' => 'required|mimes:pdf,jpeg,png,jpg',
            "ulbId" => "nullable"
        ]);
        try {

            $req->merge(["document" => $req->assetFile]);
            $docUpload = new DocUpload;
            $data = $docUpload->checkDoc($req);
            if (!$data["status"]) {
                throw new Exception("Document Not uploaded");
            }
            $req->merge($data["data"]);
            $create = new MAsset();
            if (!$create->store($req)) {
                throw new Exception("Data not stored");
            }
            return responseMsgs(true, "data save", "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Updates an existing asset by ID.
     */
    public function editAssetesv3(Request $req)
    {
        $req->validate([
            "id" => "required",
            'key' => 'required',
            "assetName" => "required",
            'assetFile' => 'required|mimes:pdf,jpeg,png,jpg',
            "ulbId" => "nullable"
        ]);
        try {
            $req->merge(["document" => $req->assetFile]);
            $docUpload = new DocUpload;
            $data = $docUpload->checkDoc($req);
            if (!$data["status"]) {
                throw new Exception("Document Not uploaded");
            }
            $req->merge($data["data"]);
            $create = new MAsset();
            if (!$create->edit($req)) {
                throw new Exception("Data not updated");
            }
            return responseMsgs(true, "data update", "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all assets.
     */
    public function allListAssetesv3(Request $req)
    {
        try {
            $create = new MAsset();
            $docUpload = new DocUpload();
            $data = $create->allList()->map(function ($val) use ($docUpload) {
                $url = $docUpload->getSingleDocUrl($val);
                $val->is_suspended = $val->status;
                $val->asset_file = $url["doc_path"] ?? null;
                return $val;
            });

            return responseMsgs(true, "All Slider List", $data, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all assets for the dashboard.
     */

    public function allListAssetesDash(Request $req)
    {
        $create = new MAsset();
        $docUpload = new DocUpload();
        $masters = $create->listDash()->map(function ($val) use ($docUpload) {
            $url = $docUpload->getSingleDocUrl($val);
            $val->is_suspended = $val->status;
            $val->asset_file = $url["doc_path"] ?? null;
            //return $val;
            return [
                'asset_name' => $val->asset_name,
                'file_url' => $url["doc_path"] ?? null
            ];
        });
        // $data = $create->listDash()->map(function ($val) use ($docUpload) {
        //     $url = $val->asset_file ? ["doc_path" => trim(Config::get('module-constants.DOC_URL') . "/" . $val->asset_file)] : $docUpload->getSingleDocUrl($val);
        //     return [
        //         'asset_name' => $val->asset_name,
        //         'file_url' => $url["doc_path"] ?? null
        //     ];
        // });

        // Reformat the data to use asset_name as the key and file_url as the value
        $assets = $masters->pluck('file_url', 'asset_name')->toArray();

        // Return only the assets as a plain array
        return $assets;
    }

    /* 
     * | Deletes or updates the status of an asset by ID.
     */
    public function deleteAssetesv3(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required'
            ]);
            $delete = new MAsset();
            $message = $delete->deleteAssets($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves an asset by its ID.
     */
    public function Assetesv3ById(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $list = new MAsset();
            $message = $list->getById($req);
            $docUpload = new DocUpload();
            $url = $docUpload->getSingleDocUrl($message);
            $message->is_suspended = $message->status;
            $message->asset_file = $url["doc_path"] ?? null;
            return responseMsgs(true, "Slider Details", $message, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }
    #==========================v2================================
    # function for upload and all functionality with local folder
    # not in used
    public function addAssetesv2(Request $req)
    {
        $req->validate([
            'key' => 'required',
            "assetName" => "required",
            'assetFile' => 'required|mimes:pdf,jpeg,png,jpg',
            "ulbId" => "nullable"
        ]);
        try {
            $req->merge(["document" => $req->assetFile]);
            $fileName = $req->key;
            $docPath = Config::get("assetsConstaint.ASSETS_PATH");
            $file = $req->assetFile;
            $docUpload = new DocUpload;
            $data = $docUpload->upload($req->key, $file, $docPath, false);
            $oldFile = public_path($docPath . "/" . $data);
            $newFile = public_path($docPath . "/AssetD/" . $fileName);
            if (!is_dir(public_path($docPath . "/AssetD"))) {
                mkdir(public_path($docPath . "/AssetD"));
            }
            if (!copy($oldFile, $newFile)) {
                throw new Exception("Document Not uploaded");
            }
            @unlink($oldFile);
            $req->only(["key", "document", "assetName", "ulbId", "uniqueId", "ReferenceNo"]);
            $newReq = new Request(["assetFile" => trim(explode(public_path(), $newFile)[1] ?? "", "/\//")]);
            $req = $newReq->merge($req->only(["key", "document", "assetName", "ulbId", "uniqueId", "ReferenceNo"]));
            $create = new MAsset();
            if (!$create->store($req)) {
                throw new Exception("Data not stored");
            }
            return responseMsgs(true, "data save", "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }


    /* 
     * | Updates an existing asset by ID.
     */
    public function editAssetesv2(Request $req)
    {
        $req->validate([
            "id" => "required",
            'key' => 'required',
            "assetName" => "required",
            'assetFile' => 'required|mimes:pdf,jpeg,png,jpg',
            "ulbId" => "nullable"
        ]);
        try {
            $req->merge(["document" => $req->assetFile]);
            $fileName = $req->key;
            $docPath = Config::get("assetsConstaint.ASSETS_PATH");
            $file = $req->assetFile;
            $docUpload = new DocUpload;
            $data = $docUpload->upload($req->key, $file, $docPath, false);
            $oldFile = public_path($docPath . "/" . $data);
            $newFile = public_path($docPath . "/AssetD/" . $fileName);
            if (!is_dir(public_path($docPath . "/AssetD"))) {
                mkdir(public_path($docPath . "/AssetD"));
            }
            if (!copy($oldFile, $newFile)) {
                throw new Exception("Document Not uploaded");
            }
            @unlink($oldFile);
            $req->only(["key", "document", "assetName", "ulbId", "uniqueId", "ReferenceNo"]);
            $newReq = new Request(["assetFile" => trim(explode(public_path(), $newFile)[1] ?? "", "/\//")]);
            $req = $newReq->merge($req->only(["id", "key", "document", "assetName", "ulbId", "uniqueId", "ReferenceNo"]));
            $create = new MAsset();
            if (!$create->edit($req)) {
                throw new Exception("Data not updated");
            }
            return responseMsgs(true, "data update", "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all assets.
     */
    public function allListAssetesv2(Request $req)
    {
        try {
            $create = new MAsset();
            $docUpload = new DocUpload();
            $data = $create->allList()->map(function ($val) use ($docUpload) {
                $url = $val->asset_file ? ["doc_path" => trim(Config::get('module-constants.DOC_URL') . "/" . $val->asset_file)] : $docUpload->getSingleDocUrl($val);
                $val->is_suspended = $val->status;
                $val->asset_file = $url["doc_path"] ?? null;
                return $val;
            });

            return responseMsgs(true, "All Assets List", $data, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all assets for the dashboard.
     */
    public function allListAssetesv1(Request $req)
    {
        $create = new MAsset();
        $docUpload = new DocUpload();
        // $data = $create->listDash()->map(function ($val) use ($docUpload) {
        //     $url = $val->asset_file ? ["doc_path" => trim(Config::get('module-constants.DOC_URL') . "/" . $val->asset_file)] : $docUpload->getSingleDocUrl($val);
        //     $val->is_suspended = $val->status;
        //     $val->asset_file = $url["doc_path"] ?? null;
        //     return $val;
        // });

        // // Return the transformed data
        // return $data;

        $data = $create->listDash()->map(function ($val) use ($docUpload) {
            $url = $val->asset_file ? ["doc_path" => trim(Config::get('module-constants.DOC_URL') . "/" . $val->asset_file)] : $docUpload->getSingleDocUrl($val);
            return [
                'asset_name' => $val->asset_name,
                'file_url' => $url["doc_path"] ?? null
            ];
        });

        // Reformat the data to use asset_name as the key and file_url as the value
        $assets = $data->pluck('file_url', 'asset_name')->toArray();

        // Return only the assets as a plain array
        return $assets;
    }

    /* 
     * | Deletes or updates the status of an asset by ID.
     */

    public function deleteAssetesv2(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required'
            ]);
            $delete = new MAsset();
            $message = $delete->deleteAssets($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves an asset by its ID.
     */
    public function Assetesv2ById(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $create = new MAsset();
            $message = $create->getById($req);
            $docUpload = new DocUpload();
            $url = $message->asset_file ? ["doc_path" => trim(Config::get('module-constants.DOC_URL') . "/" . $message->asset_file)] : $docUpload->getSingleDocUrl($message);
            $message->is_suspended = $message->status;
            $message->asset_file = $url["doc_path"] ?? null;
            //return $message;

            return responseMsgs(true, "Assets List", $message, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    #=======================end=============================

    #============================penalty type crud===========================
    // public function addPenaltyType(Request $req)
    // {
    //     $req->validate([
    //         'penaltyType' => 'required',

    //     ]);
    //     try {
    //         $user = Auth()->user();
    //         $req->merge(["userId" => $user ? $user->id : null]);
    //         DB::beginTransaction();
    //         $newPenaltyType = new RefPropPenaltyType();
    //         $id = $newPenaltyType->store($req->all());
    //         if (!$id) {
    //             throw new Exception("somethig went wrong on storing the  data");
    //         }
    //         DB::commit();
    //         return responseMsg(true, "new penalty type added successfuly", "");
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         return responseMsg(false, $e->getMessage(), "");
    //     }
    // }

    // public function editPenaltyType(Request $req)
    // {
    //     $newPenaltyType = new RefPropPenaltyType();
    //     $db_conn = $newPenaltyType->getConnectionName();
    //     $tbl_name = $newPenaltyType->getTable();
    //     $req->validate([
    //         "id" => "required|degit_between:1,1000|exist:$db_conn.$tbl_name,id",
    //         'penaltyType' => 'required',
    //         "status" => "nullable|in:1,0"

    //     ]);
    //     try {
    //         DB::beginTransaction();
    //         if (!$newPenaltyType->edit($req->id, $req->all())) {
    //             throw new Exception("Data not Edited");
    //         }
    //         DB::commit();
    //         return responseMsg(true, "data update successfully", "");
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         return responseMsg(false, $e->getMessage(), "");
    //     }
    // }

    // #=======this is without pagination=================================
    // public function penaltyTypeList(Request $req)
    // {
    //     try {
    //         $objPenlatyType = new RefPropPenaltyType();
    //         $data = $objPenlatyType->orderBy("id", "ASC");
    //         if ($req->penaltyType) {
    //             $data->where("penalty_type", $req->penaltyType);
    //         }
    //         if (isset($req->status)) {
    //             $data->where("status", $req->status);
    //         }
    //         $data = $data->get();
    //         return responseMsg(true, "penalty List", remove_null($data));
    //     } catch (Exception $e) {
    //         return responseMsg(false, $e->getMessage(), "");
    //     }
    // }

    // public function penltyDtl(Request $req)
    // {
    //     $newPenaltyType = new RefPropPenaltyType();
    //     $db_conn = $newPenaltyType->getConnectionName();
    //     $tbl_name = $newPenaltyType->getTable();
    //     $req->validate([
    //         "id" => "required|degit_between:1,1000|exist:$db_conn.$tbl_name,id",
    //     ]);
    //     try {
    //         $data = RefPropPenaltyType::find($req->id);
    //         return responseMsg(true, "penalty type Dtl", remove_null($data));
    //     } catch (Exception $e) {
    //         return responseMsg(false, $e->getMessage(), "");
    //     }
    // }
    // #==========================end penalty type crud=================================

    // public function addRodeType(Request $req)
    // {
    //     try {
    //         $objRoadeType = new MPropRoadType();
    //         if (!$objRoadeType->stor($req->all())) {
    //             throw new Exception("somthing went wrong on storing road tyep data");
    //         }
    //         DB::commit();
    //         return responseMsg(true, "roadtype store successfully", "");
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         return responseMsg(false, $e->getMessage(), "");
    //     }
    // }


    /* 
     * | Adds a new notice.
     */
    public function addNotice(Request $req)
    {
        try {
            $req->validate([
                'notice' => 'required',
            ]);
            $create = new ImportantNotice();
            $create->addNoticeType($req);

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all notices.
     */
    public function noticeList(Request $req)
    {
        try {
            $list = new ImportantNotice();
            $masters = $list->listNoticeType();

            return responseMsgs(true, "All NoticeType List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }


    /* 
     * | Updates an existing notice by ID.
     */
    public function updateNoticeType(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'notice' => 'required'
            ]);
            $update = new ImportantNotice();
            $list  = $update->updateNoticeType($req);

            return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a notice by its ID.
     */
    public function noticeTypebyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new ImportantNotice();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "Notice Type List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Deletes or updates the status of a notice by ID.
     */
    public function deleteNoticeType(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required|boolean'
            ]);
            $delete = new ImportantNotice();
            $message = $delete->deleteNoticeType($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Adds a new announcement.
     */
    public function addAnnouncement(Request $req)
    {
        try {
            $req->validate([
                'announcement' => 'required',
            ]);
            $create = new Announcement();
            $create->addAnnouncementType($req);

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all announcements.
     */
    public function announcementList(Request $req)
    {
        try {
            $list = new Announcement();
            $masters = $list->listAnnouncementType();

            return responseMsgs(true, "All AnnouncementType List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }


    /* 
     * | Updates an existing announcement by ID.
     */
    public function updateAnnouncementType(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'announcement' => 'required'
            ]);
            $update = new Announcement();
            $list  = $update->updateAnnouncementType($req);

            return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves an announcement by its ID.
     */
    public function announcementTypebyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new Announcement();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "Announcement Type List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }


    /* 
     * | Deletes or updates the status of an announcement by ID.
     */
    public function deleteAnnouncementType(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required|boolean'
            ]);
            $delete = new Announcement();
            $message = $delete->deleteAnnouncementType($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Adds a new user manual heading.
     */
    public function addUserManualHeading(Request $req)
    {
        try {
            $req->validate([
                'heading' => 'required',
            ]);
            $create = new UserManualHeading();
            $create->addHeading($req);

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all user manual headings.
     */
    public function userManualHeadingList(Request $req)
    {
        try {
            $list = new UserManualHeading();
            $masters = $list->listUserManualHeading();

            return responseMsgs(true, "All User Manual Heading List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all user manual headings.
     */
    public function userManualHeadingListMaster(Request $req)
    {
        try {
            $list = new UserManualHeading();
            $masters = $list->listUserManualHeadingMaster();

            return responseMsgs(true, "All User Manual Heading List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of user manual headings by ID in descending order.
     */
    public function userManualHeadingListMasterDesc(Request $req)
    {
        try {
            $req->validate([
                'headingId' => 'required',
            ]);
            $list = new UserManualHeading();
            $masters = $list->listUserManualHeadingMasterDesc($req->headingId);

            return responseMsgs(true, "All User Manual Heading List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }


    /* 
     * | Updates an existing user manual heading by ID.
     */
    public function updateUserManualHeading(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'heading' => 'required'
            ]);
            $heading = new UserManualHeading();
            $list  = $heading->updateUserManualHeading($req);

            return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a user manual heading by its ID.
     */
    public function userManualHeadingbyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new UserManualHeading();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "Announcement Type List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Deletes or updates the status of a user manual heading by ID.
     */
    public function deleteUserManualHeading(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required|boolean'
            ]);
            $delete = new UserManualHeading();
            $message = $delete->deleteUserManualHeading($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Adds a new user manual heading description.
     */
    public function addHeadingDescription(Request $req)
    {
        try {
            $req->validate([
                'headingId' => 'required|int',
                'description' => 'required',
                'videoLink' => 'nullable',
                'userManualLink' => 'nullable',
            ]);
            $create = new UserManualHeadingDescription();
            $create->addHeadingDes($req);

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all user manual heading descriptions.
     */
    public function userHeadingList(Request $req)
    {
        try {
            $list = new UserManualHeadingDescription();
            $masters = $list->listUserManualHeading();

            return responseMsgs(true, "All User Manual Heading Description List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Updates an existing user manual heading description by ID.
     */
    public function updateHeading(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'headingId' => 'required|int',
                'description' => 'nullable',
                'videoLink' => 'nullable',
                'userManualLink' => 'nullable',
            ]);
            $heading = new UserManualHeadingDescription();
            $list  = $heading->updateUserManualHeading($req);

            return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a user manual heading description by its ID.
     */
    public function userHeadingbyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new UserManualHeadingDescription();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "User MAnual Description List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Deletes or updates the status of a user manual heading description by ID.
     */
    public function deleteHeading(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required|boolean'
            ]);
            $delete = new UserManualHeadingDescription();
            $message = $delete->deleteUserHeading($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Adds a new mobile app link.
     */

    public function addapp(Request $req)
    {
        try {
            $req->validate([
                'appName' => 'required',
                'appLink' => 'required',
            ]);
            $create = new MMobileAppLink();
            $create->addMApp($req);

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all mobile app links.
     */
    public function appList(Request $req)
    {
        try {
            $list = new MMobileAppLink();
            $masters = $list->listMApp();

            return responseMsgs(true, "All App List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }


    /* 
     * | Updates an existing mobile app link by ID.
     */
    public function updateapp(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'appName' => 'nullable',
                'appLink' => 'nullable',
            ]);
            $heading = new MMobileAppLink();
            $list  = $heading->updateMApp($req);

            return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a mobile app link by its ID.
     */
    public function appbyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new MMobileAppLink();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "App List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Deletes or updates the status of a mobile app link by ID.
     */
    public function deleteapp(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required|boolean'
            ]);
            $delete = new MMobileAppLink();
            $message = $delete->deleteMApp($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Adds a new scheme.
     */
    public function addscheme(Request $req)
    {
        try {
            $req->validate([
                'schemeName' => 'required',
                'schemeLink' => 'required',
            ]);
            $create = new MScheme();
            $create->addScheme($req);

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all schemes.
     */
    public function schemeList(Request $req)
    {
        try {
            $list = new MScheme();
            $masters = $list->listScheme();

            return responseMsgs(true, "All Scheme List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Updates an existing scheme by ID.
     */
    public function updatescheme(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'schemeName' => 'nullable',
                'schemeLink' => 'nullable',
            ]);
            $heading = new MScheme();
            $list  = $heading->updateScheme($req);

            return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a scheme by its ID.
     */
    public function schemebyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new MScheme();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "Scheme List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Deletes or updates the status of a scheme by ID.
     */
    public function deletescheme(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required|boolean'
            ]);
            $delete = new MScheme();
            $message = $delete->deleteScheme($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Adds a new news article.
     */
    public function addnews(Request $req)
    {
        try {
            $req->validate([
                'news' => 'required',
                'newsLink' => 'required',
            ]);
            $create = new MNewsEvent();
            $create->addNews($req);

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all news articles.
     */
    public function newsList(Request $req)
    {
        try {
            $list = new MNewsEvent();
            $masters = $list->listNews();

            return responseMsgs(true, "All News List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }


    /* 
     * | Updates an existing news article by ID.
     */
    public function updatenews(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'news' => 'nullable',
                'newsLink' => 'nullable',
            ]);
            $heading = new MNewsEvent();
            $list  = $heading->updateNews($req);

            return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a news article by its ID.
     */
    public function newsbyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new MNewsEvent();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "News List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }


    /* 
     * | Deletes or updates the status of a news article by ID.
     */
    public function deletenews(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required|boolean'
            ]);
            $delete = new MNewsEvent();
            $message = $delete->deleteNews($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Adds a new service.
     */
    public function addService(Request $req)
    {
        try {
            $req->validate([
                'service' => 'required',
                'serviceLink' => 'required',
            ]);
            $create = new MEService();
            $create->addService($req);

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all services.
     */
    public function ServiceList(Request $req)
    {
        try {
            $list = new MEService();
            $masters = $list->listServices();

            return responseMsgs(true, "All Services List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Updates an existing service by ID.
     */
    public function updateService(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'service' => 'nullable',
                'serviceLink' => 'nullable'
            ]);
            $heading = new MEService();
            $list  = $heading->updateServices($req);

            return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a service by its ID.
     */
    public function ServicebyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new MEService();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "Services List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Deletes or updates the status of a service by ID.
     */
    public function deleteService(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required|boolean'
            ]);
            $delete = new MEService();
            $message = $delete->deleteServices($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Adds a new "What's New" item.
     */
    public function addWhatsNew(Request $req)
    {
        try {
            $req->validate([
                'whatsNew' => 'required',
            ]);
            $create = new MWhat();
            $create->addWhatsNew($req);

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Updates an existing "What's New" item by ID.
     */
    public function updateWhatsNew(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'whatsNew' => 'required',
            ]);
            $heading = new MWhat();
            $list  = $heading->updateWhatNew($req);

            return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Retrieves a list of all "What's New" items.
     */
    public function WhatsNewList(Request $req)
    {
        try {
            $list = new MWhat();
            $masters = $list->listWhatNew();

            return responseMsgs(true, "All List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Retrieves a "What's New" item by its ID.
     */
    public function WhatsNewbyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new MWhat();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Deletes or updates the status of a "What's New" item by ID.
     */
    public function deleteWhatsNew(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required|boolean'
            ]);
            $delete = new MWhat();
            $message = $delete->deleteWhatNew($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }


    /* 
     * | Adds a new quick link.
     */
    public function addQuickLink(Request $req)
    {
        try {
            $req->validate([
                'linkHeading' => 'required',
                'quickLink' => 'required',
            ]);
            $create = new QuickLink();
            $create->addlink($req);

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Updates an existing quick link by ID.
     */
    public function updateQuickLink(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'linkHeading' => 'nullable',
                'quickLink' => 'nullable',
            ]);
            $heading = new QuickLink();
            $list  = $heading->updateLink($req);

            return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all quick links.
     */
    public function quickLinkList(Request $req)
    {
        try {
            $list = new QuickLink();
            $masters = $list->listQuickLink();

            return responseMsgs(true, "All List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a quick link by its ID.
     */
    public function quickLinkbyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new QuickLink();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Deletes or updates the status of a quick link by ID.
     */
    public function deleteQuickLink(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required|boolean'
            ]);
            $delete = new QuickLink();
            $message = $delete->deleteLink($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }


    /* 
     * | Adds a new important link.
     */
    public function addImportantLink(Request $req)
    {
        try {
            $req->validate([
                'linkHeading' => 'required',
                'importantLink' => 'required',
            ]);
            $create = new ImportantLink();
            $create->addlink($req);

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Updates an existing important link by ID.
     */
    public function updateImportantLink(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'linkHeading' => 'nullable',
                'importantLink' => 'nullable',
            ]);
            $heading = new ImportantLink();
            $list  = $heading->updateLink($req);

            return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all important links.
     */
    public function importantLinkList(Request $req)
    {
        try {
            $list = new ImportantLink();
            $masters = $list->listImportantLink();

            return responseMsgs(true, "All List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves an important link by its ID.
     */
    public function importantLinkbyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new ImportantLink();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Deletes or updates the status of an important link by ID.
     */
    public function deleteImportantLink(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required|boolean'
            ]);
            $delete = new ImportantLink();
            $message = $delete->deleteLink($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Adds a new useful link.
     */
    public function addUsefulLink(Request $req)
    {
        try {
            $req->validate([
                'linkHeading' => 'required',
                'usefulLink' => 'required',
            ]);
            $create = new UsefulLink();
            $create->addlink($req);

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Updates an existing useful link by ID.
     */
    public function updateUsefulLink(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'linkHeading' => 'nullable',
                'usefulLink' => 'nullable',
            ]);
            $heading = new UsefulLink();
            $list  = $heading->updateLink($req);

            return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all useful links.
     */
    public function usefulLinkList(Request $req)
    {
        try {
            $list = new UsefulLink();
            $masters = $list->listUsefulLink();

            return responseMsgs(true, "All List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a useful link by its ID.
     */
    public function usefulLinkbyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new UsefulLink();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Deletes or updates the status of a useful link by ID.
     */
    public function deleteUsefulLink(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required|boolean'
            ]);
            $delete = new UsefulLink();
            $message = $delete->deleteLink($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }


    /* 
     * | Adds a new department.
     */
    public function addDepartment(Request $req)
    {
        try {
            $req->validate([
                'departnameName' => 'required',
                'link' => 'required',
            ]);
            $create = new Department();
            $create->addDepartment($req);

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Updates an existing department by ID.
     */
    public function updateDepartment(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'departnameName' => 'nullable',
                'link' => 'nullable',
            ]);
            $heading = new Department();
            $list  = $heading->updateDepartment($req);

            return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all departments.
     */
    public function departmentList(Request $req)
    {
        try {
            $list = new Department();
            $masters = $list->listDepartment();

            return responseMsgs(true, "All List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a department by its ID.
     */
    public function departmentbyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new Department();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Deletes or updates the status of a department by ID.
     */
    public function deleteDepartment(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required|boolean'
            ]);
            $delete = new Department();
            $message = $delete->deleteDepartment($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Adds a new contact.
     */
    public function addContact(Request $req)
    {
        try {
            $req->validate([
                'departnameName' => 'required',
                'address' => 'required',
                'mobile' => "required",
                'email' => 'required|email',
                'fax' => 'required',
            ]);
            $create = new Contact();
            $create->addContact($req);

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Updates an existing contact by ID.
     */
    public function updateContact(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'departnameName' => 'nullable',
                'address' => 'nullable',
                'mobile' => "nullable",
                'email' => 'nullable|email',
                'fax' => 'nullable'
            ]);
            $heading = new Contact();
            $list  = $heading->updateContact($req);

            return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all contacts.
     */
    public function ContactList(Request $req)
    {
        try {
            $list = new Contact();
            $masters = $list->listContact();

            return responseMsgs(true, "All List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a contact by its ID.
     */
    public function ContactbyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new Contact();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Deletes or updates the status of a contact by ID.
     */
    public function deleteContact(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required|boolean'
            ]);
            $delete = new Contact();
            $message = $delete->deleteContact($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Adds a new citizen desk.
     */
    public function addCDesk(Request $req)
    {
        try {
            $req->validate([
                'heading' => 'required',
            ]);
            $create = new CitizenDesk();
            $create->addCDesk($req);

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Updates an existing citizen desk by ID.
     */
    public function updateCDesk(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'heading' => 'nullable'
            ]);
            $heading = new CitizenDesk();
            $list  = $heading->updateCDesk($req);

            return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all citizen desks.
     */
    public function CDeskList(Request $req)
    {
        try {
            $list = new CitizenDesk();
            $masters = $list->listCDesk();

            return responseMsgs(true, "All List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a citizen desk by its ID.
     */
    public function CDeskbyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new CitizenDesk();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Deletes or updates the status of a citizen desk by ID.
     */
    public function deleteCDesk(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required|boolean'
            ]);
            $delete = new CitizenDesk();
            $message = $delete->deleteCDesk($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Adds a new citizen desk description.
     */
    public function addCDeskDesc(Request $req)
    {
        try {
            $req->validate([
                'headingDesc' => 'required',
                'link' => 'required',
                'deskId' => 'required'
            ]);
            $create = new CitizenDeskDescription();
            $create->addCDeskDes($req);

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Updates an existing citizen desk description by ID.
     */
    public function updateCDeskDesc(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'headingDesc' => 'nullable',
                'link' => 'nullable',
                'deskId' => 'nullable'
            ]);
            $heading = new CitizenDeskDescription();
            $list  = $heading->updateCDeskDesc($req);

            return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all citizen desk descriptions.
     */
    public function CDeskDescbyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new CitizenDeskDescription();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Deletes or updates the status of a citizen desk description by ID.
     */
    public function deleteCDeskDesc(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required|boolean'
            ]);
            $delete = new CitizenDeskDescription();
            $message = $delete->deleteCDeskDesc($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Adds a new about us section.
     */
    public function addAboutUs(Request $req)
    {
        try {
            $req->validate([
                'aboutUs' => 'required|string',
                'vision' => 'required|string',
                'mission' => 'required|string',
                'objective' => 'required|string',
                'function' => 'required|string',
            ]);
            $create = new AboutUsDetail();
            $create->addAboutUs($req);

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Updates an existing about us section by ID.
     */
    public function updateAboutUs(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'aboutUs' => 'nullable|string',
                'vision' => 'nullable|string',
                'mission' => 'nullable|string',
                'objective' => 'nullable|string',
                'function' => 'nullable|string',
            ]);
            $heading = new AboutUsDetail();
            $list  = $heading->updateAboutUs($req);

            return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all about us sections.
     */
    public function listAboutUs(Request $req)
    {
        try {
            $list = new AboutUsDetail();
            $masters = $list->listAboutUs();

            return responseMsgs(true, "All List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves an about us section by its ID.
     */
    public function aboutUsbyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new AboutUsDetail();
            $list  = $listById->getById($req);
            if (!$list)
                return responseMsgs(true, "data not found", '', "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            else
                return responseMsgs(true, "List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Deletes or updates the status of an about us section by ID.
     */
    public function deleteAboutUs(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required|boolean'
            ]);
            $delete = new AboutUsDetail();
            $message = $delete->deleteAboutUs($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Adds a new act rule.
     */
    public function addActRule(Request $req)
    {
        try {
            $req->validate([
                'ruleName' => 'required',
                'ruleImage' => 'required|mimes:pdf'
            ]);
            $req->merge(["document" => $req->ruleImage]);
            $docUpload = new DocUpload;
            $data = $docUpload->checkDoc($req);
            if (!$data["status"]) {
                throw new Exception("Document Not uploaded");
            }
            $req->merge($data["data"]);
            $create = new ActRule();
            if (!$create->addRule($req)) {
                throw new Exception("data not stored");
            }

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Updates an existing act rule by ID.
     */
    public function updateActRule(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'ruleName' => 'nullable',
                'ruleImage' => 'nullable|mimes:pdf'
            ]);
            $req->merge(["document" => $req->ruleImage]);
            $docUpload = new DocUpload;
            $data = $docUpload->checkDoc($req);
            if (!$data["status"]) {
                throw new Exception("Document Not uploaded");
            }
            $req->merge($data["data"]);
            $update = new ActRule();
            $list  = $update->updateRule($req);

            return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all act rules.
     */
    public function listActRule(Request $req)
    {
        try {
            $list = new ActRule();
            $docUpload = new DocUpload;
            $masters = $list->listRule()->map(function ($val) use ($docUpload) {
                $url = $docUpload->getSingleDocUrl($val);
                $val->is_suspended = $val->status;
                $val->rule_image_url = $url["doc_path"] ?? null;
                return $val;
            });

            return responseMsgs(true, "All List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all act rules.
     */
    public function listActRulev1(Request $req)
    {
        $list = new ActRule();
        $docUpload = new DocUpload;
        $masters = $list->listDash()->map(function ($val) use ($docUpload) {
            $url = $docUpload->getSingleDocUrl($val);
            $val->is_suspended = $val->status;
            $val->rule_image_url = $url["doc_path"] ?? null;
            return $val;
        });

        return  $masters;
    }

    /* 
     * | Retrieves an act rule by its ID.
     */
    public function deleteActRule(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required'
            ]);
            $delete = new ActRule();
            $message = $delete->deleteRule($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves an act rule by its ID.
     */
    public function actRulebyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $list = new ActRule();
            $message = $list->getById($req);
            $docUpload = new DocUpload();
            $url = $docUpload->getSingleDocUrl($message);
            $message->is_suspended = $message->status;
            $message->rule_image_url = $url["doc_path"] ?? null;
            return responseMsgs(true, " Details", $message, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Adds a new administrative entry.
     */
    public function addAdministrative(Request $req)
    {
        try {
            $req->validate([
                'Name' => 'required',
                'designation' => 'required',
                'Image' => 'required|mimes:pdf,jpeg,png,jpg',
                'address' => 'required',
                'phone' => "required",
                'email' => 'required|email',
            ]);
            $req->merge(["document" => $req->Image]);
            $docUpload = new DocUpload;
            $data = $docUpload->checkDoc($req);
            if (!$data["status"]) {
                throw new Exception("Document Not uploaded");
            }
            $req->merge($data["data"]);
            $create = new Administrative();
            if (!$create->addAdministrative($req)) {
                throw new Exception("data not stored");
            }

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Updates an existing administrative entry by ID.
     */
    public function updateAdministrative(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'Name' => 'nullable',
                'designation' => 'nullable',
                'Image' => 'nullable|mimes:pdf,jpeg,png,jpg',
                'address' => 'nullable',
                'phone' => "nullable",
                'email' => 'nullable|email'
            ]);
            $data = ['data' => []];
            if ($req->hasFile('Image')) {
                $req->merge(["document" => $req->Image]);
                $docUpload = new DocUpload;
                $data = $docUpload->checkDoc($req);
                if (!$data["status"]) {
                    throw new Exception("Document Not uploaded");
                }
            }
            $req->merge($data["data"]);
            $update = new Administrative();
            $list  = $update->updateAdministrative($req);

            return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all administrative entries.
     */
    public function listAdministrative(Request $req)
    {
        try {
            $list = new Administrative();
            $docUpload = new DocUpload;
            $masters = $list->listRule()->map(function ($val) use ($docUpload) {
                $url = $docUpload->getSingleDocUrl($val);
                $val->is_suspended = $val->status;
                $val->image_url = $url["doc_path"] ?? null;
                return $val;
            });

            return responseMsgs(true, "All List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all administrative entries for the dashboard.
     */
    public function listAdministrativev1(Request $req)
    {
        $list = new Administrative();
        $docUpload = new DocUpload;
        $masters = $list->listDash()->map(function ($val) use ($docUpload) {
            $url = $docUpload->getSingleDocUrl($val);
            $val->is_suspended = $val->status;
            $val->image_url = $url["doc_path"] ?? null;
            return $val;
        });
        return $masters;
    }

    /* 
     * | Retrieves an administrative entry by its ID.
     */
    public function deleteAdministrative(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'status' => 'required'
            ]);
            $delete = new Administrative();
            $message = $delete->deleteAdministrative($req);
            return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves an administrative entry by its ID.
     */
    public function administrativebyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $list = new Administrative();
            $message = $list->getById($req);
            $docUpload = new DocUpload();
            $url = $docUpload->getSingleDocUrl($message);
            $message->is_suspended = $message->status;
            $message->image_url = $url["doc_path"] ?? null;
            return responseMsgs(true, " Details", $message, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all administrative entries.
     */
    public function dashboardData(Request $req)
    {
        try {
            // Increment API hit count using Redis
            $today = now()->toDateString();
            
            $cacheKeyTotal = "dashboard_hits_total";
            $cacheKeyToday = "dashboard_hits_" . $today;

            // Retrieve and update hit counts using Redis
            $totalHits = Redis::incr($cacheKeyTotal);
            $todayHits = Redis::incr($cacheKeyToday);

            Redis::expire($cacheKeyToday, now()->endOfDay()->diffInSeconds());

            $whatsnew = new Mwhat();
            $notice = new ImportantNotice();
            $announcement = new Announcement();
            $quickLink = new QuickLink();
            $scheme = new MScheme();
            $mobileApp = new MMobileAppLink();
            $newsEvent = new MNewsEvent();
            $eService = new MEService();
            $impLink = new ImportantLink();
            $usefulLink = new UsefulLink();
            $department = new Department();
            $contact = new Contact();
            $citizenDesk = new CitizenDesk();
            $aboutUs = new AboutUsDetail();

            $list = [
                "Whats New" => $whatsnew->listDash(),
                "Important Notice" => $notice->listDash(),
                "Announcement" => $announcement->listDash(),
                "Quick Links" => $quickLink->listDash(),
                "Scheme" => $scheme->listDash(),
                "Mobile App" => $mobileApp->listDash(),
                "News Event" => $newsEvent->listDash(),
                "E-Service" => $eService->listDash(),
                "Important Link" => $impLink->listDash(),
                "Usefull Link" => $usefulLink->listDash(),
                "Department" => $department->listDash(),
                "Contact" => $contact->listDash(),
                "Citizen Desk" => $citizenDesk->listDash(),
                "Assets" => $this->allListAssetesDash($req),
                "Slider" => $this->allSliderListv1($req),
                "About Us" => $aboutUs->listDash(),
                "Act Rule" => $this->listActRulev1($req),
                "Administrative" => $this->listAdministrativev1($req),
                "TotalHits" => $totalHits,
                "TodayHits" => $todayHits
            ];

            return responseMsgs(true, "All Data", $list, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /* 
     * | Retrieves a list of all assets for the dashboard.
     * | added by alok 
     */
    public function dashboardSliderData(Request $req)
    {
        try {

            $sliderDtl = $this->allSliderListv1($req);
            $list = [

                "Slider" => $sliderDtl,
            ];

            return responseMsgs(true, "All Data", $list, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }
}
