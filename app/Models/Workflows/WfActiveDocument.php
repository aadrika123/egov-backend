<?php

namespace App\Models\Workflows;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class WfActiveDocument extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $connection = 'pgsql_master';

    /**
     * | Store Wf Active Documents
     */
    public function store(array $req)
    {
        WfActiveDocument::create($req);
    }

    /**
     * | Edit Wf Active Documents
       | Reference Function : docUpload
     */
    public function edit($wfActiveDocument, array $req)
    {
        $wfActiveDocument->update($req);
    }

    /**
     * | Meta Request function for updation and post the request
       | Common Function
     */
    public function metaReqs($req)
    {
        return [
            "active_id" => $req->activeId,
            "workflow_id" => $req->workflowId,
            "ulb_id" => $req->ulbId,
            "module_id" => $req->moduleId,
            "relative_path" => $req->relativePath,
            "document" => $req->document,
            // "uploaded_by" => Auth()->user()->id,
            // "uploaded_by_type" => Auth()->user()->user_type,
            "remarks" => $req->remarks ?? null,
            "doc_code" => $req->docCode,
            "owner_dtl_id" => $req->ownerDtlId,
            "doc_category" => $req->docCategory ?? null,
            "unique_id" => $req->uniqueId ?? null,
            "reference_no" => $req->referenceNo ?? null,
            "status"    => 1,
            "verify_status" => 0
        ];
    }


    /**
     * | Meta Request function for updation and post the request
       | Reference Function : uploadDocument
     */
    public function metaReq($req)
    {
        // return [
        //     "active_id" => $req->activeId,
        //     "workflow_id" => $req->workflowId,
        //     "ulb_id" => $req->ulbId,
        //     "module_id" => $req->moduleId,
        //     "relative_path" => $req->relativePath,
        //     "document" => $req->document,
        //     "uploaded_by" =>  Auth()->user()->id,
        //     "uploaded_by_type" => Auth()->user()->user_type,
        //     "remarks" => $req->remarks ?? null,
        //     "doc_code" => $req->docCode,
        //     "owner_dtl_id" => $req->ownerDtlId,
        //     "doc_category" => $req->docCategory ?? null
        // ];
        return [
            "active_id" => $req['activeId'],
            "workflow_id" => $req['workflowId'],
            "ulb_id" => $req['ulbId'],
            "module_id" => $req['moduleId'],
            // "relative_path" => $req['relativePath'],
            //"document" => $req['document1111']??null,
            // "uploaded_by" =>  Auth()->user()->id,
            // "uploaded_by_type" => Auth()->user()->user_type,
            "remarks" => $req->remarks ?? null,
            "doc_code" => $req['docCode'],
            "owner_dtl_id" => $req['ownerDtlId'],
            "doc_category" => $req['docCategory'] ?? null,
            "unique_id" => $req['uniqueId'] ?? null,
            "reference_no" => $req['referenceNo'] ?? null,
        ];
    }

    /**
     * | Post Workflow Document
       | Common Function
     */
    public function postDocuments($req)
    {
        $metaReqs = $this->metaReqs($req);
        if (isset($req->verifyStatus)) {
            $metaReqs = array_merge($metaReqs, [
                "verify_status" => $req->verifyStatus
            ]);
        }
        $var =  WfActiveDocument::create($metaReqs);
        return $var;
    }

    /**
     * | Edit Existing Document
       | Common Function
     */
    public function editDocuments($wfActiveDocument, $req)
    {
        $metaReqs = $this->metaReqs($req);
        $wfActiveDocument->update($metaReqs);
    }

    /**
     * | Check if the document is already existing or not
     */
    public function ifDocExists($activeId, $workflowId, $moduleId, $docCode, $ownerId = null)
    {
        return WfActiveDocument::where('active_id', $activeId)
            ->where('workflow_id', $workflowId)
            ->where('module_id', $moduleId)
            ->where('doc_code', $docCode)
            ->where('owner_dtl_id', $ownerId)
            ->where('verify_status', 0)
            ->where('status', 1)
            ->first();
    }

    /**
     * | Check if the Doc Category already Existing or not
       | Common Function
     */
    public function isDocCategoryExists($activeId, $workflowId, $moduleId, $docCategory, $ownerId = null)
    {
        return WfActiveDocument::where('active_id', $activeId)
            ->where('workflow_id', $workflowId)
            ->where('module_id', $moduleId)
            ->where('doc_category', $docCategory)
            ->where('owner_dtl_id', $ownerId)
            ->where('status', 1);
    }


    /**
     * | Get Application Details by Application No
       | Common Function
     */
    public function getDocsByAppId($applicationId, $workflowId, $moduleId)
    {
        $docUrl = Config::get('module-constants.DOC_URL');
        return DB::table('wf_active_documents as d')
            ->select(
                'd.id',
                'd.document',
                DB::raw("concat('$docUrl/',relative_path,'/',document) as doc_path"),
                'd.remarks',
                'd.verify_status',
                'd.doc_code',
                'o.owner_name',
                'unique_id',
                'reference_no',

            )
            ->leftJoin('prop_active_safs_owners as o', 'o.id', '=', 'd.owner_dtl_id')
            ->where('d.active_id', $applicationId)
            ->where('d.workflow_id', $workflowId)
            ->where('d.module_id', $moduleId)
            ->get();
    }

    /**
     * | Get Owner PhotoGraph By applicationId
       | Common Function
     */
    public function getOwnerPhotograph($applicationId, $workflowId, $moduleId, $ownerId)
    {
        $docUrl = Config::get('module-constants.DOC_URL');
        return DB::table('wf_active_documents as d')
            ->select(
                'd.verify_status',
                'd.id as doc_id',
                'doc_code',
                'owner_name',
                DB::raw("concat('$docUrl/',relative_path,'/',document) as doc_path"),
                'unique_id',
                'reference_no',
            )
            ->leftJoin('prop_active_safs_owners as o', 'o.id', '=', 'd.owner_dtl_id')
            ->where('d.active_id', $applicationId)
            ->where('d.workflow_id', $workflowId)
            ->where('d.module_id', $moduleId)
            ->where('doc_code', 'PHOTOGRAPH')
            ->where('owner_dtl_id', $ownerId)
            ->first();
    }

    /**
     * | Get Owner PhotoGraph By applicationId Water Module
       | Reference Function : getOwnerDocLists
     */
    public function getWaterOwnerPhotograph($applicationId, $workflowId, $moduleId, $ownerId, $docCode = null)
    {
        $secondConnection = 'pgsql_water';
        return DB::connection($secondConnection)
            ->table('wf_active_documents as d')
            ->select(
                'd.verify_status',
                DB::raw("CONCAT(relative_path, '/', document) as doc_path")
            )
            ->join('water_applicants as o', 'o.id', '=', 'd.owner_dtl_id')
            ->where('d.active_id', $applicationId)
            ->where('d.workflow_id', $workflowId)
            ->where('d.module_id', $moduleId)
            ->where('d.doc_code', ($docCode ? $docCode : 'CONSUMER_PHOTO'))
            ->where('d.owner_dtl_id', $ownerId)
            ->first();
    }

    /** 
     * | water document View
       | Reference Function : getUploadDocuments
    */
    public function getWaterDocsByAppNo($applicationId, $workflowId, $moduleId)
    {
        $secondConnection = 'pgsql_water';
        return DB::connection($secondConnection)
            ->table('wf_active_documents as d')
            ->select(
                'd.id',
                'd.document',
                DB::raw("concat(relative_path,'/',document) as ref_doc_path"),
                'd.remarks',
                'd.verify_status',
                'd.doc_code',
                'd.doc_category',
                'd.status',
                'o.applicant_name as owner_name',
                'unique_id',
                'reference_no',
            )
            ->leftJoin('water_applicants as o', 'o.id', '=', 'd.owner_dtl_id')
            ->where('d.active_id', $applicationId)
            ->where('d.workflow_id', $workflowId)
            ->where('d.module_id', $moduleId)
            ->where('d.status', '!=', 0)
            ->get();
    }

    /** 
     * | water document View
    */
    public function getWaterDocsByAppNov1($applicationId, $workflowId, $moduleId)
    {
        $secondConnection = 'pgsql_master';
        return DB::connection($secondConnection)
            ->table('wf_active_documents as d')
            ->select(
                'd.id',
                'd.document',
                DB::raw("concat(relative_path,'/',document) as ref_doc_path"),
                'd.remarks',
                'd.verify_status',
                'd.doc_code',
                'd.doc_category',
                'd.status',
                'unique_id',
                'reference_no',
            )
            ->where('d.active_id', $applicationId)
            ->where('d.workflow_id', $workflowId)
            ->where('d.module_id', $moduleId)
            ->where('d.status', '!=', 0)
            ->get();
    }

    //prity pandey
    /** 
     * | Fetches and returns consumer documents for a specific application from the pgsql_water 
     * | database based on application, workflow, and module IDs
       | Common Function
    */
    public function getConsumerDocsByAppNo($applicationId, $workflowId, $moduleId)
    {
        $secondConnection = 'pgsql_water';
        return DB::connection($secondConnection)
            ->table('wf_active_documents as d')
            ->select(
                'd.id',
                'd.document',
                DB::raw("concat(relative_path,'/',document) as ref_doc_path"),
                'd.remarks',
                'd.verify_status',
                'd.doc_code',
                'd.doc_category',
                'd.status',
                'unique_id',
                'reference_no',
            )
            ->where('d.active_id', $applicationId)
            ->where('d.workflow_id', $workflowId)
            ->where('d.module_id', $moduleId)
            ->where('d.status', '!=', 0)
            ->get();
    }

    /** 
     * | Retrieves trade-related document details for a given application from the 
     * | pgsql_trade database, including DMS file paths and owner information
       | Common Function
    */
    public function getTradeDocByAppNo($applicationId, $workflowId, $moduleId)
    {
        $dms = Config::get('module-constants.DMS_URL');
        return DB::connection("pgsql_trade")->table('wf_active_documents as d')
            ->select(
                'd.id',
                // DB::raw("concat(d.relative_path,'/',d.document) as doc_path"),
                DB::raw("concat('$dms/',relative_path,'/',document) as doc_path"),
                'd.remarks',
                'd.verify_status',
                'd.doc_code as doc_for',
                'd.doc_code',
                'o.owner_name',
                'unique_id',
                'reference_no',
            )
            ->leftJoin('active_trade_owners as o', 'o.id', '=', 'd.owner_dtl_id')
            ->where('d.active_id', $applicationId)
            ->where('d.workflow_id', $workflowId)
            ->where('d.module_id', $moduleId)
            ->where('d.status', 1)
            ->get();
    }

    /**
     * | Document Verify Reject
       | Common Function
     */
    public function docVerifyReject($id, $req)
    {
        $document = WfActiveDocument::find($id);
        $document->update($req);
    }

    /**
     * | Get Uploaded Document by document mstr id and application No
     */
    public function getAppByAppNoDocId($applicationNo, $docId)
    {
        return DB::table('wf_active_documents as d')
            ->select(
                'd.id',
                'dr.doc_type',
                'd.verify_status',
                DB::raw("concat(relative_path,'/',image) as doc_path"),
                'remarks',
                'doc_mstr_id'
            )
            ->join('ref_prop_docs_required as dr', 'dr.id', '=', 'd.doc_mstr_id')
            ->where("d.active_id", $applicationNo)
            ->whereIn("d.doc_mstr_id", $docId)
            ->first();
    }

    /**
     * | Water document 
     */
    public function getWaterAppByAppNoDocId($applicationNo, $docId)
    {
        return DB::table('wf_active_documents as d')
            ->select(
                'd.id',
                'dr.document_name',
                'd.verify_status',
                DB::raw("concat(relative_path,'/',image) as doc_path"),
                'remarks',
                'doc_mstr_id'
            )
            ->join('water_param_document_types as dr', 'dr.id', '=', 'd.doc_mstr_id')
            ->where("d.active_id", $applicationNo)
            ->whereIn("d.doc_mstr_id", $docId)
            ->first();
    }

    /**
     * | Fetches a specific trade document by application ID and document code from the 
     * | pgsql_trade database, including its verification status and full document path
       | Reference Function : uploadDocument
     */
    public function getTradeAppByAppNoDocId($appid, $ulb_id, $doc_code, $workflowId = null, $owner_id = null)
    {
        $docUrl = Config::get('module-constants.DOC_URL');

        if (!$workflowId) {
            $workflowId = Config::get('workflow-constants.TRADE_WORKFLOW_ID');
        }
        $data = DB::connection("pgsql_trade")->table('wf_active_documents as d')
            ->select(
                'd.id',
                'd.verify_status',
                DB::raw("concat('$docUrl/',relative_path,'/',document) as doc_path"),
                // DB::raw("concat(relative_path,'/',document) as doc_path"),
                'remarks',
            )
            ->where("d.active_id", $appid)
            ->where("d.workflow_id", $workflowId)
            ->where("d.ulb_id", $ulb_id)
            ->where("d.module_id", Config::get('module-constants.TRADE_MODULE_ID'))
            ->where("d.owner_dtl_id", $owner_id)
            ->where("d.status", 1)
            ->whereIn("d.doc_code", $doc_code)
            ->orderBy("d.id", "DESC")
            ->first();
        return $data;
    }

    /**
     * | Get Workflow Active Documents By Active Id
       | Common Function
     */
    public function getDocByRefIds($activeId, $workflowId, $moduleId)
    {
        $docUrl = Config::get('module-constants.DMS_URL');
        return WfActiveDocument::select(
            DB::raw("concat('$docUrl/',relative_path,'/',document) as doc_path"),
            // DB::raw("concat(relative_path,'/',document) as doc_path"),
            '*'
        )
            ->where('active_id', $activeId)
            ->where('workflow_id', $workflowId)
            ->where('module_id', $moduleId)
            ->where('status', 1)
            ->orderByDesc('id')
            ->get();
    }

    /**
     * | Get Workflow Active Documents By Active Id
       | Common Function
     */
    public function getDocByRefIdsDocCode($activeId, $workflowId, $moduleId, $docCode)
    {
        $docUrl = Config::get('module-constants.DOC_URL');
        return WfActiveDocument::select(
            DB::raw("concat('$docUrl/',relative_path,'/',document) as doc_path"),
            // DB::raw("concat(relative_path,'/',document) as doc_path"),
            '*'
        )
            ->where('active_id', $activeId)
            ->where('workflow_id', $workflowId)
            ->where('module_id', $moduleId)
            ->where('status', 1)
            ->whereIn('doc_code', $docCode)
            ->get();
    }

    /**
     * | Retrieves active owner-specific documents with full file paths based on 
     * | application, workflow, module, document code(s), and owner ID filters
       | Common Function
     */
    public function getOwnerDocByRefIdsDocCode($activeId, $workflowId, $moduleId, $docCode, $ownerId)
    {
        return WfActiveDocument::select(
            DB::raw("concat(relative_path,'/',document) as doc_path"),
            '*'
        )
            ->where('active_id', $activeId)
            ->where('workflow_id', $workflowId)
            ->where('module_id', $moduleId)
            ->where('status', 1)
            ->where('owner_dtl_id', $ownerId ?? null)
            ->whereIn('doc_code', $docCode)
            ->get();
    }
    
    /**
     * | Get Uploaded documents
       | Common Function
     */
    public function getDocsByActiveId($req)
    {
        return WfActiveDocument::where('active_id', $req->activeId)
            ->select(
                'doc_code',
                'owner_dtl_id',
                'verify_status'
            )
            ->where('workflow_id', $req->workflowId)
            ->where('module_id', $req->moduleId)
            ->where('verify_status', '!=', 2)
            ->where('status', 1)
            ->get();
    }

    /**
     * | Get Verified Documents
       | Reference Function : ifFullDocVerified
     */
    public function getVerifiedDocsByActiveId(array $req)
    {
        return WfActiveDocument::where('active_id', $req['activeId'])
            ->select(
                'doc_code',
                'owner_dtl_id',
                'verify_status'
            )
            ->where('workflow_id', $req['workflowId'])
            ->where('module_id', $req['moduleId'])
            ->where('verify_status', 1)
            ->where('status', 1)
            ->get();
    }

    /**
     * | Deactivate the Rejected Document 
     * | @param metaReqs
       | Use for deactivate the rejected document
       | Common Function
     */
    public function deactivateRejectedDoc($metaReqs)
    {
        WfActiveDocument::select('.*')
            ->where('active_id', $metaReqs->activeId)
            ->where('workflow_id', $metaReqs->workflowId)
            ->where('module_id', $metaReqs->moduleId)
            ->where('doc_code', $metaReqs->docCode)
            ->where('verify_status', 2)
            ->update([
                "status" => 0
            ]);
    }

    /**
     * | Get all Rejected Documents
       | Reference Function : backToCitizen
     */
    public function readRejectedDocuments(array $metaReqs)
    {
        return WfActiveDocument::on('pgsql::read')
            ->where('active_id', $metaReqs['activeId'])
            ->where('workflow_id', $metaReqs['workflowId'])
            ->where('module_id', $metaReqs['moduleId'])
            ->where('verify_status', 2)
            ->where('status', 1)
            ->get();
    }

    /**
     * | Get the document using moduleId,applicaionId,workflowId
       | Reference Function : checkPostCondition
     */
    public function getApplicatonDoc($relatedId, $workfloId, $moduleId)
    {
        return WfActiveDocument::on('pgsql::read')
            ->where('active_id', $relatedId)
            ->where('workflow_id', $workfloId)
            ->where('module_id', $moduleId)
            ->where('verify_status', 2)
            ->where('status', 1)
            ->first();
    }


    public function getConsumerDocs($consumerId)
    {
        $secondConnection = 'pgsql_water';
        return DB::connection($secondConnection)
            ->table('wf_active_documents as d')
            ->select(
                'd.id',
                'd.document',
                DB::raw("concat(d.relative_path, '/', d.document) as ref_doc_path"),
                'd.remarks',
                'd.verify_status',
                'd.doc_code',
                'd.doc_category',
                'd.status',
                'd.unique_id',
                'd.reference_no'
            )
            ->join('water_consumer_active_requests as wcar', 'wcar.id', '=', 'd.active_id')   
            ->whereIn('d.verify_status', [0, 2])

            ->where('d.workflow_id', 193)
            ->where('d.status', '!=', 0)
            ->where('d.active_id', $consumerId)
            ->get();
    }

}
