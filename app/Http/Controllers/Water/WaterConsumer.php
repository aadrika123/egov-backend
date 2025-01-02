<?php

namespace App\Http\Controllers\Water;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notice\Add;
use App\Http\Requests\Water\ReqApplicationId;
use App\Http\Requests\Water\reqDeactivate;
use App\Http\Requests\Water\reqMeterEntry;
use App\MicroServices\DocUpload;
use App\MicroServices\IdGeneration;
use App\MicroServices\IdGenerator\PrefixIdGenerator;
use App\Models\Citizen\ActiveCitizenUndercare;
use App\Models\Masters\RefRequiredDocument;
use App\Models\Payment\TempTransaction;
use App\Models\UlbMaster;
use App\Models\UlbWardMaster;
use App\Models\Water\WaterAdvance;
use App\Models\Water\WaterApplication;
use App\Models\Water\WaterApprovalApplicationDetail;
use App\Models\Water\WaterChequeDtl;
use App\Models\Water\WaterConnectionCharge;
use App\Models\Water\WaterConnectionTypeMstr;
use App\Models\Water\WaterConsumer as WaterWaterConsumer;
use App\Models\Water\WaterConsumerActiveRequest;
use App\Models\Water\WaterConsumerApprovalRequest;
use App\Models\Water\WaterConsumerApprovedRequest;
use App\Models\Water\WaterConsumerCharge;
use App\Models\Water\WaterConsumerChargeCategory;
use App\Models\Water\WaterConsumerDemand;
use App\Models\Water\WaterConsumerDisconnection;
use App\Models\Water\WaterConsumerInitialMeter;
use App\Models\Water\WaterConsumerMeter;
use App\Models\Water\WaterConsumerTax;
use App\Models\Water\WaterDisconnection;
use App\Models\Water\WaterMeterReadingDoc;
use App\Models\Water\WaterPenaltyInstallment;
use App\Models\Water\WaterSiteInspection;
use App\Models\Water\WaterTran;
use App\Models\Water\WaterTranDetail;
use App\Models\Workflows\WfActiveDocument;
use App\Models\Workflows\WfRole;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWorkflow;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Models\WorkflowTrack;
use App\Repository\Water\Concrete\WaterNewConnection;
use App\Repository\Water\Interfaces\IConsumer;
use App\Repository\Water\Interfaces\iNewConnection;
use App\Traits\Workflow\Workflow;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\CssSelector\Node\FunctionNode;
use App\Traits\Water\WaterTrait;
use Illuminate\Support\Facades\Auth;

class WaterConsumer extends Controller
{
    use Workflow;
    use WaterTrait;

    private $Repository;
    protected $_DB_NAME;
    protected $_DB;

    public function __construct(IConsumer $Repository)
    {
        $this->Repository = $Repository;
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
     * | Calcullate the Consumer demand 
     * | @param request
     * | @return Repository
        | Serial No : 01
        | Working
     */
    public function calConsumerDemand(Request $request)
    {
        return $this->Repository->calConsumerDemand($request);
    }


    /**
     * | List Consumer Active Demand
     * | Show the Demand With payed-status false
     * | @param request consumerId
     * | @var WaterConsumerDemand  model
     * | @var consumerDemand  
     * | @var refConsumerId
     * | @var refMeterData
     * | @var connectionName
     * | @return consumerDemand  Consumer Demand List
        | Serial no : 02
        | Working
     */
    public function listConsumerDemand(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'ConsumerId' => 'required|',
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $mWaterConsumerDemand   = new WaterConsumerDemand();
            $mWaterConsumerMeter    = new WaterConsumerMeter();
            $refConnectionName      = Config::get('waterConstaint.METER_CONN_TYPE');
            $refConsumerId          = $request->ConsumerId;

            $consumerDemand['consumerDemands'] = $mWaterConsumerDemand->getConsumerDemand($refConsumerId);
            $checkParam = collect($consumerDemand['consumerDemands'])->first();
            if (isset($checkParam)) {
                $sumDemandAmount = collect($consumerDemand['consumerDemands'])->sum('balance_amount');
                $totalPenalty = collect($consumerDemand['consumerDemands'])->sum('penalty');
                $consumerDemand['totalSumDemand'] = round($sumDemandAmount, 2);
                $consumerDemand['totalPenalty'] = round($totalPenalty, 2);

                # meter Details 
                $refMeterData = $mWaterConsumerMeter->getMeterDetailsByConsumerId($refConsumerId)->first();
                switch ($refMeterData['connection_type']) {
                    case (1):
                        if ($refMeterData['meter_status'] == 1) {
                            $connectionName = $refConnectionName['1'];
                            break;
                        }
                        $connectionName = $refConnectionName['4'];
                        break;
                    case (2):
                        $connectionName = $refConnectionName['2'];
                        break;
                    case (3):
                        $connectionName = $refConnectionName['3'];
                        break;
                }
                $refMeterData['connectionName'] = $connectionName;
                $consumerDemand['meterDetails'] = $refMeterData;

                return responseMsgs(true, "List of Consumer Demand!", $consumerDemand, "", "01", "ms", "POST", "");
            }
            throw new Exception("There is no demand!");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", "ms", "POST", "");
        }
    }


    /**
     * | Save the consumer demand 
     * | Also generate demand 
     * | @param request
     * | @var mWaterConsumerInitialMeter
     * | @var mWaterConsumerMeter
     * | @var refMeterConnectionType
     * | @var consumerDetails
     * | @var calculatedDemand
     * | @var demandDetails
     * | @var meterId
     * | @return 
        | Serial No : 03
        | Not Tested
        | Work on the valuidation and the saving of the meter details document
     */
    public function saveGenerateConsumerDemand(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'consumerId'    => "required|digits_between:1,9223372036854775807",
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $mWaterConsumerInitialMeter = new WaterConsumerInitialMeter();
            $mWaterConsumerMeter        = new WaterConsumerMeter();
            $mWaterMeterReadingDoc      = new WaterMeterReadingDoc();
            $refMeterConnectionType     = Config::get('waterConstaint.METER_CONN_TYPE');
            $meterRefImageName          = config::get('waterConstaint.WATER_METER_CODE');
            $demandIds = array();

            # Check and calculate Demand                    
            $consumerDetails = WaterWaterConsumer::findOrFail($request->consumerId);
            $this->checkDemandGeneration($request, $consumerDetails);                                       // unfinished function
            $calculatedDemand = collect($this->Repository->calConsumerDemand($request));
            if ($calculatedDemand['status'] == false) {
                throw new Exception($calculatedDemand['errors']);
            }

            # Save demand details 
            $this->begin();
            $userDetails = $this->checkUserType($request);
            if (isset($calculatedDemand)) {
                $demandDetails = collect($calculatedDemand['consumer_tax']['0']);
                switch ($demandDetails['charge_type']) {
                        # For Meter Connection
                    case ($refMeterConnectionType['1']):
                        $validated = Validator::make(
                            $request->all(),
                            [
                                'document' => "required|mimes:pdf,jpeg,png,jpg",
                            ]
                        );
                        if ($validated->fails())
                            return validationError($validated);
                        $meterDetails = $mWaterConsumerMeter->saveMeterReading($request);
                        $mWaterConsumerInitialMeter->saveConsumerReading($request, $meterDetails, $userDetails);
                        $demandIds = $this->savingDemand($calculatedDemand, $request, $consumerDetails, $demandDetails['charge_type'], $refMeterConnectionType, $userDetails);

                        # save the chages doc
                        $documentPath = $this->saveDocument($request, $meterRefImageName);
                        collect($demandIds)->map(function ($value)
                        use ($mWaterMeterReadingDoc, $meterDetails, $documentPath) {
                            $mWaterMeterReadingDoc->saveDemandDocs($meterDetails, $documentPath, $value);
                        });
                        break;
                        # For Average Connection / Meter.Fixed
                    case ($refMeterConnectionType['5']):
                        $validated = Validator::make(
                            $request->all(),
                            [
                                'document' => "required|mimes:pdf,jpeg,png,jpg",
                            ]
                        );
                        if ($validated->fails())
                            return validationError($validated);
                        $meterDetails = $mWaterConsumerMeter->saveMeterReading($request);
                        $mWaterConsumerInitialMeter->saveConsumerReading($request, $meterDetails, $userDetails);
                        $demandIds = $this->savingDemand($calculatedDemand, $request, $consumerDetails, $demandDetails['charge_type'], $refMeterConnectionType, $userDetails);

                        # save the chages doc
                        $documentPath = $this->saveDocument($request, $meterRefImageName);
                        collect($demandIds)->map(function ($value)
                        use ($mWaterMeterReadingDoc, $meterDetails, $documentPath) {
                            $mWaterMeterReadingDoc->saveDemandDocs($meterDetails, $documentPath, $value);
                        });
                        break;
                        # For Gallon Connection
                    case ($refMeterConnectionType['2']):
                        $validated = Validator::make(
                            $request->all(),
                            [
                                'document' => "required|mimes:pdf,jpeg,png,jpg",
                            ]
                        );
                        if ($validated->fails())
                            return validationError($validated);

                        $meterDetails = $mWaterConsumerMeter->saveMeterReading($request);
                        $mWaterConsumerInitialMeter->saveConsumerReading($request, $meterDetails, $userDetails);
                        $demandIds = $this->savingDemand($calculatedDemand, $request, $consumerDetails, $demandDetails['charge_type'], $refMeterConnectionType, $userDetails);

                        # save the chages doc
                        $documentPath = $this->saveDocument($request, $meterRefImageName);
                        collect($demandIds)->map(function ($value)
                        use ($mWaterMeterReadingDoc, $meterDetails, $documentPath) {
                            $mWaterMeterReadingDoc->saveDemandDocs($meterDetails, $documentPath, $value);
                        });
                        break;
                        # For Fixed connection
                    case ($refMeterConnectionType['3']):
                        $this->savingDemand($calculatedDemand, $request, $consumerDetails, $demandDetails['charge_type'], $refMeterConnectionType, $userDetails);
                        break;
                }
                $this->commit();
                return responseMsgs(true, "Demand Generated! for" . " " . $request->consumerId, "", "", "02", ".ms", "POST", "");
            }
        } catch (Exception $e) {
            $this->rollback();
            return responseMsgs(false, $e->getMessage(), [], "", "01", "ms", "POST", "");
        }
    }

    /**
     * | Save the Details for the Connection Type Meter 
     * | In Case Of Connection Type is meter OR Gallon 
     * | @param Request  
     * | @var mWaterConsumerDemand
     * | @var mWaterConsumerTax
     * | @var generatedDemand
     * | @var taxId
     * | @var meterDetails
     * | @var refDemands
        | Serial No : 03.01
        | Not Tested
     */
    public function savingDemand($calculatedDemand, $request, $consumerDetails, $demandType, $refMeterConnectionType, $userDetails)
    {
        $mWaterConsumerTax      = new WaterConsumerTax();
        $mWaterConsumerDemand   = new WaterConsumerDemand();
        $generatedDemand        = $calculatedDemand['consumer_tax'];

        $returnDemandIds = collect($generatedDemand)->map(function ($firstValue)
        use ($mWaterConsumerDemand, $consumerDetails, $request, $mWaterConsumerTax, $demandType, $refMeterConnectionType, $userDetails) {
            $taxId = $mWaterConsumerTax->saveConsumerTax($firstValue, $consumerDetails, $userDetails);
            $refDemandIds = array();
            # User for meter details entry
            $meterDetails = [
                "charge_type"       => $firstValue['charge_type'],
                "amount"            => $firstValue['charge_type'],
                "effective_from"    => $firstValue['effective_from'],
                "initial_reading"   => $firstValue['initial_reading'],
                "final_reading"     => $firstValue['final_reading'],
                "rate_id"           => $firstValue['rate_id'],
            ];
            switch ($demandType) {
                case ($refMeterConnectionType['1']):
                    $refDemands = $firstValue['consumer_demand'];
                    $check = collect($refDemands)->first();
                    if (is_array($check)) {
                        $refDemandIds = collect($refDemands)->map(function ($secondValue)
                        use ($mWaterConsumerDemand, $consumerDetails, $request, $taxId, $userDetails) {
                            $refDemandId = $mWaterConsumerDemand->saveConsumerDemand($secondValue, $consumerDetails, $request, $taxId, $userDetails);
                            return $refDemandId;
                        });
                        break;
                    }
                    $refDemandIds = $mWaterConsumerDemand->saveConsumerDemand($refDemands, $consumerDetails, $request, $taxId, $userDetails);
                    break;
                case ($refMeterConnectionType['5']):
                    $refDemands = $firstValue['consumer_demand'];
                    $check = collect($refDemands)->first();
                    if (is_array($check)) {
                        $refDemandIds = collect($refDemands)->map(function ($secondValue)
                        use ($mWaterConsumerDemand, $consumerDetails, $request, $taxId, $userDetails) {
                            $refDemandId = $mWaterConsumerDemand->saveConsumerDemand($secondValue, $consumerDetails, $request, $taxId, $userDetails);
                            return $refDemandId;
                        });
                        break;
                    }
                    $refDemandIds = $mWaterConsumerDemand->saveConsumerDemand($refDemands,  $consumerDetails, $request, $taxId, $userDetails);
                    break;
                case ($refMeterConnectionType['2']):
                    $refDemands = $firstValue['consumer_demand'];
                    $check = collect($refDemands)->first();
                    if (is_array($check)) {
                        $refDemandIds = collect($refDemands)->map(function ($secondValue)
                        use ($mWaterConsumerDemand, $consumerDetails, $request, $taxId, $userDetails) {
                            $refDemandId = $mWaterConsumerDemand->saveConsumerDemand($secondValue, $consumerDetails, $request, $taxId, $userDetails);
                            return $refDemandId;
                        });
                        break;
                    }
                    $refDemandIds = $mWaterConsumerDemand->saveConsumerDemand($refDemands,  $consumerDetails, $request, $taxId, $userDetails);
                    break;
                case ($refMeterConnectionType['3']):
                    $refDemands = $firstValue['consumer_demand'];
                    $check = collect($refDemands)->first();
                    if (is_array($check)) {
                        $refDemandIds = collect($refDemands)->map(function ($secondValue)
                        use ($mWaterConsumerDemand, $consumerDetails, $request, $taxId, $userDetails) {
                            $refDemandId = $mWaterConsumerDemand->saveConsumerDemand($secondValue,  $consumerDetails, $request, $taxId, $userDetails);
                            return $refDemandId;
                        });
                        break;
                    }
                    $refDemandIds = $mWaterConsumerDemand->saveConsumerDemand($refDemands, $consumerDetails, $request, $taxId, $userDetails);
                    break;
            }
            return $refDemandIds;
        });
        return $returnDemandIds;
    }

    /**
     * | Validate the user and other criteria for the Genereating demand
     * | @param request
        | Serial No : 03.02
        | Not Used 
     */
    public function checkDemandGeneration($request, $consumerDetails)
    {
        $user                   = Auth()->user();
        $today                  = Carbon::now();
        $refConsumerId          = $request->consumerId;
        $mWaterConsumerDemand   = new WaterConsumerDemand();

        $lastDemand = $mWaterConsumerDemand->getRefConsumerDemand($refConsumerId)->first();
        if ($lastDemand) {
            $refDemandUpto = Carbon::parse($lastDemand->demand_upto);
            if ($refDemandUpto > $today) {
                throw new Exception("The demand is generated till " . "" . $lastDemand->demand_upto);
            }
            $startDate  = Carbon::parse($refDemandUpto);
            $uptoMonth  = $startDate;
            $todayMonth = $today;
            if ($uptoMonth->greaterThan($todayMonth)) {
                throw new Exception("Demand should be generated in next month!");
            }
            $diffMonth = $startDate->diffInMonths($today);
            if ($diffMonth < 1) {
                throw new Exception("There should be a difference of month!");
            }
        }
    }



    /**
     * | Save the Meter details 
     * | @param request
        | Serial No : 04
        | Working  
        | Check the parameter for the autherised person
        | Chack the Demand for the fixed rate 
        | Re discuss
     */
    public function saveUpdateMeterDetails(reqMeterEntry $request)
    {
        try {
            $mWaterConsumerMeter    = new WaterConsumerMeter();
            $meterRefImageName      = config::get('waterConstaint.WATER_METER_CODE');
            $param                  = $this->checkParamForMeterEntry($request);

            $this->begin();
            $metaRequest = new Request([
                "consumerId"    => $request->consumerId,
                "finalRading"   => $request->oldMeterFinalReading,
                "demandUpto"    => $request->connectionDate,
                "document"      => $request->document,
            ]);
            if ($param['meterStatus'] != false) {
                $this->saveGenerateConsumerDemand($metaRequest);
            }
            $documentPath = $this->saveDocument($request, $meterRefImageName);
            $mWaterConsumerMeter->saveMeterDetails($request, $documentPath, $fixedRate = null);
            $this->commit();
            return responseMsgs(true, "Meter Detail Entry Success !", "", "", "01", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            $this->rollback();
            return responseMsgs(false, $e->getMessage(), "", "", "01", ".ms", "POST", "");
        }
    }

    /**
     * | Chech the parameter before Meter entry
     * | Validate the Admin For entring the meter details
     * | @param request
        | Serial No : 04.01
        | Working
        | Look for the meter status true condition while returning data
        | Recheck the process for meter and non meter 
        | validation for the respective meter conversion and verify the new consumer.
     */
    public function checkParamForMeterEntry($request)
    {
        $refConsumerId  = $request->consumerId;
        $todayDate      = Carbon::now();

        $mWaterWaterConsumer    = new WaterWaterConsumer();
        $mWaterConsumerMeter    = new WaterConsumerMeter();
        $mWaterConsumerDemand   = new WaterConsumerDemand();
        $refMeterConnType       = Config::get('waterConstaint.WATER_MASTER_DATA.METER_CONNECTION_TYPE');

        $refConsumerDetails     = $mWaterWaterConsumer->getConsumerDetailById($refConsumerId);
        if (!$refConsumerDetails) {
            throw new Exception("Consumer Details Not Found!");
        }
        $consumerMeterDetails   = $mWaterConsumerMeter->getMeterDetailsByConsumerId($refConsumerId)->first();
        $consumerDemand         = $mWaterConsumerDemand->getFirstConsumerDemand($refConsumerId)->first();

        # Check the meter/fixed case 
        $this->checkForMeterFixedCase($request, $consumerMeterDetails, $refMeterConnType);

        switch ($request) {
            case (strtotime($request->connectionDate) > strtotime($todayDate)):
                throw new Exception("Connection Date can not be greater than Current Date!");
                break;
            case ($request->connectionType != $refMeterConnType['Meter/Fixed']):
                if (!is_null($consumerMeterDetails)) {
                    if ($consumerMeterDetails->final_meter_reading >= $request->oldMeterFinalReading) {
                        throw new Exception("Reading Should be Greater Than last Reading!");
                    }
                }
                break;
            case ($request->connectionType != $refMeterConnType['Meter']):
                if (!is_null($consumerMeterDetails)) {
                    if ($consumerMeterDetails->connection_type == $request->connectionType) {
                        throw new Exception("You can not update same connection type as before!");
                    }
                }
                break;
        }

        # If Previous meter details exist
        if ($consumerMeterDetails) {
            # If fixed meter connection is changing to meter connection as per rule every connection should be in meter
            if ($request->connectionType != $refMeterConnType['Fixed'] && $consumerMeterDetails->connection_type == $refMeterConnType['Fixed']) {
                if ($consumerDemand) {
                    throw new Exception("Please pay the old demand amount. As per rule to change fixed connection to meter!");
                }
                throw new Exception("Please apply for regularization as per rule 16 your connection should be in meter!");
            }
            # If there is previous meter detail exist
            $reqConnectionDate = $request->connectionDate;
            if (strtotime($consumerMeterDetails->connection_date) > strtotime($reqConnectionDate)) {
                throw new Exception("Connection date should be greater than previous connection date!");
            }
            # Check the Conversion of the Connection
            $this->checkConnectionTypeUpdate($request, $consumerMeterDetails, $refMeterConnType);
        }

        # If the consumer demand exist
        if (isset($consumerDemand)) {
            $reqConnectionDate = $request->connectionDate;
            $reqConnectionDate = Carbon::parse($reqConnectionDate)->format('m');
            $consumerDmandDate = Carbon::parse($consumerDemand->demand_upto)->format('m');
            switch ($consumerDemand) {
                case ($consumerDmandDate >= $reqConnectionDate):
                    throw new Exception("Can not update connection date. Demand already generated upto that month!");
                    break;
            }
        }
        # If the meter detail do not exist 
        if (is_null($consumerMeterDetails)) {
            if (!in_array($request->connectionType, [$refMeterConnType['Meter'], $refMeterConnType['Gallon']])) {
                throw new Exception("New meter connection should be in meter and gallon!");
            }
            $returnData['meterStatus'] = false;
        }
        return $returnData;
    }


    /**
     * | Check the meter connection type in the case of meter updation 
     * | If the meter details exist check the connection type 
        | Serial No :
        | Under Con
     */
    public function checkConnectionTypeUpdate($request, $consumerMeterDetails, $refMeterConnType)
    {
        $currentConnectionType      = $consumerMeterDetails->connection_type;
        $requestedConnectionType    = $request->connectionType;

        switch ($currentConnectionType) {
                # For Fixed Connection
            case ($refMeterConnType['Fixed']):
                if ($requestedConnectionType != $refMeterConnType['Meter'] || $requestedConnectionType != $refMeterConnType['Gallon']) {
                    throw new Exception("Invalid connection type update for Fixed!");
                }
                break;
                # For Fixed Meter Connection
            case ($refMeterConnType['Meter']):
                if ($requestedConnectionType != $refMeterConnType['Meter'] || $requestedConnectionType != $refMeterConnType['Gallon'] || $requestedConnectionType != $refMeterConnType['Meter/Fixed']) {
                    throw new Exception("Invalid connection type update for Fixed!");
                }
                break;
                # For Fixed Gallon Connection
            case ($refMeterConnType['Gallon']):
                if ($requestedConnectionType != $refMeterConnType['Meter']) {
                    throw new Exception("Invalid connection type update for Fixed!");
                }
                break;
                # For Fixed Meter/Fixed Connection
            case ($refMeterConnType['Meter/Fixed']):
                if ($requestedConnectionType != $refMeterConnType['Meter']) {
                    throw new Exception("Invalid connection type update for Fixed!");
                }
                break;
                # Default
            default:
                throw new Exception("Invalid Meter Connection!");
                break;
        }
    }



    /**
     * | Check for the Meter/Fixed 
     * | @param request
     * | @param consumerMeterDetails
        | Serial No : 04.01.01
        | Not Working
     */
    public function checkForMeterFixedCase($request, $consumerMeterDetails, $refMeterConnType)
    {
        if ($request->connectionType == $refMeterConnType['Meter/Fixed']) {
            $refConnectionType = 1;
            if ($consumerMeterDetails->connection_type == $refConnectionType && $consumerMeterDetails->meter_status == 0) {
                throw new Exception("You can not update same connection type as before!");
            }
            if ($request->meterNo != $consumerMeterDetails->meter_no) {
                throw new Exception("You Can Meter/Fixed The Connection On Priviuse Meter");
            }
        }
    }

    /**
     * | Save the Document for the Meter Entry 
     * | Return the Document Path
     * | @param request
        | Serial No : 04.02 / 06.02
        | Working
        | Common function
     */
    public function saveDocument($request, $refImageName)
    {
        $document       = $request->document;
        $docUpload      = new DocUpload;
        $relativePath   = Config::get('waterConstaint.WATER_RELATIVE_PATH');
        $folder = public_path("/$relativePath");
        if (!file_exists($folder)) {
            mkdir($folder, 0777);
        }

        $imageName = $docUpload->upload($refImageName, $document, $relativePath);
        $doc = [
            "document"      => $imageName,
            "relaivePath"   => $relativePath
        ];
        return $doc;
    }


    /**
     * | Get all the meter details According to the consumer Id
     * | @param request
     * | @var 
     * | @return 
        | Serial No : 05
        | Not Working
     */
    public function getMeterList(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'consumerId' => "required|digits_between:1,9223372036854775807",
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $meterConnectionType    = null;
            $mWaterConsumerMeter    = new WaterConsumerMeter();
            $refWaterNewConnection  = new WaterNewConnection();
            $refMeterConnType       = Config::get('waterConstaint.WATER_MASTER_DATA.METER_CONNECTION_TYPE');

            $meterList = $mWaterConsumerMeter->getMeterDetailsByConsumerId($request->consumerId)->get();
            $returnData = collect($meterList)->map(function ($value)
            use ($refMeterConnType, $meterConnectionType, $refWaterNewConnection) {
                switch ($value['connection_type']) {
                    case ($refMeterConnType['Meter']):
                        if ($value['meter_status'] == 0) {
                            $meterConnectionType = "Metre/Fixed";                               // Static
                        }
                        $meterConnectionType = "Meter";                                         // Static
                        break;

                    case ($refMeterConnType['Gallon']):
                        $meterConnectionType = "Gallon";                                        // Static
                        break;
                    case ($refMeterConnType['Fixed']):
                        $meterConnectionType = "Fixed";                                         // Static
                        break;
                }
                $value['meter_connection_type'] = $meterConnectionType;
                $path = $refWaterNewConnection->readDocumentPath($value['doc_path']);
                $value['doc_path'] = !empty(trim($value['doc_path'])) ? $path : null;
                return $value;
            });
            return responseMsgs(true, "Meter List!", remove_null($returnData), "", "01", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", ".ms", "POST", "");
        }
    }

    /**
     * | Check the condition before appling for deactivation
     * | @param
     * | @var 
        | Not Working
        | Serial No : 06.01
        | Recheck the amount and the order from weaver committee 
        | Check if the consumer applied for other requests
        | Ceheck the date of the consumer demand
     */
    public function PreConsumerDeactivationCheck($request)
    {
        $consumerId                     = $request->consumerId;
        $mWaterWaterConsumer            = new WaterWaterConsumer();
        $mWaterConsumerDemand           = new WaterConsumerDemand();
        $mWaterConsumerActiveRequest    = new WaterConsumerActiveRequest();
        $refUserType                    = Config::get('waterConstaint.REF_USER_TYPE');

        $refConsumerDetails = $mWaterWaterConsumer->getConsumerDetailById($consumerId);
        $pendingDemand      = $mWaterConsumerDemand->getConsumerDemand($consumerId);
        $firstPendingDemand = collect($pendingDemand)->first();

        if (isset($firstPendingDemand)) {
            throw new Exception("There are unpaid pending demand!");
        }
        if (isset($request->ulbId) && $request->ulbId != $refConsumerDetails->ulb_id) {
            throw new Exception("Ulb not matched according to consumer connection!");
        }
        $activeReq = $mWaterConsumerActiveRequest->getRequestByConId($consumerId)->first();
        if ($activeReq) {
            throw new Exception("There are other request applied for respective consumer connection!");
        }
        return [
            "consumerDetails" => $refConsumerDetails
        ];
    }



    /**
     * | Post Other Payment Modes for Cheque,DD,Neft
     * | @param req
        | Serial No : 06.03.01
        | Not Working
     */
    public function postOtherPaymentModes($req)
    {
        $cash = Config::get('payment-constants.PAYMENT_MODE.3');
        $moduleId = Config::get('module-constants.WATER_MODULE_ID');
        $mTempTransaction = new TempTransaction();

        if ($req['paymentMode'] != $cash) {
            $mPropChequeDtl = new WaterChequeDtl();
            $chequeReqs = [
                'user_id'           => $req['userId'],
                'consumer_id'       => $req['id'],
                'transaction_id'    => $req['tranId'],
                'cheque_date'       => $req['chequeDate'],
                'bank_name'         => $req['bankName'],
                'branch_name'       => $req['branchName'],
                'cheque_no'         => $req['chequeNo']
            ];

            $mPropChequeDtl->postChequeDtl($chequeReqs);
        }

        $tranReqs = [
            'transaction_id'    => $req['tranId'],
            'application_id'    => $req['id'],
            'module_id'         => $moduleId,
            'workflow_id'       => $req['workflowId'] ?? 0,
            'transaction_no'    => $req['tranNo'],
            'application_no'    => $req['applicationNo'],
            'amount'            => $req['amount'],
            'payment_mode'      => strtoupper($req['paymentMode']),
            'cheque_dd_no'      => $req['chequeNo'],
            'bank_name'         => $req['bankName'],
            'tran_date'         => $req['todayDate'],
            'user_id'           => $req['userId'],
            'ulb_id'            => $req['ulbId'],
            'ward_no'           => $req['ward_no']
        ];
        $mTempTransaction->tempTransaction($tranReqs);
    }


    #---------------------------------------------------------------------------------------------------------#

    /**
     * | Demand deactivation process
     * | @param 
     * | @var 
     * | @return 
        | Not Working
        | Serial No :
        | Not Build
     */
    public function consumerDemandDeactivation(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'consumerId'    => "required|digits_between:1,9223372036854775807",
                'demandId'      => "required|array|unique:water_consumer_demands,id'",
                'paymentMode'   => "required|in:Cash,Cheque,DD",
                'amount'        => "required",
                'reason'        => "required"
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $mWaterWaterConsumer = new WaterWaterConsumer();
            $mWaterConsumerDemand = new WaterConsumerDemand();

            $this->checkDeactivationDemand($request);
            $this->checkForPayment($request);
        } catch (Exception $e) {
            return responseMsgs(true, $e->getMessage(), "", "", "01", ".ms", "POST", $request->deviceId);
        }
    }

    /**
     * | check if the following conditon if fullfilled for demand deactivation
     * | check for valid user
     * | @param request
     * | @var 
     * | @return 
        | Not Working
        | Serial No: 
        | Not Build
        | Get Concept for deactivation demand
     */
    public function checkDeactivationDemand($request)
    {
        return true;
    }

    /**
     * | Check the concept for payment and amount
     * | @param request
     * | @var 
     * | @return 
        | Not Working
        | Serial No:
        | Get Concept Notes for demand deactivation 
     */
    public function checkForPayment($request)
    {
        $mWaterTran = new WaterTran();
    }

    #---------------------------------------------------------------------------------------------------------#


    /**
     * | View details of the caretaken water connection
     * | using user id
     * | @param request
        | Working
        | Serial No : 07
     */
    public function viewCaretakenConnection(Request $request)
    {
        try {
            $mWaterWaterConsumer        = new WaterWaterConsumer();
            $mActiveCitizenUndercare    = new ActiveCitizenUndercare();

            $connectionDetails = $mActiveCitizenUndercare->getDetailsByCitizenId();
            $checkDemand = collect($connectionDetails)->first();
            if (is_null($checkDemand))
                throw new Exception("Under taken data not found!");

            $consumerIds = collect($connectionDetails)->pluck('consumer_id');
            $consumerDetails = $mWaterWaterConsumer->getConsumerByIds($consumerIds)->get();
            $checkConsumer = collect($consumerDetails)->first();
            if (is_null($checkConsumer)) {
                throw new Exception("Consuemr Details Not Found!");
            }
            return responseMsgs(true, 'List of undertaken water connections!', remove_null($consumerDetails), "", "01", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "01", ".ms", "POST", $request->deviceId);
        }
    }


    /**
     * | Add Fixed Rate for the Meter connection is under Fixed
     * | Admin Entered Data
        | Serial No : 08
        | Use It
        | Recheck 
     */
    public function addFixedRate(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'consumerId'    => "required|digits_between:1,9223372036854775807",
                'document'      => "required|mimes:pdf,jpg,jpeg,png",
                'ratePerMonth'  => "required|numeric"
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $consumerId             = $request->consumerId;
            $mWaterConsumerMeter    = new WaterConsumerMeter();
            $fixedMeterCode         = Config::get("waterConstaint.WATER_FIXED_CODE");

            $relatedDetails = $this->checkParamForFixedEntry($consumerId);
            $metaRequest = new Request([
                'consumerId'                => $consumerId,
                'connectionDate'            => $relatedDetails['meterDetails']['connection_date'],
                'connectionType'            => $relatedDetails['meterDetails']['connection_type'],
                'newMeterInitialReading'    => $relatedDetails['meterDetails']['initial_reading']
            ]);

            $this->begin();
            $refDocument = $this->saveDocument($request, $fixedMeterCode);
            $document = [
                'relaivePath'   => $refDocument['relaivePath'],
                'document'      => $refDocument['document']
            ];
            $mWaterConsumerMeter->saveMeterDetails($metaRequest, $document, $request->ratePerMonth);
            $this->commit();
            return responseMsgs(true, "Fixed rate entered successfully!", "", "", "01", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            $this->rollback();
            return responseMsgs(false, $e->getMessage(), [""], "", "01", ".ms", "POST", $request->deviceId);
        }
    }


    /**
     * | Check the parameter for Fixed meter entry
     * | @param consumerId
        | Seriel No : 08.01
        | Not used
     */
    public function checkParamForFixedEntry($consumerId)
    {
        $mWaterConsumerMeter    = new WaterConsumerMeter();
        $mWaterWaterConsumer    = new WaterWaterConsumer();
        $refPropertyType        = Config::get('waterConstaint.PROPERTY_TYPE');
        $refConnectionType      = Config::get('waterConstaint.WATER_MASTER_DATA.METER_CONNECTION_TYPE');

        // $consumerDetails = $mWaterWaterConsumer->getConsumerDetailById($consumerId);
        // if ($consumerDetails->property_type_id != $refPropertyType['Government'])
        // throw new Exception("Consumer's property type is not under Government!");

        $meterConnectionDetails = $mWaterConsumerMeter->getMeterDetailsByConsumerId($consumerId)->first();
        if (!$meterConnectionDetails)
            throw new Exception("Consumer meter details not found maybe meter is not installed!");

        if ($meterConnectionDetails->connection_type != $refConnectionType['Fixed'])
            throw new Exception("Consumer meter's connection type is not fixed!");

        return [
            "meterDetails" => $meterConnectionDetails
        ];
    }


    /**
     * | Calculate Final meter reading according to demand upto date and previous upto data 
     * | @param request
        | Serial No : 09
        | Working
     */
    public function calculateMeterFixedReading(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'consumerId'  => "required|",
                'uptoData'    => "required|date",
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $todayDate                  = Carbon::now();
            $refConsumerId              = $request->consumerId;
            $mWaterConsumerDemand       = new WaterConsumerDemand();
            $mWaterConsumerInitialMeter = new WaterConsumerInitialMeter();

            if ($request->uptoData > $todayDate) {
                throw new Exception("uptoDate should not be grater than" . " " . $todayDate);
            }
            $refConsumerDemand = $mWaterConsumerDemand->consumerDemandByConsumerId($refConsumerId);
            if (is_null($refConsumerDemand)) {
                throw new Exception("There should be last data regarding meter!");
            }

            $refOldDemandUpto   = $refConsumerDemand->demand_upto;
            $privdayDiff        = Carbon::parse($refConsumerDemand->demand_upto)->diffInDays(Carbon::parse($refConsumerDemand->demand_from));
            $endDate            = Carbon::parse($request->uptoData);
            $startDate          = Carbon::parse($refOldDemandUpto);

            $difference = $endDate->diffInMonths($startDate);
            if ($difference < 1 || $startDate > $endDate) {
                throw new Exception("current uptoData should be grater than the previous uptoDate! and should have a month difference!");
            }
            $diffInDays = $endDate->diffInDays($startDate);
            $finalMeterReading = $mWaterConsumerInitialMeter->getmeterReadingAndDetails($refConsumerId)
                ->orderByDesc('id')
                ->first();
            $finalSecondLastReading = $mWaterConsumerInitialMeter->getSecondLastReading($refConsumerId, $finalMeterReading->id);
            if (is_null($refConsumerDemand)) {
                throw new Exception("There should be demand for the previous meter entry!");
            }

            $refTaxUnitConsumed = ($finalMeterReading['initial_reading'] ?? 0) - ($finalSecondLastReading['initial_reading'] ?? 0);
            $avgReading         = $privdayDiff > 0 ? $refTaxUnitConsumed / $privdayDiff : 1;
            $lastMeterReading   = $finalMeterReading->initial_reading;
            $ActualReading      = ($diffInDays * $avgReading) + $lastMeterReading;

            $returnData['finalMeterReading']    = round($ActualReading, 2);
            $returnData['diffInDays']           = $diffInDays;
            $returnData['previousConsumed']     = $refTaxUnitConsumed;

            return responseMsgs(true, "calculated date difference!", $returnData, "", "01", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", ".ms", "POST", $request->deviceId);
        }
    }


    /**
     * | Get Details for memo
     * | Get all details for the consumer application and consumer both details 
     * | @param request
        | Serial No 
        | Use
        | Not Finished
        | Get the card details 
     */
    public function generateMemo(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'consumerNo'  => "required",
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $refConsumerNo          = $request->consumerNo;
            $mWaterWaterConsumer    = new WaterWaterConsumer();
            $mWaterTran             = new WaterTran();

            $dbKey = "consumer_no";
            $consumerDetails = $mWaterWaterConsumer->getRefDetailByConsumerNo($dbKey, $refConsumerNo)->first();
            if (is_null($consumerDetails)) {
                throw new Exception("Consumer Details not found!");
            }
             $applicationDetails = $this->Repository->getconsumerRelatedData($consumerDetails->id);
            if (is_null($applicationDetails)) {
                throw new Exception("Application Details not found!");
            }
            $transactionDetails = $mWaterTran->getTransNo($consumerDetails->apply_connection_id, null)->get();
            $checkTransaction = collect($transactionDetails)->first();
            if ($checkTransaction) {
                throw new Exception("Transaction not found!");
            }

            $consumerDetails;           // consumer related details
            $applicationDetails;        // application / owners / siteinspection related details
            $transactionDetails;        // all transactions details
            $var = null;

            $returnValues = [
                "consumerNo"            => $var,
                "applicationNo"         => $var,
                "year"                  => $var,
                "receivingDate"         => $var,
                "ApprovalDate"          => $var,
                "receiptNo"             => $var,
                "paymentDate"           => $var,
                "wardNo"                => $var,
                "applicantName"         => $var,
                "guardianName"          => $var,
                "correspondingAddress"  => $var,
                "mobileNo"              => $var,
                "email"                 => $var,
                "holdingNo"             => $var,
                "safNo"                 => $var,
                "builUpArea"            => $var,
                "connectionThrough"     => $var,
                "AppliedFrom"           => $var,
                "ownersDetails"         => $var,
                "siteInspectionDetails" => $var,


            ];
            return responseMsgs(true, "successfully fetched memo details!", remove_null($returnValues), "", "01", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", ".ms", "POST", $request->deviceId);
        }
    }

    //Start///////////////////////////////////////////////////////////////////////
    /**
     * | Search the governmental prop water commention 
     * | Search only the Gov water connections 
        | Serial No :
        | use
        | Not finished
     */
    public function searchFixedConsumers(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'filterBy'  => 'required',
                'parameter' => 'required'
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {

            return $waterReturnDetails = $this->getDetailByConsumerNo($request, 'consumer_no', '2016000500');
            return false;

            $mWaterConsumer = new WaterWaterConsumer();
            $key            = $request->filterBy;
            $paramenter     = $request->parameter;
            $string         = preg_replace("/([A-Z])/", "_$1", $key);
            $refstring      = strtolower($string);

            switch ($key) {
                case ("consumerNo"):                                                                        // Static
                    $waterReturnDetails = $this->getDetailByConsumerNo($request, $refstring, $paramenter);
                    $checkVal = collect($waterReturnDetails)->first();
                    if (!$checkVal)
                        throw new Exception("Data according to " . $key . " not Found!");
                    break;
                case ("holdingNo"):                                                                         // Static
                    $waterReturnDetails = $mWaterConsumer->getDetailByConsumerNo($request, $refstring, $paramenter)->get();
                    $checkVal = collect($waterReturnDetails)->first();
                    if (!$checkVal)
                        throw new Exception("Data according to " . $key . " not Found!");
                    break;
                case ("safNo"):                                                                             // Static
                    $waterReturnDetails = $mWaterConsumer->getDetailByConsumerNo($request, $refstring, $paramenter)->get();
                    $checkVal = collect($waterReturnDetails)->first();
                    if (!$checkVal)
                        throw new Exception("Data according to " . $key . " not Found!");
                    break;
                case ("applicantName"):                                                                     // Static
                    $paramenter = strtoupper($paramenter);
                    $waterReturnDetails = $mWaterConsumer->getDetailByOwnerDetails($refstring, $paramenter)->get();
                    $checkVal = collect($waterReturnDetails)->first();
                    if (!$checkVal)
                        throw new Exception("Data according to " . $key . " not Found!");
                    break;
                case ('mobileNo'):                                                                          // Static
                    $paramenter = strtoupper($paramenter);
                    $waterReturnDetails = $mWaterConsumer->getDetailByOwnerDetails($refstring, $paramenter)->get();
                    $checkVal = collect($waterReturnDetails)->first();
                    if (!$checkVal)
                        throw new Exception("Data according to " . $key . " not Found!");
                    break;
                default:
                    throw new Exception("Data provided in filterBy is not valid!");
            }
            return responseMsgs(true, "Water Consumer Data According To Parameter!", remove_null($waterReturnDetails), "", "01", "652 ms", "POST", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    // Calling function
    public function getDetailByConsumerNo($req, $key, $refNo)
    {
        $refConnectionType = Config::get('waterConstaint.WATER_MASTER_DATA.METER_CONNECTION_TYPE');
        return WaterWaterConsumer::select(
            'water_consumers.id',
            'water_consumers.consumer_no',
            'water_consumers.ward_mstr_id',
            'water_consumers.address',
            'water_consumers.holding_no',
            'water_consumers.saf_no',
            'water_consumers.ulb_id',
            'ulb_ward_masters.ward_name',
            DB::raw("string_agg(water_consumer_owners.applicant_name,',') as applicant_name"),
            DB::raw("string_agg(water_consumer_owners.mobile_no::VARCHAR,',') as mobile_no"),
            DB::raw("string_agg(water_consumer_owners.guardian_name,',') as guardian_name"),
        )
            ->join('water_consumer_owners', 'water_consumer_owners.consumer_id', '=', 'water_consumers.id')
            ->leftJoin('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'water_consumers.ward_mstr_id')
            ->leftjoin('water_consumer_meters', 'water_consumer_meters.consumer_id', 'water_consumers.id')
            ->where('water_consumers.' . $key, 'LIKE', '%' . $refNo . '%')
            ->where('water_consumers.status', 1)
            ->where('water_consumers.ulb_id', authUser($req)->ulb_id)
            ->where('water_consumer_meters.connection_type', $refConnectionType['Fixed'])
            ->groupBy(
                'water_consumers.saf_no',
                'water_consumers.holding_no',
                'water_consumers.address',
                'water_consumers.id',
                'water_consumers.ulb_id',
                'water_consumer_owners.consumer_id',
                'water_consumers.consumer_no',
                'water_consumers.ward_mstr_id',
                'ulb_ward_masters.ward_name'
            )->first();
    }
    ///////////////////////////////////////////////////////////////////////End//

    /**
     * | Citizen self generation of demand 
     * | generate demand only the last day of the month
        | Serial No :
        | Working
     */
    public function selfGenerateDemand(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            [
                'id'            => 'required',
                'finalReading'  => 'required',
                'document'      => 'required|file|'
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $today                  = Carbon::now();
            $consumerId             = $req->id;
            $mWaterWaterConsumer    = new WaterWaterConsumer();
            $refConsumerDetails     = $mWaterWaterConsumer->getConsumerDetailById($consumerId);
            $refDetails             = $this->checkUser($req, $refConsumerDetails);
            $metaReq = new Request([
                "consumerId" => $consumerId
            ]);

            $this->checkDemandGeneration($metaReq, $refConsumerDetails);
            $metaRequest = new Request([
                "consumerId"    => $consumerId,
                "finalRading"   => $req->finalReading,                          // if the demand is generated for the first time
                "demandUpto"    => $today->format('Y-m-d'),
                "document"      => $req->document,
            ]);
            $returnDetails = $this->saveGenerateConsumerDemand($metaRequest);
            if ($returnDetails->original['status'] == false) {
                throw new Exception($returnDetails->original['message']);
            }
            return responseMsgs(true, "Self Demand Generated!", [], "", "01", ".ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", ".ms", "POST", $req->deviceId);
        }
    }

    /**
     * | Check the user details for self demand generation
     * | check the consumer details with user details
        | Serial No :
     */
    public function checkUser($req, $refConsumerDetails)
    {
        $user                       = authUser($req);
        $todayDate                  = Carbon::now();
        $endDate                    = Carbon::now()->endOfMonth();
        $formatEndDate              = $endDate->format('d-m-Y');
        $refUserType                = Config::get("waterConstaint.REF_USER_TYPE");
        $mActiveCitizenUndercare    = new ActiveCitizenUndercare();

        // if ($endDate > $todayDate) {
        //     throw new Exception("Please generate the demand on $formatEndDate or after it!");
        // }
        $careTakerDetails   = $mActiveCitizenUndercare->getWaterUnderCare($user->id)->get();
        $consumerIds        = collect($careTakerDetails)->pluck('consumer_id');
        if (!in_array($req->id, ($consumerIds->toArray()))) {
            if ($refConsumerDetails->user_type != $refUserType['1']) {
                throw new Exception("You are not the citizen whose consumer is assigned!");
            }
            if ($refConsumerDetails->user_id != $user->id) {
                throw new Exception("You are not the authorized user!");
            }
        }
    }

    /**
     * | Check the user type and return its id
        | Serial No :
        | Working
     */
    public function checkUserType($req)
    {
        $user = Auth()->user();
        $confUserType = Config::get("waterConstaint.REF_USER_TYPE");
        $userType = $user->user_type;

        if ($userType == $confUserType['1']) {
            return [
                "citizen_id"    => $user->id,
                "user_type"     => $userType
            ];
        } else {
            return [
                "emp_id"    => $user->id,
                "user_type" => $userType
            ];
        }
    }


    /**
     * | Add the advance amount for consumer 
     * | If advance amount is present it should be added by a certain official
        | Serial No :
        | Under Con
     */
    public function addAdvance(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            [
                'consumerId'    => 'required|int',
                'amount'        => 'required|int',
                'document'      => 'required|file|',
                'remarks'       => 'required',
                'reason'        => 'nullable'
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            $user           = authUser($req);
            $docAdvanceCode = Config::get('waterConstaint.WATER_ADVANCE_CODE');
            $refAdvanceFor  = Config::get('waterConstaint.ADVANCE_FOR');
            $refWorkflow    = Config::get('workflow-constants.WATER_MASTER_ID');
            $mWaterAdvance  = new WaterAdvance();

            $refDetails = $this->checkParamForAdvanceEntry($req, $user);
            $req->request->add(['workflowId' => $refWorkflow]);
            $roleDetails = $this->getRole($req);
            $roleId = $roleDetails['wf_role_id'];
            $req->request->add(['roleId' => $roleId]);

            $this->begin();
            $docDetails = $this->saveDocument($req, $docAdvanceCode);
            $req->merge([
                "relatedId" => $req->consumerId,
                "userId"    => $user->id,
                "userType"  => $user->user_type,
            ]);
            $mWaterAdvance->saveAdvanceDetails($req, $refAdvanceFor['1'], $docDetails);
            $this->commit();
            return responseMsgs(true, "Advance details saved successfully!", [], "", "01", ".ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            $this->rollback();
            return responseMsgs(false, $e->getMessage(), [], "", "01", ".ms", "POST", $req->deviceId);
        }
    }


    /**
     * | Chech the params for adding advance 
        | Serial No :
        | Under Con
        | Check the autherised user is entring the advance amount
     */
    public function checkParamForAdvanceEntry($req, $user)
    {
        $consumerId = $req->consumerId;
        $refUserType = Config::get("waterConstaint.REF_USER_TYPE");
        $mWaterWaterConsumer = new WaterWaterConsumer();

        $consumerDetails = $mWaterWaterConsumer->getConsumerDetailById($consumerId);
        if ($user->user_type == $refUserType['1']) {
            throw new Exception("You are not a verified Use!");
        }
    }


    /**
     * | Get meter list for display in the process of meter entry
        | Serial No 
        | Working  
     */
    public function getConnectionList(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'consumerId' => "required|digits_between:1,9223372036854775807",
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $consumerid             = $request->consumerId;
            $mWaterConsumerMeter    = new WaterConsumerMeter();
            $refMeterConnType       = Config::get('waterConstaint.WATER_MASTER_DATA.METER_CONNECTION_TYPE');
            $consumerMeterDetails   = $mWaterConsumerMeter->getMeterDetailsByConsumerId($consumerid)->first();

            # If consumer details are null, set an indicator key and default values
            if (!$consumerMeterDetails) {
                $status = false;
                $defaultTypes = ['Meter', 'Gallon'];
            }
            # Consumer details are not null, check connection_type 
            else {
                $status = true;
                $connectionType = $consumerMeterDetails->connection_type;
                switch ($connectionType) {
                    case ("1"):                                 // Static
                        $defaultTypes = ['Meter', 'Gallon', 'Meter/Fixed'];
                        break;
                    case ("2"):                                 // Static
                        $defaultTypes = ['Meter'];
                        break;
                    case ("3"):                                 // Static
                        $defaultTypes = ['Meter'];
                        break;
                    case ("4"):                                 // Static
                        $defaultTypes = ['Meter'];
                        break;
                }
            }
            foreach ($defaultTypes as $type) {
                $responseArray['displayData'][] = [
                    'id'    => $refMeterConnType[$type],
                    'name'  => strtoupper($type)
                ];
            }
            $responseArray['status'] = $status;
            return responseMsgs(true, "Meter List!", $responseArray, "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }


    /**
     * | Apply for Ferule cleaning and Pipe shifting
        | Serial No :
        | Working
     */
    public function applyConsumerRequest(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                "consumerId"  => 'required',
                "remarks"     => 'required',
                "mobileNo"    => 'required|numeric',            // Corresponding Mobile no
                "requestType" => 'required|in:4,5'              // Charge Catagory Id
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $user       = authUser($request);
            $penalty    = 0;                                  // Static
            $consumerId = $request->consumerId;

            $mWfWorkflow                    = new WfWorkflow();
            $mWorkflowTrack                 = new WorkflowTrack();
            $mWaterWaterConsumer            = new WaterWaterConsumer();
            $mWaterConsumerActiveRequest    = new WaterConsumerActiveRequest();
            $mwaterConsumerCharge           = new WaterConsumerCharge();
            $mWaterConsumerChargeCategory   = new WaterConsumerChargeCategory();

            $refChargeCatagory  = Config::get("waterConstaint.CONSUMER_CHARGE_CATAGORY");
            $refUserType        = Config::get('waterConstaint.REF_USER_TYPE');
            $refApplyFrom       = Config::get('waterConstaint.APP_APPLY_FROM');
            $refModuleId        = Config::get('module-constants.WATER_MODULE_ID');

            $waterConsumerDetails   = $mWaterWaterConsumer->getConsumerDetailById($consumerId);
            $ulbId                  = $waterConsumerDetails['ulb_id'];
            $request->merge(["ulbId" => $ulbId]);

            # Check param for appliying for requests
            $refRelatedDetails = $this->checkParamForFeruleAndPipe($request, $waterConsumerDetails);
            $consumerCharges = $mWaterConsumerChargeCategory->getChargesByid($request->requestType);
            if (!$consumerCharges || !in_array($request->requestType, [$refChargeCatagory['FERRULE_CLEANING_CHECKING'], $refChargeCatagory['PIPE_SHIFTING_ALTERATION']])) {
                throw new Exception("Consumer charges not found");
            }

            # Get wf details
            $ulbWorkflowId  = $mWfWorkflow->getulbWorkflowId($refRelatedDetails['workflowMasterId'], $ulbId);
            if (!$ulbWorkflowId) {
                throw new Exception("Respective ULB IS NOT MAPED TO WORKFLOW!");
            }

            # If the user is not citizen
            if ($user->user_type != $refUserType['1']) {
                $request->request->add(['workflowId' => $ulbWorkflowId->id]);
                $roleDetails = $this->getRole($request);
                if (!$roleDetails) {
                    throw new exception('role not found');
                }
                $roleId = $roleDetails['wf_role_id'];
                $refRequest = [
                    "applyFrom" => $user->user_type,
                    "empId"     => $user->id
                ];
            } else {
                $refRequest = [
                    "applyFrom" => $refApplyFrom['1'],
                    "citizenId" => $user->id
                ];
            }

            # Get Initiater and finisher role 
            $refInitiaterRoleId  = $this->getInitiatorId($ulbWorkflowId->id);
            $refFinisherRoleId   = $this->getFinisherId($ulbWorkflowId->id);
            $finisherRoleId      = DB::select($refFinisherRoleId);
            $initiatorRoleId     = DB::select($refInitiaterRoleId);
            if (!$finisherRoleId || !$initiatorRoleId) {
                throw new Exception('initiator or finisher not found ');
            }
            $refRequest["initiatorRoleId"]  = collect($initiatorRoleId)->first()->role_id;
            $refRequest["finisherRoleId"]   = collect($finisherRoleId)->first()->role_id;
            $refRequest["roleId"]           = $roleId ?? null;
            $refRequest["userType"]         = $user->user_type;
            $refRequest["amount"]           = $consumerCharges->amount + $penalty;
            $refRequest["ulbWorkflowId"]    = $ulbWorkflowId->id;
            $refRequest["chargeCategory"]   = $consumerCharges->charge_category;
            $refRequest["chargeAmount"]     = $consumerCharges->amount;
            $refRequest["ruleSet"]          = null;
            $refRequest["chargeCategoryId"] = $consumerCharges->id;

            $this->begin();
            $idGeneration   = new PrefixIdGenerator($refRelatedDetails['idGenParam'], $ulbId);
            $applicationNo  = $idGeneration->generate();
            $applicationNo  = str_replace('/', '-', $applicationNo);

            $consumerRequestDetails = $mWaterConsumerActiveRequest->saveRequestDetails($request, $waterConsumerDetails, $refRequest, $applicationNo);
            $refRequest["relatedId"] = $consumerRequestDetails['id'];
            $mwaterConsumerCharge->saveConsumerCharges($refRequest, $consumerId, $consumerCharges->charge_category);

            # Save data in track
            $metaReqs = new Request(
                [
                    'citizenId'         => $refRequest['citizenId'] ?? null,
                    'moduleId'          => $refModuleId,
                    'workflowId'        => $ulbWorkflowId->id,
                    'refTableDotId'     => 'water_consumer_active_requests.id',             // Static    
                    'refTableIdValue'   => $consumerRequestDetails['id'],
                    'user_id'           => $user->id ?? null,
                    'ulb_id'            => $ulbId,
                    'senderRoleId'      => $refRequest['empId'] ?? null,
                    'receiverRoleId'    => collect($initiatorRoleId)->first()->role_id,
                ]
            );
            $mWorkflowTrack->saveTrack($metaReqs);
            $this->commit();
            $returnData = [
                "ApplicationNo" => $applicationNo
            ];
            return responseMsgs(true, "Successfully applied for the Request!", $returnData, "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            $this->rollback();
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }


    /**
     * | Check param before appliying for pipe shifting and ferrul cleaning
        | Serial No :
        | Under Con
     */
    public function checkParamForFeruleAndPipe($request, $waterConsumerDetails)
    {
        $mWaterConsumerActiveRequest    = new WaterConsumerActiveRequest();
        $refWorkflow                    = Config::get('workflow-constants.WATER_CONSUMER_WF');
        $refChargeCatagory              = Config::get('waterConstaint.CONSUMER_CHARGE_CATAGORY');
        $refConParamId                  = Config::get('waterConstaint.PARAM_IDS');

        if (!$waterConsumerDetails) {
            throw new Exception("Water Consumer not found for given consumer Id!");
        }
        if ($request->requestType == $refChargeCatagory['FERRULE_CLEANING_CHECKING']) {
            $workflowId = $refWorkflow['FERRULE_CLEANING_CHECKING'];
            $refConParamId = $refConParamId['WFC'];
        } else {
            $workflowId = $refWorkflow['PIPE_SHIFTING_ALTERATION'];
            $refConParamId = $refConParamId['WPS'];
        }

        # Check if the request is already running 
        $isPerReq = $mWaterConsumerActiveRequest->getRequestByConId($request->consumerId)
            ->where('charge_catagory_id', $request->requestType)
            ->first();
        if ($isPerReq) {
            throw new Exception("Pre Request are in process!");
        }
        return [
            "workflowMasterId"  => $workflowId,
            "idGenParam"        => $refConParamId
        ];
    }


    /**
     * this function for apply disconnection water.
        | Change the process
        | Remove
     */
    public function applyWaterDisconnection(Request $request)
    {
        $request->validate([
            "consumerId"    => 'required',
            "remarks"       => 'required|string|max:255',
            "reason"        => 'required|string|max:255',
            "mobileNo"      => "required|digits:10|regex:/[0-9]{10}/",
            "address"       => 'required|string|max:255',

        ]);
        try {
            $user                         = authUser($request);
            $penalty                      = 0;
            $consumerId                   = $request->consumerId;
            $applydate                    = Carbon::now();
            $currentDate                  = $applydate->format('Y-m-d H:i:s');
            $mWaterConsumer               = new WaterWaterConsumer();
            $ulbWorkflowObj               = new WfWorkflow();
            $mwaterConsumerDemand         = new WaterConsumerDemand();
            $mWaterConsumerActive         = new WaterConsumerActiveRequest();
            $mwaterConsumerCharge         = new WaterConsumerCharge();
            $mWaterConsumerChargeCategory = new WaterConsumerChargeCategory();
            $waterTrack                   = new WorkflowTrack();
            $refUserType                  = Config::get('waterConstaint.REF_USER_TYPE');
            $refApplyFrom                 = Config::get('waterConstaint.APP_APPLY_FROM');
            $watercharges                 = Config::get("waterConstaint.CONSUMER_CHARGE_CATAGORY");
            $waterRole                    = Config::get("waterConstaint.ROLE-LABEL");
            $refWorkflow                  = config::get('workflow-constants.WATER_DISCONNECTION');
            $refConParamId                = Config::get("waterConstaint.PARAM_IDS");
            $waterConsumer                = WaterWaterConsumer::where('id', $consumerId)->first(); // Get the consumer ID from the database based on the given consumer Id
            if (!$waterConsumer) {
                throw new Exception("Water Consumer not found on the given consumer Id");
            }
            // $this->checkprecondition($request);
            $ulbId      = $request->ulbId ?? $waterConsumer['ulb_id'];
            $ulbWorkflowId  = $ulbWorkflowObj->getulbWorkflowId($refWorkflow, $ulbId);
            if (!$ulbWorkflowId) {
                throw new Exception("Respective ULB IS NOT MAPED TO WATER WORKFLOW");
            }
            $refInitiaterRoleId  = $this->getInitiatorId($ulbWorkflowId->id);
            $refFinisherRoleId   = $this->getFinisherId($ulbWorkflowId->id);
            $finisherRoleId      = DB::select($refFinisherRoleId);
            $initiatorRoleId     = DB::select($refInitiaterRoleId);
            if (!$finisherRoleId || !$initiatorRoleId) {
                throw new Exception('initiator or finisher not found ');
            }


            $consumerCharges = $mWaterConsumerChargeCategory->getChargesByid($watercharges['WATER_DISCONNECTION']);
            if ($consumerCharges == null) {
                throw new Exception("Consumer charges not found");
            }
            $meteReq = [
                "chargeAmount"      => $consumerCharges->amount,
                "chargeCategory"    => $consumerCharges->charge_category,
                "penalty"           => $penalty,
                "amount"            => $consumerCharges->amount + $penalty,
                "ruleSet"           => "test",
                "ulbId"             => $waterConsumer->ulb_id,
                "applydate"         => $currentDate,
                "wardmstrId"        => $waterConsumer->ward_mstr_id,
                "empDetailsId"      => $waterConsumer->emp_details_id,
                "chargeCategoryID"  => $consumerCharges->id,
                "ulbWorkflowId"     => $ulbWorkflowId->id

            ];
            # If the user is not citizen
            if ($user->user_type != $refUserType['1']) {
                $request->request->add(['workflowId' => $refWorkflow]);
                $roleDetails = $this->getRole($request);
                $roleId = $roleDetails['wf_role_id'];
                $refRequest = [
                    "applyFrom" => $user->user_type,
                    "empId"     => $user->id
                ];
            } else {
                $refRequest = [
                    "applyFrom" => $refApplyFrom['1'],
                    "citizenId" => $user->id
                ];
            }

            $refRequest["initiatorRoleId"]   = collect($initiatorRoleId)->first()->role_id;
            $refRequest["finisherRoleId"]    = collect($finisherRoleId)->first()->role_id;
            $refRequest['roleId']            = $roleId ?? null;
            $refRequest['userType']          = $user->user_type;
            $this->begin();
            // Save water disconnection charge using the saveConsumerCharges function
            $idGeneration            =  new PrefixIdGenerator($refConParamId['WCD'], $ulbId);
            $applicationNo           =  $idGeneration->generate();
            $applicationNo           = str_replace('/', '-', $applicationNo);
            $savewaterDisconnection = $mWaterConsumerActive->saveWaterConsumerActive($request, $consumerId, $meteReq, $refRequest, $applicationNo); // Call the storeActive method of WaterConsumerActiveRequest and pass the consumerId
            $var = [
                'relatedId' => $savewaterDisconnection->id,
                "Status"    => 2,

            ];

            $savewaterDisconnection = $mwaterConsumerCharge->saveConsumerChargesDiactivation($consumerId, $meteReq, $var);
            # save for  work flow track
            if ($user->user_type == "Citizen") {                                                        // Static
                $receiverRoleId = $waterRole['DA'];
            }
            if ($user->user_type != "Citizen") {                                                        // Static
                $receiverRoleId = collect($initiatorRoleId)->first()->role_id;
            }
            $metaReqs = new Request(
                [
                    'citizenId'         => $refRequest['citizenId'] ?? null,
                    'moduleId'          => 2,
                    'workflowId'        => $ulbWorkflowId['id'],
                    'refTableDotId'     => 'water_consumer_active_request.id',                                     // Static
                    'refTableIdValue'   => $var['relatedId'],
                    'user_id'           => $user->id,
                    'ulb_id'            => $ulbId,
                    'senderRoleId'      => $senderRoleId ?? null,
                    'receiverRoleId'    => $receiverRoleId ?? null
                ]
            );
            $waterTrack->saveTrack($metaReqs);
            $mWaterConsumer->dissconnetConsumer($consumerId, $var['Status']);
            $returnData = [
                'applicationDetails'    => $meteReq,
                'applicationNo'         => $applicationNo,
                'Id'                    => $var['relatedId'],

            ];
            $this->commit();
            return responseMsgs(true, "Successfully apply disconnection ", remove_null($returnData), "1.0", "350ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            $this->rollback();
            return responseMsgs(false, $e->getMessage(), "", $e->getCode(), "1.0", "", 'POST', "");
        }
    }

    ####################################################################################################

    /**
     * | Doc upload through document upload service 
        | Type test
     */
    public function checkDoc(Request $request)
    {
        try {
            // $contentType = (collect(($request->headers->all())['content-type'] ?? "")->first());
            $file = $request->document;
            $filePath = $file->getPathname();
            $hashedFile = hash_file('sha256', $filePath);
            $filename = ($request->document)->getClientOriginalName();
            $api = "http://192.168.0.122:8001/document/upload";
            $transfer = [
                "file" => $request->document,
                "tags" => "good,ghdt",
                // "reference" => 425
            ];
            $returnData = Http::withHeaders([
                "x-digest"      => "$hashedFile",
                "token"         => "8Ufn6Jio6Obv9V7VXeP7gbzHSyRJcKluQOGorAD58qA1IQKYE0",
                "folderPathId"  => 1
            ])->attach([
                [
                    'file',
                    file_get_contents($request->file('document')->getRealPath()),
                    $filename
                ]
            ])->post("$api", $transfer);

            if ($returnData->successful()) {
                $statusCode = $returnData->status();
                $responseBody = $returnData->body();
                return $returnData;
            } else {
                $statusCode = $returnData->status();
                $responseBody = $returnData->body();
                return $responseBody;
            }
            return false;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }


    //written by prity pandey
    public function applyDeactivation(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'consumerId'           => "required|digits_between:1,9223372036854775807",
                'ulbId'                => "nullable",
                'reason'               => "required",
                'remarks'              => "required",
                'address'              => "required",
                'mobileNo'             => "required|digits:10|regex:/[0-9]{10}/",
                'documents'            => 'required|array',
                'documents.*.image'    => 'required|mimes:png,jpeg,pdf,jpg',
                'documents.*.docCode'  => 'required|string',
                'documents.*.ownerDtlId' => 'nullable|integer'
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            //  return    $user                           = authUser($request);
            $refRequest                     = array();
            $ulbWorkflowObj                 = new WfWorkflow();
            $mWorkflowTrack                 = new WorkflowTrack();
            $mWaterWaterConsumer            = new WaterWaterConsumer();
            $mWaterConsumerCharge           = new WaterConsumerCharge();
            $mWaterConsumerChargeCategory   = new WaterConsumerChargeCategory();
            $mWaterConsumerActiveRequest    = new WaterConsumerActiveRequest();
            $refUserType                    = Config::get('waterConstaint.REF_USER_TYPE');
            $refConsumerCharges             = Config::get('waterConstaint.CONSUMER_CHARGE_CATAGORY');
            $refApplyFrom                   = Config::get('waterConstaint.APP_APPLY_FROM');
            $refWorkflow                    = Config::get('workflow-constants.WATER_DISCONNECTION');
            $refConParamId                  = Config::get('waterConstaint.PARAM_IDS');
            $confModuleId                   = Config::get('module-constants.WATER_MODULE_ID');
            $consumerId                   = $request->consumerId;
            $waterConsumer                = $mWaterWaterConsumer->where('id', $consumerId)->first(); // Get the consumer ID from the database based on the given consumer Id
            if (!$waterConsumer) {
                throw new Exception("Water Consumer not found on the given consumer Id");
            }
            # Check the condition for deactivation
            $refDetails = $this->PreConsumerDeactivationCheck($request);
            $ulbId      = $request->ulbId ?? $user->ulb_id ?? 2;

            # Get initiater and finisher
            $ulbWorkflowId = $ulbWorkflowObj->getulbWorkflowId($refWorkflow, $ulbId);
            if (!$ulbWorkflowId) {
                throw new Exception("Respective Ulb is not maped to Water Workflow!");
            }
            $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);
            $refFinisherRoleId  = $this->getFinisherId($ulbWorkflowId->id);
            $finisherRoleId     = DB::select($refFinisherRoleId);
            $initiatorRoleId    = DB::select($refInitiatorRoleId);
            if (!$finisherRoleId || !$initiatorRoleId) {
                throw new Exception("initiatorRoleId or finisherRoleId not found for respective Workflow!");
            }

            # If the user is not citizen
            // if ($user->user_type != $refUserType['1']) {
            //     $request->request->add(['workflowId' => $refWorkflow]);
            //     $roleDetails = $this->getRole($request);
            //     if (!$roleDetails) {
            //         throw new Exception("Role detail Not found!");
            //     }
            //     $roleId = $roleDetails['wf_role_id'];
            //     $refRequest = [
            //         "applyFrom" => $user->user_type,
            //         "empId"     => $user->id
            //     ];
            // } else {
            //     $refRequest = [
            //         "applyFrom" => $refApplyFrom['1'],
            //         "citizenId" => $user->id
            //     ];
            // }

            # Get chrages for deactivation
            $chargeAmount = $mWaterConsumerChargeCategory->getChargesByid($refConsumerCharges['WATER_DISCONNECTION']);
            $refChargeList = collect($refConsumerCharges)->flip();

            $refRequest["initiatorRoleId"]   = collect($initiatorRoleId)->first()->role_id;
            $refRequest["finisherRoleId"]    = collect($finisherRoleId)->first()->role_id;
            $refRequest["roleId"]            = $roleId ?? null;
            $refRequest["ulbWorkflowId"]     = $ulbWorkflowId->id;
            $refRequest["chargeCategoryId"]  = $refConsumerCharges['WATER_DISCONNECTION'];
            $refRequest["amount"]            = $chargeAmount->amount;
            // $refRequest['userType']          = $user->user_type;
            $refRequest['ulbId']             = $ulbId;


            $this->begin();
            $idGeneration       = new PrefixIdGenerator($refConParamId['WCD'], $ulbId);
            $applicationNo      = $idGeneration->generate();
            $applicationNo      = str_replace('/', '-', $applicationNo);
            $deactivatedDetails = $mWaterConsumerActiveRequest->saveRequestDetails($request, $refDetails['consumerDetails'], $refRequest, $applicationNo);

            #Upload Document in dms System
            $mDocuments = $request->documents;
            $this->uploadDocument($deactivatedDetails['id'], $mDocuments, $request->auth);

            $metaRequest = [
                'chargeAmount'      => $chargeAmount->amount,
                'amount'            => $chargeAmount->amount,
                'ruleSet'           => null,
                'chargeCategoryId'  => $refConsumerCharges['WATER_DISCONNECTION'],
                'relatedId'         => $deactivatedDetails['id'],                                                 // Static
            ];
            $mWaterConsumerCharge->saveConsumerCharges($metaRequest, $request->consumerId, $refChargeList['2']);
            // $mWaterWaterConsumer->dissconnetConsumer($request->consumerId, $metaRequest['status']);

            # Save data in track
            $metaReqs = new Request(
                [
                    'citizenId'         => $refRequest['citizenId'] ?? null,
                    'moduleId'          => $confModuleId,
                    'workflowId'        => $ulbWorkflowId->id,
                    'refTableDotId'     => 'water_consumer_active_requests.id',             // Static                          // Static                              // Static
                    'refTableIdValue'   => $deactivatedDetails['id'],
                    'user_id'           => $refRequest['empId'] ?? null,
                    'ulb_id'            => $ulbId,
                    'senderRoleId'      => $refRequest['empId'] ?? null,
                    'receiverRoleId'    => collect($initiatorRoleId)->first()->role_id,
                ]
            );
            $mWorkflowTrack->saveTrack($metaReqs);
            $this->commit();
            $data = [];
            $data['application_id']   = $deactivatedDetails['id'];
            $data['application_no']   = $applicationNo;
            return responseMsgs(true, "Successfully Applied for Deactivation!", remove_null($data), "", "02", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            $this->rollback();
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", ".ms", "POST", "");
        }
    }

    /**
     * upload Document By Citizen At the time of Registration
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadDocument($tempId, $documents, $auth)
    {
        $docUpload = new DocUpload;
        $mWfActiveDocument = new WfActiveDocument();
        $mWaterConsumerActiveRequest = new WaterConsumerActiveRequest();
        $confModuleId                   = Config::get('module-constants.WATER_MODULE_ID');
        // $relativePath = Config::get('constants.BANQUTE_MARRIGE_HALL.RELATIVE_PATH');

        collect($documents)->map(function ($doc) use ($tempId, $docUpload, $mWfActiveDocument, $mWaterConsumerActiveRequest, $confModuleId, $auth) {
            $metaReqs = array();
            $getApplicationDtls = $mWaterConsumerActiveRequest->getRequestByAppId($tempId)->first();
            $refImageName = $doc['docCode'];
            $refImageName = $getApplicationDtls->id . '-' . $refImageName;
            $documentImg = $doc['image'];
            $newRequest = new Request([
                'document' => $documentImg
            ]);
            $imageName = $docUpload->checkDoc($newRequest);
            $metaReqs['moduleId'] = $confModuleId;
            $metaReqs['activeId'] = $getApplicationDtls->id;
            $metaReqs['workflowId'] = $getApplicationDtls->workflow_id;
            $metaReqs['ulbId'] = $getApplicationDtls->ulb_id;
            // $metaReqs['relativePath'] = $relativePath;
            $metaReqs['document'] = $imageName;
            $metaReqs['docCode'] = $doc['docCode'];
            $metaReqs['ownerDtlId'] = $doc['ownerDtlId'] ?? null;
            $metaReqs['uniqueId'] = $imageName['data']['uniqueId'];
            $metaReqs['referenceNo'] = $imageName['data']['ReferenceNo'];
            $a = new Request($metaReqs);
            // $mWfActiveDocument->postDocuments($a,$auth);
            $metaReqs =  $mWfActiveDocument->metaReq($metaReqs);
            $mWfActiveDocument->create($metaReqs);
            // foreach($metaReqs as $key=>$val)
            // {
            //     $mWfActiveDocument->$key = $val;
            // }
            // $mWfActiveDocument->save();
        });
    }


    public function searchApplication(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'filterBy'  => 'required|in:name,mobileNo,applicationNo',
                'parameter' => $request->filterBy == 'mobileNo' ? 'required|numeric|digits:10' : "required",
                'pages'     => 'nullable|integer',
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            $key                = $request->filterBy;
            $parameter          = $request->parameter;
            $pages              = $request->pages ?? 10;
            $mWaterApplicant    = new WaterConsumerActiveRequest();
            $returnData = $mWaterApplicant->searchApplication();
            switch ($key) {
                case ("name"):                                                                              // Static
                    $returnData->where("water_consumer_owners.applicant_name", 'ILIKE', '%' . $parameter . '%');
                    break;
                case ("mobileNo"):                                                                          // Static
                    $returnData->where("water_consumer_owners.mobile_no", $parameter);
                    break;
                case ("applicationNo"):                                                                             // Static
                    $returnData->where("water_consumer_active_requests.application_no", 'LIKE', '%' . $parameter . '%');
                    break;
            }

            // 
            $returnData = $returnData->paginate($pages);
            $checkVal = collect($returnData)->last();
            if (!$checkVal || $checkVal == 0)
                throw new Exception("Data according to " . strtolower(preg_replace("/([A-Z])/", " $1", $key)) . " not found!");
            $list = [
                "current_page" => $returnData->currentPage(),
                "last_page" => $returnData->lastPage(),
                "data" => collect($returnData->items())->map(function ($val) {
                    return $val->only("id", "reason", "remarks", "application_no", "apply_date", "workflow_id", "consumer_no", "address", "applicant_name", "mobile_no");
                }),
                "total" => $returnData->total(),
            ];
            return responseMsgs(true, "List of Appication!", $list, "", "01", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), "POST", $request->deviceId);
        }
    }

    // public function getByApplicationId(Request $request)
    // {
    //     try {
    //         $validated = Validator::make(
    //             $request->all(),
    //             [
    //                 'applicationId'    => "required|digits_between:1,9223372036854775807"
    //             ]
    //         );
    //         if ($validated->fails())
    //             return validationError($validated);
    //         $data = WaterConsumerActiveRequest::find($request->applicationId);
    //         if (!$data) {
    //             $data = WaterConsumerApprovedRequest::find($request->applicationId);
    //         }
    //         if (!$data) {
    //             throw new Exception("Data not found");
    //         }
    //         // $newRequest = new Request(['id' => $data->consumer_id]);
    //         // //$consumerDetails = WaterWaterConsumer::find($data->consumer_id);
    //         // $newConnectionController = App::makeWith(NewConnectionController::class, ["iNewConnection" => iNewConnection::class]);

    //         // $response = $newConnectionController->approvedWaterApplications($newRequest);
    //         // $response = $response->original;
    //         // if (!$response["status"]) {
    //         //     throw new Exception("consumer data not found");
    //         // }
    //         // $returnData = [
    //         //     'requestData' => $data,
    //         //     'consumerData' => $response['data']
    //         // ];

    //         $data = WaterConsumerActiveRequest::select('water_consumer_active_requests.*', 'water_consumers.*', 'water_consumer_owners.*')
    //             ->leftjoin('water_consumers', 'water_consumers.id', 'water_consumer_active_requests.consumer_id')
    //             ->leftjoin('water_consumer_owners', 'water_consumer_owners.consumer_id', 'water_consumers.id')
    //             ->join('ulb_ward_masters AS uwm', 'uwm.id', 'water_consumer_active_requests.ward_mstr_id')
    //             ->join('ulb_masters AS um', 'um.id', 'water_consumer_active_requests.ulb_id')
    //             ->where('water_consumer_active_requests.status', 1)
    //             ->where('water_consumer_active_requests.id', $request->applicationId)
    //             ->get();

    //         return responseMsgs(true, "Respective Consumer Deactivated!", remove_null($data), "", "02", ".ms", "POST", $request->deviceId);
    //     } catch (Exception $e) {
    //         $this->rollback();
    //         return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", ".ms", "POST", "");
    //     }
    // }

    public function getByApplicationId(ReqApplicationId $request)
    {
        try {

            $data = WaterConsumerActiveRequest::find($request->applicationId);
            // dd($data,$request->all(),DB::connection("pgsql_water")->getQueryLog());
            if (!$data) {
                $data = WaterConsumerApprovalRequest::find($request->applicationId);
            }
            if (!$data) {
                throw new Exception("Data not found");
            }
            $data->consumerDetails = $data->getConserDtls();
            $data->consumerDetails->owners = $data->consumerDetails->getOwners();
            $wards = UlbWardMaster::where("id", $data->ward_mstr_id)->first();
            $ulb = UlbMaster::where("id", $data->ulb_id)->first();
            $data->ward_no = $wards->ward_name ?? null;
            $data->ulb_name = $ulb->ulb_name ?? null;

            return responseMsgs(true, "Respective Consumer Deactivated!", remove_null($data), "", "02", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            $this->rollback();
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", ".ms", "POST", "");
        }
    }

    public function getDocList(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            [
                'applicationId' => 'required|numeric'
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $mWaterApplication  = new WaterConsumerActiveRequest();

            $refWaterApplication = $mWaterApplication->getApplicationById($req->applicationId)->first();                      // Get Saf Details
            if (!$refWaterApplication) {
                throw new Exception("Application Not Found for this id");
            }
            $documentList = $this->getRequestDocLists($refWaterApplication);
            $totalDocLists['listDocs'] = $documentList;
            //$totalDocLists['docList'] = $documentList;
            $totalDocLists['docUploadStatus'] = $refWaterApplication->doc_upload_status;
            $totalDocLists['docVerifyStatus'] = $refWaterApplication->doc_status;
            return responseMsgs(true, "", remove_null($totalDocLists), "010203", "", "", 'POST', "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010203", "1.0", "", 'POST', "");
        }
    }
    public function filterDocument($documentList, $refWaterApplication, $ownerId = null)
    {
        $mWfActiveDocument  = new WfActiveDocument();
        $docUpload = new DocUpload;
        $applicationId = $refWaterApplication->id;
        // Check if $refWaterApplication is an array
        // if (is_array($refWaterApplication)) {
        //     // Iterate through the array elements
        //     foreach ($refWaterApplication as $item) {
        //         // Check if the current element has the property 'id' or 'active_id'
        //         if (isset($item['id']) || isset($item['active_id'])) {
        //             // Use the first available ID and break the loop
        //             $applicationId = $item['id'] ?? $item['active_id'];
        //             break;
        //         }
        //     }
        // } else {
        //     // If $refWaterApplication is not an array, directly access its properties
        //     $applicationId = $refWaterApplication->id ?? $refWaterApplication->active_id;
        // }

        $workflowId         = $refWaterApplication->workflow_id;
        $moduleId           = Config::get('module-constants.WATER_MODULE_ID');
        $uploadedDocs       = $mWfActiveDocument->getDocByRefIds($applicationId, $workflowId, $moduleId);
        $uploadedDocs = $docUpload->getDocUrl($uploadedDocs);           #_Calling BLL for Document Path from DMS
        $explodeDocs = collect(explode('#', $documentList));
        $filteredDocs = $explodeDocs->map(function ($explodeDoc) use ($uploadedDocs, $ownerId, $documentList) {

            # var defining
            $document   = explode(',', $explodeDoc);
            $key        = array_shift($document);
            $label      = array_shift($document);
            $documents  = collect();

            collect($document)->map(function ($item) use ($uploadedDocs, $documents, $ownerId, $documentList) {
                $uploadedDoc = $uploadedDocs->where('doc_code', $item)
                    ->where('owner_dtl_id', $ownerId)
                    ->first();
                if ($uploadedDoc) {
                    // $path = $this->readDocumentPath($uploadedDoc->doc_path);
                    // $fullDocPath = !empty(trim($uploadedDoc->doc_path)) ? $path : null;
                    $response = [
                        "documentCode"  => $item,
                        "uploadedDocId" => $uploadedDoc['id'] ?? "",
                        "ownerId"       => $uploadedDoc['owner_dtl_id'] ?? "",
                        "docPath"       => $uploadedDoc['doc_path'] ?? "",
                        "verifyStatus"  => $uploadedDoc['verify_status'] ?? "",
                        "remarks"       => $uploadedDoc['remarks'] ?? "",
                    ];
                    $documents->push($response);
                }
            });
            $reqDoc['docType']      = $key;
            $reqDoc['uploadedDoc']  = $documents->last();
            $reqDoc['docName']      = substr($label, 1, -1);

            $reqDoc['masters'] = collect($document)->map(function ($doc) use ($uploadedDocs) {
                $uploadedDoc = $uploadedDocs->where('doc_code', $doc)->first();
                $strLower = strtolower($doc);
                $strReplace = str_replace('_', ' ', $strLower);
                // if (isset($uploadedDoc)) {
                //     $path =  $this->readDocumentPath($uploadedDoc->doc_path);
                //     $fullDocPath = !empty(trim($uploadedDoc->doc_path)) ? $path : null;
                // }
                $arr = [
                    "documentCode"  => $doc,
                    "docVal"        => ucwords($strReplace),
                    "uploadedDoc"   => $uploadedDoc['doc_path'] ?? "",
                    "uploadedDocId" => $uploadedDoc['id'] ?? "",
                    "verifyStatus'" => $uploadedDoc['verify_status'] ?? "",
                    "remarks"       => $uploadedDoc['remarks'] ?? "",
                ];
                return $arr;
            });
            return $reqDoc;
        });
        return $filteredDocs;
    }

    public function getRequestDocLists($application)
    {
        $mRefReqDocs    = new RefRequiredDocument();
        $mWaterApplication  = new WaterConsumerActiveRequest();
        $refWaterApplication = $mWaterApplication->getApplicationById($application)->first();
        $moduleId       = Config::get('module-constants.WATER_MODULE_ID');
        $documentList = $mRefReqDocs->getDocsByDocCode($moduleId, "LAST_PAYMENT_RECEIPT")->requirements;

        if (!$refWaterApplication->citizen_id)         // Holding No, SAF No // Static
        {
            $documentList .= $mRefReqDocs->getDocsByDocCode($moduleId, "DISCONNECTION_APPLICATION_FORM")->requirements;
        }
        $documentList = $this->filterDocument($documentList, $application);
        return $documentList;
    }

    public function uploadWaterDocForDeactivation(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            [
                "applicationId" => "required|numeric",
                "document"      => "required|mimes:pdf,jpeg,png,jpg|max:2048",
                "docCode"       => "required",
                "docCategory"   => "required",
                "ownerId"       => "nullable|numeric"
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $user               = authUser($req);
            $metaReqs           = array();
            $applicationId      = $req->applicationId;
            $docUpload          = new DocUpload;
            $mWfActiveDocument  = new WfActiveDocument();
            $mWaterApplication  = new WaterConsumerActiveRequest();
            $relativePath       = Config::get('waterConstaint.WATER_RELATIVE_PATH');
            $refmoduleId        = Config::get('module-constants.WATER_MODULE_ID');

            $getWaterDetails    = $mWaterApplication->getConsumerByApplication($applicationId)->first();
            if ($getWaterDetails) {
                $refImageName = $req->docRefName;
                $refImageName = $getWaterDetails->id . '-' . str_replace(' ', '_', $refImageName);
            }
            // $refImageName       = $req->docRefName;
            // $refImageName       = $getWaterDetails->id . '-' . str_replace(' ', '_', $refImageName);
            $docDetail          = $docUpload->checkDoc($req);
            $metaReqs = [
                'moduleId'      => $refmoduleId,
                'activeId'      => $applicationId,
                'workflowId'    => $getWaterDetails->workflow_id,
                'ulbId'         => $getWaterDetails->ulb_id,
                'relativePath'  => $relativePath,
                'docCode'       => $req->docCode,
                'ownerDtlId'    => $req->ownerId,
                'docCategory'   => $req->docCategory,
                'auth'          => $user,
                'uniqueId'      => $docDetail['data']['uniqueId'],
                'referenceNo'   => $docDetail['data']['ReferenceNo'],

            ];

            if ($user->user_type == "Citizen") {                                                // Static
                $isCitizen = true;
                $this->checkParamForDocUpload($isCitizen, $getWaterDetails, $user);
            } else {
                $isCitizen = false;
                $this->checkParamForDocUpload($isCitizen, $getWaterDetails, $user);
            }

            $this->begin();
            if ($getWaterDetails->parked != true) {
                $ifDocExist = $mWfActiveDocument->isDocCategoryExists($getWaterDetails->id, $getWaterDetails->workflow_id, $refmoduleId, $req->docCategory, $req->ownerId)->first();   // Checking if the document is already existing or not
                $metaReqs = new Request($metaReqs);
                if (collect($ifDocExist)->isEmpty()) {
                    $mWfActiveDocument->postDocuments($metaReqs);
                }
                if (collect($ifDocExist)->isNotEmpty()) {
                    $mWfActiveDocument->editDocuments($ifDocExist, $metaReqs);
                }
            }
            # if the application is parked and btc 
            if ($getWaterDetails->parked == true) {
                # check the doc Existence for updation and post
                $metaReqs = new Request($metaReqs);
                $mWfActiveDocument->postDocuments($metaReqs);
                $mWfActiveDocument->deactivateRejectedDoc($metaReqs);
                $refReq = new Request([
                    'applicationId' => $applicationId
                ]);
                $documentList = $this->getUploadDocuments($refReq);
                $DocList = collect($documentList)['original']['data'];
                $refVerifyStatus = $DocList->where('doc_category', '!=', $req->docCategory)->pluck('verify_status');
                if (!in_array(2, $refVerifyStatus->toArray())) {                                    // Static "2"
                    $status = false;
                    $mWaterApplication->updateParkedstatus($status, $applicationId);
                }
            }
            // #check full doc upload
            $refCheckDocument = $this->checkFullDocUpload($applicationId);

            # Update the Doc Upload Satus in Application Table

            if ($refCheckDocument == 1) {                                        // Doc Upload Status Update
                $getWaterDetails->doc_upload_status = 1;
                if ($getWaterDetails->parked == true)                                // Case of Back to Citizen
                    $getWaterDetails->parked = false;

                $getWaterDetails->save();
            }

            $this->commit();
            return responseMsgs(true, "Document Uploadation Successful", "", "", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            $this->rollback();
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    public function checkParamForDocUpload($isCitizen, $applicantDetals, $user)

    {
        $refWorkFlowMaster = Config::get('workflow-constants.WATER_MASTER_ID');
        switch ($isCitizen) {
                # For citizen 
            case (true):
                if (!is_null($applicantDetals->current_role) && $applicantDetals->parked == true) {
                    return true;
                }
                if (!is_null($applicantDetals->current_role)) {
                    throw new Exception("You aren't allowed to upload document!");
                }
                break;
                # For user
            case (false):
                $userId = $user->id;
                $ulbId = $applicantDetals->ulb_id;
                $role = $this->getUserRoll($userId, $ulbId, $refWorkFlowMaster);
                if (is_null($role)) {
                    throw new Exception("You dont have any role!");
                }
                if ($role->can_upload_document != true) {
                    throw new Exception("You dont have permission to upload Document!");
                }
                break;
        }
    }

    public function getUserRoll($user_id, $ulb_id, $workflow_id)
    {
        try {
            DB::enableQueryLog();
            $data = WfRole::select(
                DB::raw(
                    "wf_roles.id as role_id,wf_roles.role_name,
                    wf_workflowrolemaps.is_initiator, wf_workflowrolemaps.is_finisher,
                    wf_workflowrolemaps.forward_role_id,forword.role_name as forword_name,
                    wf_workflowrolemaps.backward_role_id,backword.role_name as backword_name,
                    wf_workflowrolemaps.allow_full_list,wf_workflowrolemaps.can_escalate,
                    wf_workflowrolemaps.serial_no,wf_workflowrolemaps.is_btc,
                    wf_workflowrolemaps.can_upload_document,
                    wf_workflowrolemaps.can_verify_document,
                    wf_workflowrolemaps.can_backward,
                    wf_workflowrolemaps.can_edit,
                    wf_workflows.id as workflow_id,wf_masters.workflow_name,
                    ulb_masters.id as ulb_id, ulb_masters.ulb_name,
                    ulb_masters.ulb_type"
                )
            )
                ->join("wf_roleusermaps", function ($join) {
                    $join->on("wf_roleusermaps.wf_role_id", "=", "wf_roles.id")
                        ->where("wf_roleusermaps.is_suspended", "=", FALSE);
                })
                ->join("users", "users.id", "=", "wf_roleusermaps.user_id")
                ->join("wf_workflowrolemaps", function ($join) {
                    $join->on("wf_workflowrolemaps.wf_role_id", "=", "wf_roleusermaps.wf_role_id")
                        ->where("wf_workflowrolemaps.is_suspended", "=", FALSE);
                })
                ->leftjoin("wf_roles AS forword", "forword.id", "=", "wf_workflowrolemaps.forward_role_id")
                ->leftjoin("wf_roles AS backword", "backword.id", "=", "wf_workflowrolemaps.backward_role_id")
                ->join("wf_workflows", function ($join) {
                    $join->on("wf_workflows.id", "=", "wf_workflowrolemaps.workflow_id")
                        ->where("wf_workflows.is_suspended", "=", FALSE);
                })
                ->join("wf_masters", function ($join) {
                    $join->on("wf_masters.id", "=", "wf_workflows.wf_master_id")
                        ->where("wf_masters.is_suspended", "=", FALSE);
                })
                ->join("ulb_masters", "ulb_masters.id", "=", "wf_workflows.ulb_id")
                ->where("wf_roles.is_suspended", false)
                ->where("wf_roleusermaps.user_id", $user_id)
                ->where("wf_workflows.ulb_id", $ulb_id)
                ->where("wf_workflows.wf_master_id", $workflow_id)
                ->orderBy("wf_roleusermaps.id", "desc")
                ->first();
            return $data;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function getUploadDocuments(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            [
                'applicationId' => 'required|numeric'
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $mWfActiveDocument = new WfActiveDocument();
            $mWaterApplication  = new WaterConsumerActiveRequest();
            $docUpload = new DocUpload;
            $moduleId = Config::get('module-constants.WATER_MODULE_ID');

            $waterDetails = $mWaterApplication->getApplicationById($req->applicationId)->first();
            if (!$waterDetails)
                throw new Exception("Application Not Found for this application Id");

            $workflowId = $waterDetails->workflow_id;
            $documents = $mWfActiveDocument->getConsumerDocsByAppNo($req->applicationId, $workflowId, $moduleId);
            $data = $docUpload->getDocUrl($documents);           #_Calling BLL for Document Path from DMS

            return responseMsgs(true, "Uploaded Documents", remove_null($data), "010102", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010202", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    public function checkFullDocUpload($applicationId)
    {
        $mWaterApplication  = new WaterConsumerActiveRequest();
        $mWfActiveDocument = new WfActiveDocument();
        $waterDetails = $mWaterApplication->getApplicationById($applicationId)->first();
        $waterDetails = [
            'activeId' => $applicationId,
            'workflowId' => $waterDetails->workflow_id,
            'moduleId' => 2
        ];
        $req = new Request($waterDetails);
        $refDocList = $mWfActiveDocument->getDocsByActiveId($req);
        return $this->isAllDocs($applicationId, $refDocList, $waterDetails);
    }

    public function isAllDocs($applicationId, $refDocList, $refSafs)
    {
        $docList = array();
        $verifiedDocList = array();
        $waterListDocs = $this->getRequestDocLists($refSafs);
        $docList['waterDocs'] = explode('#', $waterListDocs);
        $verifiedDocList['waterDocs'] = $refDocList->values();
        $collectUploadDocList = collect();
        collect($verifiedDocList['waterDocs'])->map(function ($item) use ($collectUploadDocList) {
            return $collectUploadDocList->push($item['doc_code']);
        });
        $mwaterDocs = collect($docList['waterDocs']);
        // water List Documents
        $flag = 1;
        foreach ($mwaterDocs as $item) {
            $explodeDocs = explode(',', $item);
            array_shift($explodeDocs);
            foreach ($explodeDocs as $explodeDoc) {
                $changeStatus = 0;
                if (in_array($explodeDoc, $collectUploadDocList->toArray())) {
                    $changeStatus = 1;
                    break;
                }
            }
            if ($changeStatus == 0) {
                $flag = 0;
                break;
            }
        }

        if ($flag == 0)
            return 0;
    }
}
