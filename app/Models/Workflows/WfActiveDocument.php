<?php

namespace App\Models\Workflows;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
