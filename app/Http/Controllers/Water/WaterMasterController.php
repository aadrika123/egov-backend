<?php

namespace App\Http\Controllers\Water;

use App\Http\Controllers\Controller;
use App\Models\Water\WaterConsumerCharge;
use App\Models\Water\WaterParamPipelineType;
use App\Models\Water\WaterPropertyTypeMstr;
use Illuminate\Http\Request;
use App\Repository\Water\Interfaces\iNewConnection;
use App\Repository\Common\CommonFunction;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class WaterMasterController extends Controller
{
    private iNewConnection $newConnection;
    private $_dealingAssistent;
    private $_waterRoles;
    protected $_commonFunction;
    private $_waterModulId;
    protected $_DB_NAME;
    protected $_DB;
    //
    public function __construct(iNewConnection $newConnection)
    {
        $this->_DB_NAME = "pgsql_water";
        $this->_DB = DB::connection($this->_DB_NAME);
        $this->_commonFunction = new CommonFunction();
        $this->newConnection = $newConnection;
        $this->_dealingAssistent = Config::get('workflow-constants.DEALING_ASSISTENT_WF_ID');
        $this->_waterRoles = Config::get('waterConstaint.ROLE-LABEL');
        $this->_waterModulId = Config::get('module-constants.WATER_MODULE_ID');
    }

    /**
     * | Database transaction connection
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
     * | Database transaction connection
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
     * | Database transaction connection
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
     * |created by - Arshad Hussain 
     */
    #==================================crud for master table===============#
    #create water_property_type_mstr
    public function createWaterPropTypeMstr(Request $request)
    {
        try {
            $mWaterPropTypeMaster = new WaterPropertyTypeMstr();
            $data = $mWaterPropTypeMaster->create($request);
            return responseMsgs(true, "Data Added", $data, "120201", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120201", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }
    #get data 
    public function getAllData(Request $request)
    {
        try {
            $mWaterPropTypeMaster = new WaterPropertyTypeMstr();
            $data = $mWaterPropTypeMaster->getWaterPropertyTypeMstr();
            return responseMsgs(true, "Data ", $data, "120201", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120201", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }
    #get data by id 
    public function getDataById(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                "id" => 'required'
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            $mWaterPropTypeMaster = new WaterPropertyTypeMstr();
            $data = $mWaterPropTypeMaster->getDataByIdDtls($request);
            return responseMsgs(true, "Data ", $data, "120201", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120201", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }
    #active deactive
    public function activeDeactiveById(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                "id" => 'required'
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            $mWaterPropTypeMaster = new WaterPropertyTypeMstr();
            $data = $mWaterPropTypeMaster->activeDeactiveData($request);
            return responseMsgs(true, "Data ", $data, "120201", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120201", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }
    #update data by Id 
    public function updateById(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                "id" => 'required',
                "propertyType" => 'required',
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            $mWaterPropTypeMaster = new WaterPropertyTypeMstr();
            $data = $mWaterPropTypeMaster->updateDataById($request);
            return responseMsgs(true, "Data ", $data, "120201", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120201", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }
    #=================crud operation for water param pipeline type =====================#\
    #create data 
    public function createWaterPipelineType(Request $request)
    {
        try {
            $mWater = new WaterParamPipelineType();
            $data = $mWater->create($request);
            return responseMsgs(true, "Data Added", $data, "120201", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120201", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }
   #get data 
   public function getAllPipeline(Request $request)
   {
       try {
           $mWater= new WaterParamPipelineType();
           $data = $mWater->getWaterParamPipelineType();
           return responseMsgs(true, "Data ", $data, "120201", "01", responseTime(), $request->getMethod(), $request->deviceId);
       } catch (Exception $e) {
           return responseMsgs(false, $e->getMessage(), "", "120201", "01", responseTime(), $request->getMethod(), $request->deviceId);
       }
   }
    #get data by id 
    public function getDataId(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                "id" => 'required'
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            $mWater= new WaterParamPipelineType();
            $data = $mWater->getDataByIdDtls($request);
            return responseMsgs(true, "Data ", $data, "120201", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120201", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }
    #active deactive
    public function dataActiveDeactive(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                "id" => 'required'
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            $mWater = new WaterParamPipelineType();
            $data = $mWater->activeDeactiveData($request);
            return responseMsgs(true, "Data ", $data, "120201", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120201", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }
     #update data by Id 
     public function updateDataById(Request $request)
     {
         $validated = Validator::make(
             $request->all(),
             [
                 "id" => 'required',
                 "propertyType" => 'required',
             ]
         );
         if ($validated->fails())
             return validationError($validated);
         try {
             $mWater = new WaterParamPipelineType();
             $data = $mWater->updateDataById($request);
             return responseMsgs(true, "Data ", $data, "120201", "01", responseTime(), $request->getMethod(), $request->deviceId);
         } catch (Exception $e) {
             return responseMsgs(false, $e->getMessage(), "", "120201", "01", responseTime(), $request->getMethod(), $request->deviceId);
         }
     }
}
