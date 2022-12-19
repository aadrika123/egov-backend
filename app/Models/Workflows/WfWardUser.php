<?php

namespace App\Models\Workflows;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WfWardUser extends Model
{
    use HasFactory;

    /**
     * | Get Wards by user id
     * | @var userId
     */
    public function getWardsByUserId($userId)
    {
        return WfWardUser::select('id', 'ward_id')
            ->where('user_id', $userId)
            ->get();
    }
}
