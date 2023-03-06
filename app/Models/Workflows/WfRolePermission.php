<?php

namespace App\Models\Workflows;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WfRolePermission extends Model
{
    use HasFactory;

    /**
     * | 
     */
    public function addRolePermission($req)
    {
        $rolePermission = new WfRolePermission();
        $rolePermission->wf_role_id = $req->wfRoleId;
        $rolePermission->permision_id = $req->permissionId;
        $rolePermission->can_read = $req->canRead;
        $rolePermission->can_write = $req->canWrite;
        $rolePermission->save();
    }
}
