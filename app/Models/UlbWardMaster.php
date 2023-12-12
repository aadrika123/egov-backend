<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UlbWardMaster extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $connection = "pgsql_master";
    /**
     * | Get the Ward No by ward id
     * | @param id $id
     */
    public function getWardById($id)
    {
        return UlbWardMaster::on('pgsql::read')->find($id);
    }

    /**
     * | Get Ward By Ulb ID
     * | @param ulbId
     */
    public function getWardByUlbId($ulbId)
    {
        return UlbWardMaster::on('pgsql::read')->select('id', 'ward_name')
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
        return UlbWardMaster::on('pgsql::read')->where('id', $id)
            ->firstOrFail();
    }

    /**
     * | get the ward by Id
     * | @param id
     */
    public function getExistWard($id)
    {
        return UlbWardMaster::on('pgsql::read')->where('id', $id)
            ->first();
    }
}
