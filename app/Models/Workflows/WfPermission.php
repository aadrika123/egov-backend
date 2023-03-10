<?php

namespace App\Models\Workflows;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WfPermission extends Model
{
    use HasFactory;

    /**
     * |
     */
    public function addPermission($req)
    {
        $permission = new WfPermission();
        $permission->permission = $req->permission;
        $permission->module_id = $req->moduleId;
        $permission->save();
    }
}
