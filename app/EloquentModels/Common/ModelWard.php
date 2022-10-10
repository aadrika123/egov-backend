<?php
namespace App\EloquentModels\Common;

use App\Models\UlbWardMaster;
use Exception;
class ModelWard
{
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
}