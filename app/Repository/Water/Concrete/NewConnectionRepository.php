<?php

namespace App\Repository\Water\Concrete;

use App\Models\Water\WaterApplicant;
use App\Models\Water\WaterApplicantDoc;
use App\Models\Water\WaterApplication;
use App\Models\Water\WaterConnectionCharge;
use App\Repository\Water\Interfaces\iNewConnection;
use Carbon\Carbon;
use DateTime;
use Exception;
use Hamcrest\Arrays\IsArray;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


/**
 * | -------------- Repository for the New Water Connection Operations ----------------------- |
 * | Created On-07-10-2022 
 * | Created By-Anshu Kumar
 */

class NewConnectionRepository implements iNewConnection
{
    private $refPropertyType = 4; //<-------------------- here 
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
        #   validation
        $validateUser = Validator::make(
            $req->all(),
            [
                'connectionTypeId'   => 'required|integer',
                'propertyTypeId'     => 'required|integer',
                'ownerType'          => 'required',
                'category'           => 'required',
                'pipelineTypeId'     => 'required|integer',
                'wardId'             => 'required|integer',
                'areaSqft'           => 'required|integer',
                'address'            => 'required',
                'landmark'           => 'required',
                'pin'                => 'required|integer',
                'flatCount'          => 'required|integer',
                'elecKNo'            => 'required',
                'elecBindBookNo'     => 'required',
                'elecAccountNo'      => 'required',
                'elecCategory'       => 'required',
                'connection_through' => 'required|integer',
            ]
        );

        if ($validateUser->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'validation error',
                'errors' => $validateUser->errors()
            ], 401);
        }


        # check the property type by saf no
        if ($req->saf_no != null) {

            $readPropetySafCheck = DB::table('active_safs')
                ->select('active_safs.prop_type_mstr_id')
                ->join('prop_m_property_types', 'prop_m_property_types.id', '=', 'active_safs.prop_type_mstr_id')
                ->where('active_safs.saf_no', $req->saf_no)
                ->get()
                ->first();
            if ($readPropetySafCheck->prop_type_mstr_id == $this->refPropertyType) //<---------- 4 for the vacand land
            {
                return responseMsg(false, "water cannot be applied on Vacant land!", "");
            }
        }

        # check the property type by holding no  
        if ($req->holdingNo != null) {

            $readpropetyHoldingCheck = DB::table('active_safs')
                ->select('active_safs.prop_type_mstr_id')
                ->join('prop_m_property_types', 'prop_m_property_types.id', '=', 'active_safs.prop_type_mstr_id')
                ->join('prop_properties', 'prop_properties.saf_id', '=', 'active_safs.id')
                ->where('prop_properties.new_holding_no', $req->holdingNo)
                ->get()
                ->first();
            if ($readpropetyHoldingCheck->prop_type_mstr_id == $this->refPropertyType) //<------------- 4 for the vacand land
            {
                return responseMsg(false, "water cannot be applied on Vacant land!", "");
            }
        }

        DB::beginTransaction();
        try {
            $newApplication = new WaterApplication();
            $newApplication->connection_type_id = $req->connectionTypeId;
            $newApplication->property_type_id = $req->propertyTypeId;
            $newApplication->owner_type = $req->ownerType;
            $newApplication->category = $req->category;
            // $newApplication->proof_document_id = $req->proofDocumentId;
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
            $newApplication->holding_no = $req->holdingNo;
            $newApplication->saf_no = $req->saf_no;

            # connection through condition
            if ($req->connection_through == 3) {
                $newApplication->id_proof = 3;
            }

            // Generating Application No 
            $now = Carbon::now();
            $applicationNo = 'APP' . $now->getTimeStamp();
            $newApplication->application_no = $applicationNo;
            $newApplication->ulb_id = auth()->user()->ulb_id;
            $newApplication->citizen_id = auth()->user()->id;
            $newApplication->save();

            // Water Applicants Owners
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

            // Generating Demand and reflecting on water connection charges table
            $charges = new WaterConnectionCharge();
            $charges->application_id = $newApplication->id;
            $charges->charge_category = $req->connectionTypeId;
            $charges->paid_status = 0;
            $charges->status = 1;
            $penalty = $charges->penalty = '4000';
            $conn_fee = $charges->conn_fee = '7000';
            $charges->amount = $penalty + $conn_fee;
            $charges->save();

            // DB::commit(); //<----------- reminder 
            return responseMsg(true, "Successfully Saved", $applicationNo);
        } catch (Exception $e) {
            DB::rollBack();
            return  $e;
        }
    }

    /**
     * |--------- Get the Water Connection charges Details for Logged In user ------------ |
     * | @param Request $req
     */
    public function getUserWaterConnectionCharges(Request $req)
    {
        try {
            $citizen_id = auth()->user()->id;
            $connections = DB::table('water_applications')
                ->join('water_connection_charges', 'water_applications.id', '=', 'water_connection_charges.application_id')
                ->select(
                    'water_connection_charges.application_id',
                    'water_applications.application_no',
                    'water_connection_charges.amount',
                    'water_connection_charges.paid_status',
                    'water_connection_charges.status',
                    'water_connection_charges.penalty',
                    'water_connection_charges.conn_fee'
                )
                ->where('water_applications.citizen_id', '=', $citizen_id)
                ->get();
            return $connections;
        } catch (Exception $e) {
            return $e;
        }
    }

    /**
     * | -------- Water Payment ----------------------------------------------------------- |
     * | @param Request
     * | @Requests ------------------------------------------------------------------------ |
     * | #application_id > Applicant Id for Payment
     * | ---------------------------------------------------------------------------------- |
     * | #waterConnectionCharge > Finds the ApplicationID
     * | @return responseMsg
     */
    public function waterPayment(Request $req)
    {
        try {
            $waterConnectionCharge = WaterConnectionCharge::where('application_id', $req->applicationId)
                ->first();
            if ($waterConnectionCharge) {
                $waterConnectionCharge->paid_status = 1;
                $waterConnectionCharge->save();
                return responseMsg(true, "Payment Done Successfully", "");
            } else {
                return responseMsg(false, "ApplicationId Not found", "");
            }
        } catch (Exception $e) {
            return $e;
        }
    }


    /**
     * | ----------------- Document Upload for the Applicant ------------------------------- |
     * | @param Request
     * | @param Request $req
     * | #documents[] > contains all the documents upload to be
     */
    public function applicantDocumentUpload(Request $req)
    {
        DB::beginTransaction();
        try {
            $document = $req['documents'];
            foreach ($document as $documents) {
                $appDoc = new WaterApplicantDoc();
                $appDoc->application_id = $documents['applicationId'];
                $appDoc->document_id = $documents['documentId'];
                $appDoc->doc_for = $documents['docFor'];
                $appDoc->save();
            }
            DB::commit();
            return responseMsg(true, "Document Successfully Uploaded", "");
        } catch (Exception $e) {
            DB::rollBack();
            return $e;
        }
    }

    /**
     * | code : Sam Kerketta
     * | ----------------- Get Connection Type / Water ------------------------------- |
     * | @var connectionTypes 
     * | #request null
     * | Operation : data fetched by table water_connection_type_mstrs 
     */
    public function getConnectionType()
    {
        try {
            $connectionTypes = DB::table('water_connection_type_mstrs')
                ->select('water_connection_type_mstrs.id', 'water_connection_type_mstrs.connection_type')
                ->where('status', 1)
                ->get();
            // $collection = collect($connectionTypes)->map(function ($value) {
            //     return $value->connection_type;
            // });
            // $type['connectionType'] = $collection;
            return response()->json(['status' => true, 'message' => 'data of the connectionType', 'data' => $connectionTypes]);
        } catch (Exception $e) {
            return $e;
        }
    }

    /**
     * | code : Sam Kerketta
     * | ----------------- Get Connection Through / Water ------------------------------- |
     * | @var connectionThrough 
     * | #request null
     * | Operation : data fetched by table water_connection_through_mstrs 
     */
    public function getConnectionThrough()
    {
        try {
            $connectionThrough = DB::table('water_connection_through_mstrs')
                ->select('water_connection_through_mstrs.id', 'water_connection_through_mstrs.connection_through')
                ->where('status', 1)
                ->orderBy('id')
                ->get();
            return response()->json(['status' => true, 'message' => 'data of the connectionThrough', 'data' => $connectionThrough]);
        } catch (Exception $e) {
            return $e;
        }
    }

    /**
     * | code : Sam Kerketta
     * | ----------------- Get Property Type / Water ------------------------------- |
     * | @var propertyType 
     * | #request null
     * | Operation : data fetched by table water_property_type_mstrs 
     */
    public function getPropertyType()
    {
        try {
            $propertyType = DB::table('water_property_type_mstrs')
                ->select('water_property_type_mstrs.id', 'water_property_type_mstrs.property_type')
                ->where('status', 1)
                ->get();
            return response()->json(['status' => true, 'message' => 'data of the propertyType', 'data' => $propertyType]);
        } catch (Exception $e) {
            return $e;
        }
    }

    /**
     * | code : Sam Kerketta
     * | ----------------- Get Owner Type / Water ------------------------------- |
     * | @var ownerType 
     * | #request null
     * | Operation : data fetched by table water_owner_type_mstrs 
     */
    public function getOwnerType()
    {
        try {
            $ownerType = DB::table('water_owner_type_mstrs')
                ->select('water_owner_type_mstrs.id', 'water_owner_type_mstrs.owner_type')
                ->where('status', 1)
                ->get();
            return response()->json(['status' => true, 'message' => 'data of the ownerType', 'data' => $ownerType]);
        } catch (Exception $e) {
            return $e;
        }
    }

    /**
     * | code : Sam Kerketta
     * | ----------------- Get Owner Type / Water ------------------------------- |
     * | @var wfmaster 
     * | @var ward
     * | #request null
     * | Operation : data fetched by table ulb_ward_masters 
     */
    public function getWardNo()
    {
        $wfmaster = 3;  //<--------------- this is fot the water ie. 3 is the wfmasterid create a constant
        try {
            $ward = DB::table('ulb_ward_masters')
                ->select('ulb_ward_masters.id', 'ulb_ward_masters.ward_name')
                ->join('wf_workflows', 'wf_workflows.ulb_id', '=', 'ulb_ward_masters.ulb_id')
                ->where('wf_workflows.wf_master_id', $wfmaster)
                ->get();

            return response()->json(['status' => true, 'message' => 'data of the Ward NO', 'data' => $ward]);
        } catch (Exception $e) {
            return $e;
        }
    }






    // /**
    //  * | code : Sam Kerketta
    //  * | ----------------- Final Applicant approval structure ------------------------------- |
    //  * | @param Request
    //  * | @param Request $req
    //  * | #application > contains all the data of the applicant
    //  * | #request > contain all data of the applicant provided by the fontend
    //  * | Operation : data is to be deleted from application and store in approval and consumer table
    //  */
    // public function approvalOfApplication(Request $request)
    // {
    //     DB::beginTransaction();
    //     try {
    //         if ($request['approved_status'] = true) {

    //             $application = WaterApplication::query()     //<------------- operation req
    //                 ->where('id', $request->safId)
    //                 ->first();
    //             $approvedSaf = $application->replicate();
    //             $approvedSaf->setTable('water_approved_application_details');
    //             $approvedSaf->setTable('water_consumers');  //<---------- (Caution) operation may not be used
    //             $approvedSaf->id = $application->id;
    //             $approvedSaf->consumer_no = rand(10); //<------------- (Reminder) Default generated consumerId here
    //             $approvedSaf->push();
    //             $application->delete();  //<--------------- (Caution)perform after all the process
    //         }
    //         DB::commit();
    //         return response()->json(["status" => true, "message" => "Application Successfully Approved", "data" => ""]);
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         return response()->json([$e]);
    //     }
    // }

    // /**
    //  * | ----------------- Applicant reject structure ------------------------------- |
    //  * | @param Request
    //  * | @param Request $req
    //  * | #application > contains all the data of the applicant
    //  * | #rejectedSaf > data to be push 
    //  * | #request > contain all data of the applicant provided by the fontend
    //  * | Operation : data is to be deleted from application and store in reject   
    //  */
    // public function rejectionOfApplication(Request $request)
    // {
    //     # code...
    //     DB::beginTransaction();
    //     try {
    //         if ($request['reject_status'] = true) {

    //             $application = WaterApplication::query()    //<------------ operation of rejection
    //                 ->where('id', $request->safId)
    //                 ->first();
    //             $rejectedSaf = $application->replicate();
    //             $rejectedSaf->setTable('Water_rejected_application_details');
    //             $rejectedSaf->id = $application->id;
    //             $rejectedSaf->push();
    //             $application->delete();   //<-------------- (Caution) maybe the status will also be changed
    //             $msg = "Application Rejected Successfully";
    //         }
    //         DB::commit();
    //         return response()->json(["status" => true, "message" => $msg]);
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         return response()->json([$e]);
    //     }
    // }

    // /**
    //  * | ----------------- Applicant level approval structure ------------------------------- |
    //  * | @param Request
    //  * | @param Request $req
    //  * | #user
    //  * | #ulbId
    //  * | #levelPending
    //  * | #reciverRole
    //  */
    // public function updationOflevel(Request $request)
    // {
    //     DB::beginTransaction();
    //     $user = auth()->user()->id;
    //     $ulbId = auth()->user()->ulb_id;
    //     try {
    //         #   previous level pending verification updation
    //         $levelPending = WaterlevelPending::where('saf_id', $request->safId)  //<---------- Make the Model
    //             ->where('verification_status', 0)
    //             ->first();
    //         $levelPending->verification_status = 1;
    //         $levelPending->remarks = $request->remarks;
    //         $levelPending->save();

    //         #   reciver role_id may not be provided 
    //         $reciverRole = WfRole::select('forward_role_id')    // <------------------ Make the Model
    //             ->join('wf_roleusermaps', 'wf_roleusermaps.user_id', '=', 'users.id')
    //             ->join('wf_roles', 'wf_roles.id', '=', 'wf_roleusermaps.wf_roleid')
    //             ->where('users.ulb_id', $ulbId)
    //             ->where('user.id', $user)
    //             ->get();

    //         #   data to be added in the waterlevelPending as it is passsed farward
    //         $levelPending = new WaterlevelPending();    // <----------- Make the Model
    //         $levelPending->saf_id = $request->safId;
    //         $levelPending->sender_role_id = $request->senderRoleId;    // <------------- here
    //         $levelPending->receiver_role_id = $reciverRole;    // <------------- here
    //         $levelPending->sender_user_id = auth()->user()->id;
    //         $levelPending->save();

    //         DB::commit();
    //         return responseMsg(true, "Forwarded The Application", "");
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         return responseMsg(false, $e->getMessage(), $request->all());
    //     }
    // }

    // /**
    //  * | ----------------- Applicant level Backword structure ------------------------------- |
    //  * | @param Request
    //  * | @param Request $req
    //  * | #user
    //  * | #ulbId
    //  * | #levelPending
    //  * | #reciverRole
    //  */
    // public function reverseInLevel(Request $request)
    // {
    //     DB::beginTransaction();
    //     $user = auth()->user()->id;
    //     $ulbId = auth()->user()->ulb_id;
    //     try {
    //         #   previous level pending verification updation
    //         $levelPending = WaterlevelPending::where('saf_id', $request->safId)  //<---------- Make the Model
    //             ->where('verification_status', 0)
    //             ->first();
    //         $levelPending->verification_status = 1;
    //         $levelPending->remarks = $request->remarks;
    //         $levelPending->save();

    //         #   reciver role_id may not be provided in case of backward
    //         $reciverRole = WfRole::select('backward_role_id')    // <------------------ Make the Model
    //             ->join('wf_roleusermaps', 'wf_roleusermaps.user_id', '=', 'users.id')
    //             ->join('wf_roles', 'wf_roles.id', '=', 'wf_roleusermaps.wf_roleid')
    //             ->where('users.ulb_id', $ulbId)
    //             ->where('user.id', $user)
    //             ->get();

    //         #   data to be added in the waterlevelPending as it is passsed farward
    //         $levelPending = new WaterlevelPending();    // <----------- Make the Model
    //         $levelPending->saf_id = $request->safId;
    //         $levelPending->sender_role_id = $request->senderRoleId;    // <------------- here
    //         $levelPending->receiver_role_id = $reciverRole;    // <------------- here
    //         $levelPending->sender_user_id = auth()->user()->id;
    //         $levelPending->save();

    //         DB::commit();
    //         return responseMsg(true, "Forwarded The Application", "");
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         return responseMsg(false, $e->getMessage(), $request->all());
    //     }
    // }

    // /**
    //  * | ----------------- Inbox ------------------------------- |
    //  * | @param Request
    //  * | @param Request $request
    //  * | #user
    //  * | #ulbId
    //  * | #roles
    //  * | #data
    //  */
    // public function showDataInbox(Request $request)
    // {
    //     #   auth of the user list
    //     $user = auth()->user()->id;
    //     $ulbId = auth()->user()->ulb_id;

    //     $roles = DB::table('wf_roles')
    //         ->select('id')
    //         ->join('wf_roleusermaps', 'wf_roleusermaps.user_id', '=', 'users.id')
    //         ->join('wf_roles', 'wf_roles.id', '=', 'wf_roleusermaps.wf_roleid')
    //         ->where('users.ulb_id', $ulbId)
    //         ->where('users.id', $user)
    //         ->get();

    //     try {
    //         $data = WaterApplication::select('water_application.*')
    //             ->join('water_level_pendings', 'water_level_pendings.saf_id', '=', 'water_application.id') //<---------- prop_level_pendings will be replaced by the water_level_pending
    //             ->where('water_level_pendings.reciver_role_id', $roles)  //<--------- prop_level_pending replace it
    //             ->where('verification_status', 0)
    //             ->where('status', 1)
    //             ->get();
    //         return response()->json(["data" => $data, "status" => true, "message" => "Data Available", 200]);
    //     } catch (Exception $e) {
    //         return response()->json($e, 400);
    //     }
    // }

    // /**
    //  * | ----------------- Outbox ------------------------------- |
    //  * | @param Request
    //  * | @param Request $request
    //  * | #user
    //  * | #ulbId
    //  * | #roles
    //  * | #data
    //  */
    // public function getDataInOutbox()
    // {
    //     #   auth of the user list
    //     $user = auth()->user()->id;
    //     $ulbId = auth()->user()->ulb_id;

    //     $roles = WfRole::select('id')   //<---------- create the Model here
    //         ->join('wf_roleusermaps', 'wf_roleusermaps.user_id', '=', 'users.id')
    //         ->join('wf_roles', 'wf_roles.id', '=', 'wf_roleusermaps.wf_roleid')
    //         ->where('users.ulb_id', $ulbId)
    //         ->where('user.id', $user)
    //         ->get();

    //     try {
    //         $data = WaterApplication::select('water_application.*')
    //             ->join('water_level_pendings', 'water_level_pendings.saf_id', '=', 'water_application.id')    //<----- prop_level_pending will be replaced by water_level_pending
    //             ->where('water_level_pendings.sender_role_id', $roles)  //<------ prop_level_pending will replace
    //             ->where('verification_status', 0)   //<------------- Codition may not be req.
    //             ->where('verification_status', 1)   //<------------- Condition may not be req.
    //             ->where('status', '=', 1)
    //             ->get();

    //         return response()->json(["data" => $data, "status" => true, "message" => "Data Available", 200]);
    //     } catch (Exception $e) {
    //         return response()->json($e, 400);
    //     }
    // }


    // /**
    //  * | ----------------- proerty Owner Detail By Saf No ------------------------------- |
    //  * | @param Req $request
    //  * | @var readPropertyOwnerDetails
    //  */
    // public function propertyOwnerDetailsBySafNo(Request $req)
    // {
    //     // property details according to safNo
    //     try {
    //         $readPropertyOwnerDetails = DB::table('active_safs')
    //             ->select(
    //                 'active_safs_owner_dtls.owner_name AS name',
    //                 'active_safs_owner_dtls.mobile_no AS phoneNo',
    //                 'active_safs_owner_dtls.email AS email',
    //                 'active_safs_owner_dtls.gender AS gender',
    //                 'active_safs_owner_dtls.id AS id'
    //             )
    //             ->join('active_safs_owner_dtls', 'active_safs_owner_dtls.saf_id', '=', 'active_safs.id')
    //             ->where('active_safs.saf_no', $req->saf_no)
    //             ->get();
    //         return responseMsg(true, "owner detail", $readPropertyOwnerDetails);
    //     } catch (Exception $e) {
    //         return $e;
    //     }
    // }

    // /**
    //  * | ----------------- proerty Owner Detail By Holding No ------------------------------- |
    //  * | @param Req $request
    //  * | @var readPropertyOwnerDetails
    //  */
    // public function propertyOwnerDetailsByHoldingNo(Request $req)
    // {
    //     try {
    //         $readPropertyOwnerDetails = DB::table('active_safs')
    //             ->select(
    //                 'active_safs_owner_dtls.owner_name AS name',
    //                 'active_safs_owner_dtls.mobile_no AS phoneNo',
    //                 'active_safs_owner_dtls.email AS email',
    //                 'active_safs_owner_dtls.gender AS gender',
    //                 'active_safs_owner_dtls.id AS id'
    //             )
    //             ->join('active_safs_owner_dtls', 'active_safs_owner_dtls.saf_id', '=', 'prop_properties.saf_id')
    //             ->where('prop_properties.new_holding_no', $req->holdingNo)
    //             ->get();
    //         return responseMsg(true, "owner detail", $readPropertyOwnerDetails);
    //     } catch (Exception $e) {
    //         return $e;
    //     }
    // }
}
