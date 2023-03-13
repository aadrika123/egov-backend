<?php

namespace App\Http\Controllers\Water;

use App\Http\Controllers\Controller;
use App\Http\Requests\Water\reqDeactivate;
use App\Http\Requests\Water\reqMeterEntry;
use App\Models\Water\WaterConsumer as WaterWaterConsumer;
use App\Models\Water\WaterConsumerDemand;
use App\Models\Water\WaterConsumerDisconnection;
use App\Models\Water\WaterConsumerInitialMeter;
use App\Models\Water\WaterConsumerMeter;
use App\Models\Water\WaterConsumerTax;
use App\Models\Water\WaterDisconnection;
use App\Repository\Water\Interfaces\IConsumer;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\CssSelector\Node\FunctionNode;

class WaterConsumer extends Controller
{
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
            $mWaterConsumerDemand = new WaterConsumerDemand();
            $mWaterConsumerMeter = new WaterConsumerMeter();
            $refConnectionName = Config::get('waterConstaint.METER_CONN_TYPE');
            $refConsumerId = $request->ConsumerId;

            $consumerDemand['consumerDemands'] = $mWaterConsumerDemand->getConsumerDemand($refConsumerId);
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
        | Work on the valuidation and the saving of the meter details 
     */
    public function saveGenerateConsumerDemand(Request $request)
    {
        try {
            $request->validate([
                'consumerId' => "required|digits_between:1,9223372036854775807",
            ]);

            $mWaterConsumerInitialMeter = new WaterConsumerInitialMeter();
            $mWaterConsumerMeter = new WaterConsumerMeter();
            $refMeterConnectionType = Config::get('waterConstaint.METER_CONN_TYPE');
            $this->checkDemandGeneration($request);
            $consumerDetails = WaterWaterConsumer::findOrFail($request->consumerId);
            $calculatedDemand = $this->Repository->calConsumerDemand($request);

            if (isset($calculatedDemand)) {
                # get the demand
                DB::beginTransaction();
                $demandDetails = collect($calculatedDemand['consumer_tax'])->first();
                switch ($demandDetails['charge_type']) {
                    case ($refMeterConnectionType['1']):
                        $meterId = $mWaterConsumerMeter->saveMeterReading($request);
                        $mWaterConsumerInitialMeter->saveConsumerReading($request, $meterId);
                        $this->savingDemand($calculatedDemand, $request, $consumerDetails, $demandDetails['charge_type'], $refMeterConnectionType);
                        break;

                    case ($refMeterConnectionType['2']):
                        $meterId = $mWaterConsumerMeter->saveMeterReading($request);
                        $mWaterConsumerInitialMeter->saveConsumerReading($request, $meterId);
                        $this->savingDemand($calculatedDemand, $request, $consumerDetails, $demandDetails['charge_type'], $refMeterConnectionType);
                        break;

                    case ($refMeterConnectionType['3']):
                        return $this->savingDemand($calculatedDemand, $request, $consumerDetails, $demandDetails['charge_type'], $refMeterConnectionType);
                        break;
                }
                DB::commit();
                return responseMsgs(true, "Demand Generated! for" . $request->consumerId, "", "", "02", ".ms", "POST", "");
            }
        } catch (Exception $e) {
            DB::rollBack();
            dd($e->getMessage(), $e->getFile(), $e->getLine());
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

        collect($generatedDemand)->map(function ($firstValue)
        use ($mWaterConsumerDemand, $consumerDetails, $request, $mWaterConsumerTax, $demandType, $refMeterConnectionType) {
            $taxId = $mWaterConsumerTax->saveConsumerTax($firstValue, $consumerDetails);
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
                    $mWaterConsumerDemand->saveConsumerDemand($refDemands, $meterDetails, $consumerDetails, $request, $taxId);
                    break;
                case ($refMeterConnectionType['2']):
                    $refDemands = $firstValue['consumer_demand'];
                    $mWaterConsumerDemand->saveConsumerDemand($refDemands, $meterDetails, $consumerDetails, $request, $taxId);
                    break;
                case ($refMeterConnectionType['3']):
                    $refDemands = $firstValue['consumer_demand'];
                    collect($refDemands)->map(function ($secondValue)
                    use ($mWaterConsumerDemand, $meterDetails, $consumerDetails, $request, $taxId) {
                        $mWaterConsumerDemand->saveConsumerDemand($secondValue, $meterDetails, $consumerDetails, $request, $taxId);
                    });
                    break;
            }
        });
    }

    /**
     * | Validate the user and other criteria for the Genereating demand
     * | @param request
        | Serial No : 03.02
        | Not Used 
     */
    public function checkDemandGeneration()
    {
    }


    /**
     * | Save the Meter details 
     * | @param
     * | @var 
     * | @return
        | Serial No : 04
        | Not working  
        | Check the parameter for the autherised person
     */
    public function saveUpdateMeterDetails(reqMeterEntry $request)
    {
        try {
            $mWaterConsumerMeter = new WaterConsumerMeter();
            $this->checkParamForMeterEntry($request);
            DB::beginTransaction();
            $documentPath = $this->saveTheMeterDocument($request);
            $mWaterConsumerMeter->saveMeterDetails($request, $documentPath);
            DB::commit();
            return responseMsgs(true, "Meter Detail Entry Success !", "", "", "01", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", ".ms", "POST", "");
        }
    }

    /**
     * | Chech the parameter before Meter entry
     * | Validate the Admin For entring the meter details
     * | @param req
        | Serial No : 04.01
        | Not Working
     */
    public function checkParamForMeterEntry($request)
    {
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
            case ($request->connectionDate > Carbon::now()->format("d-m-Y")):
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
            switch ($consumerMeterDetails) {
                case ($consumerMeterDetails->connection_date > $request->connectionDate):
                    throw new Exception("Connection Date should be grater than previous Connection date!");
            }
        }
        if (isset($consumerDemand)) {
            switch ($consumerDemand) {
                case ($consumerDemand->demand_upto > $request->connectionDate):
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
            // if()
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
    }
}
