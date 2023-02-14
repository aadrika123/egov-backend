<?php

namespace App\Http\Controllers\Property;

use App\EloquentClass\Property\PenaltyRebateCalculation;
use App\EloquentClass\Property\SafCalculation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Property\ReqPayment;
use App\Models\Payment\WebhookPaymentData;
use App\Models\Property\PaymentPropPenaltyrebate;
use App\Models\Property\PropChequeDtl;
use App\Models\Property\PropDemand;
use App\Models\Property\PropOwner;
use App\Models\Property\PropPenaltyrebate;
use App\Models\Property\PropProperty;
use App\Models\Property\PropSafsDemand;
use App\Models\Property\PropTranDtl;
use App\Models\Property\PropTransaction;
use App\Repository\Property\Interfaces\iSafRepository;
use App\Traits\Payment\Razorpay;
use App\Traits\Property\SAF;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class HoldingTaxController extends Controller
{
    use SAF;
    use Razorpay;
    protected $_propertyDetails;
    protected $_safRepo;
    /**
     * | Created On-19/01/2023 
     * | Created By-Anshu Kumar
     * | Created for Holding Property Tax Demand and Receipt Generation
     * | Status-Closed
     */

    public function __construct(iSafRepository $safRepo)
    {
        $this->_safRepo = $safRepo;
    }
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
     * | Get Holding Dues(2)
     */
    public function getHoldingDues(Request $req)
    {
        $req->validate([
            'propId' => 'required|digits_between:1,9223372036854775807'
        ]);

        try {
            $todayDate = Carbon::now()->format('Y-m-d');
            $mPropDemand = new PropDemand();
            $mPropProperty = new PropProperty();
            $penaltyRebateCalc = new PenaltyRebateCalculation;
            $currentQuarter = calculateQtr(Carbon::now()->format('Y-m-d'));
            $loggedInUserType = authUser()->user_type ?? "Citizen";
            $mPropOwners = new PropOwner();
            $pendingFYears = collect();
            $qtrs = collect();

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

            $dues = roundFigure($demandList->sum('balance'));
            $onePercTax = roundFigure($demandList->sum('onePercPenaltyTax'));
            $mLastQuarterDemand = $demandList->last()->balance;

            collect($demandList)->map(function ($value) use ($pendingFYears, $qtrs) {
                $fYear = $value->fyear;
                $qtr = $value->qtr;
                $pendingFYears->push($fYear);
                $qtrs->push($qtr);
            });

            $paymentUptoYrs = $pendingFYears->unique();
            $totalDuesList = [
                'totalDues' => $dues,
                'duesFrom' => "Quarter " . $demandList->last()->qtr . "/ Year " . $demandList->last()->fyear,
                'duesTo' => "Quarter " . $demandList->first()->qtr . "/ Year " . $demandList->first()->fyear,
                'onePercPenalty' => $onePercTax,
                'totalQuarters' => $demandList->count(),
                'arrear' => $balance
            ];
            $currentQtr = calculateQtr($todayDate);

            $pendingQtrs = $qtrs->filter(function ($value) use ($currentQtr) {
                return $value >= $currentQtr;
            });

            $totalDuesList = $penaltyRebateCalc->readRebates($currentQuarter, $loggedInUserType, $mLastQuarterDemand, $ownerDetails, $dues, $totalDuesList);

            $finalPayableAmt = ($dues + $onePercTax + $balance) - ($totalDuesList['rebateAmt'] + $totalDuesList['specialRebateAmt']);
            $totalDuesList['payableAmount'] = round($finalPayableAmt);
            $totalDuesList['paymentUptoYrs'] = $paymentUptoYrs;
            $totalDuesList['paymentUptoQtrs'] = $pendingQtrs;

            $demand['duesList'] = $totalDuesList;
            $demand['demandList'] = $demandList;

            $propBasicDtls = $mPropProperty->getPropBasicDtls($req->propId);
            $demand['basicDetails'] = collect($propBasicDtls)->only([
                'holding_no',
                'old_ward_no',
                'new_ward_no',
                'property_type',
                'zone_mstr_id',
                'is_mobile_tower',
                'is_hoarding_board',
                'is_petrol_pump',
                'is_water_harvesting'
            ]);
            return responseMsgs(true, "Demand Details", remove_null($demand), "011602", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "011602", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | One Percent Penalty Calculation(2.1)
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
     * | Generate Order ID(3)
     */
    public function generateOrderId(Request $req)
    {
        $req->validate([
            'propId' => 'required',
            'amount' => 'required|numeric'
        ]);
        try {
            $departmentId = 1;
            $propProperties = new PropProperty();

            $demand = $this->getHoldingDues($req);
            $demandData = $demand->original['data'];
            if (!$demandData)
                throw new Exception("Demand Not Available");

            $dueList = $demandData['duesList'];
            $propDtls = $propProperties->getPropById($req->propId);
            $req->request->add(['workflowId' => '0', 'departmentId' => $departmentId, 'ulbId' => $propDtls->ulb_id, 'id' => $req->propId, 'ghostUserId' => 0]);
            $orderDetails = $this->saveGenerateOrderid($req);                                      //<---------- Generate Order ID Trait
            $this->postPaymentPenaltyRebate($dueList, $req);
            return responseMsgs(true, "Order id Generated", remove_null($orderDetails), "011603", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "011603", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Post Payment Penalty Rebates(3.1)
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
    public function paymentHolding(ReqPayment $req)
    {
        try {
            $offlinePaymentModes = Config::get('payment-constants.PAYMENT_MODE_OFFLINE');
            $todayDate = Carbon::now();
            $userId = $req['userId'];
            $propDemand = new PropDemand();
            $demands = $propDemand->getDemandByPropId($req['id']);
            if ($demands->isEmpty())
                throw new Exception("No Dues For this Property");
            $mPropTrans = new PropTransaction();
            // Property Transactions
            $req->merge([
                'userId' => $userId,
                'todayDate' => $todayDate->format('Y-m-d')
            ]);
            DB::beginTransaction();
            $propTrans = $mPropTrans->postPropTransactions($req, $demands);

            if (in_array($req['paymentMode'], $offlinePaymentModes)) {
                $req->merge([
                    'chequeDate' => $req['chequeDate'],
                    'tranId' => $propTrans['id']
                ]);
                $this->postOtherPaymentModes($req);
            }

            // Reflect on Prop Tran Details
            foreach ($demands as $demand) {
                $demand->balance = 0;
                $demand->paid_status = 1;           // <-------- Update Demand Paid Status 
                $demand->save();

                $propTranDtl = new PropTranDtl();
                $propTranDtl->tran_id = $propTrans['id'];
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
                $replicate->tran_id = $propTrans['id'];
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
     * | Post Other Payment Modes for Cheque,DD,Neft
     */
    public function postOtherPaymentModes($req)
    {
        $cash = Config::get('payment-constants.PAYMENT_MODE.3');
        if ($req['paymentMode'] != $cash) {
            $mPropChequeDtl = new PropChequeDtl();
            $chequeReqs = [
                'prop_id' => $req['id'],
                'transaction_id' => $req['tranId'],
                'cheque_date' => $req['chequeDate'],
                'bank_name' => $req['bankName'],
                'branch_name' => $req['branchName'],
                'cheque_no' => $req['chequeNo']
            ];

            $mPropChequeDtl->postChequeDtl($chequeReqs);
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
            $mTransaction = new PropTransaction();
            $mPropPenalties = new PropPenaltyrebate();
            $safController = new ActiveSafController($this->_safRepo);

            $mTowards = Config::get('PropertyConstaint.SAF_TOWARDS');
            $mAccDescription = Config::get('PropertyConstaint.ACCOUNT_DESCRIPTION');
            $mDepartmentSection = Config::get('PropertyConstaint.DEPARTMENT_SECTION');

            $propTrans = $mTransaction->getPropByTranPropId($req->tranNo);

            $reqPropId = new Request(['propertyId' => $propTrans->property_id]);
            $propProperty = $safController->getPropByHoldingNo($reqPropId)->original['data'];
            if (empty($propProperty))
                throw new Exception("Property Not Found");

            // Get Property Penalty and Rebates
            $penalRebates = $mPropPenalties->getPropPenalRebateByTranId($propTrans->id);

            $onePercPenalty = collect($penalRebates)->where('head_name', '1% Monthly Penalty')->first()->amount ?? "";
            $rebate = collect($penalRebates)->where('head_name', 'Rebate')->first()->amount ?? "";
            $specialRebate = collect($penalRebates)->where('head_name', 'Special Rebate')->first()->amount ?? 0;
            $firstQtrRebate = collect($penalRebates)->where('head_name', 'First Qtr Rebate')->first()->amount ?? 0;
            $jskOrOnlineRebate = collect($penalRebates)->where('head_name', 'Rebate From Jsk/Online Payment')->first()->amount ?? 0;
            $lateAssessmentPenalty = 0;

            $taxDetails = $safController->readPenalyPmtAmts($lateAssessmentPenalty, $onePercPenalty, $rebate, $specialRebate, $firstQtrRebate, $propTrans->amount, $jskOrOnlineRebate);
            $responseData = [
                "departmentSection" => $mDepartmentSection,
                "accountDescription" => $mAccDescription,
                "transactionDate" => $propTrans->tran_date,
                "transactionNo" => $propTrans->tran_no,
                "transactionTime" => $propTrans->created_at->format('H:i:s'),
                "applicationNo" => !empty($propProperty['new_holding_no']) ? $propProperty['new_holding_no'] : $propProperty['holding_no'],
                "customerName" => $propProperty['applicant_name'],
                "receiptWard" => $propProperty['new_ward_no'],
                "address" => $propProperty['prop_address'],
                "paidFrom" => $propTrans->from_fyear,
                "paidFromQtr" => $propTrans->from_qtr,
                "paidUpto" => $propTrans->to_fyear,
                "paidUptoQtr" => $propTrans->to_qtr,
                "paymentMode" => $propTrans->payment_mode,
                "bankName" => "",
                "branchName" => "",
                "chequeNo" => "",
                "chequeDate" => "",
                "noOfFlats" => "",
                "monthlyRate" => "",
                "demandAmount" => $propTrans->demand_amt,
                "taxDetails" => $taxDetails,
                "ulbId" => $propProperty['ulb_id'],
                "oldWardNo" => $propProperty['old_ward_no'],
                "newWardNo" => $propProperty['new_ward_no'],
                "towards" => $mTowards,
                "description" => [
                    "keyString" => "Holding Tax"
                ],
                "totalPaidAmount" => $propTrans->amount,
                "paidAmtInWords" => getIndianCurrency($propTrans->amount),
            ];

            return responseMsgs(true, "Payment Receipt", remove_null($responseData), "011605", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "011605", "1.0", "", "POST", $req->deviceId);
        }
    }

    /**
     * | Property Payment History
     */
    public function propPaymentHistory(Request $req)
    {
        $req->validate([
            'propId' => 'required|digits_between:1,9223372036854775807'
        ]);

        try {
            $mPropTrans = new PropTransaction();
            $mPropProperty = new PropProperty();

            $transactions = array();

            $propertyDtls = $mPropProperty->getSafByPropId($req->propId);
            if (!$propertyDtls)
                throw new Exception("Property Not Found");

            $propTrans = $mPropTrans->getPropTransactions($req->propId, 'property_id');         // Holding Payment History
            if (!$propTrans || $propTrans->isEmpty())
                throw new Exception("No Transaction Found");

            $propSafId = $propertyDtls->saf_id;
            if (!$propSafId)
                throw new Exception("This Property has not Saf Id");

            $safTrans = $mPropTrans->getPropTransactions($propSafId, 'saf_id');                 // Saf payment History

            if (!$safTrans)
                throw new Exception("Saf Tran Details not Found");

            $transactions['Holding'] = collect($propTrans)->sortByDesc('id')->values();
            $transactions['Saf'] = collect($safTrans)->sortByDesc('id')->values();

            return responseMsgs(true, "", remove_null($transactions), "011606", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "011606", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }
}
