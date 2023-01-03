<?php

namespace App\Http\Controllers\Water;

use App\Http\Controllers\Controller;
use App\Models\Water\WaterApplication;
use App\Models\Water\WaterApprovalApplicationDetail;
use App\Models\Water\WaterConnectionThroughMstrs;
use App\Models\Water\WaterConnectionTypeMstr;
use App\Models\Water\WaterOwnerTypeMstr;
use App\Models\Water\WaterPropertyTypeMstr;
use Illuminate\Http\Request;
use App\Repository\Water\Interfaces\iNewConnection;
use App\Traits\Ward;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class NewConnectionController extends Controller
{
    use Ward;

    private iNewConnection $newConnection;
    public function __construct(iNewConnection $newConnection)
    {
        $this->newConnection = $newConnection;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validateUser = Validator::make(
                $request->all(),
                [
                    'connectionTypeId'   => 'required|integer',
                    'propertyTypeId'     => 'required|integer',
                    'ownerType'          => 'required',
                    'wardId'             => 'required|integer',
                    'areaSqft'           => 'required',
                    'landmark'           => 'required',
                    'pin'                => 'required|digits:6',
                    'elecKNo'            => 'required',
                    'elecBindBookNo'     => 'required',
                    'elecAccountNo'      => 'required',
                    'elecCategory'       => 'required',
                    'connection_through' => 'required|integer',
                    'owners'             => 'required',
                    'ulbId'              => 'required'
                ]
            );

            if ($validateUser->fails()) {
                return responseMsg(false, "Validation Error!", $validateUser->getMessageBag());
            }
            return $this->newConnection->store($request);
        } catch (Exception $error) {
            return responseMsg(false, $error->getMessage(), "");
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * |---------------------------------------- Citizen/ View Water Screen For Mobile -------------------------------------------|
     */

    // Get connection type / water
    public function getConnectionType()
    {
        try {
            $objConnectionTypes = new WaterConnectionTypeMstr();
            $connectionTypes = $objConnectionTypes->getConnectionType();
            return responseMsgs(true, 'data of the connectionType', $connectionTypes, "", "02", "", "GET", "");
        } catch (Exception $error) {
            return responseMsg(false, $error->getMessage(), "");
        }
    }

    // Get connection through / water
    public function getConnectionThrough()
    {
        try {
            $objConnectionThrough = new WaterConnectionThroughMstrs();
            $connectionThrough = $objConnectionThrough->getAllThrough();
            return responseMsgs(true, 'data of the connectionThrough', $connectionThrough, "", "02", "", "GET", "");
        } catch (Exception $error) {
            return responseMsg(false, $error->getMessage(), "");
        }
    }

    // Get property type / water
    public function getPropertyType()
    {
        try {
            $objPropertyType = new WaterPropertyTypeMstr();
            $propertyType = $objPropertyType->getAllPropertyType();
            return responseMsgs(true, 'data of the propertyType', $propertyType, "", "02", "", "GET", "");
        } catch (Exception $error) {
            return responseMsg(false, $error->getMessage(), "");
        }
    }

    // Get owner type / water
    public function getOwnerType()
    {
        try {
            $objOwnerType = new WaterOwnerTypeMstr();
            $ownerType = $objOwnerType->getallOwnwers();
            return responseMsgs(true, 'data of the ownerType', $ownerType, "", "02", "", "GET", "");
        } catch (Exception $error) {
            return responseMsg(false, $error->getMessage(), "");
        }
    }

    // Get ward no / water
    public function getWardNo()
    {
        try {
            $ulbId = auth()->user()->ulb_id;
            $ward = $this->getAllWard($ulbId);
            return responseMsgs(true, "Ward List!", $ward, "", "02", "", "GET", "");
        } catch (Exception $error) {
            return responseMsg(false, $error->getMessage(), "");
        }
    }


    /**
     * |--------------------------------------------- Water workflow -----------------------------------------------|
     */

    // Water Inbox
    public function waterInbox()
    {
        try {
            return $this->newConnection->waterInbox();
        } catch (Exception $error) {
            return responseMsg(false, $error->getMessage(), "");
        }
    }

    // Water Outbox
    public function waterOutbox()
    {
        try {
            return $this->newConnection->waterOutbox();
        } catch (Exception $error) {
            return responseMsg(false, $error->getMessage(), "");
        }
    }

    // Post Next Level
    public function postNextLevel(Request $request)
    {
        try {
            $request->validate([
                'appId' => 'required',
                'senderRoleId' => 'required',
                'receiverRoleId' => 'required',
                'verificationStatus' => 'required',
                'comment' => "required"
            ]);
            return $this->newConnection->postNextLevel($request);
        } catch (Exception $error) {
            DB::rollBack();
            return responseMsg(false, $error->getMessage(), "");
        }
    }

    // Water Application details for the view in workflow
    public function getApplicationsDetails(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required'
            ]);
            return $this->newConnection->getApplicationsDetails($request);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // Water Special Inbox
    public function waterSpecialInbox(Request $request)
    {
        try {
            return $this->newConnection->waterSpecialInbox($request);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // Application's Post Escalated
    public function postEscalate(Request $request)
    {
        try {
            $request->validate([
                "escalateStatus" => "required|int",
                "applicationId" => "required|int",
            ]);
            return $this->newConnection->postEscalate($request);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // View Uploaded Documents
    public function getWaterDocDetails(Request $request)
    {
        try {
            $request->validate([
                "applicationNo" => "required",
            ]);
            return $this->newConnection->getWaterDocDetails($request);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // Verification/Rejection of Document 
    public function waterDocStatus(Request $request)
    {
        try {
            $request->validate([
                "id" => "required",
                "docStatus" => "required"
            ]);
            return $this->newConnection->waterDocStatus($request);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // final Approval or Rejection of the Application
    public function approvalRejectionWater(Request $request)
    {
        try {
            $request->validate([
                "id" => "required",
                "roleId" => "required",
                "status" => "required"
            ]);
            $waterDetails = WaterApplication::find($request->id);
            if ($waterDetails) {
                return $this->newConnection->approvalRejectionWater($request);
            }
            throw new Exception("Application dont exist!");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // Indipendent Comment on the Water Applications
    public function commentIndependent(Request $request)
    {
        try {
            $request->validate([
                'comment' => 'required',
                'id' => 'required|integer'
            ]);
            return $this->newConnection->commentIndependent($request);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // Get Approved Water Appliction
    public function approvedWaterApplications(Request $request)
    {
        try {
            $obj = new WaterApprovalApplicationDetail();
            $approvedWater = $obj->getApprovedApplications($request);
            return responseMsgs(true, "List of Approved water Applications!", remove_null($approvedWater), "", "02", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
}
