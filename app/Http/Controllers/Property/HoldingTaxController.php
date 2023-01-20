<?php

namespace App\Http\Controllers\Property;

use App\EloquentClass\Property\SafCalculation;
use App\Http\Controllers\Controller;
use App\Models\Property\PropDemand;
use App\Models\Property\PropProperty;
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
            $propId = $req->propId;
            $mPropProperty = new PropProperty();
            $safCalculation = new SafCalculation;
            $details = $mPropProperty->getPropFullDtls($propId);
            $this->_propertyDetails = $details;
            $calReqs = $this->generateSafRequest($details);                                                   // Generate Calculation Parameters
            $calParams = $this->generateCalculationParams($propId, $calReqs);                                 // (1.1)

            $calParams = new Request($calParams);
            $taxes = $safCalculation->calculateTax($calParams);
            return $taxes;
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "011601", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Read the Calculation From Date (1.1)
     */
    public function generateCalculationParams($propertyId, $propDetails)
    {
        $mPropDemand = new PropDemand();
        $propDemand = $mPropDemand->readLastDemandDateByPropId($propertyId);
        $lastPayDate = $propDemand->due_date;
        $payFrom = Carbon::parse($lastPayDate)->addDay(1);
        $payFrom = $payFrom->format('Y-m-d');

        $realFloor = collect($propDetails['floor'])->map(function ($floor) use ($payFrom) {
            $floor['dateFrom'] = $payFrom;
            return $floor;
        });

        $propDetails['floor'] = $realFloor->toArray();
        return $propDetails;
    }
}
