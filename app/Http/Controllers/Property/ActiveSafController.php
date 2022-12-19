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
        $this->_workflowIds = [3, 4, 5];
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
            $updStatus = $mPropSaf->edit($req);                     // Updation SAF Basic Details

            collect($mOwners)->map(function ($owner, $key) use ($mPropSafOwners) {
                $mPropSafOwners->edit($owner);
            });

            DB::commit();
            return responseMsg($updStatus, "Successfully Updated the Data", "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "");
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

    // Inbox list
    public function inbox()
    {
        $data = $this->Repository->inbox();
        return $data;
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
            return responseMsgs(true, "BTC Inbox List", remove_null($safInbox), 010123, 1.0, "271ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", 010123, 1.0, "271ms", "POST", $req->deviceId);
        }
    }

    public function outbox(Request $request)
    {
        $data = $this->Repository->outbox($request);
        return $data;
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
    // SAF special Inbox
    public function specialInbox()
    {
        $data = $this->Repository->specialInbox();
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
