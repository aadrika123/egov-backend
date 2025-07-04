<?php

namespace App\Repository\Citizen;

use App\EloquentClass\Property\PenaltyRebateCalculation;
use App\Models\UlbMaster;
use Illuminate\Http\Request;
use App\Repository\Citizen\iCitizenRepository;
use App\Models\ActiveCitizen;
use App\Models\Citizen\ActiveCitizenUndercare;
use App\Models\Payment\PaymentRequest;
use App\Models\Property\PropAdvance;
use App\Models\Property\PropDemand;
use App\Models\Property\PropLevelPending;
use App\Models\Property\PropOwner;
use App\Models\Property\PropProperty;
use App\Models\Trade\ActiveLicence;
use App\Models\Trade\ActiveTradeLicence;
use App\Models\Trade\TradeLicence;
use App\Models\User;
use App\Models\Water\WaterApplication;
use App\Models\Workflows\WfRoleusermap;
use App\Models\WorkflowTrack;
use App\Traits\Auth;
use App\Traits\Workflow\Workflow;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

/**
 * | Created On-08-08-2022 
 * | Created By-Anshu Kumar.
 * -------------------------------------------------------------------------------------------
 * | Eloquent For Citizen Registration and Approval
 */

class CitizenRepository implements iCitizenRepository
{
    private $_appliedApplications;
    private $_redis;

    use Auth, Workflow;

    public function __construct()
    {
        $this->_redis = Redis::connection();
    }



    /**
     * | Get Citizens by ID
     * | @param Citizen-id $id
     * | join with ulb_masters
     */
    public function getCitizenByID($id)
    {
        $citizen = ActiveCitizen::select('active_citizens.id', 'user_name', 'mobile', 'email', 'user_type', 'ulb_id', 'is_approved', 'ulb_name')
            ->where('active_citizens.id', $id)
            ->leftJoin('ulb_masters', 'active_citizens.ulb_id', '=', 'ulb_masters.id')
            ->first();
        return $citizen;
    }

    /**
     * | Get All Citizens
     * | Join With ulb_masters
     */
    public function getAllCitizens()
    {
        $citizen = ActiveCitizen::select('active_citizens.id', 'user_name', 'mobile', 'email', 'user_type', 'ulb_id', 'is_approved', 'ulb_name')
            ->where('is_approved', null)
            ->leftJoin('ulb_masters', 'active_citizens.ulb_id', '=', 'ulb_masters.id')
            ->get();
        return $citizen;
    }


    /**
     * | Get All Applied Applications of All Modules
     */
    public function getAllAppliedApplications($req)
    {
        try {
            $userId = authUser($req)->id;
            // $userId = 36;
            $applications = array();

            if ($req->getMethod() == 'GET') {                                                       // For All Applications
                $property = $this->appliedSafApplications($userId);
                $applications['Property'] = $property;
                $applications['Property']['totalApplications'] = $this->countTotalApplications($property);

                $waters = $this->appliedWaterApplications($userId);
                $applications['Water'] = $waters;

                $applications['Trade'] = $this->appliedTradeApplications($userId);
                $applications['CareTaker'] = $this->getCaretakerProperty($userId);
                $applications['Holding'] = $this->getProperties($userId, $applications['CareTaker']);

                // $applications['totalPetRegistrations'] = $this->getPetRegistrations($userId);
            }

            if ($req->getMethod() == 'POST') {                                                      // Get Applications By Module
                if ($req->module == 'Property') {
                    $applications['Property'] = $this->appliedSafApplications($userId);
                }

                if ($req->module == 'Water') {
                    $applications['Water'] = $this->appliedWaterApplications($userId);
                }

                if ($req->module == 'Trade') {
                    $applications['Trade'] = $this->appliedTradeApplications($userId);
                }

                if ($req->module == 'Holding') {
                    $applications['Holding'] = $this->getCitizenProperty($userId);
                }

                if ($req->module == 'careTaker') {
                    $applications['CareTaker'] = $this->getCaretakerProperty($userId);
                }

                if ($req->module == 'careTakeTrade')
                    $applications['CareTakerTrade'] = $this->getCareTakerTrade($userId);
            }

            return responseMsg(true, "All Applied Applications", remove_null($applications));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    /**
     * | Count Total
     */
    public function countTotalApplications($applications)
    {
        $counters = collect();
        foreach ($applications as $application) {
            $numberOfApplications = collect($application)->count();
            $counters->push($numberOfApplications);
        }
        $totalCounter = $counters->sum();
        return $totalCounter;
    }

    /**
     * | Applied Saf Applications
     * | Redis Should Be delete on SAF Apply, Trade License Apply and Water Apply
     * | Status - Closed
     */
    public function appliedSafApplications($userId)
    {
        $applications = array();
        $propertyApplications = DB::table('prop_active_safs')
            ->Join('wf_roles as r', 'r.id', '=', 'prop_active_safs.current_role')
            ->Join('ulb_masters', 'ulb_masters.id', '=', 'prop_active_safs.ulb_id')
            ->leftJoin('prop_transactions as t', 't.saf_id', '=', 'prop_active_safs.id')
            ->select(
                'prop_active_safs.id as application_id',
                'saf_no',
                'pt_no',
                'holding_no',
                'assessment_type',
                DB::raw(
                    "CASE 
                            WHEN prop_active_safs.assessment_type ='New Assessment' THEN '1'
                            WHEN prop_active_safs.assessment_type ='Reassessment' THEN '2'  
                            WHEN prop_active_safs.assessment_type ='Mutation' THEN '3'  
                            WHEN prop_active_safs.assessment_type ='Bifurcation' THEN '4'  
                            WHEN prop_active_safs.assessment_type ='Amalgamation' THEN '5'  
                        END as assessment_type_key"
                ),
                'r.role_name as current_level',
                DB::raw("TO_CHAR(application_date, 'DD-MM-YYYY') as application_date"),
                'applicant_name',
                'payment_status',
                'doc_upload_status',
                'saf_pending_status',
                'parked as backToCitizen',
                'workflow_id',
                'prop_active_safs.created_at',
                'prop_active_safs.updated_at',
                't.tran_no as transaction_no',
                't.tran_date as transaction_date',
                'prop_active_safs.is_agency_verified',
                'prop_active_safs.is_field_verified as is_ulb_verified',
                'ulb_masters.ulb_name'
            )
            ->where('prop_active_safs.citizen_id', $userId)
            ->where('prop_active_safs.status', 1)
            ->orderByDesc('prop_active_safs.id')
            ->get();
        $applications['SAF'] = collect($propertyApplications)->values();

        $concessionApplications = DB::table('prop_active_concessions')
            ->join('wf_roles as r', 'r.id', '=', 'prop_active_concessions.current_role')
            ->join('prop_properties as p', 'p.id', '=', 'prop_active_concessions.property_id')
            ->select(
                'prop_active_concessions.id as application_id',
                'prop_active_concessions.application_no',
                'prop_active_concessions.applicant_name',
                DB::raw("TO_CHAR(prop_active_concessions.date, 'DD-MM-YYYY') as apply_date"),
                'p.holding_no',
                'p.new_holding_no',
                'p.pt_no',
                'r.role_name as pending_at',
                'prop_active_concessions.workflow_id'
            )
            ->where('prop_active_concessions.citizen_id', $userId)
            ->get();
        $applications['concessions'] = $concessionApplications;

        $objectionApplications = DB::table('prop_active_objections')
            ->join('wf_roles as r', 'r.id', '=', 'prop_active_objections.current_role')
            ->join('prop_properties as p', 'p.id', '=', 'prop_active_objections.property_id')
            ->join('prop_owners', 'prop_owners.property_id', '=', 'p.id')
            ->select(
                'prop_active_objections.id as application_id',
                'prop_active_objections.objection_no',
                DB::raw("TO_CHAR(prop_active_objections.date, 'DD-MM-YYYY') as apply_date"),
                'p.holding_no',
                'p.new_holding_no',
                'p.pt_no',
                DB::raw("string_agg(prop_owners.owner_name,',') as applicant_name"),
                'r.role_name as pending_at',
                'prop_active_objections.workflow_id',
                'prop_active_objections.objection_for'
            )
            ->where('prop_active_objections.citizen_id', $userId)
            ->groupBy(
                'prop_active_objections.id',
                'objection_no',
                'date',
                'p.id',
                'r.role_name',
                'prop_active_objections.workflow_id',
                'prop_active_objections.objection_for'
            )
            ->get();
        $applications['objections'] = $objectionApplications;

        $harvestingApplications = DB::table('prop_active_harvestings')
            ->join('wf_roles as r', 'r.id', '=', 'prop_active_harvestings.current_role')
            ->join('prop_properties as p', 'p.id', '=', 'prop_active_harvestings.property_id')
            ->join('prop_owners', 'prop_owners.property_id', '=', 'p.id')
            ->select(
                'prop_active_harvestings.id as application_id',
                'prop_active_harvestings.application_no',
                DB::raw("TO_CHAR(prop_active_harvestings.date, 'DD-MM-YYYY') as apply_date"),
                DB::raw("string_agg(prop_owners.owner_name,',') as applicant_name"),
                'p.holding_no',
                'p.new_holding_no',
                'p.pt_no',
                'r.role_name as pending_at',
                'prop_active_harvestings.workflow_id'
            )
            ->where('prop_active_harvestings.citizen_id', $userId)
            ->groupBy('prop_active_harvestings.id', 'p.id', 'r.role_name')
            ->get();
        $applications['harvestings'] = $harvestingApplications;

        return collect($applications);
    }

    /**
     * | Applied Water Applications
     * | Status-Closed
     */
    public function appliedWaterApplications($userId)
    {
        $applications = array();
        $waterApplications = WaterApplication::select('water_applications.id as application_id', 'water_applications.category', 'application_no', 'holding_no', 'workflow_id', 'water_applications.created_at', 'water_applications.updated_at', 'ulb_name')
            ->join('ulb_masters', 'ulb_masters.id', '=', 'water_applications.ulb_id')
            ->where('user_id', $userId)
            ->where('status', 1)
            ->orderByDesc('water_applications.id')
            ->get();
        $applications['applications'] = $waterApplications;
        $applications['totalApplications'] = $waterApplications->count();
        return collect($applications)->reverse();
    }

    /**
     * | Applied Trade Applications
     * | Status- Closed
     */
    public function appliedTradeApplications($userId)
    {
        $applications = array();
        $tradeApplications = ActiveTradeLicence::select('active_trade_licences.id as application_id', 'application_no', 'holding_no', 'workflow_id', 'active_trade_licences.created_at', 'active_trade_licences.updated_at', 'ulb_name')
            ->join('ulb_masters', 'ulb_masters.id', '=', 'active_trade_licences.ulb_id')
            ->where('citizen_id', $userId)
            ->where('is_active', 1)
            ->orderByDesc('active_trade_licences.id')
            ->get();
        $applications['applications'] = $tradeApplications;
        $applications['totalApplications'] = $tradeApplications->count();
        return collect($applications)->reverse();
    }

    /**
     * | Get User Property List by UserID
     */
    public function getCitizenProperty($userId)
    {
        try {
            $application = array();
            $query = "SELECT  p.id AS prop_id,
                                p.pt_no,
                                p.holding_no,
                                p.new_holding_no,
                                p.application_date AS apply_date,
                                o.owner_name,
                                p.balance AS leftAmount,
                                t.amount AS lastPaidAmount,
                                t.tran_date AS lastPaidDate,
                                ulb_name

                                FROM prop_properties p
                                join ulb_masters on ulb_masters.id = p.ulb_id
                                LEFT JOIN (
                                    SELECT property_id,amount,tran_date,
                                        ROW_NUMBER() OVER(
                                            PARTITION BY property_id
                                            ORDER BY id desc
                                        ) AS row_num
                                    FROM prop_transactions 
                                    ORDER BY id DESC
                                ) AS t ON t.property_id=p.id AND row_num =1

                                LEFT JOIN (
                                    SELECT property_id,owner_name,
                                    row_number() over(
                                        partition BY property_id
                                        ORDER BY id ASC
                                    ) AS ROW1
                                    FROM prop_owners 
                                    ORDER BY id ASC 
                                    ) AS o ON o.property_id=p.id AND ROW1=1
                                    WHERE p.citizen_id=$userId";
            $properties = DB::select($query);
            $application['applications'] = $properties;
            $application['totalApplications'] = collect($properties)->count();
            return collect($application);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    //
    public function getCaretakerProperty($userId)
    {
        $data = [];
        $mActiveCitizenCareTaker = new ActiveCitizenUndercare();
        $propertiesByCitizen = $mActiveCitizenCareTaker->getTaggedPropsByCitizenId($userId);
        $propIds = $propertiesByCitizen->pluck('property_id');

        if ($propIds->isEmpty())
            return $data;

        foreach ($propIds as $propId) {
            if (!$propId) continue;

            $propdtl = PropProperty::where('prop_properties.id', $propId)
                ->select(
                    'prop_properties.id',
                    'prop_properties.holding_no',
                    'prop_properties.new_holding_no',
                    DB::raw("TO_CHAR(application_date, 'DD-MM-YYYY') as application_date"),
                    'prop_owners.owner_name',
                    'prop_demands.balance',
                    'prop_transactions.amount',
                    "ulb_name",
                    DB::raw("TO_CHAR(prop_transactions.tran_date, 'DD-MM-YYYY') as tran_date")
                )
                ->join('prop_owners', 'prop_owners.property_id', 'prop_properties.id')
                ->join('ulb_masters', 'ulb_masters.id', 'prop_properties.ulb_id')
                ->leftJoin('prop_transactions', 'prop_transactions.property_id', 'prop_properties.id')
                ->leftJoin(DB::raw("(
                SELECT property_id, SUM(balance) as balance
                FROM prop_demands
                WHERE paid_status = 0
                GROUP BY property_id
            ) as prop_demands"), 'prop_demands.property_id', '=', 'prop_properties.id')
                ->orderByDesc('prop_transactions.id')
                ->first();

            if (!$propdtl) continue;

            // Now get demand dues from API/controller method
            $request = new Request();
            $request->merge(["propId" => $propId]);
            $demandDues = $this->getHoldingDues($request);

            $totalDemandAmount = 0;
            if ($demandDues && isset($demandDues->original['data']['duesList']['payableAmount'])) {
                $totalDemandAmount = $demandDues->original['data']['duesList']['payableAmount'];
            }

            $propDtls = [
                'prop_id' => $propdtl->id,
                'holding_no' => $propdtl->holding_no,
                'new_holding_no' => $propdtl->new_holding_no,
                'apply_date' => $propdtl->application_date,
                'owner_name' => $propdtl->owner_name,
                'leftamount' => $totalDemandAmount, // more accurate than just balance
                'lastpaidamount' => $propdtl->amount,
                'lastpaiddate' => $propdtl->tran_date,
                'ulb_name' => $propdtl->ulb_name,
            ];

            $data[] = $propDtls;
        }

        return collect($data);
    }


    /**
     * | Get care taker trade
     */
    public function getCareTakerTrade($userId)
    {
        $data = collect();
        $mTradeLicense = new TradeLicence;
        $mActiveCitizenCareTaker = new ActiveCitizenUndercare();
        $details = $mActiveCitizenCareTaker->getDetailsByCitizenId($userId);
        $licenses = $details->pluck('license_id');

        foreach ($licenses as $license) {
            if (!$license)
                continue;
            $license = $mTradeLicense->getTradeDtlsByLicenseNo($license);
            $data->push($license);
        }

        return $data;
    }


    /**
     * | Total Pet Registrations
     */
    public function getPetRegistrations($userId)
    {
        $applications = array();

        $registrations = DB::table('pet_active_registrations')
            ->where('citizen_id', $userId)
            ->where('status', 1)
            ->get();
        $applications['applications'] = $registrations;
        $applications['totalApplications'] = $registrations->count();
        return $applications;
    }


    /**
     * | Get Total Properties
     */
    public function getProperties($userId, $careTakerProperties)
    {
        $applications = array();
        $properties = PropProperty::select('holding_no', 'applicant_name', 'application_date')
            ->where('citizen_id', $userId)
            ->get();

        $applications['applications'] = $properties;
        $totalCareTakers = collect($careTakerProperties)->count();
        $applications['totalApplications'] = $properties->count() + $totalCareTakers;
        return $applications;
    }

    /**
     * | Independent Comment for the Citizen on their applications
     * | @param req requested parameters
     * | Status-Closed
     */
    public function commentIndependent($req)
    {
        $path = storage_path() . "/json/workflow.json";
        $json = json_decode(file_get_contents($path), true);                                                    // get Data from the storage path workflow
        $collection = collect($json['workflowId']);
        $refTable = collect($collection)->where('id', 4)->first();

        $array = array();
        $array['workflowId'] = $req->workflowId;
        $array['citizenId'] = authUser($req)->id;
        $array['refTableId'] = $refTable['workflow_name'] . '.id';
        $array['applicationId'] = $req->applicationId;
        $array['message'] = $req->message;

        $workflowTrack = new WorkflowTrack();
        $this->workflowTrack($workflowTrack, $array);                                                            // Trait For Workflow Track
        $workflowTrack->save();
        return responseMsg(true, "Successfully Given the Message", "");
    }

    /**
     * | Get Transaction History
     */
    public function getTransactionHistory($req)
    {
        try {
            $userId = authUser($req)->id;
            $trans = PaymentRequest::where('citizen_id', $userId)
                ->get();
            return responseMsg(true, "Data Fetched", remove_null($trans));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Get Holding Dues(2)
     * | Validates the input property ID, then calculates and returns
       | Common Function
     */
    public function getHoldingDues(Request $req)
    {
        $req->validate([
            'propId' => 'required|digits_between:1,9223372036854775807'
        ]);

        try {
            $todayDate = Carbon::now()->format('Y-m-d');
            $mPropAdvance = new PropAdvance();
            $mPropDemand = new PropDemand();
            $mPropProperty = new PropProperty();
            $penaltyRebateCalc = new PenaltyRebateCalculation;
            $mWfRoleUser = new WfRoleusermap();
            $currentQuarter = calculateQtr(Carbon::now()->format('Y-m-d'));
            $currentFYear = getFY();
            $canTakePayment = false; // Initializing $canTakePayment to false by default
            if ($req->auth) {
                $canTakePaymentRole = [4, 5, 8];
                $user = authUser($req);
                $roleIds = $mWfRoleUser->getRoleIdByUserId($user->id)->pluck('wf_role_id')->toArray();

                foreach ($roleIds as $roleId) {
                    if (in_array($roleId, $canTakePaymentRole)) {
                        $canTakePayment = true;
                        break; // No need to continue checking once we find a match
                    }
                }
            }
            $loggedInUserType = $user->user_type ?? "Citizen";
            $mPropOwners = new PropOwner();
            $pendingFYears = collect();
            $qtrs = collect([1, 2, 3, 4]);
            $mUlbMasters = new UlbMaster();

            $ownerDetails = $mPropOwners->getOwnerByPropId($req->propId)->first();
            $demand = array();
            $demandList = $mPropDemand->getDueDemandByPropId($req->propId);
            $demandList = collect($demandList);

            collect($demandList)->map(function ($value) use ($pendingFYears) {
                $fYear = $value->fyear;
                $pendingFYears->push($fYear);
            });
            // Property Part Payment
            if (isset($req->fYear) && isset($req->qtr)) {
                $demandTillQtr = $demandList->where('fyear', $req->fYear)->where('qtr', $req->qtr)->first();
                if (collect($demandTillQtr)->isNotEmpty()) {
                    $demandDueDate = $demandTillQtr->due_date;
                    $demandList = $demandList->filter(function ($item) use ($demandDueDate) {
                        return $item->due_date <= $demandDueDate;
                    });
                    $demandList = $demandList->values();
                }

                if (collect($demandTillQtr)->isEmpty())
                    $demandList = collect();                                    // Demand List blank in case of fyear and qtr         
            }
            $propDtls = $mPropProperty->getPropById($req->propId);
            $balance = $propDtls->balance ?? 0;

            $propBasicDtls = $mPropProperty->getPropBasicDtls($req->propId);
            if (collect($propBasicDtls)->isEmpty()) {
                throw new Exception("Property Details Not Available");
            }
            $holdingType = $propBasicDtls->holding_type;
            $ownershipType = $propBasicDtls->ownership_type;
            $basicDtls = collect($propBasicDtls)->only([
                'holding_no',
                'new_holding_no',
                'old_ward_no',
                'new_ward_no',
                'property_type',
                'zone_mstr_id',
                'is_mobile_tower',
                'is_hoarding_board',
                'is_petrol_pump',
                'is_water_harvesting',
                'ulb_id',
                'prop_address',
                'area_of_plot'
            ]);
            $basicDtls["holding_type"] = $holdingType;
            $basicDtls["ownership_type"] = $ownershipType;

            if ($demandList->isEmpty())
                throw new Exception("No Dues Found Please See Your Payment History For Your Recent Transactions");

            $demandList = $demandList->map(function ($item) {                                // One Perc Penalty Tax
                return $this->calcOnePercPenalty($item);
            });

            $dues = roundFigure($demandList->sum('balance'));
            $dues = ($dues > 0) ? $dues : 0;

            $onePercTax = roundFigure($demandList->sum('onePercPenaltyTax'));
            $onePercTax = ($onePercTax > 0) ? $onePercTax : 0;

            $rwhPenaltyTax = roundFigure($demandList->sum('additional_tax'));
            $advanceAdjustments = $mPropAdvance->getPropAdvanceAdjustAmt($req->propId);
            if (collect($advanceAdjustments)->isEmpty())
                $advanceAmt = 0;
            else
                $advanceAmt = $advanceAdjustments->advance - $advanceAdjustments->adjustment_amt;

            $mLastQuarterDemand = $demandList->where('fyear', $currentFYear)->sum('balance');

            $paymentUptoYrs = $pendingFYears->unique()->values();
            $dueFrom = "Quarter " . $demandList->last()->qtr . "/ Year " . $demandList->last()->fyear;
            $dueTo = "Quarter " . $demandList->first()->qtr . "/ Year " . $demandList->first()->fyear;
            $totalDuesList = [
                'dueFromFyear' => $demandList->last()->fyear,
                'dueFromQtr' => $demandList->last()->qtr,
                'dueToFyear' => $demandList->first()->fyear,
                'dueToQtr' => $demandList->first()->qtr,
                'totalDues' => $dues,
                'duesFrom' => $dueFrom,
                'duesTo' => $dueTo,
                'onePercPenalty' => $onePercTax,
                'totalQuarters' => $demandList->count(),
                'arrear' => $balance,
                'advanceAmt' => $advanceAmt,
                'additionalTax' => $rwhPenaltyTax
            ];
            $currentQtr = calculateQtr($todayDate);

            $pendingQtrs = $qtrs->filter(function ($value) use ($currentQtr) {
                return $value >= $currentQtr;
            });

            $totalDuesList = $penaltyRebateCalc->readRebates($currentQuarter, $loggedInUserType, $mLastQuarterDemand, $ownerDetails, $dues, $totalDuesList);

            $totalRebates = $totalDuesList['rebateAmt'] + $totalDuesList['specialRebateAmt'];
            $finalPayableAmt = ($dues + $onePercTax + $balance) - ($totalRebates + $advanceAmt);
            if ($finalPayableAmt < 0)
                $finalPayableAmt = 0;
            $totalDuesList['totalRebatesAmt'] = $totalRebates;
            $totalDuesList['totalPenaltiesAmt'] = $onePercTax;
            $totalDuesList['payableAmount'] = round($finalPayableAmt);
            $totalDuesList['paymentUptoYrs'] = [$paymentUptoYrs->first()];
            $totalDuesList['paymentUptoQtrs'] = $pendingQtrs->unique()->values()->sort()->values();

            $demand['duesList'] = $totalDuesList;
            $demand['demandList'] = $demandList;

            $demand['basicDetails'] = $basicDtls;
            if ($canTakePayment == false) {
                $canTakePayment = in_array($loggedInUserType, ['Citizen']) ? true : false;
            }

            $demand['can_pay'] = $canTakePayment;
            // $demand['can_pay'] = true;

            // Calculations for showing demand receipt without any rebate
            $total = roundFigure($dues - $advanceAmt);
            if ($total < 0)
                $total = 0;
            $totalPayable = round($total + $onePercTax);
            $totalPayable = roundFigure($totalPayable);
            if ($totalPayable < 0)
                $totalPayable = 0;
            $demand['dueReceipt'] = [
                'holdingNo' => $basicDtls['holding_no'],
                'new_holding_no' => $basicDtls['new_holding_no'],
                'area_of_plot' => $basicDtls['area_of_plot'],
                'date' => $todayDate,
                'wardNo' => $basicDtls['old_ward_no'],
                'newWardNo' => $basicDtls['new_ward_no'],
                'holding_type' => $holdingType,
                'ownerName' => $ownerDetails->ownerName,
                'ownerMobile' => $ownerDetails->mobileNo,
                'address' => $basicDtls['prop_address'],
                'duesFrom' => $dueFrom,
                'duesTo' => $dueTo,
                'rwhPenalty' => $rwhPenaltyTax,
                'demand' => $dues,
                'alreadyPaid' => $advanceAmt,
                'total' => $total,
                'onePercPenalty' => $onePercTax,
                'totalPayable' => $totalPayable,
                'totalPayableInWords' => getIndianCurrency($totalPayable)
            ];

            $ulb = $mUlbMasters->getUlbDetails($propDtls->ulb_id);
            $demand['ulbDetails'] = $ulb;
            return responseMsgs(true, "Demand Details", remove_null($demand), "011502", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), ['basicDetails' => $basicDtls ?? []], "011502", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    public function calcOnePercPenalty($item)
    {
        $penaltyRebateCalc = new PenaltyRebateCalculation;
        $dueDate = $item->due_date ?? $item['due_date'];
        $onePercPenalty = $penaltyRebateCalc->calcOnePercPenalty($dueDate);        // Calculation One Percent Penalty
        $item['onePercPenalty'] = $onePercPenalty;
        $onePercPenaltyTax = ($item['balance'] * $onePercPenalty) / 100;
        $item['onePercPenaltyTax'] = roundFigure($onePercPenaltyTax);
        return $item;
    }
}
