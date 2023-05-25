<?php

namespace App\Http\Controllers\Water;

use App\Http\Controllers\Controller;
use App\Http\Requests\Water\reqDeactivate;
use App\Http\Requests\Water\reqMeterEntry;
use App\MicroServices\DocUpload;
use App\MicroServices\IdGeneration;
use App\Models\Citizen\ActiveCitizenUndercare;
use App\Models\Payment\TempTransaction;
use App\Models\Water\WaterApplication;
use App\Models\Water\WaterApprovalApplicationDetail;
use App\Models\Water\WaterChequeDtl;
use App\Models\Water\WaterConnectionCharge;
use App\Models\Water\WaterConsumer as WaterWaterConsumer;
use App\Models\Water\WaterConsumerDemand;
use App\Models\Water\WaterConsumerDisconnection;
use App\Models\Water\WaterConsumerInitialMeter;
use App\Models\Water\WaterConsumerMeter;
use App\Models\Water\WaterConsumerTax;
use App\Models\Water\WaterDisconnection;
use App\Models\Water\WaterMeterReadingDoc;
use App\Models\Water\WaterPenaltyInstallment;
use App\Models\Water\WaterTran;
use App\Models\Water\WaterTranDetail;
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
        $request->validate([
            'consumerId' => "required|digits_between:1,9223372036854775807",
        ]);
        try {
            $mWaterConsumerInitialMeter = new WaterConsumerInitialMeter();
            $mWaterConsumerMeter        = new WaterConsumerMeter();
            $mWaterMeterReadingDoc      = new WaterMeterReadingDoc();
            $refMeterConnectionType     = Config::get('waterConstaint.METER_CONN_TYPE');
            $meterRefImageName          = config::get('waterConstaint.WATER_METER_CODE');
            $demandIds = array();

            # Check and calculate Demand
            $this->checkDemandGeneration($request);                                                    // unfinished function
            $consumerDetails = WaterWaterConsumer::findOrFail($request->consumerId);
            $calculatedDemand = collect($this->Repository->calConsumerDemand($request));
            if ($calculatedDemand['status'] == false) {
                throw new Exception($calculatedDemand['errors']);
            }

            # Save demand details 
            DB::beginTransaction();
            if (isset($calculatedDemand)) {
                $demandDetails = collect($calculatedDemand['consumer_tax']['0']);
                switch ($demandDetails['charge_type']) {
                    case ($refMeterConnectionType['1']):
                        $request->validate([
                            'document' => "required|mimes:pdf,jpeg,png,jpg",
                        ]);
                        $meterDetails = $mWaterConsumerMeter->saveMeterReading($request);
                        $mWaterConsumerInitialMeter->saveConsumerReading($request, $meterDetails);
                        $demandIds = $this->savingDemand($calculatedDemand, $request, $consumerDetails, $demandDetails['charge_type'], $refMeterConnectionType);

                        # save the chages doc
                        $documentPath = $this->saveDocument($request, $meterRefImageName);
                        collect($demandIds)->map(function ($value)
                        use ($mWaterMeterReadingDoc, $meterDetails, $documentPath) {
                            $mWaterMeterReadingDoc->saveDemandDocs($meterDetails, $documentPath, $value);
                        });
                        break;
                    case ($refMeterConnectionType['5']):
                        $request->validate([
                            'document' => "required|mimes:pdf,jpeg,png,jpg",
                        ]);
                        $meterDetails = $mWaterConsumerMeter->saveMeterReading($request);
                        $mWaterConsumerInitialMeter->saveConsumerReading($request, $meterDetails);
                        $demandIds = $this->savingDemand($calculatedDemand, $request, $consumerDetails, $demandDetails['charge_type'], $refMeterConnectionType);

                        # save the chages doc
                        $documentPath = $this->saveDocument($request, $meterRefImageName);
                        collect($demandIds)->map(function ($value)
                        use ($mWaterMeterReadingDoc, $meterDetails, $documentPath) {
                            $mWaterMeterReadingDoc->saveDemandDocs($meterDetails, $documentPath, $value);
                        });
                        break;

                    case ($refMeterConnectionType['2']):
                        $request->validate([
                            'document' => "required|mimes:pdf,jpeg,png,jpg",
                        ]);
                        $meterDetails = $mWaterConsumerMeter->saveMeterReading($request);
                        $mWaterConsumerInitialMeter->saveConsumerReading($request, $meterDetails);
                        $demandIds = $this->savingDemand($calculatedDemand, $request, $consumerDetails, $demandDetails['charge_type'], $refMeterConnectionType);

                        # save the chages doc
                        $documentPath = $this->saveDocument($request, $meterRefImageName);
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
    public function savingDemand($calculatedDemand, $request, $consumerDetails, $demandType, $refMeterConnectionType)
    {
        $mWaterConsumerTax      = new WaterConsumerTax();
        $mWaterConsumerDemand   = new WaterConsumerDemand();
        $generatedDemand        = $calculatedDemand['consumer_tax'];

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
                    $refDemands     = $firstValue['consumer_demand'];
                    $check = collect($refDemands)->first();
                    if (is_array($check)) {
                        $refDemandIds = collect($refDemands)->map(function ($secondValue)
                        use ($mWaterConsumerDemand, $meterDetails, $consumerDetails, $request, $taxId) {
                            $refDemandId = $mWaterConsumerDemand->saveConsumerDemand($secondValue, $meterDetails, $consumerDetails, $request, $taxId);
                            return $refDemandId;
                        });
                        break;
                    }
                    $refDemandIds = $mWaterConsumerDemand->saveConsumerDemand($refDemands, $meterDetails, $consumerDetails, $request, $taxId);
                    break;
                case ($refMeterConnectionType['5']):
                    $refDemands = $firstValue['consumer_demand'];
                    $check = collect($refDemands)->first();
                    if (is_array($check)) {
                        $refDemandIds = collect($refDemands)->map(function ($secondValue)
                        use ($mWaterConsumerDemand, $meterDetails, $consumerDetails, $request, $taxId) {
                            $refDemandId = $mWaterConsumerDemand->saveConsumerDemand($secondValue, $meterDetails, $consumerDetails, $request, $taxId);
                            return $refDemandId;
                        });
                        break;
                    }
                    $refDemandIds = $mWaterConsumerDemand->saveConsumerDemand($refDemands, $meterDetails, $consumerDetails, $request, $taxId);
                    break;
                case ($refMeterConnectionType['2']):
                    $refDemands = $firstValue['consumer_demand'];
                    $check = collect($refDemands)->first();
                    if (is_array($check)) {
                        $refDemandIds = collect($refDemands)->map(function ($secondValue)
                        use ($mWaterConsumerDemand, $meterDetails, $consumerDetails, $request, $taxId) {
                            $refDemandId = $mWaterConsumerDemand->saveConsumerDemand($secondValue, $meterDetails, $consumerDetails, $request, $taxId);
                            return $refDemandId;
                        });
                        break;
                    }
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
            $param = $this->checkParamForMeterEntry($request);

            DB::beginTransaction();
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
            // $fixedRate = $this->getFixedRate($request);                             // Manul Entry of fixed rate
            $mWaterConsumerMeter->saveMeterDetails($request, $documentPath, $fixedRate = null);
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
     * | @param request
        | Serial No : 04.01
        | Working
        | Look for the meter status true condition while returning data
        | Recheck the process for meter and non meter 
     */
    public function checkParamForMeterEntry($request)
    {
        $refConsumerId = $request->consumerId;
        $todayDate = Carbon::now();

        $mWaterWaterConsumer    = new WaterWaterConsumer();
        $mWaterConsumerMeter    = new WaterConsumerMeter();
        $mWaterConsumerDemand   = new WaterConsumerDemand();
        $refMeterConnType       = Config::get('waterConstaint.WATER_MASTER_DATA.METER_CONNECTION_TYPE');

        $mWaterWaterConsumer->getConsumerDetailById($refConsumerId);
        $consumerMeterDetails = $mWaterConsumerMeter->getMeterDetailsByConsumerId($refConsumerId)->first();
        $consumerDemand = $mWaterConsumerDemand->getFirstConsumerDemand($refConsumerId)->first();
        $this->checkForMeterFixedCase($request, $consumerMeterDetails, $refMeterConnType);

        switch ($request) {
            case (strtotime($request->connectionDate) > strtotime($todayDate)):
                throw new Exception("Connection Date can not be greater than Current Date!");
                break;
            case ($request->connectionType != $refMeterConnType['Meter/Fixed']):
                if (!is_null($consumerMeterDetails)) {
                    if ($consumerMeterDetails->final_meter_reading >= $request->oldMeterFinalReading) {
                        throw new Exception("Rading Should be Greater Than last Reading!");
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
        if (is_null($consumerMeterDetails)) {
            $returnData['meterStatus'] = false;
        }
        return $returnData;
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

        $imageName = $docUpload->upload($refImageName, $document, $relativePath);
        $doc = [
            "document" => $imageName,
            "relaivePath" => $relativePath
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
        $request->validate([
            'consumerId' => "required|digits_between:1,9223372036854775807",
        ]);

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
        | Differenciate btw citizen and user 
     */
    public function applyDeactivation(Request $request)
    {
        $request->validate([
            'consumerId'    => "required|digits_between:1,9223372036854775807",
            'amount'        => "required",
            'paymentMode'   => "required|in:Cash,Cheque,DD",
            'ulbId'         => "nullable",
            'document'      => "required|mimes:pdf,jpg,jpeg,png",
            'reason'        => "required|in:1,2,3",
            'remarks'       => "required"
        ]);

        try {
            $user                           = authUser();
            $currentDate                    = Carbon::now();
            $refIdGeneration                = new IdGeneration();
            $mWaterWaterConsumer            = new WaterWaterConsumer();
            $mWaterConsumerDisconnection    = new WaterConsumerDisconnection();
            $refWorkflow                    = Config::get('workflow-constants.WATER_MASTER_ID');
            $deactiveRefImageName           = config::get('waterConstaint.WATER_CONSUMER_DEACTIVATION');

            $request->request->add(['workflowId' => $refWorkflow]);
            $roleId = $this->getRole($request)->pluck('wf_role_id');
            $request->request->add(['roleId' => $roleId]);

            $consumerDetails = $this->PreConsumerDeactivationCheck($request);

            DB::beginTransaction();
            $document = $this->saveDocument($request, $deactiveRefImageName);
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
        | Recheck the amount and the order from weaver committee
     */
    public function PreConsumerDeactivationCheck($request)
    {
        $consumerId             = $request->consumerId;
        $mWaterWaterConsumer    = new WaterWaterConsumer();
        $mWaterConsumerDemand   = new WaterConsumerDemand();

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
            $mWaterWaterConsumer = new WaterWaterConsumer();
            $mActiveCitizenUndercare = new ActiveCitizenUndercare();

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
            return responseMsgs(true, 'list of undertaken water connections!', remove_null($consumerDetails), "", "01", ".ms", "POST", $request->deviceId);
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
        $request->validate([
            'consumerId'    => "required|digits_between:1,9223372036854775807",
            'ratePerMonth'  => "required|numeric"
        ]);
        try {
            $consumerId = $request->consumerId;
            $mWaterConsumerMeter = new WaterConsumerMeter();
            $relatedDetails = $this->checkParamForFixedEntry($consumerId);
            $metaRequest = new Request([
                'consumerId'                => $consumerId,
                'connectionDate'            => $relatedDetails['meterDetails']['connection_date'],
                'connectionType'            => $relatedDetails['meterDetails']['connection_type'],
                'newMeterInitialReading'    => $relatedDetails['meterDetails']['initial_reading']
            ]);
            $document = [
                'relaivePath'   => $relatedDetails['meterDetails']['relative_path'],
                'document'      => $relatedDetails['meterDetails']['meter_doc']
            ];
            $mWaterConsumerMeter->saveMeterDetails($metaRequest, $document, $request->ratePerMonth);
            return responseMsgs(true, "Fixed rate entered successfully!", "", "", "01", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
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

        $consumerDetails = $mWaterWaterConsumer->getConsumerDetailById($consumerId);
        if ($consumerDetails->property_type_id != $refPropertyType['Government'])
            throw new Exception("Consumer's property type is not under Government!");

        $meterConnectionDetails = $mWaterConsumerMeter->getMeterDetailsByConsumerId($consumerId)->first();
        if (!$meterConnectionDetails)
            throw new Exception("Consumer not found!");

        if ($meterConnectionDetails->connection_type != $refConnectionType['Fixed'])
            throw new Exception("Consumer meter's connection type is not fixed!");

        return $returnData = [
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
        $request->validate([
            'consumerId'  => "required|",
            'uptoData'    => "required|date",
        ]);
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

            $refOldDemandUpto = $refConsumerDemand->demand_upto;
            $privdayDiff = Carbon::parse($refConsumerDemand->demand_upto)->diffInDays(Carbon::parse($refConsumerDemand->demand_from));
            $endDate = Carbon::parse($request->uptoData);
            $startDate = Carbon::parse($refOldDemandUpto);

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

            $returnData['finalMeterReading'] = round($ActualReading, 2);
            $returnData['diffInDays'] = $diffInDays;
            $returnData['previousConsumed'] = $refTaxUnitConsumed;

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
     */
    public function generateMemo(Request $request)
    {
        $request->validate([
            'consumerNo'  => "required|",
        ]);
        try {
            $refConsumerNo          = $request->consumerNo;
            $mWaterConsumerDemand   = new WaterConsumerDemand();
            $mWaterWaterConsumer    = new WaterWaterConsumer();
            $mWaterTranDetail       = new WaterTranDetail();
            $mWaterChequeDtl        = new WaterChequeDtl();
            $mWaterTran             = new WaterTran();

            // $mWaterWaterConsumer->

        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", ".ms", "POST", $request->deviceId);
        }
    }
}
