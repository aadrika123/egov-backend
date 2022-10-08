<?php
namespace App\EloquentModels\Trade;

use App\Models\Trade\TradeParamCategoryType;
use Exception;

class ModelCategoryType
{
    public function __construct()
    {
        $this->obj=new TradeParamCategoryType();
    }

    public function getCotegoryList()
    {
        try{
            return $this->obj->select("id","category_type")
                ->where("status",1)
                ->get();
        }
        catch (Exception $e)
        {
            echo $e->getMessage();
        }
    }
}