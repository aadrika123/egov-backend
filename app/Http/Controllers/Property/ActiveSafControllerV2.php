<?php

namespace App\Http\Controllers\Property;

use App\EloquentClass\Property\PenaltyRebateCalculation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Property\reqApplySaf;
use App\Http\Requests\Property\ReqPayment;
use App\MicroServices\IdGeneration;
use App\Models\Cluster\Cluster;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropActiveSafsFloor;
use App\Models\Property\PropActiveSafsOwner;
use App\Models\Property\PropApartmentDtl;
use App\Models\Property\PropDemand;
use App\Models\Property\PropProperty;
use App\Models\Property\PropSafMemoDtl;
use App\Models\Property\PropSafsDemand;
use App\Models\Property\PropTranDtl;
use App\Models\Property\PropTransaction;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWardUser;
use App\Models\Workflows\WfWorkflowrolemap;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Repository\Property\Interfaces\iSafRepository;
use Carbon\Carbon;

use function PHPUnit\Framework\isEmpty;

/**
 * | Created On-10-02-2023 
 * | Created By-Mrinal Kumar
 * */

class ActiveSafControllerV2 extends Controller
{

    /**
     * | Edit Applied Saf by SAF Id for BackOffice
     * | @param request $req
     * | Serial 01
     */
    public function editCitizenSaf(reqApplySaf $req)
    {
        $req->validate([
            'id' => 'required|numeric'
        ]);
        try {
            $id = $req->id;
            $mPropActiveSaf = PropActiveSaf::find($id);
            $citizenId = authUser()->id;
            $mPropSafOwners = new PropActiveSafsOwner();
            $mPropSafFloors = new PropActiveSafsFloor();
            $mActiveSaf = new PropActiveSaf();
            $reqOwners = $req->owner;
            $reqFloors = $req->floor;

            $refSafFloors = $mPropSafFloors->getSafFloorsBySafId($id);
            $refSafOwners = $mPropSafOwners->getOwnersBySafId($id);

            if ($mPropActiveSaf->payment_status == 1)
                throw new Exception("You cannot edit the application");

            if ($mPropActiveSaf->payment_status == 0) {
                // Floors
                $newFloors = collect($reqFloors)->whereNull('safFloorId')->values();
                $existingFloors = collect($reqFloors)->whereNotNull('safFloorId')->values();
                $existingFloorIds = $existingFloors->pluck('safFloorId');
                $toDeleteFloors = $refSafFloors->whereNotIn('id', $existingFloorIds)->values();
                $toDeleteFloorIds = $toDeleteFloors->pluck('id');
                // Owners
                $newOwners = collect($reqOwners)->whereNull('safOwnerId')->values();
                $existingOwners = collect($reqOwners)->whereNotNull('safOwnerId')->values();
                $existingOwnerIds = $existingOwners->pluck('safOwnerId');
                $toDeleteOwners = $refSafOwners->whereNotIn('id', $existingOwnerIds)->values();
                $toDeleteOwnerIds = $toDeleteOwners->pluck('id');
                DB::beginTransaction();
                // Edit Active Saf
                $mActiveSaf->safEdit($req, $mPropActiveSaf, $citizenId);
                // Delete No Existing floors
                PropActiveSafsFloor::destroy($toDeleteFloorIds);
                // Update Existing floors
                foreach ($existingFloors as $existingFloor) {
                    $mPropSafFloors->editFloor($existingFloor, $citizenId);
                }
                // Add New Floors
                foreach ($newFloors as $newFloor) {
                    $mPropSafFloors->addfloor($newFloor, $id, $citizenId);
                }

                // Delete No Existing Owners
                PropActiveSafsOwner::destroy($toDeleteOwnerIds);
                // Update Existing Owners
                foreach ($existingOwners as $existingOwner) {
                    $mPropSafOwners->edit($existingOwner);
                }

                // Add New Owners
                foreach ($newOwners as $newOwner) {
                    $mPropSafOwners->addOwner($newOwner, $id, $citizenId);
                }
            }
            DB::commit();
            return responseMsgs(true, "Successfully Updated the Data", "", 010124, 1.0, "308ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", 010124, 1.0, "308ms", "POST", $req->deviceId);
        }
    }

    /**
     * | Delete Citizen Saf
     * | Serial 02
     */
    public function deleteCitizenSaf(Request $req)
    {
        try {
            $id = $req->id;
            $mPropActiveSaf = PropActiveSaf::find($id);
            $mPropSafOwner = PropActiveSafsOwner::where('saf_id', $id)->get();
            $mPropSafFloor =  PropActiveSafsFloor::where('saf_id', $id)->get();

            if ($mPropActiveSaf->payment_status == 1)
                throw new Exception("Payment Done Saf Cannot be deleted");

            if ($mPropActiveSaf->payment_status == 0) {
                $mPropActiveSaf->status = 0;
                $mPropActiveSaf->update();

                foreach ($mPropSafOwner as $mPropSafOwners) {
                    $mPropSafOwners->status = 0;
                    $mPropSafOwners->save();
                }
                foreach ($mPropSafFloor as $mPropSafFloors) {
                    $mPropSafFloors->status = 0;
                    $mPropSafFloors->save();
                }
            }
            return responseMsgs(true, "Saf Deleted", "", 010124, 1.0, "308ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", 010124, 1.0, "308ms", "POST", $req->deviceId);
        }
    }

    /**
     * | Generate memo receipt
     * | Serial 03
     */
    public function memoReceipt(Request $req)
    {
        $req->validate([
            'memoId' => 'required|numeric'
        ]);
        try {
            $mPropSafMemoDtl = new PropSafMemoDtl();
            $mPropDemands = new PropDemand();
            $mPropSafDemands = new PropSafsDemand();

            $details = $mPropSafMemoDtl->getMemoDtlsByMemoId($req->memoId);
            if (collect($details)->isEmpty())
                $details = $mPropSafMemoDtl->getPropMemoDtlsByMemoId($req->memoId);

            if (collect($details)->isEmpty())
                throw new Exception("Memo Details Not Available");
            $details = collect($details)->first();

            // Fam Receipt
            if ($details->memo_type == 'FAM') {
                $memoFyear = $details->from_fyear;
                $propId = $details->prop_id;
                $safId = $details->saf_id;
                $propDemands = $mPropDemands->getFullDemandsByPropId($propId);
                $safDemands = $mPropSafDemands->getFullDemandsBySafId($safId);

                $holdingTax2Perc = $propDemands->where('fyear', $memoFyear);
                if (collect($holdingTax2Perc)->isEmpty())
                    $holdingTax2Perc = $safDemands->where('fyear', $memoFyear);

                if (collect($holdingTax2Perc)->isEmpty())
                    throw new Exception("Demand Not Available");

                $groupedPropTaxDiff = $propDemands->where('due_date', '>=', $holdingTax2Perc->first()->due_date)->values();
                $groupedSafTaxDiff = $safDemands->where('due_date', '>=', $holdingTax2Perc->first()->due_date)->values();
                $merged = $groupedSafTaxDiff->merge($groupedPropTaxDiff);
                $taxDiffs = $merged->groupBy('arv');
                $qtrParam = 5;                                                                                                      // For Calculating Qtr
                $holdingTaxes = collect($taxDiffs)->map(function ($taxDiff) use ($qtrParam) {
                    $totalFirstQtrs = $qtrParam - $taxDiff->first()->qtr;
                    $selfAssessAmt = ($taxDiff->first()->amount - $taxDiff->first()->additional_tax) * $totalFirstQtrs;               // Holding Tax Amount without penalty
                    $ulbAssessAmt = ($taxDiff->first()->amount - $taxDiff->first()->additional_tax) * $totalFirstQtrs;                // Holding Tax Amount Without Panalty
                    $diffAmt = $ulbAssessAmt - $selfAssessAmt;
                    return [
                        'Particulars' => $taxDiff->first()->fyear == '2016-2017' ? "Holding Tax @ 2%" : "Holding Tax @ 0.075% or 0.15% or 0.2%",
                        'quarterFinancialYear' => 'Quarter' . $taxDiff->first()->qtr . '/' . $taxDiff->first()->fyear,
                        'basedOnSelfAssess' => roundFigure($selfAssessAmt),
                        'basedOnUlbCalc' => roundFigure($ulbAssessAmt),
                        'diffAmt' => roundFigure($diffAmt)
                    ];
                });
                $holdingTaxes = $holdingTaxes->values();

                $total = collect([
                    'Particulars' => 'Total Amount',
                    'quarterFinancialYear' => "",
                    'basedOnSelfAssess' => roundFigure($holdingTaxes->sum('basedOnSelfAssess')),
                    'basedOnUlbCalc' => roundFigure($holdingTaxes->sum('basedOnUlbCalc')),
                    'diffAmt' => roundFigure($holdingTaxes->sum('diffAmt')),
                ]);
                $details->taxTable = $holdingTaxes->merge([$total])->values();
            }
            return responseMsgs(true, "", remove_null($details), "011803", 1.0, "", "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "011803", 1.0, "", "POST", $req->deviceId);
        }
    }

    /**
     * | Search Holding of user not logged in
     * | Serial 04
     */
    public function searchHolding(Request $req)
    {
        $req->validate([
            "holdingNo" => "required",
            "ulbId" => "required"
        ]);
        try {
            $holdingNo = $req->holdingNo;
            $ulbId = $req->ulbId;

            $data = PropProperty::select(
                'prop_properties.id',
                'ulb_name as ulb',
                'prop_properties.holding_no',
                'prop_properties.new_holding_no',
                'ward_name',
                'prop_address',
                'prop_properties.status',
                DB::raw("string_agg(prop_owners.mobile_no::VARCHAR,',') as mobile_no"),
                DB::raw("string_agg(prop_owners.owner_name,',') as owner_name"),
            )
                ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'prop_properties.ward_mstr_id')
                ->join('ulb_masters', 'ulb_masters.id', 'prop_properties.ulb_id')
                ->join('prop_owners', 'prop_owners.property_id', 'prop_properties.id')
                ->where('prop_properties.holding_no', $holdingNo)
                ->where('prop_properties.ulb_id', $ulbId)
                ->where('prop_properties.status', 1)
                ->groupby('prop_properties.id', 'ulb_ward_masters.ward_name', 'ulb_name')
                ->get();

            if ($data->isNotEmpty()) {
                return responseMsgs(true, "Holding Details", $data, 010124, 1.0, "308ms", "POST", $req->deviceId);
            }

            $data = PropProperty::select(
                'prop_properties.id',
                'ulb_name as ulb',
                'prop_properties.holding_no',
                'prop_properties.new_holding_no',
                'ward_name',
                'prop_address',
                'prop_properties.status',
                DB::raw("string_agg(prop_owners.mobile_no::VARCHAR,',') as mobile_no"),
                DB::raw("string_agg(prop_owners.owner_name,',') as owner_name"),
            )
                ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'prop_properties.ward_mstr_id')
                ->join('ulb_masters', 'ulb_masters.id', 'prop_properties.ulb_id')
                ->join('prop_owners', 'prop_owners.property_id', 'prop_properties.id')
                ->where('prop_properties.new_holding_no', $holdingNo)
                ->where('prop_properties.ulb_id', $ulbId)
                ->where('prop_properties.status', 1)
                ->groupby('prop_properties.id', 'ulb_ward_masters.ward_name', 'ulb_name')
                ->get();

            if ($data->isEmpty()) {
                throw new Exception("Enter Valid Holding No.");
            }

            return responseMsgs(true, "Holding Details", $data, 010124, 1.0, "308ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", 010124, 1.0, "308ms", "POST", $req->deviceId);
        }
    }

    /**
     * | Serial 05
     */
    public function verifyHoldingNo(Request $req)
    {
        try {
            $req->validate([
                'holdingNo' => 'required',
                'ulbId' => 'required',
            ]);
            $mPropProperty = new PropProperty();
            $data = $mPropProperty->verifyHolding($req);

            if (!isset($data)) {
                throw new Exception("Enter Valid Holding No.");
            }
            $datas['id'] = $data->id;

            return responseMsgs(true, "Holding Exist", $datas, 010124, 1.0, "308ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", 010124, 1.0, "308ms", "POST", $req->deviceId);
        }
    }

    /**
     * | Get Apartment List by Ward Id
     */
    public function getAptList(Request $req)
    {
        try {
            $req->validate([
                'wardMstrId' => 'required',
                'ulbId' => 'nullable',
            ]);
            $mPropApartmentDtl = new PropApartmentDtl();
            $ulbId = $req->ulbId ?? authUser()->ulb_id;
            $req->request->add(['ulbId' => $ulbId,]);

            $data = $mPropApartmentDtl->apartmentList($req);

            if (($data->isEmpty())) {
                throw new Exception("Enter Valid wardMstrId");
            }

            return responseMsgs(true, "Apartment List", $data, 010124, 1.0, "308ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", 010124, 1.0, "308ms", "POST", $req->deviceId);
        }
    }

    /**
     * | Get Pending GeoTaggings
     */
    public function pendingGeoTaggingList(Request $req, iSafRepository $iSafRepo)
    {
        try {
            $agencyTcRole = Config::get('PropertyConstaint.SAF-LABEL.TC');
            $mWfWardUser = new WfWardUser();
            $mWorkflowRoleMap = new WfWorkflowrolemap();
            $userId = auth()->user()->id;
            $ulbId = auth()->user()->ulb_id;

            $workflowIds = $mWorkflowRoleMap->getWfByRoleId([$agencyTcRole])->pluck('workflow_id');
            $readWards = $mWfWardUser->getWardsByUserId($userId);                       // Model () to get Occupied Wards of Current User
            $occupiedWards = collect($readWards)->pluck('ward_id');
            $safInbox = $iSafRepo->getSaf($workflowIds)                                 // Repository function to get SAF Details
                ->where('parked', false)
                ->where('is_geo_tagged', false)
                ->where('prop_active_safs.ulb_id', $ulbId)
                ->where('prop_active_safs.status', 1)
                ->where('current_role', $agencyTcRole)
                ->whereIn('ward_mstr_id', $occupiedWards)
                ->orderByDesc('id')
                ->groupBy('prop_active_safs.id', 'p.property_type', 'ward.ward_name')
                ->get();

            return responseMsgs(true, "Data Fetched", remove_null($safInbox->values()), "011806", "1.0", "", "POST", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "011806", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }


    /**
     * | Cluster Demand for Saf
     */
    public function getClusterSafDues(Request $req, iSafRepository $iSafRepository)
    {
        $req->validate([
            'clusterId' => 'required|integer'
        ]);

        try {
            $todayDate = Carbon::now();
            $clusterId = $req->clusterId;
            $mPropActiveSaf = new PropActiveSaf();
            $penaltyRebateCal = new PenaltyRebateCalculation;
            $activeSafController = new ActiveSafController($iSafRepository);
            $mClusters = new Cluster();
            $clusterDtls = $mClusters::findOrFail($clusterId);

            $clusterDemands = array();
            $finalClusterDemand = array();
            $clusterDemandList = array();
            $currentQuarter = calculateQtr($todayDate->format('Y-m-d'));
            $loggedInUserType = authUser()->user_type;
            $currentFYear = getFY();

            $clusterSafs = $mPropActiveSaf->getSafsByClusterId($clusterId);
            if ($clusterSafs->isEmpty())
                throw new Exception("Safs Not Available");

            foreach ($clusterSafs as $item) {
                $propIdReq = new Request([
                    'id' => $item['id']
                ]);
                $demandList = $activeSafController->calculateSafBySafId($propIdReq)->original['data'];
                $safDues['demand'] = $demandList['demand'] ?? [];
                $safDues['details'] = $demandList['details'] ?? [];
                array_push($clusterDemandList, $safDues['details']);
                array_push($clusterDemands, $safDues);
            }

            $collapsedDemand = collect($clusterDemandList)->collapse();                       // Clusters Demands Collapsed into One

            if ($collapsedDemand->isEmpty())
                throw new Exception("Demand Not Available for this Cluster");

            $totalLateAssessmentPenalty = collect($clusterDemands)->map(function ($item) {      // Total Collective Late Assessment Penalty
                return $item['demand']['lateAssessmentPenalty'] ?? 0;
            })->sum();

            $groupedByYear = $collapsedDemand->groupBy('due_date');                           // Grouped By Financial Year and Quarter for the Separation of Demand  
            $summedDemand = $groupedByYear->map(function ($item) use ($penaltyRebateCal) {    // Sum of all the Demands of Quarter and Financial Year
                $quarterDueDate = $item->first()['due_date'];
                $onePercPenaltyPerc = $penaltyRebateCal->calcOnePercPenalty($quarterDueDate);
                $balance = roundFigure($item->sum('balance'));

                $onePercPenaltyTax = ($balance * $onePercPenaltyPerc) / 100;
                $onePercPenaltyTax = roundFigure($onePercPenaltyTax);

                return [
                    'quarterYear' => $item->first()['qtr']  . "/" . $item->first()['fyear'],
                    'arv' => roundFigure($item->sum('arv')),
                    'qtr' => $item->first()['qtr'],
                    'holding_tax' => roundFigure($item->sum('holding_tax')),
                    'water_tax' => roundFigure($item->sum('water_tax')),
                    'education_cess' => roundFigure($item->sum('education_cess')),
                    'health_cess' => roundFigure($item->sum('health_cess')),
                    'latrine_tax' => roundFigure($item->sum('latrine_tax')),
                    'additional_tax' => roundFigure($item->sum('additional_tax')),
                    'amount' => roundFigure($item->sum('amount')),
                    'balance' => $balance,
                    'fyear' => $item->first()['fyear'],
                    'adjust_amount' => roundFigure($item->sum('adjust_amt')),
                    'due_date' => $quarterDueDate,
                    'onePercPenalty' => $onePercPenaltyPerc,
                    'onePercPenaltyTax' => $onePercPenaltyTax,
                ];
            })->values();
            $finalDues = collect($summedDemand)->sum('balance');
            $finalDues = roundFigure($finalDues);

            $finalOnePerc = collect($summedDemand)->sum('onePercPenaltyTax');
            $finalOnePerc = roundFigure($finalOnePerc);
            $finalAmt = $finalDues + $finalOnePerc + $totalLateAssessmentPenalty;
            $finalAmt = roundFigure($finalAmt);
            $duesFrom = collect($clusterDemands)->first()['demand']['duesFrom'] ?? collect($clusterDemands)->last()['demand']['duesFrom'] ?? [];
            $duesTo = collect($clusterDemands)->first()['demand']['duesTo'] ?? collect($clusterDemands)->last()['demand']['duesTo'] ?? [];

            $finalClusterDemand['demand'] = [
                'duesFrom' => $duesFrom,
                'duesTo' => $duesTo,
                'totalTax' => $finalDues,
                'totalDues' => $finalDues,
                'totalOnePercPenalty' => $finalOnePerc,
                'lateAssessmentPenalty' => $totalLateAssessmentPenalty,
                'finalAmt' => $finalAmt,
                'totalDemand' => $finalAmt,
            ];
            $mLastQuarterDemand = collect($summedDemand)->where('fyear', $currentFYear)->sum('balance');
            $finalClusterDemand['demand'] = $penaltyRebateCal->readRebates($currentQuarter, $loggedInUserType, $mLastQuarterDemand, null, $finalAmt, $finalClusterDemand['demand']);
            $payableAmount = $finalAmt - ($finalClusterDemand['demand']['rebateAmt'] + $finalClusterDemand['demand']['specialRebateAmt']);
            $finalClusterDemand['demand']['payableAmount'] = round($payableAmount);

            $finalClusterDemand['details'] = $summedDemand;
            $finalClusterDemand['basicDetails'] = $clusterDtls;
            return responseMsgs(true, "Cluster Demands", remove_null($finalClusterDemand), "011807", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), ['basicDetails' => $clusterDtls], "011807", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Cluster Payment
     */
    public function clusterSafPayment(ReqPayment $req, iSafRepository $iSafRepository)
    {
        try {
            $dueReq = new Request([
                'clusterId' => $req->id
            ]);
            $clusterId = $req->id;
            $todayDate = Carbon::now();
            $idGeneration = new IdGeneration;
            $mPropTrans = new PropTransaction();
            $mPropSafsDemand = new PropSafsDemand();
            $activeSafController = new ActiveSafController($iSafRepository);
            $offlinePaymentModes = Config::get('payment-constants.PAYMENT_MODE_OFFLINE');

            $dues1 = $this->getClusterSafDues($dueReq, $iSafRepository);

            if ($dues1->original['status'] == false)
                throw new Exception($dues1->original['message']);

            $dues = $dues1->original['data'];

            $demands = $dues['details'];
            $tranNo = $idGeneration->generateTransactionNo();
            $payableAmount = $dues['demand']['payableAmount'];
            // Property Transactions
            if (in_array($req['paymentMode'], $offlinePaymentModes)) {
                $userId = auth()->user()->id ?? null;
                if (!$userId)
                    throw new Exception("User Should Be Logged In");
                $tranBy = authUser()->user_type;
            }
            $req->merge([
                'userId' => $userId,
                'todayDate' => $todayDate->format('Y-m-d'),
                'tranNo' => $tranNo,
                'amount' => $payableAmount,
                'tranBy' => $tranBy,
                'clusterType' => "Saf"
            ]);

            DB::beginTransaction();
            $propTrans = $mPropTrans->postClusterTransactions($req, $demands, 'Saf');
            if (in_array($req['paymentMode'], $offlinePaymentModes)) {
                $req->merge([
                    'chequeDate' => $req['chequeDate'],
                    'tranId' => $propTrans['id'],
                    'id' => null
                ]);
                $activeSafController->postOtherPaymentModes($req, $clusterId);
            }
            // Reflect on Prop Tran Details
            foreach ($demands as $demand) {
                $demand = $demand->toArray();
                unset($demand['ruleSet'], $demand['rwhPenalty'], $demand['onePercPenalty'], $demand['onePercPenaltyTax'], $demand['quarterYear']);
                if (isset($demand['status']))
                    unset($demand['status']);
                $demand['paid_status'] = 1;
                $demand['cluster_id'] = $clusterId;
                $demand['balance'] = 0;
                $storedSafDemand = $mPropSafsDemand->postDemands($demand);

                $mPropTranDtl = new PropTranDtl();
                $tranReq = [
                    'tran_id' => $propTrans['id'],
                    'saf_cluster_demand_id' => $storedSafDemand['demandId'],
                    'total_demand' => $demand['amount'],
                    'ulb_id' => $req['ulbId'],
                ];
                $mPropTranDtl->store($tranReq);
            }
            // Replication Prop Rebates Penalties
            $activeSafController->postPenaltyRebates($dues1, null, $propTrans['id'], $clusterId);
            DB::commit();
            return responseMsgs(true, "Payment Successfully Done", "", "011612", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "011612", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }
}
