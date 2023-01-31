<?php

namespace App\Http\Controllers\Property;

use App\EloquentClass\Property\PenaltyRebateCalculation;
use App\EloquentClass\Property\SafCalculation;
use App\Http\Controllers\Controller;
use App\Models\Property\PropDemand;
use App\Models\Property\PropOwner;
use App\Models\Property\PropProperty;
use App\Models\Property\PropSafsDemand;
use App\Traits\Property\SAF;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class HoldingTaxController extends Controller
{
    use SAF;
    protected $_propertyDetails;
    /**
     * | Created On-19/01/2023 
     * | Created By-Anshu Kumar
     * | Created for Holding Property Tax Demand and Receipt Generation
     * | Status-Open
     */

    /**
     * | Generate Holding Demand(1)
     */
    public function generateHoldingDemand(Request $req)
    {
        $req->validate([
            'propId' => 'required|numeric'
        ]);
        try {
            $holdingDemand = array();
            $responseDemand = array();
            $propId = $req->propId;
            $mPropProperty = new PropProperty();
            $safCalculation = new SafCalculation;
            $details = $mPropProperty->getPropFullDtls($propId);
            $this->_propertyDetails = $details;
            $calReqs = $this->generateSafRequest($details);                                                   // Generate Calculation Parameters
            $calParams = $this->generateCalculationParams($propId, $calReqs);                                 // (1.1)
            $calParams = array_merge($calParams, ['isProperty' => true]);
            $calParams = new Request($calParams);
            $taxes = $safCalculation->calculateTax($calParams);
            $holdingDemand['amount'] = $taxes->original['data']['demand'];
            $holdingDemand['details'] = $this->generateSafDemand($taxes->original['data']['details']);
            $holdingDemand['holdingNo'] = $details['holding_no'];
            $responseDemand['amount'] = $holdingDemand['amount'];
            $responseDemand['details'] = collect($taxes->original['data']['details'])->groupBy('ruleSet');
            return responseMsgs(true, "Property Demand", remove_null($responseDemand), "011601", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), ['holdingNo' => $details['holding_no']], "011601", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Read the Calculation From Date (1.1)
     */
    public function generateCalculationParams($propertyId, $propDetails)
    {
        $mPropDemand = new PropDemand();
        $mSafDemand = new PropSafsDemand();
        $safId = $this->_propertyDetails->saf_id;
        $todayDate = Carbon::now();
        $propDemand = $mPropDemand->readLastDemandDateByPropId($propertyId);
        if (!$propDemand) {
            $propDemand = $mSafDemand->readLastDemandDateBySafId($safId);
            if (!$propDemand)
                throw new Exception("Last Demand is Not Available for this Property");
        }
        $lastPayDate = $propDemand->due_date;
        if (Carbon::parse($lastPayDate) > $todayDate)
            throw new Exception("No Dues For This Property");
        $payFrom = Carbon::parse($lastPayDate)->addDay(1);
        $payFrom = $payFrom->format('Y-m-d');

        $realFloor = collect($propDetails['floor'])->map(function ($floor) use ($payFrom) {
            $floor['dateFrom'] = $payFrom;
            return $floor;
        });

        $propDetails['floor'] = $realFloor->toArray();
        return $propDetails;
    }

    /**
     * | Get Holding Dues 
     */
    public function getHoldingDues(Request $req)
    {
        $req->validate([
            'propId' => 'required|digits_between:1,9223372036854775807'
        ]);

        try {
            $mPropDemand = new PropDemand();
            $penaltyRebateCalc = new PenaltyRebateCalculation;
            $currentQuarter = calculateQtr(Carbon::now()->format('Y-m-d'));
            $loggedInUserType = authUser()->user_type;
            $mPropOwners = new PropOwner();
            $ownerDetails = $mPropOwners->getOwnerByPropId($req->propId)->first();
            $demand = array();
            $demandList = $mPropDemand->getDueDemandByPropId($req->propId);
            $demandList = collect($demandList);

            if (!$demandList)
                throw new Exception("Dues Not Found for this Property");

            $demandList = $demandList->map(function ($item) {                                // One Perc Penalty Tax
                return $this->calcOnePercPenalty($item);
            });

            $dues = $demandList->sum('balance');
            $onePercTax = $demandList->sum('onePercPenaltyTax');
            $mLastQuarterDemand = $demandList->last()->balance;
            $totalDuesList = [
                'totalDues' => $dues,
                'duesFrom' => "quarter " . $demandList->last()->qtr . "/Year " . $demandList->last()->fyear,
                'duesTo' => "quarter " . $demandList->first()->qtr . "/Year " . $demandList->last()->fyear,
                'onePercPenalty' => $onePercTax,
                'payableAmt' => roundFigure($dues + $onePercTax),
                'totalQuarters' => $demandList->count()
            ];

            $demand['duesList'] = $totalDuesList;
            $demand['demandList'] = $demandList;

            return responseMsgs(true, "Demand Details", remove_null($demand), "011602", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "011602", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | One Percent Penalty Calculation
     */
    public function calcOnePercPenalty($item)
    {
        $penaltyRebateCalc = new PenaltyRebateCalculation;
        $onePercPenalty = $penaltyRebateCalc->calcOnePercPenalty($item->due_date);        // Calculation One Percent Penalty
        $item['onePercPenalty'] = $onePercPenalty;
        $onePercPenaltyTax = ($item['balance'] * $onePercPenalty) / 100;
        $item['onePercPenaltyTax'] = roundFigure($onePercPenaltyTax);
        return $item;
    }
}
