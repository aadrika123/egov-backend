<?php
namespace App\EloquentModels\Trade;

use App\Models\Trade\TradeParamFirmType;
use Exception;

class ModelFirmType
{
    public function __construct()
    {
        $this->obj=new TradeParamFirmType();
    }

    public function getFirmTypeList()
    {
        try{
            return $this->obj->select("id","firm_type")
                ->where("status",1)
                ->get();
        }
        catch (Exception $e)
        {
            echo $e->getMessage();
        }
    }
}