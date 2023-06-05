<?php

namespace App\BLL\Property;

use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropActiveSafsFloor;
use App\Models\Property\PropActiveSafsOwner;
use App\Traits\Property\SAF;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * | Created On-03-06-2023 
 * | Author - Anshu Kumar
 * | Created for SAF Application Edition, Change Assessment Types etc.
 */

class EditSafBll
{
    use SAF;
    public $_reqs;
    private $_assessmentType;
    private $_safId;
    private $_mPropActiveSafFloors;
    private $_mPropActiveSaf;
    private $_mPropActiveOwners;

    public function __construct()
    {
        $this->_mPropActiveSaf = new PropActiveSaf();
        $this->_mPropActiveSafFloors = new PropActiveSafsFloor();
        $this->_mPropActiveOwners = new PropActiveSafsOwner();
    }

    /**
     * | Function 1(Edit Saf Application)
     */
    public function edit(): void
    {
        $this->readReferences();                // (1.1)
        $this->updateSafTbl();                  // (1.2)
        $this->updateSafFloorTbl();             // (1.3)
        $this->updateSafOwnerTbl();             // (1.4)
    }

    /**
     * | Read References and Masters(1.1)
     */
    public function readReferences(): void
    {
        $this->_assessmentType = $this->readAssessmentType($this->_reqs->assessmentType);
        $this->_safId = $this->_reqs->id;
    }

    /**
     * | Update Active Saf Table Only (1.2)
     */
    public function updateSafTbl(): void
    {
        $updateReqs = [
            'transfer_mode_mstr_id' => $this->_reqs->transferModeId,
            'holding_no' => $this->_reqs->holdingNo,
            'ward_mstr_id' => $this->_reqs->ward,
            'ownership_type_mstr_id' => $this->_reqs->ownershipType,
            'prop_type_mstr_id' => $this->_reqs->propertyType,
            'zone_mstr_id' => $this->_reqs->zone,
            'no_electric_connection' => $this->_reqs->electricityConnection,
            'elect_consumer_no' => $this->_reqs->electricityCustNo,
            'elect_acc_no' => $this->_reqs->electricityAccNo,
            'elect_bind_book_no' => $this->_reqs->electricityBindBookNo,
            'elect_cons_category' => $this->_reqs->electricityConsCategory,
            'building_plan_approval_no' => $this->_reqs->buildingPlanApprovalNo,
            'building_plan_approval_date' => $this->_reqs->buildingPlanApprovalDate,
            'water_conn_no' => $this->_reqs->waterConnNo,
            'water_conn_date' => $this->_reqs->waterConnDate,
            'khata_no' => $this->_reqs->khataNo,
            'plot_no' => $this->_reqs->plotNo,
            'village_mauja_name' => $this->_reqs->villageMaujaName,
            'area_of_plot' => $this->_reqs->areaOfPlot,
            'prop_address' => $this->_reqs->propAddress,
            'prop_city' => $this->_reqs->propCity,
            'prop_dist' => $this->_reqs->propDist,
            'prop_pin_code' => $this->_reqs->propPinCode,
            'is_corr_add_differ' => $this->_reqs->isCorrAddDiffer,
            'corr_address' => $this->_reqs->corrAddress,
            'corr_city' => $this->_reqs->corrCity,
            'corr_dist' => $this->_reqs->corrDist,
            'corr_pin_code' => $this->_reqs->corrPinCode,
            'is_mobile_tower' => $this->_reqs->isMobileTower,
            'tower_area' => $this->_reqs->mobileTower['area'],
            'tower_installation_date' => $this->_reqs->mobileTower['dateFrom'],
            'is_hoarding_board' => $this->_reqs->isHoardingBoard,
            'hoarding_area' => $this->_reqs->hoardingBoard['area'],
            'hoarding_installation_date' => $this->_reqs->hoardingBoard['dateFrom'],
            'is_petrol_pump' => $this->_reqs->isPetrolPump,
            'under_ground_area' => $this->_reqs->petrolPump['area'],
            'petrol_pump_completion_date' => $this->_reqs->petrolPump['dateFrom'],
            'is_water_harvesting' => $this->_reqs->isWaterHarvesting,
            'land_occupation_date' => $this->_reqs->landOccupationDate,
            'assessment_type' => $this->_assessmentType,
            'prop_state' => $this->_reqs->propState,
            'corr_state' => $this->_reqs->corrState,
            'holding_type' => $this->_reqs->holdingType,
            'new_ward_mstr_id' => $this->_reqs->newWard,
            'apartment_details_id' => $this->_reqs->apartmentId,
            'ulb_id' => $this->_reqs->ulbId,
            'applicant_name' => Str::upper(collect($this->_reqs->owner)->first()['ownerName']),
            'road_width' => $this->_reqs->roadType,
            'building_name' => $this->_reqs->buildingName,
            'street_name' => $this->_reqs->streetName,
            'location' => $this->_reqs->location,
            'landmark' => $this->_reqs->landmark
        ];
        DB::beginTransaction();
        $this->_mPropActiveSaf::where('id', $this->_reqs->id)
            ->update($updateReqs);
    }

    /**
     * | Update Saf Floors (1.3)
     */
    public function updateSafFloorTbl(): void
    {
        $floors = $this->_reqs['floor'];
        foreach ($floors as $floor) {
            $paramCarpetAreaPerc = ($floor['useType'] == 1) ? 0.70 : 0.80;
            $carpetArea = $floor['buildupArea'] * $paramCarpetAreaPerc;
            $editFloorReqs = [                                              // for Updation
                "date_upto" => $floor['dateUpto'],
                "prop_floor_details_id" => $floor['propFloorDetailId'],
                "user_id" => authUser()->id,
            ];

            $createFloorReqs = array_merge($editFloorReqs, [                // For Creation
                "floor_mstr_id" => $floor['floorNo'],
                "usage_type_mstr_id" => $floor['useType'],
                "const_type_mstr_id" => $floor['constructionType'],
                "occupancy_type_mstr_id" => $floor['occupancyType'],
                "builtup_area" => $floor['buildupArea'],
                "date_from" => $floor['dateFrom'],
                "carpet_area" => $carpetArea,
            ]);

            if (!is_null($floor['safFloorId'])) {                           // Update The floor
                $this->_mPropActiveSafFloors::findOrFail($floor['safFloorId'])
                    ->update($editFloorReqs);
            } else
                $this->_mPropActiveSafFloors::create($createFloorReqs);   // Add New Floors
        }
    }

    /**
     * | Update Saf Owners( 1.4 )
     */
    public function updateSafOwnerTbl(): void
    {
        $owners = $this->_reqs['owner'];
        foreach ($owners as $owner) {
            $ownerReqs = [
                "owner_name" => $owner['ownerName'],
                "guardian_name" => $owner['guardianName'],
                "relation_type" => $owner['relation'],
                "mobile_no" => $owner['mobileNo'],
                "email" => $owner['email'],
                "pan_no" => $owner['pan'] ?? null,
                "aadhar_no" => $owner['aadhar'] ?? null,
                "gender" => $owner['gender'],
                "dob" => $owner['dob'],
                "is_armed_force" => $owner['isArmedForce'],
                "is_specially_abled" => $owner['isSpeciallyAbled'],
                "user_id" => $owner,
                "prop_owner_id" => $owner['propOwnerDetailId'] ?? null,
            ];
            if (!is_null($owner['safOwnerId']))                                                                       // Update Existing Owners
                $this->_mPropActiveOwners::findOrFail($owner['safOwnerId'])->update($ownerReqs);

            if (is_null($owner['safOwnerId']) && in_array($this->_reqs->assessmentType, [1, 3, 4, 5]))            // Addition of the Owner in case of these assessment types only
                $this->_mPropActiveOwners::create($ownerReqs);                                                        // Add New Owner
        }
    }
}
