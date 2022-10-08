<?php
namespace App\EloquentModels\Trade;

use App\Models\Trade\TradeParamOwnershipType;
use Exception;

class ModelOwnershipType
{
    public function __construct()
    {
        $this->obj=new TradeParamOwnershipType();
    }

    public function getownershipTypeList()
    {
        try{
            return $this->obj->select("id","ownership_type")
                ->where("status",1)
                ->get();
        }
        catch (Exception $e)
        {
            echo $e->getMessage();
        }
    }
}