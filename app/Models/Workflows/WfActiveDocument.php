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
            "image" => $req->image,
            "uploaded_by" => authUser()->id,
            "remarks" => $req->remarks ?? null,
            "doc_mstr_id" => $req->docMstrId,
            "owner_dtl_id" => $req->ownerDtlId,
        ];

        WfActiveDocument::create($metaReqs);
    }

    /**
     * | Get Application Details by Application No
     */
    public function getDocsByAppNo($applicationNo)
    {
        return DB::table('wf_active_documents as d')
            ->select(
                'd.id',
                'd.image',
                DB::raw("concat(relative_path,'/',image) as doc_path"),
                'd.remarks',
                'd.verify_status',
                'd.doc_mstr_id',
                'dm.doc_type',
                'o.owner_name'
            )
            ->leftJoin('ref_prop_docs_required as dm', 'dm.id', '=', 'd.doc_mstr_id')
            ->leftJoin('prop_active_safs_owners as o', 'o.id', '=', 'd.owner_dtl_id')
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
}
