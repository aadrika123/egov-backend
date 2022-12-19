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
}
