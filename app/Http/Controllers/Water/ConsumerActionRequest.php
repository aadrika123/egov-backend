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

    /**
     * | Set Disconnection Site Inspection
     */
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

    /**
     * | Get Site Inspection Details for Deactivation
     */
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

    /**
     * | Cancel Disconnection Site Inspection
     */
    public function cancelDisconnectionSitInspection(Request $request)
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
            $schedules = $ModelWaterDisconnectionSiteInspections->find($request->applicationId);
            if(!$schedules)
            {
                throw new Exception("schedule for inspection first");
            }
            $this->begin();
            $schedules->status = 0;
            $schedules->update();
            $this->commit();
            return responseMsgs(true, "Site Inspection Canceled", "", '', '01', responseTime(), $request->getMethod(), $request->deviceId);
        }
        catch(Exception $e)
        {
            $this->rollback();
            return responseMsgs(false, $e->getMessage(), [], '', '01', responseTime(), $request->getMethod(), $request->deviceId);
        }
    }

    /**
     * | Update Site Inspection
     */
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

    /**
     * | Get Inspection Data for Deactivation
     */
    public function getInspectionData(Request $request)
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
            $data = $ModelWaterDisconnectionSiteInspections->find($request->applicationId);            
            $lastInspections = $data->getApplicationInspection();
            $deactivationRequestData = $data->getConsumerRequests();
            $consumerData = $deactivationRequestData->getConserDtls();
            if(!$consumerData) {
                throw new Exception("Consumer data not found");
            }
            $propertyType  = $data->getPropType();
            $pipelineType  = $data->getPipelineType();
            $connectionType  = $data->getConnectionType();

            $lastInspectPropertyType  = $lastInspections->getPropType();
            $lastInspectPipelineType  = $lastInspections->getPipelineType();
            $lastInspectConnectionType  = $lastInspections->getConnectionType();

            $data->propertyType = $propertyType ? $propertyType->property_type:"";
            $data->pipelineType = $pipelineType ? $pipelineType->pipeline_type:"";
            $data->connectionType = $connectionType ? $connectionType->connection_type:"";

            $lastInspections->propertyType = $lastInspectPropertyType ? $lastInspectPropertyType->property_type:"";
            $lastInspections->pipelineType = $lastInspectPipelineType ? $lastInspectPipelineType->pipeline_type:"";
            $lastInspections->connectionType = $lastInspectConnectionType ? $lastInspectConnectionType->connection_type:"";

            $compairData=[
                [
                    "name"=>"connection through",
                    "app"=>$lastInspections->connection_through,
                    "inspection"=>$data->connection_through,
                ],
                [
                    "name"=>"property type",
                    "app"=>$lastInspections->propertyType,
                    "inspection"=>$data->propertyType,
                ],
                [
                    "name"=>"pipeline type",
                    "app"=>$lastInspections->pipelineType,
                    "inspection"=>$data->pipelineType,
                ],
                [
                    "name"=>"connection type",
                    "app"=>$lastInspections->connectionType,
                    "inspection"=>$data->connectionType,
                ],
                [
                    "name"=>"category",
                    "app"=>$lastInspections->category,
                    "inspection"=>$data->category,
                ],
                [
                    "name"=>"flat count",
                    "app"=>$lastInspections->flat_count,
                    "inspection"=>$data->flat_count,
                ],
                [
                    "name"=>"area sqft",
                    "app"=>$lastInspections->area_sqft,
                    "inspection"=>$data->area_sqft,
                ],
                [
                    "name"=>"pipeline size",
                    "app"=>$lastInspections->pipeline_size,
                    "inspection"=>$data->pipeline_size,
                ],
                [
                    "name"=>"pipeline size type",
                    "app"=>$lastInspections->pipeline_size_type,
                    "inspection"=>$data->pipeline_size_type,
                ],
                [
                    "name"=>"pipeline size type",
                    "app"=>$lastInspections->pipeline_size_type,
                    "inspection"=>$data->pipeline_size_type,
                ],
                [
                    "name"=>"pipe size",
                    "app"=>$lastInspections->pipe_size,
                    "inspection"=>$data->pipe_size,
                ],
                [
                    "name"=>"pipe type",
                    "app"=>$lastInspections->pipe_type,
                    "inspection"=>$data->pipe_type,
                ],
                [
                    "name"=>"ferrule type",
                    "app"=>$lastInspections->ferrule_type,
                    "inspection"=>$data->ferrule_type,
                ],
                [
                    "name"=>"road type",
                    "app"=>$lastInspections->road_type,
                    "inspection"=>$data->road_type,
                ],
            ];
            $response = [
                "compairData" =>$compairData,
                "consumerData" =>$consumerData,
            ];
    
            return responseMsgs(true, "", $response, '', '01', responseTime(), $request->getMethod(), $request->deviceId);
        }
        catch(Exception $e)
        {
            return responseMsgs(false, $e->getMessage(), [], '', '01', responseTime(), $request->getMethod(), $request->deviceId);
        }
    }
}
