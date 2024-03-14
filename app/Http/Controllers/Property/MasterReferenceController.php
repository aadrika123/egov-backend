<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
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
use App\Models\MPropForgeryType;






use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;

class MasterReferenceController extends Controller
{
    /**
     * |Construction Type Crud
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
     * |Floor Type Crud
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
     * |Gb Building Usage Type Crud
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
     * |Gb Prop Usage Type Crud
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
     * |Gb Prop Objection Type Crud
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
}
