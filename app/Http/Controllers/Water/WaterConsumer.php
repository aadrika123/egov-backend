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
     * | Consumer Deactivation Process
     * | Deactivet the consumer using the consumer Id
     * | @param consumerId
     * | @var 
     * | @return 
        | Serial No : 03
        | Validate
     */
    public function deactivateConsumer(reqDeactivate $request)
    {
        try {
            $mWaterConsumerDemand = new WaterConsumerDemand();
            $WaterWaterConsumer = new WaterWaterConsumer();
            $mWaterConsumerDisconnection = new WaterConsumerDisconnection();
            $refDeactivationCriteria = Config::get('waterConstaint.WATER_MASTER_DATA.DEACTIVATION_CRITERIA');
            $refConsumerId = $request->consumerId;
            $refConsumerDetails = $WaterWaterConsumer->getConsumerById($refConsumerId);
            $this->checkDeactivationParameters($request);

            switch ($request) {
                case (in_array($request->deactivateReason, $refDeactivationCriteria)):
                    $mWaterConsumerDisconnection->saveDeactivationDetails($refConsumerDetails);
                    $refConsumerDemand = $mWaterConsumerDemand->getConsumerDemand($refConsumerId);
                    $checkDemand = collect($refConsumerDemand)->first();
                    if ($checkDemand) {
                        $consumerDemandIds = collect($refConsumerDemand)->map(function ($value, $key) {
                            return $value['id'];
                        });
                        $mWaterConsumerDemand->deactivateDemand($consumerDemandIds);
                    }
                    $WaterWaterConsumer->dissconnetConsumer($refConsumerId);
                    break;

                case (!$request->deactivateReason):
                    $refConsumerDemand = $mWaterConsumerDemand->getConsumerDemand($refConsumerId);
                    $checkDemand = collect($refConsumerDemand)->first();
                    if (!$checkDemand) {
                        throw new Exception("Demand for the respective Consumer id Unpaid!");
                    }
                    $consumerDemandIds = collect($refConsumerDemand)->map(function ($value, $key) {
                        return $value['id'];
                    });
                    $mWaterConsumerDisconnection->saveDeactivationDetails($refConsumerDetails);
                    $WaterWaterConsumer->dissconnetConsumer($refConsumerId);
                    $mWaterConsumerDemand->deactivateDemand($consumerDemandIds);
                    break;
            }
        } catch (Exception $e) {
            return responseMsgs(true, $e->getMessage(), $e->getFile(), "", "01", ".ms", "POST", "");
        }
    }
}
