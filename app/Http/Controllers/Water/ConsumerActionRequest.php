<?php

namespace App\Http\Controllers\Water;

use App\Http\Controllers\Controller;
use App\Http\Requests\Water\ReqApplicationId;
use App\Http\Requests\Water\siteAdjustment;
use App\Http\Requests\Water\WaterDisconnectionSiteInspection;
use App\Models\Water\WaterConsumerActiveRequest;
use App\Models\water\WaterDisconnectionSiteInspections;
use App\Models\Water\WaterSiteInspection;
use App\Models\Water\WaterTran;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ConsumerActionRequest extends Controller
{

    private $_waterRoles;
    private $_waterModuleId;
    protected $_DB_NAME;
    protected $_DB;

    public function __construct()
    {
        $this->_waterRoles      = Config::get('waterConstaint.ROLE-LABEL');
        $this->_waterModuleId   = Config::get('module-constants.WATER_MODULE_ID');
        $this->_DB_NAME = "pgsql_water";
        $this->_DB = DB::connection($this->_DB_NAME);
    }

    /**
     * | Database transaction
     */
    public function begin()
    {
        $db1 = DB::connection()->getDatabaseName();
        $db2 = $this->_DB->getDatabaseName();
        DB::beginTransaction();
        if ($db1 != $db2)
            $this->_DB->beginTransaction();
    }
    /**
     * | Database transaction
     */
    public function rollback()
    {
        $db1 = DB::connection()->getDatabaseName();
        $db2 = $this->_DB->getDatabaseName();
        DB::rollBack();
        if ($db1 != $db2)
            $this->_DB->rollBack();
    }
    /**
     * | Database transaction
     */
    public function commit()
    {
        $db1 = DB::connection()->getDatabaseName();
        $db2 = $this->_DB->getDatabaseName();
        DB::commit();
        if ($db1 != $db2)
            $this->_DB->commit();
    }


    public function setDisconnectionSitInspection(Request $request)
    {
        $ModelWaterDisconnectionSiteInspections = new WaterDisconnectionSiteInspections();
        $ModelActiveRequest = new WaterConsumerActiveRequest();
        $rules = [
            "applicationId"=>"required|digits_between:1,9223372036854775807|exists:".$ModelActiveRequest->getConnectionName().".".$ModelActiveRequest->getTable().",id",
            'inspectionDate'    => 'required|date|date_format:Y-m-d',
            'inspectionTime'    => 'required|date_format:H:i'
        ];

        $validated = Validator::make(
            $request->all(),
            $rules
        );
        if ($validated->fails()){
            return validationErrorV2($validated);
        }
        try{
            $this->begin();
            
            $id = $ModelWaterDisconnectionSiteInspections->setInspectionDate($request);
            $data = ["applicationId"=>$id];
            $this->commit();
            return responseMsgs(true, "site inspection scheduled",$data , '', '01', responseTime(), $request->getMethod(), $request->deviceId);
        }
        catch(Exception $e)
        {
            $this->rollback();
            return responseMsgs(false, $e->getMessage(), [], '', '01', responseTime(), $request->getMethod(), $request->deviceId);
        }
    }

    public function getSiteInspectionCompar(Request $request)
    {
        $ModelWaterDisconnectionSiteInspections = new WaterDisconnectionSiteInspections();
        $rules = [
            "applicationId" =>"required|digits_between:1,9223372036854775807|exists:".$ModelWaterDisconnectionSiteInspections->getConnectionName().".".$ModelWaterDisconnectionSiteInspections->getTable().",id"
        ];
        $validated = Validator::make(
            $request->all(),
            $rules
        );
        if ($validated->fails()){
            return validationErrorV2($validated);
        }
        try{
            $waterPaymentController = new WaterPaymentController();
            $ModelActiveRequest = new WaterConsumerActiveRequest();
            $ModelWaterTran = new WaterTran();
            $schedules = $ModelWaterDisconnectionSiteInspections->find($request->applicationId);
            if(!$schedules)
            {
                throw new Exception("schedule for inspection first");
            }
            $deactivationRequestData = $schedules->getConsumerRequests();
            $consumerData = $deactivationRequestData->getConserDtls();
            if(!$consumerData) {
                throw new Exception("Consumer data not found");
            }
            $lastInspections = WaterSiteInspection::where("apply_connection_id",$consumerData->apply_connection_id??0)->where("status",1)->orderBy("id","DESC")->first();
            $masterData = $waterPaymentController->getWaterMasterData();
            if(!$masterData->original["status"]){
                throw new Exception("Master data not get");
            }
            $masterData =$masterData->original["data"];
            $data = [
                "inspectionDate"=>$schedules->inspection_date,
                "inspectionTime"=>$schedules->inspection_time,
                "applicationDetails"=>$consumerData->getWaterApplication(),
                "consumer"=>$consumerData,
                "ownerDetails"=>$consumerData->getOwners(),
                "waterTransDetail"=> $ModelWaterTran->ConsumerTransaction($consumerData->id),
                "lastInspections"=>$lastInspections,
                "masters"=>$masterData,
            ];
            return responseMsgs(true, "Site Inspection Details for Deactivation", ($data), '', '01', responseTime(), $request->getMethod(), $request->deviceId);
        }
        catch(Exception $e)
        {
            return responseMsgs(false, $e->getMessage(), [], '', '01', responseTime(), $request->getMethod(), $request->deviceId);
        }

    }

    public function updateSiteInspection(WaterDisconnectionSiteInspection $request)
    {
        try{
            $ModelActiveRequest = new WaterConsumerActiveRequest();
            $ModelWaterDisconnectionSiteInspections = new WaterDisconnectionSiteInspections();
            $schedules = $ModelWaterDisconnectionSiteInspections->find($request->applicationId);
            if($schedules->verify_status!=0)
            {
                throw new Exception("schedule inspection already update Please Reschedule");
            }
            $deactivationRequestData = $schedules->getConsumerRequests();
            if(!$deactivationRequestData) {
                throw new Exception("data not found");
            }          
            $consumerData = $deactivationRequestData->getConserDtls();
            if(!$consumerData) {
                throw new Exception("Consumer data not found");
            }
            $lastInspections = WaterSiteInspection::where("apply_connection_id",$consumerData->apply_connection_id??0)->where("status",1)->orderBy("id","DESC")->first();
            
            $request->merge(["waterSiteInspectionsId"=>$lastInspections->id??null,"inspectionsJson"=>json_encode($lastInspections->toArray(), JSON_UNESCAPED_UNICODE)]);
            $this->begin();
            $ModelWaterDisconnectionSiteInspections->updateInspection($request);
            $this->commit();
            return responseMsgs(true, "Site Inspection Done!", "", '', '01', responseTime(), $request->getMethod(), $request->deviceId);
        }
        catch(Exception $e)
        {
            $this->rollback();
            return responseMsgs(false, $e->getMessage(), [], '', '01', responseTime(), $request->getMethod(), $request->deviceId);
        }
    }
}
