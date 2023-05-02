<?php

namespace App\Http\Controllers\Water;

use App\Http\Controllers\Controller;
use App\Http\Requests\Water\reqDeactivate;
use App\Http\Requests\Water\reqMeterEntry;
use App\MicroServices\DocUpload;
use App\MicroServices\IdGeneration;
use App\Models\Citizen\ActiveCitizenUndercare;
use App\Models\Payment\TempTransaction;
use App\Models\Water\WaterChequeDtl;
use App\Models\Water\WaterConsumer as WaterWaterConsumer;
use App\Models\Water\WaterConsumerDemand;
use App\Models\Water\WaterConsumerDisconnection;
use App\Models\Water\WaterConsumerInitialMeter;
use App\Models\Water\WaterConsumerMeter;
use App\Models\Water\WaterConsumerTax;
use App\Models\Water\WaterDisconnection;
use App\Models\Water\WaterMeterReadingDoc;
use App\Models\Water\WaterTran;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Repository\Water\Concrete\WaterNewConnection;
use App\Repository\Water\Interfaces\IConsumer;
use App\Traits\Workflow\Workflow;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\CssSelector\Node\FunctionNode;

class WaterConsumer extends Controller
{
    use Workflow;

    private $Repository;
    public function __construct(IConsumer $Repository)
    {
        $this->Repository = $Repository;
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
        $request->validate([
            'ConsumerId' => 'required|',
        ]);
        try {
            $mWaterConsumerDemand   = new WaterConsumerDemand();
            $mWaterConsumerMeter    = new WaterConsumerMeter();
            $refConnectionName      = Config::get('waterConstaint.METER_CONN_TYPE');
            $refConsumerId = $request->ConsumerId;

            $consumerDemand['consumerDemands'] = $mWaterConsumerDemand->getConsumerDemand($refConsumerId);
            $checkParam = collect($consumerDemand['consumerDemands'])->first();
            if (isset($checkParam)) {
                $consumerDemand['totalSumDemand'] = collect($consumerDemand['consumerDemands'])->map(function ($value, $key) {
                    return $value['balance_amount'];
                })->sum();
                $consumerDemand['totalPenalty'] = collect($consumerDemand['consumerDemands'])->map(function ($value, $key) {
                    return $value['penalty'];
                })->sum();

                # meter Details 
                $refMeterData = $mWaterConsumerMeter->getMeterDetailsByConsumerId($refConsumerId)->first();
                switch ($refMeterData['connection_type']) {
                    case (1):
                        $connectionName = $refConnectionName['1'];
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
            throw new Exception("there is no demand!");
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
        $request->validate([
            'consumerId' => "required|digits_between:1,9223372036854775807",
            'document' => "required|mimes:pdf,jpg,jpeg,png",
        ]);
        try {
            $mWaterConsumerInitialMeter = new WaterConsumerInitialMeter();
            $mWaterConsumerMeter        = new WaterConsumerMeter();
            $mWaterMeterReadingDoc      = new WaterMeterReadingDoc();
            $refMeterConnectionType     = Config::get('waterConstaint.METER_CONN_TYPE');
            $demandIds = array();

            $this->checkDemandGeneration($request);                                         // unfinished function
            $consumerDetails = WaterWaterConsumer::findOrFail($request->consumerId);
            $calculatedDemand = collect($this->Repository->calConsumerDemand($request));
            if ($calculatedDemand['status'] == false) {
                throw new Exception($calculatedDemand);
            }
            if (isset($calculatedDemand)) {
                # get the demand
                DB::beginTransaction();
                $demandDetails = collect($calculatedDemand['consumer_tax'])->first();
                switch ($demandDetails['charge_type']) {
                    case ($refMeterConnectionType['1']):
                        $meterDetails = $mWaterConsumerMeter->saveMeterReading($request);
                        $mWaterConsumerInitialMeter->saveConsumerReading($request, $meterDetails);
                        $demandIds = $this->savingDemand($calculatedDemand, $request, $consumerDetails, $demandDetails['charge_type'], $refMeterConnectionType);

                        # save the chages doc
                        $documentPath = $this->saveTheMeterDocument($request);
                        collect($demandIds)->map(function ($value)
                        use ($mWaterMeterReadingDoc, $meterDetails, $documentPath) {
                            $mWaterMeterReadingDoc->saveDemandDocs($meterDetails, $documentPath, $value);
                        });
                        break;

                    case ($refMeterConnectionType['2']):
                        $meterDetails = $mWaterConsumerMeter->saveMeterReading($request);
                        $mWaterConsumerInitialMeter->saveConsumerReading($request, $meterDetails);
                        $demandIds = $this->savingDemand($calculatedDemand, $request, $consumerDetails, $demandDetails['charge_type'], $refMeterConnectionType);

                        # save the chages doc
                        $documentPath = $this->saveTheMeterDocument($request);
                        collect($demandIds)->map(function ($value)
                        use ($mWaterMeterReadingDoc, $meterDetails, $documentPath) {
                            $mWaterMeterReadingDoc->saveDemandDocs($meterDetails, $documentPath, $value);
                        });
                        break;

                    case ($refMeterConnectionType['3']):
                        $this->savingDemand($calculatedDemand, $request, $consumerDetails, $demandDetails['charge_type'], $refMeterConnectionType);
                        break;
                }
                DB::commit();
                return responseMsgs(true, "Demand Generated! for" . " " . $request->consumerId, "", "", "02", ".ms", "POST", "");
            }
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", "ms", "POST", "");
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
    public function savingDemand($calculatedDemand, $request, $consumerDetails, $demandType, $refMeterConnectionType)
    {
        $mWaterConsumerDemand = new WaterConsumerDemand();
        $mWaterConsumerTax = new WaterConsumerTax();
        $generatedDemand = $calculatedDemand['consumer_tax'];

        $returnDemandIds = collect($generatedDemand)->map(function ($firstValue)
        use ($mWaterConsumerDemand, $consumerDetails, $request, $mWaterConsumerTax, $demandType, $refMeterConnectionType) {
            $taxId = $mWaterConsumerTax->saveConsumerTax($firstValue, $consumerDetails);
            $refDemandIds = array();
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
                    $refDemandIds = $mWaterConsumerDemand->saveConsumerDemand($refDemands, $meterDetails, $consumerDetails, $request, $taxId);
                    break;
                case ($refMeterConnectionType['2']):
                    $refDemands = $firstValue['consumer_demand'];
                    $refDemandIds = $mWaterConsumerDemand->saveConsumerDemand($refDemands, $meterDetails, $consumerDetails, $request, $taxId);
                    break;
                case ($refMeterConnectionType['3']):
                    $refDemands = $firstValue['consumer_demand'];
                    $refDemandIds = collect($refDemands)->map(function ($secondValue)
                    use ($mWaterConsumerDemand, $meterDetails, $consumerDetails, $request, $taxId) {
                        $refDemandId = $mWaterConsumerDemand->saveConsumerDemand($secondValue, $meterDetails, $consumerDetails, $request, $taxId);
                        return $refDemandId;
                    });
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
    public function checkDemandGeneration()
    {
        // write code for checking the restrictions of demand generation
    }



    /**
     * | Save the Meter details 
     * | @param
     * | @var 
     * | @return
        | Serial No : 04
        | Not working  
        | Check the parameter for the autherised person
        | Chack the Demand
     */
    public function saveUpdateMeterDetails(reqMeterEntry $request)
    {
        try {
            $mWaterConsumerMeter = new WaterConsumerMeter();
            $this->checkParamForMeterEntry($request);
            DB::beginTransaction();
            $metaRequest = new Request([
                "consumerId"    => $request->consumerId,
                "finalRading"   => $request->oldMeterFinalReading,
                "demandUpto"    => $request->connectionDate,
            ]);
            $this->saveGenerateConsumerDemand($metaRequest);
            $documentPath = $this->saveTheMeterDocument($request);
            $fixedRate = $this->getFixedRate($request);
            $mWaterConsumerMeter->saveMeterDetails($request, $documentPath);
            DB::commit();
            return responseMsgs(true, "Meter Detail Entry Success !", "", "", "01", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "", "01", ".ms", "POST", "");
        }
    }

    /**
     * | Chech the parameter before Meter entry
     * | Validate the Admin For entring the meter details
     * | @param req
        | Serial No : 04.01
        | Working
     */
    public function checkParamForMeterEntry($request)
    {
        $todayDate = Carbon::now();
        $mWaterWaterConsumer = new WaterWaterConsumer();
        $mWaterConsumerMeter = new WaterConsumerMeter();
        $mWaterConsumerDemand = new WaterConsumerDemand();
        $refMeterConnType = Config::get('waterConstaint.WATER_MASTER_DATA.METER_CONNECTION_TYPE');

        $refConsumerId = $request->consumerId;

        $mWaterWaterConsumer->getConsumerDetailById($refConsumerId);
        $consumerMeterDetails = $mWaterConsumerMeter->getMeterDetailsByConsumerId($refConsumerId)->first();
        $consumerDemand = $mWaterConsumerDemand->getFirstConsumerDemand($refConsumerId)->first();
        $this->checkForMeterFixedCase($request, $consumerMeterDetails, $refMeterConnType);

        switch ($request) {
            case (strtotime($request->connectionDate) > strtotime($todayDate)):
                throw new Exception("Connection Date can not be greater than Current Date!");
                break;
            case ($request->connectionType != $refMeterConnType['Meter/Fixed']):
                if ($consumerMeterDetails->final_meter_reading >= $request->oldMeterFinalReading) {
                    throw new Exception("Rading Should be Greater Than last Reading!");
                }
                break;
            case ($request->connectionType != $refMeterConnType['Meter']):
                if ($consumerMeterDetails->connection_type == $request->connectionType) {
                    throw new Exception("You can not update same connection type as before!");
                }
                break;
        }

        if (isset($consumerMeterDetails)) {
            $reqConnectionDate = $request->connectionDate;
            switch ($consumerMeterDetails) {
                case (strtotime($consumerMeterDetails->connection_date) > strtotime($reqConnectionDate)):
                    throw new Exception("Connection Date should be grater than previous Connection date!");
            }
        }
        if (isset($consumerDemand)) {
            $reqConnectionDate = $request->connectionDate;
            $reqConnectionDate = Carbon::parse($reqConnectionDate)->format('m');
            $consumerDmandDate = Carbon::parse($consumerDemand->demand_upto)->format('m');
            switch ($consumerDemand) {
                case ($consumerDmandDate >= $reqConnectionDate):
                    throw new Exception("Can not update Connection Date, Demand already generated upto that month!");
                    break;
            }
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
        | Serial No : 04.02
        | Not Working
     */
    public function saveTheMeterDocument($request)
    {
        $docUpload = new DocUpload;
        $relativePath = Config::get('waterConstaint.WATER_RELATIVE_PATH');
        $refImageName = config::get('waterConstaint.WATER_METER_CODE');
        $document = $request->document;
        $imageName = $docUpload->upload($refImageName, $document, $relativePath);
        $doc = [
            "document" => $imageName,
            "relaivePath" => $relativePath
        ];
        return $doc;
    }

    /**
     * | Get the Fixed Rate According to the Rate Chart
     * | Only in Case of Meter Connection Type Fixed
        | Serial No : 04.03
        | Not Working
        | find the fixed rate for the fixed charges from the table
     */
    public function getFixedRate($request)
    {
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
        try {
            $request->validate([
                'consumerId' => "required|digits_between:1,9223372036854775807",
            ]);
            $meterConnectionType = null;
            $mWaterConsumerMeter = new WaterConsumerMeter();
            $refWaterNewConnection  = new WaterNewConnection();
            $refMeterConnType = Config::get('waterConstaint.WATER_MASTER_DATA.METER_CONNECTION_TYPE');
            $meterList = $mWaterConsumerMeter->getMeterDetailsByConsumerId($request->consumerId)->get();
            $returnData = collect($meterList)->map(function ($value)
            use ($refMeterConnType, $meterConnectionType, $refWaterNewConnection) {
                switch ($value['connection_type']) {
                    case ($refMeterConnType['Meter']):
                        if ($value['meter_status'] == 0) {
                            $meterConnectionType = "Metre/Fixed";
                        }
                        $meterConnectionType = "Meter";
                        break;

                    case ($refMeterConnType['Gallon']):
                        $meterConnectionType = "Gallon";
                        break;
                    case ($refMeterConnType['Fixed']):
                        $meterConnectionType = "Fixed";
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
     * | Apply For Deactivation
     * | Save the details for Deactivation
     * | @param request
     * | @var 
        | Not Working
        | Serial No : 06
     */
    public function applyDeactivation(Request $request)
    {
        try {
            $request->validate([
                'consumerId'    => "required|digits_between:1,9223372036854775807",
                'amount'        => "required",
                'paymentMode'   => "required|in:Cash,Cheque,DD",
                'ulbId'         => "nullable",
                'document'      => "required|mimes:pdf,jpg,jpeg,png",
                'reason'        => "required|in:1,2,3",
                'remarks'       => "required"
            ]);

            $user = authUser();
            $currentDate = Carbon::now();
            $mWaterWaterConsumer = new WaterWaterConsumer();
            $mWaterConsumerDisconnection = new WaterConsumerDisconnection();
            $refIdGeneration = new IdGeneration();
            $refWorkflow = Config::get('workflow-constants.WATER_MASTER_ID');

            $request->request->add(['workflowId' => $refWorkflow]);
            $roleId = $this->getRole($request)->pluck('wf_role_id');
            $request->request->add(['roleId' => $roleId]);

            $consumerDetails = $this->PreConsumerDeactivationCheck($request);

            DB::beginTransaction();
            $document = $this->saveDeactivationDoc($request);
            $transactionNo = $refIdGeneration->generateTransactionNo();
            $mWaterWaterConsumer->dissconnetConsumer($request);
            $deactivatedDetails = $mWaterConsumerDisconnection->saveDeactivationDetails($request, $currentDate, $document, $consumerDetails);
            $metaRequest = [
                'id'                => $deactivatedDetails['id'],
                'amount'            => $request->amount,
                'chargeCategory'    => "Demand Deactivation",
                'todayDate'         => $currentDate->format('Y-m-d'),
                'tranNo'            => $transactionNo,
                'paymentMode'       => $request->paymentMode,
                'userId'            => $user->id,
                'userType'          => $user->user_type,
                'ulbId'             => $request->ulbId ?? $user->ulb_id,
            ];
            $this->makeDeactivationTransaction($metaRequest, $request, $consumerDetails);
            DB::commit();
            return responseMsgs(true, "Respective Consumer Deactivated!", "", "", "02", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", ".ms", "POST", "");
        }
    }

    /**
     * | Check the condition before appling for deactivation
     * | @param
     * | @var 
        | Not Working
        | Serial No : 06.01
        | Recheck
     */
    public function PreConsumerDeactivationCheck($request)
    {
        $consumerId = $request->consumerId;
        $mWaterWaterConsumer = new WaterWaterConsumer();
        $mWaterConsumerDemand = new WaterConsumerDemand();
        $refConsumerDetails = $mWaterWaterConsumer->getConsumerDetailById($consumerId);
        if (isset($refConsumerDetails)) {
            throw new Exception("Consumer Don't Exist!");
        }

        $pendingDemand = $mWaterConsumerDemand->getConsumerDemand($consumerId);
        $firstPendingDemand = collect($pendingDemand)->first();
        if (isset($firstPendingDemand)) {
            throw new Exception("There are unpaid pending demand!");
        }

        if ($request->amount != 450) {
            throw new Exception("Amount not matched!");
        }
        return $refConsumerDetails;
    }


    /**
     * | Save document for deactivating the consumer 
     * | @param request
        | Not Working
        | Serial No: 06.02
     */
    public function saveDeactivationDoc($request)
    {
        $docUpload = new DocUpload;
        $relativePath = Config::get('waterConstaint.WATER_RELATIVE_PATH');
        $refImageName = config::get('waterConstaint.WATER_CONSUMER_DEACTIVATION');
        $document = $request->document;
        $imageName = $docUpload->upload($refImageName, $document, $relativePath);
        $doc = [
            "document" => $imageName,
            "relaivePath" => $relativePath
        ];
        return $doc;
    }

    /**
     * | Payment for demand deactivation
     * | @param metaRequest
     * | @param request
     * | @param consumerDetails
        | Not Working
        | Serial No : 06.03
        | check for the transaction 
     */
    public function makeDeactivationTransaction($metaRequest, $request, $consumerDetails)
    {
        $mWaterTran = new WaterTran();
        $offlinePaymentModes = Config::get('payment-constants.VERIFICATION_PAYMENT_MODE');
        $transactionId = $mWaterTran->waterTransaction($metaRequest, $consumerDetails);

        if (in_array($request['paymentMode'], $offlinePaymentModes)) {
            $request->merge([
                'chequeDate'    => $request['chequeDate'],
                'tranId'        => $transactionId['id'],
                'id'            => $request->consumerId,
                'applicationNo' => $consumerDetails->consumer_no,
                'workflowId'    => null,
                'ward_no'       => $consumerDetails->ward_mstr_id
            ]);
            $this->postOtherPaymentModes($request);
        }
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
        try {
            $request->validate([
                'consumerId'    => "required|digits_between:1,9223372036854775807",
                'demandId'      => "required|array|unique:water_consumer_demands,id'",
                'paymentMode'   => "required|in:Cash,Cheque,DD",
                'amount'        => "required",
                'reason'        => "required"
            ]);
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
        | Get Concept for deactivation 
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
        | Get Concept Notes
     */
    public function checkForPayment($request)
    {
        $mWaterTran = new WaterTran();
    }


    /**
     * | View details of the caretaken water connection
     * | using user id
     */
    public function viewCaretakenConnection(Request $request)
    {
        try {
            $mWaterWaterConsumer = new WaterWaterConsumer();
            $mActiveCitizenUndercare = new ActiveCitizenUndercare();
            $connectionDetails = $mActiveCitizenUndercare->getDetailsByCitizenId();
            $checkData = collect($connectionDetails)->first();
            if (is_null($checkData))
                throw new Exception("Under taken data not found!");

            $consumerIds = collect($connectionDetails)->pluck('consumer_id');
            $consumerDetails = $mWaterWaterConsumer->getConsumerByIds($consumerIds)->get();
            return responseMsgs(true, 'list of undertaken water connections!', remove_null($consumerDetails), "", "01", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "01", ".ms", "POST", $request->deviceId);
        }
    }
}
