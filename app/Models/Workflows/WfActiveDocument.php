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

    /**
     * | Meta Request function for updation and post the request
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
            "uploaded_by" => authUser()->id,
            "uploaded_by_type" => authUser()->user_type,
            "remarks" => $req->remarks ?? null,
            "doc_code" => $req->docCode,
            "owner_dtl_id" => $req->ownerDtlId,
        ];
    }

    /**
     * | Post Workflow Document
     */
    public function postDocuments($req)
    {
        $metaReqs = $this->metaReqs($req);
        if (isset($req->verifyStatus)) {
            $metaReqs = array_merge($metaReqs, [
                "verify_status" => $req->verifyStatus
            ]);
        }
        WfActiveDocument::create($metaReqs);
    }

    /**
     * | Edit Existing Document
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
     * | Get Application Details by Application No
     */
    public function getDocsByAppId($applicationId, $workflowId, $moduleId)
    {
        return DB::table('wf_active_documents as d')
            ->select(
                'd.id',
                'd.document',
                DB::raw("concat(relative_path,'/',document) as doc_path"),
                'd.remarks',
                'd.verify_status',
                'd.doc_code',
                'o.owner_name'
            )
            ->leftJoin('prop_active_safs_owners as o', 'o.id', '=', 'd.owner_dtl_id')
            ->where('d.active_id', $applicationId)
            ->where('d.workflow_id', $workflowId)
            ->where('d.module_id', $moduleId)
            ->get();
    }

    /**
     * | Get Owner PhotoGraph By applicationId
     */
    public function getOwnerPhotograph($applicationId, $workflowId, $moduleId, $ownerId)
    {
        return DB::table('wf_active_documents as d')
            ->select(
                'd.verify_status',
                DB::raw("concat(relative_path,'/',document) as doc_path")
            )
            ->join('prop_active_safs_owners as o', 'o.id', '=', 'd.owner_dtl_id')
            ->where('d.active_id', $applicationId)
            ->where('d.workflow_id', $workflowId)
            ->where('d.module_id', $moduleId)
            ->where('doc_code', 'PHOTOGRAPH')
            ->where('owner_dtl_id', $ownerId)
            ->first();
    }

    /**
     * | Get Owner PhotoGraph By applicationId Water Module
     */
    public function getWaterOwnerPhotograph($applicationId, $workflowId, $moduleId, $ownerId, $docCode = null)
    {
        return DB::table('wf_active_documents as d')
            ->select(
                'd.verify_status',
                DB::raw("concat(relative_path,'/',document) as doc_path")
            )
            ->join('water_applicants as o', 'o.id', '=', 'd.owner_dtl_id')
            ->where('d.active_id', $applicationId)
            ->where('d.workflow_id', $workflowId)
            ->where('d.module_id', $moduleId)
            ->where('doc_code', ($docCode ? $docCode : 'CONSUMER_PHOTO'))
            ->where('owner_dtl_id', $ownerId)
            ->first();
    }

    # water document View
    public function getWaterDocsByAppNo($applicationId, $workflowId, $moduleId)
    {
        return DB::table('wf_active_documents as d')
            ->select(
                'd.id',
                'd.document',
                DB::raw("concat(relative_path,'/',document) as ref_doc_path"),
                'd.remarks',
                'd.verify_status',
                'd.doc_code',
                'o.applicant_name as owner_name'
            )
            ->leftJoin('water_applicants as o', 'o.id', '=', 'd.owner_dtl_id')
            ->where('d.active_id', $applicationId)
            ->where('d.workflow_id', $workflowId)
            ->where('d.module_id', $moduleId)
            ->get();
    }

    public function getTradeDocByAppNo($applicationId, $workflowId, $moduleId)
    {

        return DB::table('wf_active_documents as d')
            ->select(
                'd.id',
                'd.document',
                DB::raw("concat(d.relative_path,'/',d.document) as doc_path"),
                'd.remarks',
                'd.verify_status',
                'd.doc_code as doc_for',
                'd.doc_code',
                'o.owner_name'
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
     * | trade
     */
    public function getTradeAppByAppNoDocId($appid, $ulb_id, $doc_code, $owner_id = null)
    {
        // DB::enableQueryLog();
        $data = DB::table('wf_active_documents as d')
            ->select(
                'd.id',
                'd.verify_status',
                DB::raw("concat(relative_path,'/',document) as doc_path"),
                'remarks',
            )
            ->where("d.active_id", $appid)
            ->where("d.workflow_id", Config::get('workflow-constants.TRADE_WORKFLOW_ID'))
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
     */
    public function getDocByRefIds($activeId, $workflowId, $moduleId)
    {
        return WfActiveDocument::select(
            DB::raw("concat(relative_path,'/',document) as doc_path"),
            '*'
        )
            ->where('active_id', $activeId)
            ->where('workflow_id', $workflowId)
            ->where('module_id', $moduleId)
            ->where('status', 1)
            ->get();
    }

    /**
     * | Get Workflow Active Documents By Active Id
     */
    public function getDocByRefIdsDocCode($activeId, $workflowId, $moduleId, $docCode)
    {
        return WfActiveDocument::select(
            DB::raw("concat(relative_path,'/',document) as doc_path"),
            '*'
        )
            ->where('active_id', $activeId)
            ->where('workflow_id', $workflowId)
            ->where('module_id', $moduleId)
            ->where('status', 1)
            ->whereIn('doc_code', $docCode)
            ->get();
    }
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
}
