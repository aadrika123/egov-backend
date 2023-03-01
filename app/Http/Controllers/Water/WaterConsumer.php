<?php

namespace App\Http\Controllers\Water;

use App\Http\Controllers\Controller;
use App\Http\Requests\Water\reqDeactivate;
use App\Models\Water\WaterConsumer as WaterWaterConsumer;
use App\Models\Water\WaterConsumerDemand;
use App\Models\Water\WaterConsumerDisconnection;
use App\Models\Water\WaterConsumerInitialMeter;
use App\Models\Water\WaterConsumerMeter;
use App\Models\Water\WaterDisconnection;
use App\Repository\Water\Interfaces\IConsumer;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;



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
        | Serial No : 00
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
     * | @param 
     * | @var 
     * | @return 
        | Serial No : 03
        | Not Working
        | Work on the valuidation and the saving of the meter details 
     */
    public function saveGenerateConsumerDemand(Request $request)
    {
        try {
            $mWaterConsumerDemand = new WaterConsumerDemand();
            $mWaterConsumerInitialMeter = new WaterConsumerInitialMeter();
            $mWaterConsumerMeter = new WaterConsumerMeter();

            // $this->checkDemandGeneration($request);
            $consumerDetails = WaterWaterConsumer::findOrFail($request->consumerId);
            $calculatedDemand = $this->Repository->calConsumerDemand($request);
            if (isset($calculatedDemand)) {
                # get the demand
                $mWaterConsumerMeter->saveMeterReading($request);
                $mWaterConsumerInitialMeter->saveConsumerReading();
                return $generatedDemand = $calculatedDemand['consumer_tax'];
                return  collect($generatedDemand)->map(function ($firstValue)
                use ($mWaterConsumerDemand, $mWaterConsumerInitialMeter, $mWaterConsumerMeter, $consumerDetails, $request) {
                    $meterDetails = [
                        "charge_type"       => $firstValue['charge_type'],
                        "amount"            => $firstValue['charge_type'],
                        "effective_from"    => $firstValue['effective_from'],
                        "initial_reading"   => $firstValue['initial_reading'],
                        "final_reading"     => $firstValue['final_reading'],
                        "rate_id"           => $firstValue['rate_id'],
                    ];
                    $refDemands = $firstValue['consumer_demand'];
                    collect($refDemands)->map(function ($secondValue)
                    use ($mWaterConsumerDemand, $meterDetails, $mWaterConsumerInitialMeter, $mWaterConsumerMeter, $consumerDetails, $request) {
                        $mWaterConsumerDemand->saveConsumerDemand($secondValue, $meterDetails, $consumerDetails, $request);
                        
                    });
                })->first();

                return responseMsgs(true, "Demand Generated! for" . $request->consumerId, "", "", "02", ".ms", "POST", "");
            }
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", "ms", "POST", "");
        }
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
    public function saveMeterDetails(Request $request)
    {
        try{
            $mWaterConsumerMeter = new WaterConsumerMeter();
            $this->checkParamForMeterEntry($request);
            $mWaterConsumerMeter->saveMeterDetails($request);
        }
        catch(Exception $e)
        {
            return responseMsgs(false,$e->getMessage(),$e->getFile(),"","01",".ms","POST","");
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

    }
}
