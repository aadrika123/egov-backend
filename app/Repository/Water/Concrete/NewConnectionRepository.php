<?php

namespace App\Repository\Water\Concrete;

use App\Models\Payment\WebhookPaymentData;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropProperty;
use App\Models\Water\WaterApplicant;
use App\Models\Water\WaterApplicantDoc;
use App\Models\Water\WaterApplication;
use App\Models\Water\WaterConnectionCharge;
use App\Models\Water\WaterConnectionThroughMstrs;
use App\Models\Water\WaterConnectionTypeMstr;
use App\Models\Water\WaterOwnerTypeMstr;
use App\Models\Water\WaterPropertyTypeMstr;
use App\Models\Workflows\WfWorkflow;
use App\Repository\Water\Interfaces\iNewConnection;
use App\Traits\Payment\Razorpay;
use App\Traits\Ward;
use App\Traits\Workflow\Workflow;
use App\Traits\Property\SAF;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use League\CommonMark\Node\Block\Document;

/**
 * | -------------- Repository for the New Water Connection Operations ----------------------- |
 * | Created On-07-10-2022 
 * | Created By-Anshu Kumar
 * | Updated By-Sam kerketta
 */

class NewConnectionRepository implements iNewConnection
{
    use SAF;
    use Workflow;
    use Ward;
    use Razorpay;

    /**
     * | ----------------- Get Owner Type / Water ------------------------------- |
     * | @var ulbId 
     * | @var ward
     * | Operation : data fetched by table ulb_ward_masters 
     */
    public function getWardNo()
    {
        try {
            $ulbId = auth()->user()->ulb_id;
            $ward = $this->getAllWard($ulbId);
            return responseMsg(true, "Ward List!", $ward);
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }


    /**
     * | ----------------- Get Owner Type / Water ------------------------------- |
     * | @var ownerType 
     * | #request null
     * | Operation : data fetched by table water_owner_type_mstrs 
     */
    public function getOwnerType()
    {
        try {
            $ownerType = new WaterOwnerTypeMstr();  //<---------------- make object
            $ownerType = $ownerType->getallOwnwers();
            return response()->json(['status' => true, 'message' => 'data of the ownerType', 'data' => $ownerType]);
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }


    /**
     * | ----------------- Get Connection Type / Water ------------------------------- |
     * | @var connectionTypes 
     * | #request null
     * | Operation : data fetched by table water_connection_type_mstrs 
     */
    public function getConnectionType()
    {
        try {
            $connectionTypes = new WaterConnectionTypeMstr();       //<---------------- make object
            $connectionTypes = $connectionTypes->getConnectionType();
            return response()->json(['status' => true, 'message' => 'data of the connectionType', 'data' => $connectionTypes]);
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }



    /**
     * | ----------------- Get Connection Through / Water ------------------------------- |
     * | @var connectionThrough 
     * | #request null
     * | Operation : data fetched by table water_connection_through_mstrs 
     */
    public function getConnectionThrough()
    {
        try {
            $connectionThrough = new WaterConnectionThroughMstrs();     //<---------------- make object
            $connectionThrough = $connectionThrough->getAllThrough();
            return response()->json(['status' => true, 'message' => 'data of the connectionThrough', 'data' => $connectionThrough]);
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }


    /**
     * | ----------------- Get Property Type / Water ------------------------------- |
     * | @var propertyType 
     * | #request null
     * | Operation : data fetched by table water_property_type_mstrs 
     */
    public function getPropertyType()
    {
        try {
            $propertyType = new WaterPropertyTypeMstr();        //<---------------- make object
            $propertyType = $propertyType->getAllPropertyType();
            return response()->json(['status' => true, 'message' => 'data of the propertyType', 'data' => $propertyType]);
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }


    /**
     * | -------------  Apply for the new Application for Water Application ------------- |
     * | Edited by Sam Kerketta
     * | @param Request $req
     * | Post the value in Water Application table
     * | post the value in Water Applicants table by loop
     * ------------------------------------------------------------------------------------
     * | Generating the demand amount for the applicant in Water Connection Charges Table 
     */
    public function store(Request $req)
    {
        try {

            $vacantLand = Config::get('PropertyConstaint.VACANT_LAND');
            $wfWater = Config::get('workflow-constants.WATER_MASTER_ID');
            $ulbId = auth()->user()->ulb_id;

            # check the property type by saf no
            if ($req->saf_no != null) {

                $readPropetySafCheck = PropActiveSaf::select('prop_active_safs.prop_type_mstr_id')
                    ->where('prop_active_safs.saf_no', $req->saf_no)
                    ->get()
                    ->first();
                if ($readPropetySafCheck->prop_type_mstr_id == $vacantLand) {
                    return responseMsg(false, "water cannot be applied on Vacant land!", "");
                }
            }

            # check the property type by holding no  
            elseif ($req->holdingNo != null) {

                $readpropetyHoldingCheck = PropProperty::select('prop_properties.prop_type_mstr_id')
                    ->where('prop_properties.new_holding_no', $req->holdingNo)
                    ->get()
                    ->first();
                if ($readpropetyHoldingCheck->prop_type_mstr_id == $vacantLand) {
                    return responseMsg(false, "water cannot be applied on Vacant land!", "");
                }
            }

            DB::beginTransaction();

            $newApplication = new WaterApplication();
            $newApplication->connection_type_id = $req->connectionTypeId;
            $newApplication->property_type_id = $req->propertyTypeId;
            $newApplication->owner_type = $req->ownerType;
            $newApplication->category = $req->category;
            $newApplication->pipeline_type_id = $req->pipelineTypeId;
            $newApplication->ward_id = $req->wardId;
            $newApplication->area_sqft = $req->areaSqft;
            $newApplication->address = $req->address;
            $newApplication->landmark = $req->landmark;
            $newApplication->pin = $req->pin;
            $newApplication->flat_count = $req->flatCount;
            $newApplication->elec_k_no = $req->elecKNo;
            $newApplication->elec_bind_book_no = $req->elecBindBookNo;
            $newApplication->elec_account_no = $req->elecAccountNo;
            $newApplication->elec_category = $req->elecCategory;
            $newApplication->connection_through = $req->connection_through;
            $newApplication->apply_date = date('Y-m-d H:i:s');

            $ulbWorkflowId = WfWorkflow::where('wf_master_id', $wfWater)
                ->where('ulb_id', $ulbId)
                ->first();
            $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);
            $initiatorRoleId = DB::select($refInitiatorRoleId);

            $newApplication->workflow_id = $ulbWorkflowId->id;
            $newApplication->current_role = collect($initiatorRoleId)->first()->role_id;
            $newApplication->initiator = collect($initiatorRoleId)->first()->role_id;
            $newApplication->holding_no = $req->holdingNo;
            $newApplication->saf_no = $req->saf_no;

            # condition entry 
            if ($req->connection_through == 3) {
                $newApplication->id_proof = 3;
            }
            if (!is_null($req->holdingNo)) {
                $propertyId = new PropProperty();
                $propertyId = $propertyId->getPropertyId($req->holdingNo);
                $newApplication->prop_id = $propertyId->id;
            }
            if (!is_null($req->saf_no)) {
                $safId = new PropActiveSaf();
                $safId = $safId->getSafId($req->saf_no);
                $newApplication->saf_id = $safId->id;
            }

            # Generating Application No 
            $now = Carbon::now();
            $applicationNo = 'APP' . $now->getTimeStamp();
            $newApplication->application_no = $applicationNo;
            $newApplication->ulb_id = $ulbId;
            $newApplication->user_id = auth()->user()->id;
            $newApplication->save();

            # Water Applicants Owners
            $owner = $req['owners'];
            foreach ($owner as $owners) {
                $applicant = new WaterApplicant();
                $applicant->application_id = $newApplication->id;
                $applicant->applicant_name = $owners['ownerName'];
                $applicant->guardian_name = $owners['guardianName'];
                $applicant->mobile_no = $owners['mobileNo'];
                $applicant->email = $owners['email'];
                $applicant->save();
            }

            # Generating Demand and reflecting on water connection charges table
            $charges = new WaterConnectionCharge();
            $charges->application_id = $newApplication->id;
            $charges->charge_category = $req->connectionTypeId;
            $charges->paid_status = 0;
            $charges->status = 1;
            $penalty = $charges->penalty = '4000';
            $conn_fee = $charges->conn_fee = '7000';
            $charges->amount = $penalty + $conn_fee;
            $charges->save();

            DB::commit();
            return responseMsg(true, "Successfully Saved", $applicationNo);
        } catch (Exception $error) {
            DB::rollBack();
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }


    /**
     * | ----------------- Document verification processs ------------------------------- |
     * | @param Req <------------------------- (rourte)
     * | @var userId
     * | @var docStatus
     */
    public function waterDocStatus($req)
    {
        try {
            $userId = auth()->user()->id;

            $docStatus = WaterApplicantDoc::find($req->id);
            $docStatus->remarks = $req->docRemarks;
            $docStatus->verified_by_emp_id = $userId;
            $docStatus->verified_on = Carbon::now();
            $docStatus->updated_at = Carbon::now();

            if ($req->docStatus == 'Verified') {                        //<------------ (here data type small int)        
                $docStatus->verify_status = 1;
            }
            if ($req->docStatus == 'Rejected') {                        //<------------ (here data type small int)
                $docStatus->verify_status = 2;
            }

            $docStatus->save();

            return responseMsg(true, "Successfully Done", '');
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }


    /**
     * |--------- Get the Water Connection charges Details for Logged In user ------------ |
     * | @param Req
     * | @var userId
     * | @var connections
     */
    public function getUserWaterConnectionCharges(Request $req)
    {
        try {
            $userId = auth()->user()->id;
            $connections = WaterApplication::join('water_connection_charges', 'water_applications.id', '=', 'water_connection_charges.application_id')
                ->join('water_applicants', 'water_applicants.application_id', '=', 'water_applications.id')
                ->select(
                    'water_connection_charges.application_id AS applicationId',
                    'water_applications.application_no',
                    'water_applicants.applicant_name AS appplicantName',
                    'water_connection_charges.amount',
                    'water_connection_charges.paid_status',
                    'water_connection_charges.status',
                    'water_connection_charges.penalty',
                    'water_connection_charges.conn_fee',
                )
                ->where('water_applications.user_id', '=', $userId)
                ->orwhere('water_applications.id', $req->applicationId)
                ->get();
            return responseMsg(true, "", $connections);
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }
}
