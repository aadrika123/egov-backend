<?php

namespace App\Models\Workflows;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WfRoleusermap extends Model
{
    use HasFactory;

    /**
     * | get Role By User Id
     */
    public function getRoleIdByUserId($userId)
    {
        return WfRoleusermap::select('id', 'wf_role_id', 'user_id')
            ->where('user_id', $userId)
            ->where('is_suspended', false)
            ->get();
    }

    /**
     * | Get Role details by User Id
     */
    public function getRoleDetailsByUserId($userId)
    {
        return WfRoleusermap::leftJoin('wf_roles', 'wf_roles.id', '=', 'wf_roleusermaps.wf_role_id')
            ->where('wf_roleusermaps.user_id', $userId)
            ->where('wf_roleusermaps.is_suspended', false)
            ->select(
                'wf_roles.role_name AS roles',
                'wf_roles.id AS roleId'
            )
            ->orderByDesc('wf_roles.id')
            ->get();
    }
}
