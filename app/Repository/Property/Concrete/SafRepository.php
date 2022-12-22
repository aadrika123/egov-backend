<?php

namespace App\Repository\Property\Concrete;

use App\Repository\Property\Interfaces\iSafRepository;
use Illuminate\Http\Request;
use App\Models\UlbWardMaster;
use App\Traits\Auth;
use App\Traits\Property\WardPermission;

use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\EloquentClass\Property\InsertTax;
use App\EloquentClass\Property\SafCalculation;
use App\MicroServices\DocUpload;
use App\Models\Payment\WebhookPaymentData;
use App\Models\Property\PaymentPropPenalty;
use App\Models\Property\PaymentPropRebate;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropActiveSafsDoc;
use App\Models\Property\PropActiveSafsFloor;
use App\Models\Property\PropActiveSafsOwner;
use App\Models\Property\PropLevelPending;
use App\Models\Property\PropPenalty;
use App\Models\Property\PropSafGeotagUpload;
use App\Models\Property\PropSafsDemand;
use App\Models\Property\PropSafVerification;
use App\Models\Property\PropSafVerificationDtl;
use App\Models\Property\PropTranDtl;
use App\Models\Property\PropTransaction;
use App\Models\Property\RefPropConstructionType;
use App\Models\Property\RefPropFloor;
use App\Models\Property\RefPropOccupancyType;
use App\Models\Property\RefPropOwnershipType;
use App\Models\Property\RefPropTransferMode;
use App\Models\Property\RefPropType;
use App\Models\Property\RefPropUsageType;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWardUser;
use App\Models\Workflows\WfWorkflow;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Models\WorkflowTrack;
use App\Traits\Workflow\Workflow as WorkflowTrait;
use App\Traits\Helper;
use App\Traits\Payment\Razorpay;
use App\Traits\Property\SAF as GlobalSAF;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

/**
 * | Created On-10-08-2022
 * | Created By-Anshu Kumar
 * -----------------------------------------------------------------------------------------
 * | SAF Module all operations 
 * | --------------------------- Workflow Parameters ---------------------------------------
 * |                                 # SAF New Assessment
 * | wf_master id=4 
 * | wf_workflow_id=4
 * |                                 # SAF Reassessment 
 * | wf_mstr_id=5
 * | wf_workflow_id=3
 * |                                 # SAF Mutation
 * | wf_mstr_id=9
 * | wf_workflow_id=5
 */
class SafRepository implements iSafRepository
{
    use Auth;                                                               // Trait Used added by sandeep bara date 17-08-2022
    use WardPermission;
    use WorkflowTrait;
    use GlobalSAF;
    use Razorpay;
    use Helper;

    /**
     * | Citizens Applying For SAF
     * | Proper Validation will be applied after 
     * | @param Illuminate\Http\Request
     * | @param Request $request
     * | @param response
     */
    protected $user_id;
    protected $_redis;
    protected $_todayDate;
    protected $_workflowIds;

    public function __construct()
    {
        $this->_redis = Redis::connection();
        $this->_todayDate = Carbon::now();
        $this->_workflowIds = Config::get('PropertyConstaint.SAF_WORKFLOWS');
    }

    /**
     * @param \Illuminate\Http\Request $req
     * @return \Illuminate\Http\JsonResponse
     * desc This function get the application brief details 
     * request : saf_id (requirde)
     * ---------------Tables-----------------
     * active_saf_details            |
     * ward_mastrs                   | Saf details
     * property_type                 |
     * active_saf_owner_details      -> Saf Owner details
     * active_saf_floore_details     -> Saf Floore Details
     * workflow_tracks               |  
     * users                         | Comments and  date rolles
     * role_masters                  |
     * =======================================
     * helpers : Helpers/utility_helper.php   ->remove_null() -> for remove  null values
     * | Status-Closed
     * | Query Cost-378ms 
     * | Rating-4 
     */
    #Saf Details
    public function details($req)
    {
        try {
            // Saf Details
            $data = [];
            if ($req->id) {                             //<------- Search By SAF ID
                $data = $this->tActiveSafDetails()      // <------- Trait Active SAF Details
                    ->where('prop_active_safs.id', $req->id)
                    ->first();
            }

            if ($req->safNo) {                      // <-------- Search By SAF No
                $data = $this->tActiveSafDetails()    // <------- Trait Active SAF Details
                    ->where('prop_active_safs.saf_no', $req->safNo)
                    ->first();
            }

            $data = json_decode(json_encode($data), true);

            $ownerDetails = PropActiveSafsOwner::where('saf_id', $data['id'])->get();
            $data['owners'] = $ownerDetails;

            $floorDetails = DB::table('prop_active_safs_floors')
                ->select('prop_active_safs_floors.*', 'f.floor_name', 'u.usage_type', 'o.occupancy_type', 'c.construction_type')
                ->join('ref_prop_floors as f', 'f.id', '=', 'prop_active_safs_floors.floor_mstr_id')
                ->join('ref_prop_usage_types as u', 'u.id', '=', 'prop_active_safs_floors.usage_type_mstr_id')
                ->join('ref_prop_occupancy_types as o', 'o.id', '=', 'prop_active_safs_floors.occupancy_type_mstr_id')
                ->join('ref_prop_construction_types as c', 'c.id', '=', 'prop_active_safs_floors.const_type_mstr_id')
                ->where('saf_id', $data['id'])
                ->get();
            $data['floors'] = $floorDetails;

            $levelComments = DB::table('prop_level_pendings')
                ->select(
                    'prop_level_pendings.id',
                    'prop_level_pendings.receiver_role_id as commentedByRoleId',
                    'r.role_name as commentedByRoleName',
                    'prop_level_pendings.remarks',
                    'prop_level_pendings.forward_date',
                    'prop_level_pendings.forward_time',
                    'prop_level_pendings.verification_status',
                    'prop_level_pendings.created_at as received_at'
                )
                ->where('prop_level_pendings.saf_id', $data['id'])
                ->where('prop_level_pendings.status', 1)
                ->leftJoin('wf_roles as r', 'r.id', '=', 'prop_level_pendings.receiver_role_id')
                ->orderByDesc('prop_level_pendings.id')
                ->get();
            $data['levelComments'] = $levelComments;

            $citizenComment = DB::table('workflow_tracks')
                ->select(
                    'workflow_tracks.ref_table_dot_id',
                    'workflow_tracks.message',
                    'workflow_tracks.track_date',
                    'u.email as citizenEmail',
                    'u.user_name as citizenName'
                )
                ->where('ref_table_id_value', $data['id'])
                ->join('users as u', 'u.id', '=', 'workflow_tracks.user_id')
                ->get();

            $data['citizenComment'] = $citizenComment;

            return responseMsgs(true, 'Data Fetched', remove_null($data), "010104", "1.0", "303ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Approve or Reject The SAF Application
     * --------------------------------------------------
     * | ----------------- Initialization ---------------
     * | @param mixed $req
     * | @var activeSaf The Saf Record by Saf Id
     * | @var approvedSaf replication of the saf record to be approved
     * | @var rejectedSaf replication of the saf record to be rejected
     * ------------------- Alogrithm ---------------------
     * | $req->status (if 1 Application to be approved && if 0 application to be rejected)
     * ------------------- Dump --------------------------
     * | @return msg
     * | Status-Closed
     * | Query Cost-430ms 
     * | Rating-3
     */
    public function approvalRejectionSaf($req)
    {
        try {
            // Check if the Current User is Finisher or Not
            $safDetails = PropActiveSaf::find($req->safId);
            if ($safDetails->finisher_role_id != $req->roleId) {
                return responseMsg(false, "Forbidden Access", "");
            }
            $reAssessment = Config::get('PropertyConstaint.ASSESSMENT-TYPE.2');
            DB::beginTransaction();
            // Approval
            if ($req->status == 1) {
                if ($req->assessmentType == $reAssessment)
                    $safDetails->holding_no = $safDetails->previous_holding_id;
                if ($req->assessmentType != $reAssessment) {
                    $safDetails->holding_no = 'HOL-SAF-' . $req->safId;
                }

                $safDetails->fam_no = 'FAM/' . $req->safId;
                $safDetails->saf_pending_status = 0;
                $safDetails->save();

                // SAF Application replication
                $activeSaf = PropActiveSaf::query()
                    ->where('id', $req->safId)
                    ->first();
                $ownerDetails = PropActiveSafsOwner::query()
                    ->where('saf_id', $req->safId)
                    ->get();
                $floorDetails = PropActiveSafsFloor::query()
                    ->where('saf_id', $req->safId)
                    ->get();

                $toBeProperties = PropActiveSaf::query()
                    ->where('id', $req->safId)
                    ->select(
                        'ulb_id',
                        'cluster_id',
                        'holding_no',
                        'applicant_name',
                        'ward_mstr_id',
                        'ownership_type_mstr_id',
                        'prop_type_mstr_id',
                        'appartment_name',
                        'no_electric_connection',
                        'elect_consumer_no',
                        'elect_acc_no',
                        'elect_bind_book_no',
                        'elect_cons_category',
                        'building_plan_approval_no',
                        'building_plan_approval_date',
                        'water_conn_no',
                        'water_conn_date',
                        'khata_no',
                        'plot_no',
                        'village_mauja_name',
                        'road_type_mstr_id',
                        'area_of_plot',
                        'prop_address',
                        'prop_city',
                        'prop_dist',
                        'prop_pin_code',
                        'prop_state',
                        'corr_address',
                        'corr_city',
                        'corr_dist',
                        'corr_pin_code',
                        'corr_state',
                        'is_mobile_tower',
                        'tower_area',
                        'tower_installation_date',
                        'is_hoarding_board',
                        'hoarding_area',
                        'hoarding_installation_date',
                        'is_petrol_pump',
                        'under_ground_area',
                        'petrol_pump_completion_date',
                        'is_water_harvesting',
                        'land_occupation_date',
                        'new_ward_mstr_id',
                        'zone_mstr_id',
                        'flat_registry_date',
                        'assessment_type',
                        'holding_type',
                        'apartment_details_id',
                        'ip_address',
                        'status',
                        'user_id'
                    )->first();

                $propProperties = $toBeProperties->replicate();
                $propProperties->setTable('prop_properties');
                $propProperties->saf_id = $activeSaf->id;
                $propProperties->new_holding_no = $activeSaf->holding_no;
                $propProperties->save();

                $approvedSaf = $activeSaf->replicate();
                $approvedSaf->setTable('prop_safs');
                $approvedSaf->id = $activeSaf->id;
                $approvedSaf->property_id = $propProperties->id;
                $approvedSaf->save();

                $activeSaf->delete();

                // SAF Owners replication
                foreach ($ownerDetails as $ownerDetail) {
                    $approvedOwner = $ownerDetail->replicate();
                    $approvedOwner->setTable('prop_safs_owners');
                    $approvedOwner->id = $ownerDetail->id;
                    $approvedOwner->save();

                    $approvedOwners = $ownerDetail->replicate();
                    $approvedOwners->setTable('prop_owners');
                    $approvedOwners->property_id = $propProperties->id;
                    $approvedOwners->save();

                    $ownerDetail->delete();
                }

                // SAF Floors Replication
                foreach ($floorDetails as $floorDetail) {
                    $approvedFloor = $floorDetail->replicate();
                    $approvedFloor->setTable('prop_safs_floors');
                    $approvedFloor->id = $floorDetail->id;
                    $approvedFloor->save();

                    $propFloor = $floorDetail->replicate();
                    $propFloor->setTable('prop_floors');
                    $propFloor->property_id = $propProperties->id;
                    $propFloor->save();

                    $floorDetail->delete();
                }

                $msg = "Application Successfully Approved !! Holding No " . $safDetails->holding_no;
            }
            // Rejection
            if ($req->status == 0) {
                $activeSaf = PropActiveSaf::query()
                    ->where('id', $req->safId)
                    ->first();

                $ownerDetails = PropActiveSafsOwner::query()
                    ->where('saf_id', $req->safId)
                    ->get();

                $floorDetails = PropActiveSafsFloor::query()
                    ->where('saf_id', $req->safId)
                    ->get();

                // Rejected SAF Application replication
                $rejectedSaf = $activeSaf->replicate();
                $rejectedSaf->setTable('prop_rejected_safs');
                $rejectedSaf->id = $activeSaf->id;
                $rejectedSaf->push();
                $activeSaf->delete();

                // SAF Owners replication
                foreach ($ownerDetails as $ownerDetail) {
                    $approvedOwner = $ownerDetail->replicate();
                    $approvedOwner->setTable('prop_rejected_safs_owners');
                    $approvedOwner->id = $ownerDetail->id;
                    $approvedOwner->save();
                    $ownerDetail->delete();
                }

                // SAF Floors Replication
                foreach ($floorDetails as $floorDetail) {
                    $approvedFloor = $floorDetail->replicate();
                    $approvedFloor->setTable('prop_rejected_safs_floors');
                    $approvedFloor->id = $floorDetail->id;
                    $approvedFloor->save();
                    $floorDetail->delete();
                }

                $msg = "Application Rejected Successfully";
            }

            DB::commit();
            return responseMsgs(true, $msg, ['holdingNo' => $safDetails->holding_no], "010110", "1.0", "410ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Calculate SAF by Saf ID
     * | @param req request saf id
     * | @var array contains all the details for the saf id
     * | @var data contains the details of the saf id by the current object function
     * | @return safTaxes returns all the calculated demand
     * | Status-Closed
     * | Query Costing-417ms
     * | Rating-3 
     */
    public function calculateSafBySafId($req)
    {
        $safDetails = $this->details($req);
        $safNo = $safDetails->original['data']['saf_no'];
        $req = $safDetails->original['data'];
        $array = $this->generateSafRequest($req);                                                                       // Generate SAF Request by SAF Id Using Trait
        $safCalculation = new SafCalculation();
        $request = new Request($array);
        $safTaxes = $safCalculation->calculateTax($request);
        $safTaxes = json_decode(json_encode($safTaxes), true);
        $safTaxes['original']['safNo'] = $safNo;
        return $safTaxes['original'];
    }

    /**
     * | Generate Order ID 
     * | @param req requested Data
     * | @var auth authenticated users credentials
     * | @var calculateSafById calculated SAF amounts and details by request SAF ID
     * | @var totalAmount filtered total amount from the collection
     * | Status-closed
     * | Query Costing-1.41s
     * | Rating - 5
     */

    public function generateOrderId($req)
    {
        try {
            $auth = auth()->user();
            $safRepo = new SafRepository();
            $calculateSafById = $safRepo->calculateSafBySafId($req);
            $safDemandDetails = $this->generateSafDemand($calculateSafById['data']['details']);
            $rwhPenaltyId = Config::get('PropertyConstaint.PENALTIES.RWH_PENALTY_ID');
            $lateAssesPenaltyId = Config::get('PropertyConstaint.PENALTIES.LATE_ASSESSMENT_ID');

            $demands = $calculateSafById['data']['demand'];
            $rebates = $calculateSafById['data']['rebates'];
            $totalAmount = $demands['payableAmount'];
            $lateAssessPenalty = $calculateSafById['data']['demand']['lateAssessmentPenalty'];

            // Check Requested amount is matching with the generated amount or not
            // if ($req->amount == $totalAmount) {
            $orderDetails = $this->saveGenerateOrderid($req);       //<---------- Generate Order ID Trait
            $orderDetails['name'] = $auth->user_name;
            $orderDetails['mobile'] = $auth->mobile;
            $orderDetails['email'] = $auth->email;
            DB::beginTransaction();
            // Update the data in saf prop demands
            foreach ($safDemandDetails as $safDemandDetail) {
                $mSafDemand = new PropSafsDemand();
                $propSafDemand = $mSafDemand->getPropSafDemands($safDemandDetail['quarterYear'], $safDemandDetail['qtr'], $req->id); // Get SAF demand from model function
                if ($propSafDemand) {       // <---------------- If The Data is already Existing then update the data
                    $this->tSaveSafDemand($propSafDemand, $safDemandDetail);    // <--- Trait is Used for SAF Demand Update
                    $propSafDemand->save();
                }
                if (!$propSafDemand) {                                          // <----------------- If not Existing then add new 
                    $propSafDemand = new PropSafsDemand();
                    $this->tSaveSafDemand($propSafDemand, $safDemandDetail);    // <--------- Trait is Used for Saf Demand Update
                    $propSafDemand->save();
                }

                // Save Prop Transaction Penalties

                //  RWH Penalty
                $mPayPropPenalty = new PaymentPropPenalty();
                $checkRwhPenaltyExist = $mPayPropPenalty->getPenaltyByDemandPenaltyID($propSafDemand->id, $rwhPenaltyId);   // <--- Check the Presence of data

                if ($checkRwhPenaltyExist) {
                    $this->tSavePropPenalties($checkRwhPenaltyExist, $rwhPenaltyId, $propSafDemand->id, $safDemandDetail['rwhPenalty']);   // <-------- trait to save rwh
                    $checkRwhPenaltyExist->save();
                }
                if (!$checkRwhPenaltyExist) {
                    $paymentPropPenalty = new PaymentPropPenalty();
                    $this->tSavePropPenalties($paymentPropPenalty, $rwhPenaltyId, $propSafDemand->id, $safDemandDetail['rwhPenalty']);   // <-------- trait to save rwh
                    $paymentPropPenalty->save();
                }

                // One Perc Penalty
                $checkOnePercExist = $mPayPropPenalty->getPenaltyByDemandPenaltyID($propSafDemand->id, $lateAssesPenaltyId);      // <------ Check The Presence of data

                if ($checkOnePercExist) {
                    $this->tSavePropPenalties($checkOnePercExist, $lateAssesPenaltyId, $propSafDemand->id, $safDemandDetail['onePercPenaltyTax']);   // <-------- trait to save rwh
                    $checkOnePercExist->save();
                }
                if (!$checkOnePercExist) {
                    $paymentPropPenalty = new PaymentPropPenalty();
                    $this->tSavePropPenalties($paymentPropPenalty, $lateAssesPenaltyId, $propSafDemand->id, $safDemandDetail['onePercPenaltyTax']);   // <-------- trait to save rwh
                    $paymentPropPenalty->save();
                }
            }

            // Save Prop Transaction Rebates
            foreach ($rebates as $rebate) {
                $paymentPropRebate = new PaymentPropRebate();
                $checkExisting = $paymentPropRebate->getPaymentRebate('saf_id', $req->id, $rebate['rebateTypeId']);
                if ($checkExisting) {
                    $this->tSavePropRebate($checkExisting, $req, $rebate);      // <------- Trait to Save Property Rebate
                    $checkExisting->save();
                }
                if (!$checkExisting) {
                    $paymentPropRebate = new PaymentPropRebate();
                    $this->tSavePropRebate($paymentPropRebate, $req, $rebate);  // <------- Trait to Save Property Rebate
                    $paymentPropRebate->save();
                }
            }

            DB::commit();
            return responseMsgs(true, "Order ID Generated", remove_null($orderDetails), "010114", "1.0", "1s", "POST", $req->deviceId);
            // }

            // return responseMsg(false, "Amount Not Matched", "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | SAF Payment
     * | @param req  
     * | @var workflowId SAF workflow ID
     * | Status-Closed
     * | Query Consting-374ms
     * | Rating-3
     */

    public function paymentSaf($req)
    {
        try {
            $userId = $req['userId'];
            $propSafsDemand = new PropSafsDemand();
            $demands = $propSafsDemand->getDemandBySafId($req['id']);
            DB::beginTransaction();
            // Property Transactions
            $propTrans = new PropTransaction();
            $propTrans->saf_id = $req['id'];
            $propTrans->amount = $req['amount'];
            $propTrans->tran_date = Carbon::now()->format('Y-m-d');
            $propTrans->tran_no = $req['transactionNo'];
            $propTrans->payment_mode = $req['paymentMode'];
            $propTrans->user_id = $userId;
            $propTrans->save();

            // Reflect on Prop Tran Details
            foreach ($demands as $demand) {
                $demand->paid_status = 1;           // <-------- Update Demand Paid Status 
                $demand->save();

                $propTranDtl = new PropTranDtl();
                $propTranDtl->tran_id = $propTrans->id;
                $propTranDtl->saf_demand_id = $demand['id'];
                $propTranDtl->total_demand = $demand['amount'];
                $propTranDtl->save();
            }

            // Update SAF Payment Status
            $activeSaf = PropActiveSaf::find($req['id']);
            $activeSaf->payment_status = 1;
            $activeSaf->save();

            // Replication Prop Rebates
            $mPaymentPropRebates = new PaymentPropRebate();
            $paymentRebates = $mPaymentPropRebates->getRebatesBySafId($req['id']);
            foreach ($paymentRebates as $paymentRebate) {
                $propRebate = $paymentRebate->replicate();
                $propRebate->setTable('prop_rebates');
                $propRebate->tran_id = $propTrans->id;
                $propRebate->tran_date = $this->_todayDate->format('Y-m-d');
                $propRebate->save();
            }

            // Replication Prop Penalties
            foreach ($demands as $demand) {
                $pPropPenalties = PaymentPropPenalty::where('saf_demand_id', $demand['id'])->get();
                foreach ($pPropPenalties as $pPropPenaltie) {
                    $propPenalties = $pPropPenaltie->replicate();
                    $propPenalties->setTable('prop_penalties');
                    $propPenalties->penalty_date = $this->_todayDate->format('Y-m-d');
                    $propPenalties->tran_id = $propTrans->id;
                    $propPenalties->save();
                }
            }

            Redis::del('property-transactions-user-' . $userId);
            DB::commit();
            return responseMsgs(true, "Payment Successfully Done", "", "010115", "1.0", "567ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Generate Payment Receipt(1)
     * | @param request req
     * | Status-Closed
     * | Query Cost-3
     */
    public function generatePaymentReceipt($req)
    {
        try {
            $paymentData = new WebhookPaymentData();
            $propSafsDemand = new PropSafsDemand();
            $transaction = new PropTransaction();
            $propPenalties = new PropPenalty();

            $mOnePercPenaltyId = Config::get('PropertyConstaint.PENALTIES.LATE_ASSESSMENT_ID');
            $mTowards = Config::get('PropertyConstaint.SAF_TOWARDS');
            $mAccDescription = Config::get('PropertyConstaint.ACCOUNT_DESCRIPTION');
            $mDepartmentSection = Config::get('PropertyConstaint.DEPARTMENT_SECTION');

            $applicationDtls = $paymentData->getApplicationId($req->paymentId);
            // Saf Payment
            $safId = json_decode($applicationDtls)->applicationId;

            $reqSafId = new Request(['id' => $safId]);
            $activeSafDetails = $this->details($reqSafId);
            $demands = $propSafsDemand->getDemandBySafId($safId);
            $calDemandAmt = collect($demands)->sum('amount');
            $checkOtherTaxes = collect($demands)->first();

            $mDescriptions = $this->readDescriptions($checkOtherTaxes);      // Check the Taxes are Only Holding or Not

            $fromFinYear = $demands->first()['fyear'];
            $fromFinQtr = $demands->first()['qtr'];
            $upToFinYear = $demands->last()['fyear'];
            $upToFinQtr = $demands->last()['qtr'];

            // Get PropertyTransactions
            $propTrans = $transaction->getPropTransactions($safId, "saf_id");
            $propTrans = collect($propTrans)->last();

            // Get Property Penalties against property transaction
            $propPenalties = $propPenalties->getPenalties('tran_id', $propTrans->id);
            $mOnePercPenalty = collect($propPenalties)->where('penalty_type_id', $mOnePercPenaltyId)->sum('amount');

            $taxDetails = $this->readPenalyPmtAmts($activeSafDetails->original['data']['late_assess_penalty'], $mOnePercPenalty, $propTrans->amount);   // Get Holding Tax Dtls
            // Response Return Data
            $responseData = [
                "departmentSection" => $mDepartmentSection,
                "accountDescription" => $mAccDescription,
                "transactionDate" => $propTrans->tran_date,
                "transactionNo" => $propTrans->tran_no,
                "transactionTime" => $propTrans->created_at->format('H:i:s'),
                "applicationNo" => $activeSafDetails->original['data']['saf_no'],
                "customerName" => $activeSafDetails->original['data']['applicant_name'],
                "receiptWard" => $activeSafDetails->original['data']['new_ward_no'],
                "address" => $activeSafDetails->original['data']['prop_address'],
                "paidFrom" => $fromFinYear,
                "paidFromQtr" => $fromFinQtr,
                "paidUpto" => $upToFinYear,
                "paidUptoQtr" => $upToFinQtr,
                "paymentMode" => $propTrans->payment_mode,
                "bankName" => "",
                "branchName" => "",
                "chequeNo" => "",
                "chequeDate" => "",
                "noOfFlats" => "",
                "monthlyRate" => "",
                "demandAmount" => roundFigure($calDemandAmt),
                "taxDetails" => $taxDetails,
                "ulbId" => $activeSafDetails->original['data']['ulb_id'],
                "oldWardNo" => $activeSafDetails->original['data']['old_ward_no'],
                "newWardNo" => $activeSafDetails->original['data']['new_ward_no'],
                "towards" => $mTowards,
                "description" => $mDescriptions
            ];
            return responseMsgs(true, "Payment Receipt", remove_null($responseData), "010116", "1.0", "451ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
    /**
     * | Read Taxes Descriptions(1.1)
     * | @param checkOtherTaxes first collection from the details
     */
    public function readDescriptions($checkOtherTaxes)
    {
        $taxes = [
            'Holding Tax' => $checkOtherTaxes->holding_tax,
            'Water Tax' => $checkOtherTaxes->water_tax,
            'Education Cess' => $checkOtherTaxes->education_cess,
            'Latrine Tax' => $checkOtherTaxes->latrine_tax
        ];
        $filtered = collect($taxes)->filter(function ($tax, $key) {
            if ($tax > 0) {
                return $key;
            }
        });

        return $filtered->keys();
    }
    /**
     * | Read Penalty Tax Details with Penalties and final payable amount(1.2)
     */
    public function readPenalyPmtAmts($lateAssessPenalty = 0, $onePercPenalty = 0, $amount)
    {
        $amount = [
            "Late Assessment Fine(Rule 14.1)" => $lateAssessPenalty,
            "1% Monthly Penalty" => roundFigure($onePercPenalty),
            "Total Paid Amount" => $amount,
            "Paid Amount In Words" => getIndianCurrency($amount),
            "Remaining Amount" => 0,
        ];

        $filtered = collect($amount)->filter(function ($value, $key) {
            return $value > 0;
        });

        return $filtered;
    }

    /**
     * | Get Property Transactions
     * | @param req requested parameters
     * | @var userId authenticated user id
     * | @var propTrans Property Transaction details of the Logged In User
     * | @return responseMsg
     * | Status-Closed
     * | Run time Complexity-346ms
     * | Rating - 3
     */
    public function getPropTransactions($req)
    {
        $propTransaction = new PropTransaction();
        $userId = auth()->user()->id;

        $propTrans = json_decode(Redis::get('property-transactions-user-' . $userId));                      // Should Be Deleted SAF Payment
        if (!$propTrans) {
            $propTrans = $propTransaction->getPropTransByUserId($userId);
            $this->_redis->set('property-transactions-user-' . $userId, json_encode($propTrans));
        }
        return responseMsgs(true, "Transactions History", remove_null($propTrans), "010117", "1.0", "265ms", "POST", $req->deviceId);
    }

    /**
     * | Get Transactions by Property id or SAF id
     * | @param Request $req
     */
    public function getTransactionBySafPropId($req)
    {
        $propTransaction = new PropTransaction();
        if ($req->safId)                                                // Get By SAF Id
            $propTrans = $propTransaction->getPropTransBySafId($req->safId);
        if ($req->propertyId)                                           // Get by Property Id
            $propTrans = $propTransaction->getPropTransByPropId($req->propertyId);

        return responseMsg(true, "Property Transactions", remove_null($propTrans));
    }

    /**
     * | Get Property Details by Property Holding No
     * | Rating - 2
     * | Run Time Complexity-500 ms
     */
    public function getPropByHoldingNo($req)
    {
        try {
            $propertyDtl = [];
            if ($req->holdingNo) {
                $properties = $this->tPropertyDetails()
                    ->where('prop_properties.ward_mstr_id', $req->wardId)
                    ->where('prop_properties.holding_no', $req->holdingNo)
                    ->first();
            }

            if ($req->propertyId) {
                $properties = $this->tPropertyDetails()
                    ->where('prop_properties.id', $req->propertyId)
                    ->first();
            }

            $floors = DB::table('prop_floors')
                ->select('prop_floors.*', 'f.floor_name', 'u.usage_type', 'o.occupancy_type', 'c.construction_type')
                ->join('ref_prop_floors as f', 'f.id', '=', 'prop_floors.floor_mstr_id')
                ->join('ref_prop_usage_types as u', 'u.id', '=', 'prop_floors.usage_type_mstr_id')
                ->join('ref_prop_occupancy_types as o', 'o.id', '=', 'prop_floors.occupancy_type_mstr_id')
                ->join('ref_prop_construction_types as c', 'c.id', '=', 'prop_floors.const_type_mstr_id')
                ->where('property_id', $properties->property_id)
                ->get();

            $owners = DB::table('prop_owners')
                ->where('property_id', $properties->property_id)
                ->get();

            $propertyDtl = collect($properties);
            $propertyDtl['floors'] = $floors;
            $propertyDtl['owners'] = $owners;

            return responseMsgs(true, "Property Details", remove_null($propertyDtl), "010112", "1.0", "238ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Site Verification
     * | @param req requested parameter
     */
    public function siteVerification($req)
    {
        try {
            $taxCollectorRole = Config::get('PropertyConstaint.SAF-LABEL.TC');
            $ulbTaxCollectorRole = Config::get('PropertyConstaint.SAF-LABEL.UTC');
            $verificationStatus = $req->verificationStatus;                                             // Verification Status true or false

            $verification = new PropSafVerification();

            switch ($req->currentRoleId) {
                case $taxCollectorRole;                                                                  // In Case of Agency TAX Collector
                    if ($verificationStatus == 1) {
                        $verification->agency_verification = true;
                        $msg = "Site Successfully Verified";
                    }
                    if ($verificationStatus == 0) {
                        $verification->agency_verification = false;
                        $msg = "Site Successfully rebuted";
                    }
                    break;

                case $ulbTaxCollectorRole;                                                                // In Case of Ulb Tax Collector
                    if ($verificationStatus == 1) {
                        $verification->ulb_verification = true;
                        $msg = "Site Successfully Verified";
                    }
                    if ($verificationStatus == 0) {
                        $verification->ulb_verification = false;
                        $msg = "Site Successfully rebuted";
                    }
                    break;

                default:
                    return responseMsg(false, "Forbidden Access", "");
            }

            // Verification Store
            DB::beginTransaction();
            $verification->saf_id = $req->safId;
            $verification->prop_type_id = $req->propertyType;
            $verification->road_type_id = $req->roadTypeId;
            $verification->area_of_plot = $req->areaOfPlot;
            $verification->ward_id = $req->wardId;
            $verification->has_mobile_tower = $req->isMobileTower;
            $verification->tower_area = $req->mobileTowerArea;
            $verification->tower_installation_date = $req->mobileTowerDate;
            $verification->has_hoarding = $req->isHoardingBoard;
            $verification->hoarding_area = $req->hoardingArea;
            $verification->hoarding_installation_date = $req->hoardingDate;
            $verification->is_petrol_pump = $req->isPetrolPump;
            $verification->underground_area = $req->petrolPumpUndergroundArea;
            $verification->petrol_pump_completion_date = $req->petrolPumpDate;
            $verification->has_water_harvesting = $req->isHarvesting;
            $verification->zone_id = $req->zone;
            $verification->user_id = $req->userId;
            $verification->save();

            // Verification Dtl Table Update                                         // For Tax Collector
            foreach ($req->floorDetails as $floorDetail) {
                $verificationDtl = new PropSafVerificationDtl();
                $verificationDtl->verification_id = $verification->id;
                $verificationDtl->saf_id = $req->safId;
                $verificationDtl->saf_floor_id = $floorDetail['floorId'];
                $verificationDtl->floor_mstr_id = $floorDetail['floorMstrId'];
                $verificationDtl->usage_type_id = $floorDetail['usageType'];
                $verificationDtl->construction_type_id = $floorDetail['constructionType'];
                $verificationDtl->occupancy_type_id = $floorDetail['occupancyType'];
                $verificationDtl->builtup_area = $floorDetail['builtupArea'];
                $verificationDtl->date_from = $floorDetail['fromDate'];
                $verificationDtl->date_to = $floorDetail['toDate'];
                $verificationDtl->save();
            }

            DB::commit();
            return responseMsgs(true, $msg, "", "010118", "1.0", "310ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Geo Tagging Photo Uploads
     * | @param request req
     * | @var relativePath Geo Tagging Document Ralative path
     * | @var array images- request image path
     * | @var array directionTypes- request direction types
     */
    public function geoTagging($req)
    {
        try {
            $docUpload = new DocUpload;
            $relativePath = Config::get('PropertyConstaint.GEOTAGGING_RELATIVE_PATH');
            $images = $req->imagePath;
            $directionTypes = $req->directionType;

            collect($images)->map(function ($image, $key) use ($directionTypes, $relativePath, $req, $docUpload) {
                $geoTagging = new PropSafGeotagUpload();
                $refImageName = 'saf-geotagging-' . $directionTypes[$key] . '-' . $req->safId;

                $imageName = $docUpload->upload($refImageName, $image, $relativePath);         // <------- Get uploaded image name and move the image in folder

                $geoTagging->saf_id = $req->safId;
                $geoTagging->image_path = $imageName;
                $geoTagging->direction_type = $directionTypes[$key];
                $geoTagging->relative_path = $relativePath;
                $geoTagging->user_id = authUser()->id;
                $geoTagging->save();
            });

            return responseMsgs(true, "Geo Tagging Done Successfully", "", "010119", "1.0", "289ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Get Tc Verifications
     * | @param request $req
     */
    public function getTcVerifications($req)
    {
        try {
            $data = array();
            $safVerifications = new PropSafVerification();
            $safVerificationDtls = new PropSafVerificationDtl();

            $data = $safVerifications->getVerificationsData($req->safId);           // <--------- Prop Saf Verification Model Function to Get Prop Saf Verifications Data 

            $data = json_decode(json_encode($data), true);

            $verificationDtls = $safVerificationDtls->getFullVerificationDtls($data['id']);     // <----- Prop Saf Verification Model Function to Get Verification Floor Dtls

            $data['floorDetails'] = $verificationDtls;
            return responseMsgs(true, "TC Verification Details", remove_null($data), "010120", "1.0", "258ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    //document verification calling to model
    public function safDocStatus($req)
    {
        $docVerify = new PropActiveSafsDoc();
        return $docVerify->safDocStatus($req);
    }
}
