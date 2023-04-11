<?php

namespace App\BLL\Property;

use App\EloquentClass\Property\PenaltyRebateCalculation;
use App\EloquentClass\Property\SafCalculation;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropActiveSafsFloor;
use App\Models\Property\PropActiveSafsOwner;
use App\Models\Property\PropDemand;
use App\Models\Property\PropProperty;
use App\Models\Property\PropSafsDemand;
use App\Traits\Property\SAF;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

/**
 * | Calculate Saf By Saf Id Service
 * | Created By-Anshu Kumar
 * | Created On-29-03-2023 
 * | Status-Closed
 */

class CalculateSafById
{
    use SAF;
    private $_mPropActiveSaf;
    private $_mPropActiveSafFloors;
    private $_mPropActiveSafOwner;
    private $_penaltyRebateCalc;
    private $_safId;
    public $_safDetails;
    private $_safFloorDetails;
    private $_safCalculation;
    public $_safCalculationReq;
    public $_calculatedDemand;
    public $_generatedDemand = array();
    public $_demandDetails;
    private $_todayDate;
    private $_currentQuarter;
    public $_holdingNo;
    public $_firstOwner;
    public $_mPropActiveSafOwners;

    public function __construct()
    {
        $this->_mPropActiveSafOwner = new PropActiveSafsOwner();
        $this->_mPropActiveSafFloors = new PropActiveSafsFloor();
        $this->_mPropActiveSaf = new PropActiveSaf();
        $this->_safCalculation = new SafCalculation;
        $this->_penaltyRebateCalc = new PenaltyRebateCalculation;
        $this->_todayDate = Carbon::now();
        $this->_mPropActiveSafOwners = new PropActiveSafsOwner();
    }

    /**
     * | Calculation Function (1)
     */

    public function calculateTax(Request $req)
    {
        $this->_safId = $req->id;
        $this->_holdingNo = $req->holdingNo;

        $this->readMasters();            // Function (1.1)

        $this->generateFloorCalcReq();

        $this->generateCalculationReq();                                        // (Function 1.3)
        // Saf Calculation
        $reqCalculation = $this->_safCalculationReq;
        $calculation = $this->_safCalculation->calculateTax($reqCalculation);

        // Throw Exception on Calculation Error
        if ($calculation->original['status'] == false)
            throw new Exception($calculation->original['message']);

        $this->_calculatedDemand = $calculation->original['data'];

        $this->generateSafDemand();   // (1.2)

        return $this->_generatedDemand;
    }

    /**
     * | Read All Master Data (1.1)
     */

    public function readMasters()
    {
        $this->_safDetails = $this->_mPropActiveSaf::findOrFail($this->_safId);
        $this->_currentQuarter = calculateQtr($this->_todayDate->format('Y-m-d'));

        // Read Owner
        $this->readOwnerDetails();
    }

    /**
     * | Read Owner Details
     */
    public function readOwnerDetails()
    {
        $mPropSafsOwners = $this->_mPropActiveSafOwner;
        $this->_firstOwner = $mPropSafsOwners->getOwnerDtlsBySafId1($this->_safId);
    }

    /**
     * | Function Generate Floor Calculation Reqs
     */
    public function generateFloorCalcReq()
    {
        $safFloors = array();
        // Building Case
        if ($this->_safDetails['property_type'] != 4) {
            $floors = $this->_mPropActiveSafFloors->getSafFloorsBySafId($this->_safId);
            foreach ($floors as $floor) {
                $floorReq = [
                    "floorNo" => $floor['floor_mstr_id'],
                    "useType" => $floor['usage_type_mstr_id'],
                    "constructionType" => $floor['const_type_mstr_id'],
                    "occupancyType" => $floor['occupancy_type_mstr_id'],
                    "buildupArea" => $floor['builtup_area'],
                    "dateFrom" => $floor['date_from'],
                    "dateUpto" => $floor['date_upto'],
                    "carpetArea" => $floor['carpet_area']
                ];
                array_push($safFloors, $floorReq);
            }
            $this->_safFloorDetails = $safFloors;
        }
    }

    /**
     * | Function (1.3)
     */
    public function generateCalculationReq()
    {
        $safDetails = $this->_safDetails;
        $calculationReq = [
            "ulbId" => $safDetails['ulb_id'],
            "ward" => $safDetails['ward_mstr_id'],
            "propertyType" => $safDetails['prop_type_mstr_id'],
            "landOccupationDate" => $safDetails['land_occupation_date'],
            "ownershipType" => $safDetails['ownership_type_mstr_id'],
            "roadType" => $safDetails['road_width'],
            "areaOfPlot" => $safDetails['area_of_plot'],
            "isMobileTower" => $safDetails['is_mobile_tower'],
            "mobileTower" => [
                "area" => $safDetails['tower_area'],
                "dateFrom" => $safDetails['tower_installation_date']
            ],
            "isHoardingBoard" => $safDetails['is_hoarding_board'],
            "hoardingBoard" => [
                "area" => $safDetails['hoarding_area'],
                "dateFrom" => $safDetails['hoarding_installation_date']
            ],
            "isPetrolPump" => $safDetails['is_petrol_pump'],
            "petrolPump" => [
                "area" => $safDetails['under_ground_area'],
                "dateFrom" => $safDetails['petrol_pump_completion_date']
            ],
            "isWaterHarvesting" => $safDetails['is_water_harvesting'],
            "zone" => $safDetails['zone_mstr_id'],
            "floor" => $this->_safFloorDetails,
            "isGBSaf" => $safDetails['is_gb_saf'],
            "apartmentId" => $safDetails['apartment_details_id'],
            "isTrust" => $safDetails['is_trust'],
            "trustType" => $safDetails['trust_type'],
            "isTrustVerified" => $safDetails['is_trust_verified']
        ];
        $this->_safCalculationReq = new Request($calculationReq);
    }

    /**
     * | Generated SAF Demand to push the value in propSafsDemand Table // (1.2)
     */
    public function generateSafDemand()
    {
        $collection = $this->_calculatedDemand['details'];
        $filtered = collect($collection)->map(function ($value) {
            return collect($value)->only([
                'qtr', 'holdingTax', 'waterTax', 'educationTax',
                'healthTax', 'latrineTax', 'quarterYear', 'dueDate', 'totalTax', 'arv', 'rwhPenalty', 'onePercPenalty', 'onePercPenaltyTax', 'ruleSet'
            ]);
        });

        $groupBy = $filtered->groupBy(['quarterYear', 'qtr']);

        $taxes = $groupBy->map(function ($values) {
            return $values->map(function ($collection) {
                $amount = roundFigure($collection->sum('totalTax'));
                return collect([
                    'qtr' => $collection->first()['qtr'],
                    'holding_tax' => roundFigure($collection->sum('holdingTax')),
                    'water_tax' => roundFigure($collection->sum('waterTax')),
                    'education_cess' => roundFigure($collection->sum('educationTax')),
                    'health_cess' => roundFigure($collection->sum('healthTax')),
                    'latrine_tax' => roundFigure($collection->sum('latrineTax')),
                    'additional_tax' => roundFigure($collection->sum('rwhPenalty')),
                    'fyear' => $collection->first()['quarterYear'],
                    'due_date' => $collection->first()['dueDate'],
                    'amount' => $amount,
                    'arv' => roundFigure($collection->sum('arv')),
                    'adjust_amount' => 0,
                    'ruleSet' => $collection->first()['ruleSet'],
                    'balance' => $amount,
                    'rwhPenalty' => roundFigure($collection->sum('rwhPenalty'))
                ]);
            });
        });

        $demandDetails = $taxes->values()->collapse();

        $this->_demandDetails = $demandDetails;

        if (in_array($this->_safDetails['assessment_type'], ['Re Assessment', 'ReAssessment', 'Mutation', '2', '3']))     // In Case of Reassessment Adjust the Amount
            $this->adjustAmount();         // (1.2.1)

        $this->calculateOnePercPenalty();   // (1.2.2)

        $demandDetails = $this->_demandDetails;
        $dueFrom = "Quarter " . $demandDetails->first()['qtr'] . '/' . 'Year ' . $demandDetails->first()['fyear'];
        $dueTo = "Quarter " . $demandDetails->last()['qtr'] . '/' . 'Year ' . $demandDetails->last()['fyear'];

        $totalTax = roundFigure($demandDetails->sum('balance'));
        $totalOnePercPenalty = roundFigure($demandDetails->sum('onePercPenaltyTax'));
        $totalDemand = $totalTax + $totalOnePercPenalty + $this->_calculatedDemand['demand']['lateAssessmentStatus'] + $this->_calculatedDemand['demand']['lateAssessmentPenalty'];
        $this->_generatedDemand['demand'] = [
            'dueFromFyear' => $demandDetails->first()['fyear'],
            'dueToFyear' => $demandDetails->last()['fyear'],
            'dueFromQtr' => $demandDetails->first()['qtr'],
            'dueToQtr' => $demandDetails->last()['qtr'],
            'totalTax' => $totalTax,
            'totalOnePercPenalty' => $totalOnePercPenalty,
            'totalQuarters' => $demandDetails->count(),
            'duesFrom' => $dueFrom,
            'duesTo' => $dueTo,
            'lateAssessmentStatus' => $this->_calculatedDemand['demand']['lateAssessmentStatus'],
            'lateAssessmentPenalty' => $this->_calculatedDemand['demand']['lateAssessmentPenalty'],
            'totalDemand' => $totalDemand
        ];

        $this->_generatedDemand['details'] = $this->_demandDetails;

        $this->readRebates();                                               // (1.2.3)

        $payableAmount = $totalDemand - ($this->_generatedDemand['demand']['rebateAmt'] + $this->_generatedDemand['demand']['specialRebateAmt']);   // Final Payable Amount Calculation
        $this->_generatedDemand['demand']['payableAmount'] = round($payableAmount);

        $this->generateTaxDtls();        // (1.2.3)
    }


    /**
     * | Adjust Amount In Case of Reassessment (1.2.1)
     */
    public function adjustAmount()
    {
        $propDemandList = array();
        $mSafDemand = new PropSafsDemand();
        $propProperty = new PropProperty();
        $mPropDemands = new PropDemand();
        $generatedDemand = $this->_demandDetails;
        $holdingNo = $this->_holdingNo;
        $propDtls = $propProperty->getSafIdByHoldingNo($holdingNo);
        $propertyId = $propDtls->id;
        $safDemandList = $mSafDemand->getFullDemandsBySafId($propDtls->saf_id);
        if ($safDemandList->isEmpty())
            throw new Exception("Previous Saf Demand is Not Available");

        $propDemandList = $mPropDemands->getFullDemandsByPropId($propertyId);
        $fullDemandList = $safDemandList->merge($propDemandList);
        $generatedDemand = $generatedDemand->sortBy('due_date');

        // Demand Adjustment
        foreach ($generatedDemand as $item) {
            $demand = $fullDemandList->where('due_date', $item['due_date'])->first();
            if (collect($demand)->isEmpty())
                $item['adjustAmount'] = 0;
            else
                $item['adjustAmount'] = $demand->amount - $demand->balance;

            $item['adjust_amount'] = $item['adjustAmount'];
            $item['balance'] = roundFigure($item['amount'] - $item['adjust_amount']);
            if ($item['balance'] == 0)
                $item['onePercPenaltyTax'] = 0;
        }
        $this->_demandDetails = $generatedDemand;
    }

    /**
     * | One Percent Penalty Calculation (1.2.2)
     */
    public function calculateOnePercPenalty()
    {
        $penaltyRebateCalculation = $this->_penaltyRebateCalc;
        $demandDetails = $this->_demandDetails;
        foreach ($demandDetails as $demandDetail) {
            $penaltyPerc = $penaltyRebateCalculation->calcOnePercPenalty($demandDetail['due_date']);
            $penaltyTax = roundFigure(($demandDetail['balance'] * $penaltyPerc) / 100);
            $demandDetail['onePercPenalty'] = $penaltyPerc;
            $demandDetail['onePercPenaltyTax'] = $penaltyTax;
        }

        $this->_demandDetails = $demandDetails;
    }

    /**
     * | Calculation for Read Rebates (1.2.3)
     */
    public function readRebates()
    {
        $penaltyRebateCalculation = $this->_penaltyRebateCalc;
        $currentQuarter = $this->_currentQuarter;
        $loggedInUserType = auth()->user()->user_type ?? 'Citizen';
        $currentFYear = getFY();
        $lastQuarterDemand = $this->_generatedDemand['details']->where('fyear', $currentFYear)->sum('balance');
        $ownerDetails = $this->_firstOwner;
        $totalDemand = $this->_generatedDemand['demand']['totalTax'];
        $totalDuesList = $this->_generatedDemand['demand'];
        $this->_generatedDemand['demand'] = $penaltyRebateCalculation->readRebates(
            $currentQuarter,
            $loggedInUserType,
            $lastQuarterDemand,
            $ownerDetails,
            $totalDemand,
            $totalDuesList
        );
    }

    /**
     * | Generation of Tax Details(1.2.3)
     */
    public function generateTaxDtls()
    {
        $taxDetails = collect();
        $demandDetails = $this->_generatedDemand['details'];
        $groupByDemands = collect($demandDetails)->groupBy('arv');
        $currentArv = $groupByDemands->last()->first()['arv'];          // Get Current Demand Arv Rate
        foreach ($groupByDemands as $key => $item) {
            $firstTax = collect($item)->first();
            if ($key == $currentArv)
                $firstTax['status'] = "Current";
            else
                $firstTax['status'] = "Old";

            $taxDetails->push($firstTax);
        }
        $this->_generatedDemand['taxDetails'] = $taxDetails;
    }
}
