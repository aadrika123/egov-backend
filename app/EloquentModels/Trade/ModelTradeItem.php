<?php
namespace App\EloquentModels\Trade;

use App\Models\Trade\TradeParamItemType;
use Exception;

class ModelTradeItem
{
    public function __construct()
    {
        $this->obj=new TradeParamItemType();
    }

    public function gettradeitemsList()
    {
        try{
            return $this->obj->select("id","trade_item","trade_code")
                ->where("status",1)
                ->get();
        }
        catch (Exception $e)
        {
            echo $e->getMessage();
        }
    }
}