<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropActiveSafsDoc;
use App\Models\Property\PropActiveSafsFloor;
use App\Models\Property\PropActiveSafsOwner;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWardUser;
use Illuminate\Http\Request;
use App\Repository\Property\Interfaces\iSafRepository;
use App\Traits\Property\SAF;
use App\Traits\Workflow\Workflow;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class ActiveSafController extends Controller
{
    use Workflow;
    use SAF;
    /**
     * | Created On-08-08-2022 
     * | Created By-Anshu Kumar
     * --------------------------------------------------------------------------------------
     * | Controller regarding with SAF Module
     */

    private $_workflowIds;
    // Initializing function for Repository
    protected $saf_repository;
    public function __construct(iSafRepository $saf_repository)
    {
        $this->Repository = $saf_repository;
        $this->_workflowIds = Config::get('PropertyConstaint.SAF_WORKFLOWS');;
    }

    // Get All master data in saf
    public function masterSaf()
    {
        return $this->Repository->masterSaf();
    }

    //  Function for applying SAF
    public function applySaf(Request $request)
    {
        $request->validate([
            'ulbId' => 'required|integer'
        ]);

        return $this->Repository->applySaf($request);
    }

    /**
     * | Edit Applied Saf by SAF Id for BackOffice
     * | @param request $req
     */
    public function editSaf(Request $req)
    {
        $req->validate([
            'id' => 'required|integer'
        ]);

        try {
            $mPropSaf = new PropActiveSaf();
            $mPropSafOwners = new PropActiveSafsOwner();
            $mOwners = $req->owner;

            DB::beginTransaction();
            $mPropSaf->edit($req);                                                      // Updation SAF Basic Details

            collect($mOwners)->map(function ($owner) use ($mPropSafOwners) {            // Updation of Owner Basic Details
                $mPropSafOwners->edit($owner);
            });

            DB::commit();
            return responseMsgs(true, "Successfully Updated the Data", "", 010124, 1.0, "308ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", 010124, 1.0, "308ms", "POST", $req->deviceId);
        }
    }

    // Document Upload By Citizen Or JSK
    public function documentUpload(Request $req)
    {
        $req->validate([
            'safId' => 'required|integer'
        ]);
        return $this->Repository->documentUpload($req);
    }

    // Verify Document By Dealing Assistant
    public function verifyDoc(Request $req)
    {
        $req->validate([
            "verifications" => "required"
        ]);
        return $this->Repository->verifyDoc($req);
    }

    /**
     * ---------------------- Saf Workflow Inbox --------------------
     * | Initialization
     * -----------------
     * | @var userId > logged in user id
     * | @var ulbId > Logged In user ulb Id
     * | @var refWorkflowId > Workflow ID 
     * | @var workflowId > SAF Wf Workflow ID 
     * | @var query > Contains the Pg Sql query
     * | @var workflow > get the Data in laravel Collection
     * | @var checkDataExisting > check the fetched data collection in array
     * | @var roleId > Fetch all the Roles for the Logged In user
     * | @var data > all the Saf data of current logged roleid 
     * | @var occupiedWard > get all Permitted Ward Of current logged in user id
     * | @var wardId > filtered Ward Id from the data collection
     * | @var safInbox > Final returned Data
     * | @return response #safInbox
     * | Status-Closed
     * | Query Cost-327ms 
     * | Rating-3
     * ---------------------------------------------------------------
     */
    #Inbox
    public function inbox()
    {
        try {
            $mWfRoleUser = new WfRoleusermap();
            $mWfWardUser = new WfWardUser();

            $userId = auth()->user()->id;
            $ulbId = auth()->user()->ulb_id;
            $readWards = $mWfWardUser->getWardsByUserId($userId);                       // Model () to get Occupied Wards of Current User

            $occupiedWards = collect($readWards)->map(function ($ward) {
                return $ward->ward_id;
            });

            $readRoles = $mWfRoleUser->getRoleIdByUserId($userId);                      // Model to () get Role By User Id

            $roleIds = $readRoles->map(function ($role, $key) {
                return $role->wf_role_id;
            });

            $data = $this->getSaf($this->_workflowIds)                                  // Global SAF 
                ->where('parked', false)
                ->where('prop_active_safs.ulb_id', $ulbId)
                ->where('prop_active_safs.status', 1)
                ->whereIn('current_role', $roleIds)
                ->orderByDesc('id')
                ->groupBy('prop_active_safs.id', 'p.property_type', 'ward.ward_name')
                ->get();

            $safInbox = $data->whereIn('ward_mstr_id', $occupiedWards);

            return responseMsgs(true, "Data Fetched", remove_null($safInbox->values()), "010103", "1.0", "339ms", "POST", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Inbox for the Back To Citizen parked true
     * | @var mUserId authenticated user id
     * | @var mUlbId authenticated user ulb id
     * | @var readWards get all the wards of the user id
     * | @var occupiedWardsId get all the wards id of the user id
     * | @var readRoles get all the roles of the user id
     * | @var roleIds get all the logged in user role ids
     */
    public function btcInbox(Request $req)
    {
        try {
            $mWfRoleUser = new WfRoleusermap();
            $mWfWardUser = new WfWardUser();

            $mUserId = authUser()->id;
            $mUlbId = authUser()->ulb_id;
            $mDeviceId = $req->deviceId ?? "";

            $readWards = $mWfWardUser->getWardsByUserId($mUserId);                  // Model function to get ward list
            $occupiedWardsId = collect($readWards)->map(function ($ward) {              // Collection filteration
                return $ward->ward_id;
            });

            $readRoles = $mWfRoleUser->getRoleIdByUserId($mUserId);                 // Model function to get Role By User Id
            $roleIds = $readRoles->map(function ($role, $key) {
                return $role->wf_role_id;
            });

            $data = $this->getSaf($this->_workflowIds)                                       // Global SAF 
                ->where('parked', true)
                ->where('prop_active_safs.ulb_id', $mUlbId)
                ->where('prop_active_safs.status', 1)
                ->whereIn('current_role', $roleIds)
                ->orderByDesc('id')
                ->groupBy('prop_active_safs.id', 'p.property_type', 'ward.ward_name')
                ->get();

            $safInbox = $data->whereIn('ward_mstr_id', $occupiedWardsId);
            return responseMsgs(true, "BTC Inbox List", remove_null($safInbox), 010123, 1.0, "271ms", "POST", $mDeviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", 010123, 1.0, "271ms", "POST", $mDeviceId);
        }
    }

    /**
     * | Saf Outbox
     * | @var userId authenticated user id
     * | @var ulbId authenticated user Ulb Id
     * | @var workflowRoles get All Roles of the user id
     * | @var roles filteration of roleid from collections
     * | Status-Closed
     * | Query Cost-369ms 
     * | Rating-4
     */

    public function outbox()
    {
        try {
            $mWfRoleUser = new WfRoleusermap();
            $mWfWardUser = new WfWardUser();

            $userId = auth()->user()->id;
            $ulbId = auth()->user()->ulb_id;

            $workflowRoles = $mWfRoleUser->getRoleIdByUserId($userId);
            $roles = $workflowRoles->map(function ($value, $key) {
                return $value->wf_role_id;
            });

            $refWard = $mWfWardUser->getWardsByUserId($userId);
            $wardId = $refWard->map(function ($value, $key) {
                return $value->ward_id;
            });

            $safData = $this->getSaf($this->_workflowIds)
                ->where('prop_active_safs.ulb_id', $ulbId)
                ->whereNotIn('current_role', $roles)
                ->whereIn('ward_mstr_id', $wardId)
                ->orderByDesc('id')
                ->groupBy('prop_active_safs.id', 'p.property_type', 'ward.ward_name')
                ->get();
            return responseMsgs(true, "Data Fetched", remove_null($safData->values()), "010104", "1.0", "274ms", "POST", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | @var ulbId authenticated user id
     * | @var ulbId authenticated ulb Id
     * | @var occupiedWard get ward by user id using trait
     * | @var wardId Filtered Ward ID from the collections
     * | @var safData SAF Data List
     * | @return
     * | @var \Illuminate\Support\Collection $safData
     * | Status-Closed
     * | Query Costing-336ms 
     * | Rating-2 
     */
    public function specialInbox()
    {
        try {
            $mWfWardUser = new WfWardUser();
            $userId = authUser()->id;
            $ulbId = authUser()->ulb_id;
            $occupiedWard = $mWfWardUser->getWardsByUserId($userId);                        // Get All Occupied Ward By user id using trait
            $wardId = $occupiedWard->map(function ($item, $key) {                           // Filter All ward_id in an array using laravel collections
                return $item->ward_id;
            });
            $safData = $this->getSaf($this->_workflowIds)
                ->where('is_escalate', 1)
                ->where('prop_active_safs.ulb_id', $ulbId)
                ->whereIn('ward_mstr_id', $wardId)
                ->orderByDesc('id')
                ->groupBy('prop_active_safs.id', 'prop_active_safs.saf_no', 'ward.ward_name', 'p.property_type')
                ->get();
            return responseMsgs(true, "Data Fetched", remove_null($safData), "010107", "1.0", "251ms", "POST", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    public function details(Request $request)
    {
        $data = $this->Repository->details($request);
        return $data;
    }

    // postEscalate
    public function postEscalate(Request $request)
    {
        $data = $this->Repository->postEscalate($request);
        return $data;
    }

    // Post Independent Comment
    public function commentIndependent(Request $request)
    {
        $request->validate([
            'comment' => 'required',
            'safId' => 'required'
        ]);

        return $this->Repository->commentIndependent($request);
    }

    // Forward to Next Level
    public function postNextLevel(Request $request)
    {
        $data = $this->Repository->postNextLevel($request);
        return $data;
    }

    // Saf Application Approval Or Reject
    public function approvalRejectionSaf(Request $req)
    {
        $req->validate([
            'workflowId' => 'required|integer',
            'roleId' => 'required|integer',
            'safId' => 'required|integer',
            'status' => 'required|integer'
        ]);

        return $this->Repository->approvalRejectionSaf($req);
    }

    // Back to Citizen
    public function backToCitizen(Request $req)
    {
        $req->validate([
            'safId' => 'required|integer',
            'workflowId' => 'required|integer',
            'currentRoleId' => 'required|integer',
            'comment' => 'required|string'
        ]);
        return $this->Repository->backToCitizen($req);
    }

    // Calculate SAF by saf ID
    public function calculateSafBySafId(Request $req)
    {
        return $this->Repository->calculateSafBySafId($req);
    }

    // Generate Payment Order ID
    public function generateOrderId(Request $req)
    {
        $req->validate([
            'id' => 'required|integer',
            'amount' => 'required|numeric',
            'departmentId' => 'required|integer'
        ]);

        return $this->Repository->generateOrderId($req);
    }

    // SAF Payment 
    public function paymentSaf(Request $req)
    {
        return $this->Repository->paymentSaf($req);
    }

    // Generate Payment Receipt
    public function generatePaymentReceipt(Request $req)
    {
        $req->validate([
            'paymentId' => 'required'
        ]);

        return $this->Repository->generatePaymentReceipt($req);
    }

    // Get Property Transactions
    public function getPropTransactions(Request $req)
    {
        return $this->Repository->getPropTransactions($req);
    }

    // Get Transactions by prop id or safid
    public function getTransactionBySafPropId(Request $req)
    {
        return $this->Repository->getTransactionBySafPropId($req);
    }

    // Get Property by Holding No
    public function getPropByHoldingNo(Request $req)
    {
        return $this->Repository->getPropByHoldingNo($req);
    }

    // Site Verification
    public function siteVerification(Request $req)
    {
        $req->validate([
            'safId' => 'required|integer',
            'verificationStatus' => 'required|bool',
            'propertyType' => 'required|integer',
            'roadTypeId' => 'required|integer',
            'wardId' => 'required|integer'
        ]);
        return $this->Repository->siteVerification($req);
    }

    // Geo Tagging
    public function geoTagging(Request $req)
    {
        $req->validate([
            "safId" => "required|integer",
            "imagePath.*" => "image|mimes:jpeg,jpg,png,gif|required"
        ]);
        return $this->Repository->geoTagging($req);
    }

    //document verification
    public function safDocStatus(Request $req)
    {
        return $this->Repository->safDocStatus($req);
    }

    // Get TC Verifications
    public function getTcVerifications(Request $req)
    {
        return $this->Repository->getTcVerifications($req);
    }
}
