<?php

namespace App\Http\Controllers\Property;

use App\EloquentClass\Property\PenaltyRebateCalculation;
use App\EloquentClass\Property\SafCalculation;
use App\Http\Controllers\Controller;
use App\Models\Payment\WebhookPaymentData;
use App\Models\Property\PaymentPropPenaltyrebate;
use App\Models\Property\PropDemand;
use App\Models\Property\PropOwner;
use App\Models\Property\PropProperty;
use App\Models\Property\PropSafsDemand;
use App\Models\Property\PropTranDtl;
use App\Models\Property\PropTransaction;
use App\Traits\Payment\Razorpay;
use App\Traits\Property\SAF;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HoldingTaxController extends Controller
{
    use SAF;
    use Razorpay;
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
            $mPropProperty = new PropProperty();
            $penaltyRebateCalc = new PenaltyRebateCalculation;
            $currentQuarter = calculateQtr(Carbon::now()->format('Y-m-d'));
            $loggedInUserType = authUser()->user_type;
            $mPropOwners = new PropOwner();
            $ownerDetails = $mPropOwners->getOwnerByPropId($req->propId)->first();
            $demand = array();
            $demandList = $mPropDemand->getDueDemandByPropId($req->propId);
            $demandList = collect($demandList);
            $propDtls = $mPropProperty->getPropById($req->propId);
            $balance = $propDtls->balance ?? 0;

            if ($demandList->isEmpty())
                throw new Exception("Dues Not Available for this Property");

            $demandList = $demandList->map(function ($item) {                                // One Perc Penalty Tax
                return $this->calcOnePercPenalty($item);
            });

            $dues = $demandList->sum('balance');
            $onePercTax = $demandList->sum('onePercPenaltyTax');
            $mLastQuarterDemand = $demandList->last()->balance;
            $totalDuesList = [
                'totalDues' => $dues,
                'duesFrom' => "Quarter " . $demandList->last()->qtr . "/ Year " . $demandList->last()->fyear,
                'duesTo' => "Quarter " . $demandList->first()->qtr . "/ Year " . $demandList->last()->fyear,
                'onePercPenalty' => $onePercTax,
                'totalQuarters' => $demandList->count(),
                'arrear' => $balance
            ];

            $totalDuesList = $penaltyRebateCalc->readRebates($currentQuarter, $loggedInUserType, $mLastQuarterDemand, $ownerDetails, $dues, $totalDuesList);

            $finalPayableAmt = ($dues + $onePercTax + $balance) - ($totalDuesList['rebateAmt'] + $totalDuesList['specialRebateAmt']);
            $totalDuesList['payableAmount'] = roundFigure($finalPayableAmt);

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

    /**
     * | Generate Order ID
     */
    public function generateOrderId(Request $req)
    {
        $req->validate([
            'propId' => 'required',
            'amount' => 'required|numeric'
        ]);
        try {
            $demand = $this->getHoldingDues($req);
            $dueList = $demand->original['data']['duesList'];
            $departmentId = 1;
            $propProperties = new PropProperty();
            $propDtls = $propProperties->getPropById($req->propId);
            $req->request->add(['workflowId' => '0', 'departmentId' => $departmentId, 'ulbId' => $propDtls->ulb_id, 'id' => $req->propId]);
            $orderDetails = $this->saveGenerateOrderid($req);                                      //<---------- Generate Order ID Trait
            $this->postPaymentPenaltyRebate($dueList, $req);
            return responseMsgs(true, "Order id Generated", remove_null($orderDetails), "011603", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "011603", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Post Payment Penalty Rebates
     */
    public function postPaymentPenaltyRebate($dueList, $req)
    {
        $mPaymentRebatePanelties = new PaymentPropPenaltyrebate();
        $headNames = [
            [
                'keyString' => '1% Monthly Penalty',
                'value' => $dueList['onePercPenalty'],
                'isRebate' => false
            ],
            [
                'keyString' => 'Special Rebate',
                'value' => $dueList['specialRebateAmt'],
                'isRebate' => true
            ],
            [
                'keyString' => 'Rebate',
                'value' => $dueList['rebateAmt'],
                'isRebate' => true
            ]
        ];

        collect($headNames)->map(function ($headName) use ($mPaymentRebatePanelties, $req) {
            $propPayRebatePenalty = $mPaymentRebatePanelties->getRebatePanelties('prop_id', $req->propId, $headName['keyString']);
            if ($headName['value'] > 0) {
                $reqs = [
                    'prop_id' => $req->propId,
                    'head_name' => $headName['keyString'],
                    'amount' => $headName['value'],
                    'is_rebate' => $headName['isRebate'],
                    'tran_date' => Carbon::now()->format('Y-m-d')
                ];

                if ($propPayRebatePenalty)
                    $mPaymentRebatePanelties->editRebatePenalty($propPayRebatePenalty->id, $reqs);
                else
                    $mPaymentRebatePanelties->postRebatePenalty($reqs);
            }
        });
    }

    /**
     * | Payment Holding
     */
    public function paymentHolding(Request $req)
    {
        try {
            $todayDate = Carbon::now();
            $userId = $req['userId'];
            $propDemand = new PropDemand();
            $demands = $propDemand->getDemandByPropId($req['id']);
            DB::beginTransaction();
            // Property Transactions
            $propTrans = new PropTransaction();
            $propTrans->property_id = $req['id'];
            $propTrans->amount = $req['amount'];
            $propTrans->tran_date = $todayDate->format('Y-m-d');
            $propTrans->tran_no = $req['transactionNo'];
            $propTrans->payment_mode = $req['paymentMode'];
            $propTrans->user_id = $userId;
            $propTrans->ulb_id = $req['ulbId'];
            $propTrans->save();

            // Reflect on Prop Tran Details
            foreach ($demands as $demand) {
                $demand->balance = 0;
                $demand->paid_status = 1;           // <-------- Update Demand Paid Status 
                $demand->save();

                $propTranDtl = new PropTranDtl();
                $propTranDtl->tran_id = $propTrans->id;
                $propTranDtl->prop_demand_id = $demand['id'];
                $propTranDtl->total_demand = $demand['amount'];
                $propTranDtl->ulb_id = $req['ulbId'];
                $propTranDtl->save();
            }

            // Replication Prop Rebates Penalties
            $mPropPenalRebates = new PaymentPropPenaltyrebate();
            $rebatePenalties = $mPropPenalRebates->getPenalRebatesByPropId($req['id']);

            collect($rebatePenalties)->map(function ($rebatePenalty) use ($propTrans, $todayDate) {
                $replicate = $rebatePenalty->replicate();
                $replicate->setTable('prop_penaltyrebates');
                $replicate->tran_id = $propTrans->id;
                $replicate->tran_date = $todayDate->format('Y-m-d');
                $replicate->save();
            });

            DB::commit();
            return responseMsgs(true, "Payment Successfully Done", "", "011604", "1.0", "", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "011604", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Generate Payment Receipt
     */
    public function propPaymentReceipt(Request $req)
    {
        $req->validate([
            'tranNo' => 'required'
        ]);
        try {
            $mPaymentData = new WebhookPaymentData();
            $applicationDtls = $mPaymentData->getApplicationId($req->tranNo);
            return $applicationDtls;
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "011605", "1.0", "", "POST", $req->deviceId);
        }
    }
}
