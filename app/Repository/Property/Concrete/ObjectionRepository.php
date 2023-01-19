<?php

namespace App\Repository\Property\Concrete;

use App\MicroServices\DocUpload;
use App\Models\CustomDetail;
use App\Models\Property\PropOwner;
use Exception;
use App\Repository\Property\Interfaces\iObjectionRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Traits\Workflow\Workflow as WorkflowTrait;
use Illuminate\Support\Facades\Config;
use App\Models\Property\PropActiveObjection;
use App\Models\Property\PropActiveObjectionOwner;
use App\Traits\Property\Objection;
use App\Models\Workflows\WfWorkflow;
use App\Models\Property\PropProperty;
use App\Models\Property\PropObjectionLevelpending;
use Illuminate\Support\Facades\Redis;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Models\PropActiveObjectionDtl;
use App\Models\PropActiveObjectionFloor;
use App\Models\PropActiveObjectionDocdtl;
use App\Models\Workflows\WfActiveDocument;
use App\Models\WorkflowTrack;
use App\Repository\Property\Concrete\PropertyBifurcation;
use App\Repository\WorkflowMaster\Concrete\WorkflowMap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * | Created On-20-11-2022
 * | Created By-Mrinal Kumar
 * | -----------------------------------------------------------------------------------------
 * | Objection Module all operations 
 * | --------------------------- Workflow Parameters ---------------------------------------
 * | CLERICAL Master ID=36                | Assesment Master ID=56              | Forgery Master ID=79
 * | CLERICAL WorkflowID=169              | Assesment Workflow ID=183           | Forgery Workflow ID=212
 */

class ObjectionRepository implements iObjectionRepository
{
    use Objection;
    use WorkflowTrait;
    private $_objectionNo;
    private $_bifuraction;
    private $_workflow_id_assesment;
    private $_workflow_id_clerical;
    private $_workflow_id_forgery;

    public function __construct()

    {
        /**
         | change the underscore for the reference var
         */
        $this->_bifuraction = new PropertyBifurcation();
        $this->_workflow_id_clerical = Config::get('workflow-constants.PROPERTY_OBJECTION_CLERICAL');
        $this->_workflow_id_assesment = Config::get('workflow-constants.PROPERTY_OBJECTION_ASSESSMENT');
        $this->_workflow_id_forgery = Config::get('workflow-constants.PROPERTY_OBJECTION_FORGERY');
    }



    //apply objection
    public function applyObjection($request)
    {
        try {
            $userId = authUser()->id;
            $ulbId = auth()->user()->ulb_id;
            $userType = auth()->user()->user_type;
            $objectionFor = $request->objectionFor;
            $objectionNo = "";
            $objNo = "";

            $ulbWorkflowId = WfWorkflow::where('wf_master_id', $this->_workflow_id_clerical)
                ->where('ulb_id', $ulbId)
                ->first();

            $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);            // Get Current Initiator ID
            $refFinisherRoleId = $this->getFinisherId($ulbWorkflowId->id);              // Get Finisher ID
            $initiatorRoleId = DB::select($refInitiatorRoleId);
            $finisherRoleId = DB::select($refFinisherRoleId);

            if ($objectionFor == "Clerical Mistake") {
                DB::beginTransaction();

                //saving objection details
                # Flag : call model <-----
                $objection = new PropActiveObjection();
                $objection->ulb_id = $ulbId;
                $objection->user_id = $userId;
                $objection->objection_for =  $objectionFor;
                $objection->property_id = $request->propId;
                $objection->remarks = $request->remarks;
                $objection->date = Carbon::now();
                $objection->created_at = Carbon::now();
                $objection->workflow_id = $ulbWorkflowId->id;
                $objection->current_role = $initiatorRoleId[0]->role_id;
                $objection->initiator_role_id = collect($initiatorRoleId)->first()->role_id;
                $objection->finisher_role_id = collect($finisherRoleId)->first()->role_id;
                $objection->save();

                //objection No generation in model
                $objNo = new PropActiveObjection();
                $objectionNo = $objNo->objectionNo($objection->id);

                PropActiveObjection::where('id', $objection->id)
                    ->update(['objection_no' => $objectionNo]);

                //saving objection owner details
                # Flag : call model <----------
                $objectionOwner = new PropActiveObjectionOwner();
                $objectionOwner->objection_id = $objection->id;
                $objectionOwner->prop_owner_id = $request->ownerId;
                $objectionOwner->owner_name = $request->ownerName;
                $objectionOwner->owner_mobile = $request->mobileNo;
                $objectionOwner->corr_address = $request->corrAddress;
                $objectionOwner->corr_city = $request->corrCity;
                $objectionOwner->corr_dist = $request->corrDist;
                $objectionOwner->corr_pin_code = $request->corrPinCode;
                $objectionOwner->corr_state = $request->corrState;
                $objectionOwner->created_at = Carbon::now();
                $objectionOwner->save();


                $this->docUpload($request, $objection, $objectionNo);


                //name document
                # call a funcion for the file uplode 
                if ($file = $request->file('nameDoc')) {
                    $docName = "nameDoc";
                    $name = $this->moveFile($docName, $file);

                    $objectionDoc = new PropActiveObjectionDocdtl();
                    $objectionDoc->objection_id = $objection->id;
                    $this->citizenDocUpload($objectionDoc, $name, $docName);
                }

                // //address document 
                if ($file = $request->file('addressDoc')) {
                    $docName = "addressDoc";
                    $name = $this->moveFile($docName, $file);

                    $objectionDoc = new PropActiveObjectionDocdtl;
                    $objectionDoc->objection_id = $objection->id;
                    $this->citizenDocUpload($objectionDoc, $name, $docName);
                }

                // //saf doc
                if ($file = $request->file('safMemberDoc')) {
                    $docName = "safMemberDoc";
                    $name = $this->moveFile($docName, $file);

                    $objectionDoc = new PropActiveObjectionDocdtl;
                    $objectionDoc->objection_id = $objection->id;
                    $this->citizenDocUpload($objectionDoc, $name, $docName);
                }
            }

            //objection for forgery 
            if ($objectionFor == 'Forgery') {

                $ulbWorkflowId = WfWorkflow::where('wf_master_id', $this->_workflow_id_forgery)
                    ->where('ulb_id', $ulbId)
                    ->first();

                $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);            // Get Current Initiator ID
                $initiatorRoleId = DB::select($refInitiatorRoleId);

                $objection = new PropActiveObjection;
                $objection->ulb_id = $ulbId;
                $objection->user_id = $userId;
                $objection->objection_for =  $objectionFor;
                $objection->property_id = $request->propId;
                $objection->remarks = $request->remarks;
                $objection->date = Carbon::now();
                $objection->created_at = Carbon::now();
                $objection->workflow_id =  $ulbWorkflowId->id;
                $objection->current_role = collect($initiatorRoleId)->first()->role_id;
                $objection->save();

                //objection_form
                if ($file = $request->file('objFormDoc')) {
                    $docName = "objectionForm";
                    $name = $this->moveFile($docName, $file);

                    $objectionDoc = new PropActiveObjectionDocdtl;
                    $objectionDoc->objection_id = $objection->id;
                    $objectionDoc->remarks = $request->objRemarks;
                    $this->citizenDocUpload($objectionDoc, $name, $docName);
                }

                //Evidence Doc
                if ($file = $request->file('objEvidenceDoc')) {
                    $docName = "evidenceDoc";
                    $name = $this->moveFile($docName, $file);

                    $objectionDoc = new PropActiveObjectionDocdtl;
                    $objectionDoc->objection_id = $objection->id;
                    $objectionDoc->remarks = $request->objRemarks;
                    $this->citizenDocUpload($objectionDoc, $name, $docName);
                }
                return responseMsg(true, "Successfully Saved", '');
            }

            // objection against assesment
            if ($objectionFor == 'Assessment Error') {
                // return $request;
                $ulbWorkflowId = WfWorkflow::where('wf_master_id', $this->_workflow_id_assesment)
                    ->where('ulb_id', $ulbId)
                    ->first();
                $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);            // Get Current Initiator ID
                $initiatorRoleId = DB::select($refInitiatorRoleId);

                $objection = new PropActiveObjection;
                $objection->objection_for =  $objectionFor;
                $objection->ulb_id = $ulbId;
                $objection->user_id = $userId;
                $objection->property_id = $request->propId;
                $objection->remarks = $request->remarks;
                $objection->date = Carbon::now();
                $objection->created_at = Carbon::now();
                $objection->workflow_id = $ulbWorkflowId->id;
                $objection->current_role = $initiatorRoleId[0]->role_id;
                $objection->save();

                $abc =  json_decode($request->assessmentData);
                $a = collect($abc);

                // return $request;
                foreach ($a as $otid) {

                    $assement_error = new PropActiveObjectionDtl;
                    $assement_error->objection_id = $objection->id;
                    $assement_error->objection_type_id = $otid->id;
                    $assement_error->applicant_data =  $otid->value;

                    $assesmentDetail = $this->assesmentDetails($request);
                    $assesmentData = collect($assesmentDetail);

                    if ($otid->id == 2) {
                        $assement_error->data_ref_type = 'boolean';
                        $objection->objection_type_id = 2;
                        $assement_error->assesment_data = collect($assesmentData['original']['data']['isWaterHarvesting']);
                    }
                    //road width
                    if ($otid->id == 3) {
                        $assement_error->data_ref_type = 'ref_prop_road_types.id';
                        $objection->objection_type_id = 3;
                        $assement_error->assesment_data = collect($assesmentData['original']['data']['road_type_mstr_id']);
                    }
                    //property_types
                    if ($otid->id == 4) {
                        $assement_error->data_ref_type = 'ref_prop_types.id';
                        $objection->objection_type_id = 4;
                        $assement_error->assesment_data = collect($assesmentData['original']['data']['prop_type_mstr_id']);
                    }
                    //area off plot
                    if ($otid->id == 5) {
                        $assement_error->data_ref_type = 'area';
                        $objection->objection_type_id = 5;
                        $assement_error->assesment_data = collect($assesmentData['original']['data']['areaOfPlot']);
                    }
                    //mobile tower
                    if ($otid->id == 6) {
                        $assement_error->data_ref_type = 'boolean';
                        $objection->objection_type_id = 6;
                        $assement_error->assesment_data = collect($assesmentData['original']['data']['isMobileTower']);
                        // $assement_error->applicant_area = $otid->area;
                        // $assement_error->applicant_date = $otid->date;
                    }
                    //hoarding board
                    if ($otid->id == 7) {
                        $assement_error->data_ref_type = 'boolean';
                        $objection->objection_type_id = 7;
                        $assement_error->assesment_data = collect($assesmentData['original']['data']['isHoarding']);
                        // $assement_error->applicant_area = $otid->area;
                        // $assement_error->applicant_date = $otid->date;
                    }

                    if (isset($otid->area) && ($otid->date)) {
                        $assement_error->applicant_area = $otid->area;
                        $assement_error->applicant_date = $otid->date;
                    }
                    $assement_error->save();
                }

                //objection No generation in model
                $objNo = new PropActiveObjection();
                $objectionNo = $objNo->objectionNo($objection->id);

                PropActiveObjection::where('id', $objection->id)
                    ->update(['objection_no' => $objectionNo]);

                $floorData = $request->floorData;
                $floor = json_decode($floorData);
                $floor = collect($floor);

                foreach ($floor as $floors) {
                    $assement_floor = new PropActiveObjectionFloor;
                    $assement_floor->property_id = $request->propId;
                    $assement_floor->objection_id = $objection->id;
                    $assement_floor->prop_floor_id = $floors->propFloorId;
                    $assement_floor->floor_mstr_id = $floors->floorNo;
                    $assement_floor->usage_type_mstr_id = $floors->usageType;
                    $assement_floor->occupancy_type_mstr_id = $floors->occupancyType;
                    $assement_floor->const_type_mstr_id = $floors->constructionType;
                    $assement_floor->builtup_area = $floors->buildupArea;
                    $assement_floor->date_from = $floors->dateFrom;
                    $assement_floor->date_upto = $floors->dateUpto;
                    $assement_floor->save();
                }

                //objection_form
                if ($file = $request->file('objFormDoc')) {
                    $docName = "objectionForm";
                    $name = $this->moveFile($docName, $file);

                    $objectionDoc = new PropActiveObjectionDocdtl;
                    $objectionDoc->objection_id = $objection->id;
                    $objectionDoc->remarks = $request->objRemarks;
                    $this->citizenDocUpload($objectionDoc, $name, $docName);
                }

                //Evidence Doc
                if ($file = $request->file('objEvidenceDoc')) {
                    $docName = "evidenceDoc";
                    $name = $this->moveFile($docName, $file);

                    $objectionDoc = new PropActiveObjectionDocdtl;
                    $objectionDoc->objection_id = $objection->id;
                    $objectionDoc->remarks = $request->objRemarks;
                    $this->citizenDocUpload($objectionDoc, $name, $docName);
                }
            }
            DB::commit();

            return responseMsgs(true, "Successfully Saved", $objectionNo, '010801', '01', '382ms-547ms', 'Post', '');
        } catch (Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    //assesment detail
    public function assesmentDetails($request)
    {
        try {
            $assesmentDetails = PropProperty::select(
                'is_hoarding_board as isHoarding',
                'hoarding_area',
                'hoarding_installation_date',
                'is_water_harvesting as isWaterHarvesting',
                'is_mobile_tower as isMobileTower',
                'tower_area',
                'tower_installation_date',
                'area_of_plot as areaOfPlot',
                'property_type as propertyType',
                'road_type_mstr_id',
                'road_type as roadType',
                'prop_type_mstr_id'
            )
                ->where('prop_properties.id', $request->propId)
                ->join('prop_floors', 'prop_floors.property_id', '=', 'prop_properties.id')
                ->join('ref_prop_types', 'ref_prop_types.id', '=', 'prop_properties.prop_type_mstr_id')
                ->join('ref_prop_road_types', 'ref_prop_road_types.id', '=', 'prop_properties.road_type_mstr_id')
                ->get();
            foreach ($assesmentDetails as $assesmentDetailss) {
                $assesmentDetailss['floor'] = PropProperty::select(
                    'ref_prop_floors.floor_name as floorNo',
                    'ref_prop_usage_types.usage_type as usageType',
                    'ref_prop_occupancy_types.occupancy_type as occupancyType',
                    'ref_prop_construction_types.construction_type as constructionType',
                    'prop_floors.builtup_area as buildupArea',
                    'prop_floors.date_from as dateFrom',
                    'prop_floors.date_upto as dateUpto',
                )
                    ->where('prop_properties.id', $request->propId)
                    ->join('prop_floors', 'prop_floors.property_id', '=', 'prop_properties.id')
                    ->join('ref_prop_floors', 'ref_prop_floors.id', '=', 'prop_floors.floor_mstr_id')
                    ->join('ref_prop_usage_types', 'ref_prop_usage_types.id', '=', 'prop_floors.usage_type_mstr_id')
                    ->join('ref_prop_occupancy_types', 'ref_prop_occupancy_types.id', '=', 'prop_floors.occupancy_type_mstr_id')
                    ->join('ref_prop_construction_types', 'ref_prop_construction_types.id', '=', 'prop_floors.const_type_mstr_id')
                    ->get();
            }
            if (isset($assesmentDetailss)) {
                return responseMsgs(true, "Successfully Retrieved", remove_null($assesmentDetailss), '010804', '01', '332ms-367ms', 'Post', '');
            } else {
                return responseMsg(false, "Supply Valid Property Id", "");
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    // /**
    //  * | Forward Or BackWard Application
    //  * | @param $req
    //  */
    // public function postNextLevel($req)
    // {
    //     try {
    //         DB::beginTransaction();

    //         // $levelPending = new PropObjectionLevelpending();
    //         // $levelPending->objection_id = $req->objectionId;
    //         // $levelPending->sender_role_id = $req->senderRoleId;
    //         // $levelPending->receiver_role_id = $req->receiverRoleId;
    //         // $levelPending->sender_user_id = auth()->user()->id;
    //         // $levelPending->save();
    //         $metaReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
    //         $metaReqs['workflowId'] = Config::get('workflow-constants.PROPERTY_OBJECTION_CLERICAL');
    //         $metaReqs['refTableDotId'] = 'prop_active_objections.id';
    //         $metaReqs['refTableIdValue'] = $req->objectionId;
    //         $req->request->add($metaReqs);

    //         $track = new WorkflowTrack();
    //         $track->saveTrack($req);

    //         // objection Application Update Current Role Updation
    //         $objection = PropActiveObjection::find($req->objectionId);
    //         $objection->current_role = $req->receiverRoleId;
    //         $objection->save();

    //         // Add Comment On Prop Level Pending  and Verification Status true
    //         // $ObjLevelPending = new PropObjectionLevelpending();
    //         // $commentOnlevel = $ObjLevelPending->getCurrentObjByReceiver($req->objectionId, $req->senderRoleId);

    //         // $commentOnlevel->remarks = $req->comment;
    //         // $commentOnlevel->verification_status = 1;
    //         // $commentOnlevel->save();

    //         DB::commit();
    //         return responseMsgs(true, "Successfully Forwarded The Application!!", "", '010810', '01', '474ms-573', 'Post', '');
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         return responseMsg(false, $e->getMessage(), "");
    //     }
    // }


    //get objection list
    public function objectionDocList($req)
    {
        try {
            $list = PropActiveObjectionDocdtl::select(
                'id',
                'doc_type as docName',
                'doc_name as docUrl',
                'relative_path',
                'verify_status as docStatus',
                'remarks as docRemarks'
            )
                ->where('prop_active_objection_docdtls.objection_id', $req->id)
                ->orderByDesc('prop_active_objection_docdtls.id')
                ->get();

            $list = $list->map(function ($val) {
                $path = $this->_bifuraction->readDocumentPath($val->relative_path . $val->docUrl);
                $val->docUrl = $path;
                return $val;
            });

            return responseMsgs(true, "Successfully Done", remove_null($list), '010815', '01', '329ms-400ms', 'Post', '');
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }


    //objectionn document upload 
    public function objectionDocUpload($req)
    {
        try {
            $validator = Validator::make($req->all(), [
                // 'nameDoc' => 'max:2000',
                // 'addressDoc' => 'max:2000',
                // 'safMemberDoc' => 'max:2000',
                // 'objectionFormDoc' => 'max:2000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 401);
            }
            // return $req;
            // $doc['nameDoc'] = $req->nameDoc;
            // $doc['addressDoc'] = $req->addressDoc;
            // $doc['safMemberDoc'] = $req->safMemberDoc;

            // foreach ($req->doc as  $documents) {

            //     $doc = array_key_last($documents);
            //     $base64Encode = base64_encode($documents[$doc]->getClientOriginalName());
            //     $extention = $documents[$doc]->getClientOriginalExtension();
            //     $imageName = time() . '-' . $base64Encode . '.' . $extention;
            //     $documents[$doc]->storeAs('public/objection/' . $doc, $imageName);

            //     $appDoc = new PropActiveObjectionDocdtl();
            //     $appDoc->objection_id = $req->objectionId;
            //     $appDoc->doc_name = $imageName;
            //     $appDoc->relative_path = ('objection/' . $doc . '/');
            //     $appDoc->doc_type = $doc;
            //     $appDoc->save();
            // }


            //for address doc
            // return $doc = key($req->nameDoc);


            if ($file = $req->file('addressDoc')) {
                $docName = "addressDoc";
                $name = $this->moveFile($docName, $file);

                $checkExisting = PropActiveObjectionDocdtl::where('objection_id', $req->id)
                    ->where('doc_type', $docName)
                    ->get()
                    ->first();
                if ($checkExisting) {
                    $this->updateDocument($req, $name,  $docName);
                } else {
                    $this->saveObjectionDoc($req, $name,  $docName);
                }
            }

            // saf doc
            if ($file = $req->file('safMemberDoc')) {
                $docName = "safMemberDoc";
                $name = $this->moveFile($docName, $file);

                $checkExisting = PropActiveObjectionDocdtl::where('objection_id', $req->id)
                    ->where('doc_type', $docName)
                    ->get()
                    ->first();
                if ($checkExisting) {
                    $this->updateDocument($req, $name,  $docName);
                } else {
                    $this->saveObjectionDoc($req, $name,  $docName);
                }
            }

            if ($file = $req->file('nameDoc')) {
                $docName = "nameDoc";
                $name = $this->moveFile($docName, $file);

                $checkExisting = PropActiveObjectionDocdtl::where('objection_id', $req->id)
                    ->where('doc_type', $docName)
                    ->get()
                    ->first();
                if ($checkExisting) {
                    $this->updateDocument($req, $name,  $docName);
                } else {
                    $this->saveObjectionDoc($req, $name,  $docName);
                }
            }

            if ($file = $req->file('objectionFormDoc')) {
                $docName = "objectionFormDoc";
                $name = $this->moveFile($docName, $file);

                $checkExisting = PropActiveObjectionDocdtl::where('objection_id', $req->id)
                    ->where('doc_type', $docName)
                    ->get()
                    ->first();
                if ($checkExisting) {
                    $this->updateDocument($req, $name,  $docName);
                } else {
                    $this->saveObjectionDoc($req, $name,  $docName);
                }
            }
            return responseMsgs(true, "Document Successfully Uploaded!", '', '010816', '01', '364ms-389ms', 'Post', '');
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    //citizen doc upload
    public function citizenDocUpload($objectionDoc, $name, $docName)
    {
        $userId = auth()->user()->id;

        $objectionDoc->doc_type = $docName;
        $objectionDoc->relative_path = ('/objection/' . $docName . '/');
        $objectionDoc->doc_name = $name;
        $objectionDoc->user_id = $userId;
        $objectionDoc->date = Carbon::now();
        $objectionDoc->created_at = Carbon::now();
        $objectionDoc->save();
    }

    //save objection
    public function saveObjectionDoc($req, $name,  $docName)
    {
        $objectionDoc =  new PropActiveObjectionDocdtl();
        $objectionDoc->objection_id = $req->id;
        $objectionDoc->doc_type = $docName;
        $objectionDoc->relative_path = ('/objection/' . $docName . '/');
        $objectionDoc->doc_name = $name;
        $objectionDoc->status = 1;
        $objectionDoc->date = Carbon::now();
        $objectionDoc->created_at = Carbon::now();
        $objectionDoc->save();
    }



    public function updateDocument($req, $name,  $docName)
    {
        PropActiveObjectionDocdtl::where('objection_id', $req->id)
            ->where('doc_type', $docName)
            ->update([
                'objection_id' => $req->id,
                'doc_type' => $docName,
                'relative_path' => ('/objection/' . $docName . '/'),
                'doc_name' => $name,
                'status' => 1,
                'verify_status' => 0,
                'remarks' => '',
                'updated_at' => Carbon::now()
            ]);
    }

    //moving function to location
    public function moveFile($docName, $file)
    {
        $name = time() . $docName . '.' . $file->getClientOriginalExtension();
        $path = storage_path('app/public/objection/' . $docName . '/');
        $file->move($path, $name);

        return $name;
    }


    /**
     * =================================================================
     */

    public function docUpload($req, $objection, $objectionNo)
    {
        $req->validate([
            "document" => "required|mimes:pdf,jpeg,png,jpg,gif",
            "docMstrId" => "required|numeric",
            // "ownerId" => "nullable|numeric",
            "docRefName" => "required"
        ]);

        try {
            $metaReqs = array();
            $docUpload = new DocUpload;
            $mWfActiveDocument = new WfActiveDocument();
            // $mActiveSafs = new PropActiveSaf();
            $relativePath = Config::get('PropertyConstaint.OBJECTION_RELATIVE_PATH');
            // $getSafDtls = $mActiveSafs->getSafNo($objection->id);
            $refImageName = $req->docRefName;
            $refImageName = $objection->id . '-' . str_replace(' ', '_', $refImageName);
            $document = $req->document;
            $imageName = $docUpload->upload($refImageName, $document, $relativePath);

            $metaReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
            $metaReqs['activeId'] = $objectionNo;
            $metaReqs['workflowId'] = $objection->workflow_id;
            $metaReqs['ulbId'] = $objection->ulb_id;
            $metaReqs['relativePath'] = $relativePath;
            $metaReqs['image'] = $imageName;
            $metaReqs['docMstrId'] = $req->docMstrId;
            // $metaReqs['ownerDtlId'] = $req->ownerId;
            $metaReqs = new Request($metaReqs);
            $mWfActiveDocument->postDocuments($metaReqs);
            return responseMsgs(true, "Document Uploadation Successful", "", "010201", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010201", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }
}
