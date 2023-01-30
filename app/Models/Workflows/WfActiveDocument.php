<?php

namespace App\Models\Workflows;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WfActiveDocument extends Model
{
    use HasFactory;
    protected $guarded = [];

    /**
     * | Post Workflow Document
     */
    public function postDocuments($req)
    {
        $metaReqs = [
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

        WfActiveDocument::create($metaReqs);
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

    # water document View
    public function getWaterDocsByAppNo($applicationNo)
    {
        return DB::table('wf_active_documents as d')
            ->select(
                'd.id',
                'd.image',
                DB::raw("concat(relative_path,'/',image) as doc_path"),
                'd.remarks',
                'd.verify_status',
                'd.doc_mstr_id',
                'dm.document_name',
                'o.applicant_name as owner_name'
            )
            ->join('water_param_document_types as dm', 'dm.id', '=', 'd.doc_mstr_id')
            ->leftJoin('water_applicants as o', 'o.id', '=', 'd.owner_dtl_id')
            ->where('d.active_id', $applicationNo)
            ->get();
    }

    public function getTradeDocByAppNo($applicationNo)
    {
        return DB::table('wf_active_documents as d')
            ->select(
                'd.id',
                'd.image',
                DB::raw("concat(relative_path,'/',image) as doc_path"),
                'd.remarks',
                'd.verify_status',
                'd.doc_mstr_id',
                'dm.doc_for',
                'o.owner_name'
            )
            ->join('trade_param_document_types as dm', 'dm.id', '=', 'd.doc_mstr_id')
            ->leftJoin('active_trade_owners as o', 'o.id', '=', 'd.owner_dtl_id')
            ->where('d.active_id', $applicationNo)
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
    public function getTradeAppByAppNoDocId($applicationNo, $docId)
    {
        return DB::table('wf_active_documents as d')
            ->select(
                'd.id',
                'dr.doc_name',
                'd.verify_status',
                DB::raw("concat(relative_path,'/',image) as doc_path"),
                'remarks',
                'doc_mstr_id'
            )
            ->join('trade_param_document_types as dr', 'dr.id', '=', 'd.doc_mstr_id')
            ->where("d.active_id", $applicationNo)
            ->whereIn("d.doc_mstr_id", $docId)
            ->first();
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
}
