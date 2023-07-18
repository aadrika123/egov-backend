<?php

namespace App\Repository\Property\Concrete;

use App\MicroServices\DocUpload;
use App\MicroServices\IdGenerator\PrefixIdGenerator;
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
use App\Models\PropActiveObjectionDtl;
use App\Models\PropActiveObjectionFloor;
use App\Models\Workflows\WfActiveDocument;
use App\Models\Property\MPropForgeryType;
use App\Models\WorkflowTrack;
use App\Repository\Property\Concrete\PropertyBifurcation;
use Illuminate\Http\Request;
use stdClass;

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
            $user = authUser($request);
            $userId = $user->id;
            $ulbId = $request->ulbId ?? $user->ulb_id;
            $userType = $user->user_type;
            $objectionFor = $request->objectionFor;
            $tracks = new WorkflowTrack();
            $objParamId = Config::get('PropertyConstaint.OBJ_PARAM_ID');
            $objectionNo = "";
            $objNo = "";

            $ulbWorkflowId = WfWorkflow::where('wf_master_id', $this->_workflow_id_clerical)
                ->where('ulb_id', $ulbId)
                ->first();

            $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);            // Get Current Initiator ID
            $refFinisherRoleId = $this->getFinisherId($ulbWorkflowId->id);              // Get Finisher ID
            $initiatorRoleId = DB::select($refInitiatorRoleId);
            $finisherRoleId = DB::select($refFinisherRoleId);

            DB::beginTransaction();
            if ($objectionFor == "Clerical Mistake") {

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
                $objection->last_role_id = collect($initiatorRoleId)->first()->role_id;
                $objection->finisher_role_id = collect($finisherRoleId)->first()->role_id;

                if ($userType == 'Citizen') {
                    $objection->current_role = collect($initiatorRoleId)->first()->forward_role_id;
                    $objection->initiator_role_id = collect($initiatorRoleId)->first()->forward_role_id;      // Send to DA in Case of Citizen
                    $objection->last_role_id = collect($initiatorRoleId)->first()->forward_role_id;
                    $objection->user_id = null;
                    $objection->citizen_id = $userId;
                    $objection->doc_upload_status = 1;
                }
                $objection->save();

                //objection No through id generation
                $idGeneration = new PrefixIdGenerator($objParamId, $objection->ulb_id);
                $objectionNo = $idGeneration->generate();

                # Flag : call model <---------- 
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

                //name document
                # call a funcion for the file uplode 
                if ($file = $request->file('nameDoc')) {

                    $docUpload = new DocUpload;
                    $mWfActiveDocument = new WfActiveDocument();
                    $relativePath = Config::get('PropertyConstaint.OBJECTION_RELATIVE_PATH');
                    $refImageName = $request->nameCode;
                    $refImageName = $objection->id . '-' . str_replace(' ', '_', $refImageName);
                    $document = $request->nameDoc;
                    $imageName = $docUpload->upload($refImageName, $document, $relativePath);

                    $metaReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
                    $metaReqs['activeId'] = $objection->id;
                    $metaReqs['workflowId'] = $objection->workflow_id;
                    $metaReqs['ulbId'] = $objection->ulb_id;
                    $metaReqs['document'] = $imageName;
                    $metaReqs['relativePath'] = $relativePath;
                    $metaReqs['docCode'] = $request->nameCode;

                    $metaReqs = new Request($metaReqs);
                    $mWfActiveDocument->postDocuments($metaReqs);
                }

                // //address document 
                if ($file = $request->file('addressDoc')) {

                    $docUpload = new DocUpload;
                    $mWfActiveDocument = new WfActiveDocument();
                    $relativePath = Config::get('PropertyConstaint.OBJECTION_RELATIVE_PATH');
                    $refImageName = $request->addressCode;
                    $refImageName = $objection->id . '-' . str_replace(' ', '_', $refImageName);
                    $document = $request->addressDoc;
                    $imageName = $docUpload->upload($refImageName, $document, $relativePath);

                    $addressReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
                    $addressReqs['activeId'] = $objection->id;
                    $addressReqs['workflowId'] = $objection->workflow_id;
                    $addressReqs['ulbId'] = $objection->ulb_id;
                    $addressReqs['relativePath'] = $relativePath;
                    $addressReqs['document'] = $imageName;
                    $addressReqs['docCode'] = $request->addressCode;

                    $addressReqs = new Request($addressReqs);
                    $mWfActiveDocument->postDocuments($addressReqs);
                }
            }

            //objection for forgery 
            if ($objectionFor == 'Forgery') {
                // return $request;

                # Flag : call model <----------
                $ulbWorkflowId = WfWorkflow::where('wf_master_id', $this->_workflow_id_forgery)
                    ->where('ulb_id', $ulbId)
                    ->first();

                $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);            // Get Current Initiator ID
                $initiatorRoleId = DB::select($refInitiatorRoleId);

                # Flag : call model <----------
                $objection = new PropActiveObjection;
                $objection->ulb_id = $ulbId;
                $objection->user_id = $userId;
                $objection->objection_for =  $objectionFor;
                $objection->property_id = $request->propId;
                $objection->remarks = $request->remarks;
                $objection->date = Carbon::now();
                $objection->created_at = Carbon::now();
                $objection->workflow_id =  $ulbWorkflowId->id;
                $objection->applicant_name =  $request->applicantName;
                $objection->forgery_type_mstr_id =  $request->forgeryTypeMstrId;
                $objection->current_role = collect($initiatorRoleId)->first()->role_id;
                $objection->initiator_role_id = collect($initiatorRoleId)->first()->role_id;
                $objection->finisher_role_id = collect($finisherRoleId)->first()->role_id;
                if ($userType == 'Citizen') {
                    $objection->current_role = collect($initiatorRoleId)->first()->forward_role_id;
                    $objection->initiator_role_id = collect($initiatorRoleId)->first()->forward_role_id;      // Send to DA in Case of Citizen
                    $objection->last_role_id = collect($initiatorRoleId)->first()->forward_role_id;
                    $objection->user_id = null;
                    $objection->citizen_id = $userId;
                    // $objection->doc_upload_status = 1;
                }
                $objection->save();

                $idGeneration = new PrefixIdGenerator($objParamId, $objection->ulb_id);
                $objectionNo = $idGeneration->generate();

                PropActiveObjection::where('id', $objection->id)
                    ->update(['objection_no' => $objectionNo]);

                $owner = $request->owners;
                //saving objection owner details
                foreach ($owner as $owners) {
                    $mPropActiveObjectionOwner = new PropActiveObjectionOwner();
                    $mPropActiveObjectionOwner->objection_id = $objection->id;
                    $mPropActiveObjectionOwner->prop_owner_id = $owners['ownerId'] ?? null;
                    $mPropActiveObjectionOwner->gender = $owners['gender'] ?? null;
                    $mPropActiveObjectionOwner->owner_name = $owners['ownerName'] ?? null;
                    $mPropActiveObjectionOwner->owner_mobile = $owners['mobileNo'] ?? null;
                    $mPropActiveObjectionOwner->aadhar = $owners['aadhar'] ?? null;
                    $mPropActiveObjectionOwner->dob = $owners['dob'] ?? null;
                    $mPropActiveObjectionOwner->guardian_name = $owners['guardianName'] ?? null;
                    $mPropActiveObjectionOwner->relation = $owners['relation'] ?? null;
                    $mPropActiveObjectionOwner->pan = $owners['pan'] ?? null;
                    $mPropActiveObjectionOwner->email = $owners['email'] ?? null;
                    $mPropActiveObjectionOwner->is_armed_force = $owners['isArmedForce'] ?? false;
                    $mPropActiveObjectionOwner->is_specially_abled = $owners['isSpeciallyAbled'] ?? false;
                    $mPropActiveObjectionOwner->created_at = Carbon::now();
                    $mPropActiveObjectionOwner->save();

                    if (isset($owners['ownerId']) && !$owners['ownerId']) {

                        $docUpload = new DocUpload;
                        $mWfActiveDocument = new WfActiveDocument();
                        $relativePath = Config::get('PropertyConstaint.OBJECTION_RELATIVE_PATH');
                        $refImageName = 'FORGERY_OWNER_DOC';
                        $refImageName = $objection->id . '-' . str_replace(' ', '_', $refImageName);
                        $document = $owners['ownerDoc'];
                        $imageName = $docUpload->upload($refImageName, $document, $relativePath);

                        $metaReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
                        $metaReqs['activeId'] = $objection->id;
                        $metaReqs['workflowId'] = $objection->workflow_id;
                        $metaReqs['ulbId'] = $objection->ulb_id;
                        $metaReqs['document'] = $imageName;
                        $metaReqs['relativePath'] = $relativePath;
                        $metaReqs['docCode'] = 'FORGERY_OWNER_DOC';

                        $metaReqs = new Request($metaReqs);
                        $mWfActiveDocument->postDocuments($metaReqs);
                    }
                }

                $documents = $request->documents;
                if (collect($documents)->isNotEmpty()) {
                    foreach ($documents as $document) {
                        $docUpload = new DocUpload;
                        $mWfActiveDocument = new WfActiveDocument();
                        $relativePath = Config::get('PropertyConstaint.OBJECTION_RELATIVE_PATH');
                        $refImageName = $document['docCode'];
                        $refImageName = $objection->id . '-' . str_replace(' ', '_', $refImageName);
                        $documents = $document['doc'];
                        $imageName = $docUpload->upload($refImageName, $documents, $relativePath);

                        $metaReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
                        $metaReqs['activeId'] = $objection->id;
                        $metaReqs['workflowId'] = $objection->workflow_id;
                        $metaReqs['ulbId'] = $objection->ulb_id;
                        $metaReqs['document'] = $imageName;
                        $metaReqs['relativePath'] = $relativePath;
                        $metaReqs['docCode'] = $document['docCode'];

                        $reqs = new Request($metaReqs);
                        $mWfActiveDocument->postDocuments($reqs);
                    }
                }
            }

            // Objection Against Assessment
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
                $objection->initiator_role_id = collect($initiatorRoleId)->first()->role_id;
                $objection->finisher_role_id = collect($finisherRoleId)->first()->role_id;

                if ($userType == 'Citizen') {
                    $objection->current_role = collect($initiatorRoleId)->first()->forward_role_id;
                    $objection->initiator_role_id = collect($initiatorRoleId)->first()->forward_role_id;      // Send to DA in Case of Citizen
                    $objection->last_role_id = collect($initiatorRoleId)->first()->forward_role_id;
                    $objection->user_id = null;
                    $objection->citizen_id = $userId;
                    // $objection->doc_upload_status = 1;
                }
                $objection->save();

                if (is_string($request->assessmentData)) {
                    $request->assessmentData = json_decode($request->assessmentData);
                }
                $abc = $request->assessmentData;
                $a = collect($abc);

                // return $request;
                foreach ($a as $otid) {
                    if ($otid instanceof stdClass) {
                        $otid = (array)$otid;
                    }
                    $assement_error = new PropActiveObjectionDtl;
                    $assement_error->objection_id = $objection->id;
                    $assement_error->objection_type_id = $otid['id'];
                    // $assement_error->applicant_data =  $otid['value'];

                    $assesmentDetail = $this->assesmentDetails($request);
                    $assesmentData = collect($assesmentDetail);

                    if ($otid['id'] == 2) {
                        $assement_error->data_ref_type = 'boolean';
                        $objection->objection_type_id = 2;
                        $assessmmtData = collect($assesmentData['isWaterHarvesting']);
                        $assement_error->assesment_data =  $assessmmtData->first();
                    }
                    //road width
                    if ($otid['id'] == 3) {
                        $assement_error->data_ref_type = 'road_width';
                        $objection->objection_type_id = 3;
                        $assessmmtData = collect($assesmentData['road_width']);
                        $assement_error->assesment_data =  $assessmmtData->first();
                    }
                    //property_types
                    if ($otid['id'] == 4) {
                        $assement_error->data_ref_type = 'ref_prop_types.id';
                        $objection->objection_type_id = 4;
                        $assessmmtData = collect($assesmentData['prop_type_mstr_id']);
                        $assement_error->assesment_data =  $assessmmtData->first();
                    }
                    //area off plot
                    if ($otid['id'] == 5) {
                        $assement_error->data_ref_type = 'area';
                        $objection->objection_type_id = 5;
                        $assessmmtData = collect($assesmentData['areaOfPlot']);
                        $assement_error->assesment_data =  $assessmmtData->first();
                    }
                    //rwh date
                    if ($otid['id'] == 8) {
                        $assement_error->data_ref_type = 'date';
                        $objection->objection_type_id = 8;
                        $assessmmtData = collect($assesmentData['rwh_date_from']);
                        $assement_error->assesment_data =  $assessmmtData->first();
                    }
                    $assement_error->applicant_data = $otid['value'] ?? null;
                    $assement_error->save();
                }

                //objection No through id generation
                $idGeneration = new PrefixIdGenerator($objParamId, $objection->ulb_id);
                $objectionNo = $idGeneration->generate();

                PropActiveObjection::where('id', $objection->id)
                    ->update(['objection_no' => $objectionNo]);

                $floorData = $request->floorData;
                if (is_string($floorData)) {
                    $floorData = json_decode($floorData);
                }
                $floor = $floorData;
                $floor = collect($floor);
                foreach ($floor as $floors) {
                    if ($floors instanceof stdClass) {
                        $floors = (array)$floors;
                    }
                    $assement_floor = new PropActiveObjectionFloor;
                    $assement_floor->property_id = $request->propId;
                    $assement_floor->objection_id = $objection->id;
                    $assement_floor->prop_floor_id = $floors['propFloorId'];
                    $assement_floor->floor_mstr_id = $floors['floorNo'];
                    $assement_floor->usage_type_mstr_id = $floors['usageType'];

                    $assement_floor->occupancy_type_mstr_id = $floors['occupancyType'];
                    $assement_floor->const_type_mstr_id = $floors['constructionType'];
                    $assement_floor->builtup_area = $floors['buildupArea'];
                    if ($floors['usageType'] == 1)
                        $assement_floor->carpet_area = $floors['buildupArea'] * 0.70;
                    else
                        $assement_floor->carpet_area = $floors['buildupArea'] * 0.80;
                    $assement_floor->date_from = $floors->dateFrom ?? null;
                    $assement_floor->date_upto = $floors->dateUpto ?? null;
                    $assement_floor->save();
                }
            }
            if ($request->document) {
                $docUpload = new DocUpload;
                $mWfActiveDocument = new WfActiveDocument();
                $relativePath = Config::get('PropertyConstaint.OBJECTION_RELATIVE_PATH');
                $refImageName = $request->docCode;
                $refImageName = $objection->id . '-' . str_replace(' ', '_', $refImageName);
                $document = $request->document;

                $imageName = $docUpload->upload($refImageName, $document, $relativePath);
                $metaReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
                $metaReqs['activeId'] = $objection->id;
                $metaReqs['workflowId'] = $objection->workflow_id;
                $metaReqs['ulbId'] = $objection->ulb_id;
                $metaReqs['document'] = $imageName;
                $metaReqs['relativePath'] = $relativePath;
                $metaReqs['docCode'] = $request->docCode;

                $metaReqs = new Request($metaReqs);
                $mWfActiveDocument->postDocuments($metaReqs);

                PropActiveObjection::where('id', $objection->id)
                    ->update(['doc_upload_status' => 1]);
            }
            $wfReqs['workflowId'] = $ulbWorkflowId->id;
            $wfReqs['refTableDotId'] = 'prop_active_objections.id';
            $wfReqs['refTableIdValue'] = $objection->id;
            $wfReqs['ulb_id'] = $objection->ulb_id;
            $wfReqs['user_id'] = $userId;
            if ($userType == 'Citizen') {
                $wfReqs['citizenId'] = $userId;
                $wfReqs['user_id'] = NULL;
            }
            $wfReqs['receiverRoleId'] = $objection->current_role;
            $wfReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
            $request->request->add($wfReqs);
            $tracks->saveTrack($request);
            DB::commit();

            return responseMsgs(true, "Successfully Applied Application", $objectionNo, '010801', '01', '382ms-547ms', 'Post', '');
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json($e->getMessage());
        }
    }

    //assesment detail
    public function assesmentDetails($request)
    {
        $assesmentDetails = PropProperty::select(
            'rwh_date_from',
            'road_width',
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
            ->leftjoin('prop_floors', 'prop_floors.property_id', '=', 'prop_properties.id')
            ->leftjoin('ref_prop_types', 'ref_prop_types.id', '=', 'prop_properties.prop_type_mstr_id')
            ->leftjoin('ref_prop_road_types', 'ref_prop_road_types.id', '=', 'prop_properties.road_type_mstr_id')
            ->get();

        foreach ($assesmentDetails as $assesmentDetailss) {
            $assesmentDetailss['floor'] = PropProperty::select(
                'ref_prop_floors.floor_name as floorNo',
                'ref_prop_usage_types.usage_type as usageType',
                'ref_prop_occupancy_types.occupancy_type as occupancyType',
                'ref_prop_construction_types.construction_type as constructionType',
                'prop_floors.builtup_area as buildupArea',
                'prop_floors.id',
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
        return $assesmentDetailss;
    }
}
