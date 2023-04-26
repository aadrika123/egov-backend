<?php
namespace App\EloquentModels\Common;

use App\Models\UlbWardMaster;
use Exception;
use Illuminate\Support\Facades\DB;

class ModelWard
{
    private $obj;
    public function __construct()
    {
        $this->obj = new UlbWardMaster();
    }
    public function getAllWard(int $ulb_id)
    {
        try{
            return $this->obj->select("id","ward_name")
            ->where("ulb_id",$ulb_id)
            ->get();
        }
        catch (Exception $e)
        {
            echo $e->getMessage();
        }
    }

    public function getOldWard(int $ulb_id)
    {
        try{
            return $this->obj->select(DB::raw("min(id) as id,ward_name"))
            ->where("ulb_id",$ulb_id)
            ->groupBy("ward_name")
            ->orderBy("ward_name")
            ->get();
        }
        catch (Exception $e)
        {
            echo $e->getMessage();
        }
    }
}