<?php

namespace App\Repository\Citizen;

use Illuminate\Http\Request;
use App\Repository\Citizen\iCitizenRepository;
use App\Models\ActiveCitizen;
use App\Models\Payment\PaymentRequest;
use App\Models\Property\PropLevelPending;
use App\Models\Property\PropProperty;
use App\Models\Trade\ActiveLicence;
use App\Models\Trade\ActiveTradeLicence;
use App\Models\User;
use App\Models\Water\WaterApplication;
use App\Models\WorkflowTrack;
use App\Traits\Auth;
use App\Traits\Workflow\Workflow;
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
            $userId = auth()->user()->id;
            $applications = array();

            if ($req->getMethod() == 'GET') {                                                       // For All Applications
                $applications['Property'] = $this->appliedSafApplications($userId);
                $applications['Water'] = $this->appliedWaterApplications($userId);
                $applications['Trade'] = $this->appliedTradeApplications($userId);
                $applications['Holding'] = $this->getCitizenProperty($userId);
                $applications['CareTaker'] = $this->getCaretakerProperty($userId);
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
            }

            return responseMsg(true, "All Applied Applications", remove_null($applications));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
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
            ->leftJoin('wf_roles as r', 'r.id', '=', 'prop_active_safs.current_role')
            ->leftJoin('prop_transactions as t', 't.saf_id', '=', 'prop_active_safs.id')
            ->select(
                'r.role_name as current_level',
                'prop_active_safs.id as application_id',
                'saf_no',
                'holding_no',
                'assessment_type',
                'application_date',
                'applicant_name',
                'payment_status',
                'doc_upload_status',
                'saf_pending_status',
                'parked as backToCitizen',
                'workflow_id',
                'prop_active_safs.created_at',
                'prop_active_safs.updated_at',
                't.tran_no as transaction_no',
                't.tran_date as transaction_date'
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
                'prop_active_concessions.date as apply_date',
                'p.holding_no',
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
                'prop_active_objections.date as apply_date',
                'p.holding_no',
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
                'holding_no',
                'r.role_name',
                'prop_active_objections.workflow_id',
                'prop_active_objections.objection_for'
            )
            ->get();
        $applications['objections'] = $objectionApplications;

        $harvestingApplications = DB::table('prop_active_harvestings')
            ->join('wf_roles as r', 'r.id', '=', 'prop_active_harvestings.current_role')
            ->join('prop_properties as p', 'p.id', '=', 'prop_active_harvestings.property_id')
            ->select(
                'prop_active_harvestings.id as application_id',
                'prop_active_harvestings.application_no',
                'prop_active_harvestings.created_at as apply_date',
                'p.holding_no',
                'r.role_name as pending_at',
                'prop_active_harvestings.workflow_id'
            )
            ->where('prop_active_harvestings.citizen_id', $userId)
            ->get();
        $applications['harvestings'] = $harvestingApplications;

        //
        $deactivationDetails = DB::table('prop_active_deactivation_requests')
            ->join('wf_roles as r', 'r.id', '=', 'prop_active_deactivation_requests.current_role')
            ->join('prop_properties as p', 'p.id', '=', 'prop_active_deactivation_requests.property_id')
            ->select(
                'prop_active_deactivation_requests.id as application_id',
                // 'prop_active_deactivation_requests.application_no',
                'prop_active_deactivation_requests.apply_date',
                'p.holding_no',
                'r.role_name as pending_at',
                'prop_active_deactivation_requests.workflow_id'
            )
            ->where('prop_active_deactivation_requests.emp_detail_id', $userId)
            ->get();
        $applications['deactivation'] = $deactivationDetails;

        return collect($applications);
    }

    /**
     * | Applied Water Applications
     * | Status-Closed
     */
    public function appliedWaterApplications($userId)
    {
        $applications = array();
        $waterApplications = WaterApplication::select('id as application_id', 'category', 'application_no', 'holding_no', 'workflow_id', 'created_at', 'updated_at')
            ->where('citizen_id', $userId)
            ->where('status', 1)
            ->orderByDesc('id')
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
        $tradeApplications = ActiveTradeLicence::select('id as application_id', 'application_no', 'holding_no', 'workflow_id', 'created_at', 'updated_at')
            ->where('emp_details_id', $userId)
            ->where('status', 1)
            ->orderByDesc('id')
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
            $query = "SELECT   p.id AS prop_id,
                                p.holding_no,
                                p.new_holding_no,
                                p.application_date AS apply_date,
                                o.owner_name,
                                p.balance AS leftAmount,
                                t.amount AS lastPaidAmount,
                                t.tran_date AS lastPaidDate

                                FROM prop_properties p
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
        $data = array();
        $activeCitizen = DB::table('active_citizens')
            ->where('id', $userId)
            ->first();
        $propIds =  ($activeCitizen->caretaker);
        if (!$propIds)
            throw new Exception('No Caretaker');
        $propIds = explode(',', $propIds);

        foreach ($propIds as $propId) {
            $a = json_decode($propId);
            $propdtl =  PropProperty::where('prop_properties.id', $a->propId)
                ->join('prop_owners', 'prop_owners.property_id', 'prop_properties.id')
                ->leftjoin('prop_transactions', 'prop_transactions.property_id', 'prop_properties.id')
                ->orderBydesc('prop_transactions.id')
                ->first();

            $propDtls = [
                'prop_id' => $propdtl->id,
                'holding_no' => $propdtl->holding_no,
                'new_holding_no' => $propdtl->new_holding_no,
                'apply_date' => $propdtl->application_date,
                'owner_name' => $propdtl->owner_name,
                'leftamount' => $propdtl->balance,
                'lastpaidamount' => $propdtl->amount,
                'lastpaiddate' => $propdtl->tran_date,
            ];

            array_push($data, $propDtls);
        }
        return collect($data);
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
        $array['citizenId'] = auth()->user()->id;
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
    public function getTransactionHistory()
    {
        try {
            $userId = auth()->user()->id;
            $trans = PaymentRequest::where('citizen_id', $userId)
                ->get();
            return responseMsg(true, "Data Fetched", remove_null($trans));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
}
