<?php

namespace App\BLL\Property;

use App\EloquentClass\Property\SafCalculation;
use App\Http\Controllers\Property\ApplySafController;
use App\Models\Property\PropDemand;
use App\Models\Property\PropSafsDemand;
use App\Traits\Property\SAF;
use Illuminate\Http\Request;

/**
 * | Created On-27-03-2023 
 * | Created by- Anshu Kumar
 * | Handle TC Verifications Datas
 */
class HandleTcVerification
{
    use SAF;
    public $_mPropSafsDemands;
    public $_safCalculation;
    public $_applySafController;
    public $_reqs;
    public $_activeSafDtls;
    public $_quaterlyTax;
    public $_mPropDemands;
    public function __construct()
    {
        $this->_mPropSafsDemands = new PropSafsDemand();
        $this->_safCalculation = new SafCalculation;
        $this->_applySafController = new ApplySafController;
        $this->_mPropDemands = new PropDemand();
    }

    /** 
     * | Generate Tc Verification Demand (1)
     */
    public function generateTcVerifiedDemand($req)
    {
        $this->_reqs = $req;
        $this->_activeSafDtls = $req['activeSafDtls'];
        $this->_quaterlyTax = $this->calculateQuaterlyTax();           // (1.1)
        return $this->adjustVerifiedDemand();
    }

    /**
     * | Calculate Quaterly Tax (1.1)
     */
    public function calculateQuaterlyTax()
    {
        $activeSafDtls = $this->_activeSafDtls;
        $floors = array();
        $fieldVerifiedSafs = collect($this->_reqs['fieldVerificationDtls']);
        if ($fieldVerifiedSafs->first()->prop_type_id != 4) {
            foreach ($fieldVerifiedSafs as $item) {
                $floorDtl = [
                    "floorNo" => $item->floor_mstr_id,
                    "useType" => $item->usage_type_id,
                    "constructionType" => $item->construction_type_id,
                    "occupancyType" => $item->occupancy_type_id,
                    "buildupArea" => $item->builtup_area,
                    "dateFrom" => $item->date_from,
                    "dateUpto" => $item->date_to
                ];
                array_push($floors, $floorDtl);
            }
        }
        $fieldVerifiedSaf = $fieldVerifiedSafs->first();
        $calculationReq = [
            "assessmentType" => $this->_reqs['assessmentType'],
            "ulbId" => $this->_reqs['ulbId'],
            "ward" => $fieldVerifiedSaf->ward_id,
            "propertyType" => $fieldVerifiedSaf->prop_type_id,
            "landOccupationDate" => $activeSafDtls->land_occupation_date,
            "ownershipType" => $activeSafDtls->ownership_type,
            "apartmentId" => $activeSafDtls->apartment_details_id,
            "roadType" => $fieldVerifiedSaf->road_width,
            "areaOfPlot" => $fieldVerifiedSaf->area_of_plot,
            "isMobileTower" => $fieldVerifiedSaf->has_mobile_tower,
            "mobileTower" => [
                "area" => $fieldVerifiedSaf->tower_area,
                "dateFrom" => $fieldVerifiedSaf->tower_installation_date
            ],
            "isHoardingBoard" => $fieldVerifiedSaf->has_hoarding,
            "hoardingBoard" => [
                "area" => $fieldVerifiedSaf->hoarding_area,
                "dateFrom" => $fieldVerifiedSaf->hoarding_installation_date
            ],
            "isPetrolPump" => $fieldVerifiedSaf->is_petrol_pump,
            "petrolPump" => [
                "area" => $fieldVerifiedSaf->underground_area,
                "dateFrom" => $fieldVerifiedSaf->petrol_pump_completion_date
            ],
            "isWaterHarvesting" => $fieldVerifiedSaf->has_water_harvesting,
            "zone" => $activeSafDtls->zone_mstr_id,
            "floor" => $floors
        ];
        $calculationReq = new Request($calculationReq);
        $calculation = $this->_safCalculation->calculateTax($calculationReq);
        $calculation = $calculation->original['data'];
        $demandDetails = $calculation['details'];
        $quaterlyTax = $this->generateSafDemand($demandDetails);

        if ($this->_reqs['assessmentType'] == 'Re Assessment') {
            $this->_applySafController->_generatedDemand = $quaterlyTax;
            $this->_applySafController->_holdingNo = $activeSafDtls->holding_no;
            $quaterlyTax = $this->_applySafController->adjustDemand();
        }
        return $quaterlyTax;
    }

    /**
     * | Adjust Demand (1.2)
     */
    public function adjustVerifiedDemand()
    {
        $newDemand = array();
        $mPropDemand = $this->_mPropDemands;
        $mPropSafsDemands = $this->_mPropSafsDemands;
        $quaterlyTax = $this->_quaterlyTax;
        $propSafsDemands = $mPropSafsDemands->getFullDemandsBySafId($this->_activeSafDtls['id']);
        foreach ($quaterlyTax as $tax) {
            $safQtrDemand = $propSafsDemands->where('due_date', $tax['dueDate'])->first();
            if ($tax['totalTax'] > $safQtrDemand->amount) {
                $adjustAmt = roundFigure($safQtrDemand->amount - $safQtrDemand->adjust_amount);
                $balance = roundFigure($tax['balance'] - $adjustAmt);
                $taxes = [
                    'property_id' => 1,
                    'qtr' => $tax['qtr'],
                    'holding_tax' => $tax['holdingTax'],
                    'water_tax' => $tax['waterTax'],
                    'education_cess' => $tax['educationTax'],
                    'health_cess' => $tax['healthCess'],
                    'latrine_tax' => $tax['latrineTax'],
                    'additional_tax' => $tax['additionTax'],
                    'fyear' => $tax['quarterYear'],
                    'due_date' => $tax['dueDate'],
                    'amount' => $tax['totalTax'],
                    'arv' => $tax['arv'],
                    'adjust_amount' => $adjustAmt,
                    'balance' => $balance,
                ];
                array_push($newDemand, $taxes);
            }
        }
        return $newDemand;
    }
}
