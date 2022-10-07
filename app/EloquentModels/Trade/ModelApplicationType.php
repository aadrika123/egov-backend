<?php
namespace App\EloquentModels\Trade;

use App\Models\Trade\TradeParamApplicationType;
use Exception;

class ModelApplicationType 
{   
    public function __construct()
    {
        $this->obj = new TradeParamApplicationType();
    }
    public function getAllApplicationType()
    {
        try
        {
            $data = $this->obj->select("id","application_type")
            ->where('status','1')
            ->get();
            return $data;

        }
        catch (Exception $e)
        {
            echo $e->getMessage();
        }
    }
}
?>