<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Exception;

class UlbWardMaster extends Model
{
    use HasFactory;
<<<<<<< HEAD

=======
    public $timestamps = false;
>>>>>>> d00f9ecdb7ad26492e009324550114a31bd18da5
    /**
     * | Get the Ward No by ward id
     * | @param id $id
     */
    public function getWardById($id)
    {
        return UlbWardMaster::find($id);
    }

    /**
     * | Get Ward By Ulb ID
     * | @param ulbId
     */
    public function getWardByUlbId($ulbId)
    {
        return UlbWardMaster::select('id', 'ward_name')
            ->where('ulb_id', $ulbId)
            ->where('status', 1)
            ->get();
    }

    /**
     * | get the ward by Id
     * | @param id
     */
    public function getWard($id)
    {
        return UlbWardMaster::where('id', $id)
            ->firstOrFail();
    }

    /**
     * | get the ward by Id
     * | @param id
     */
    public function getExistWard($id)
    {
        return UlbWardMaster::where('id', $id)
            ->first();
    }
}
