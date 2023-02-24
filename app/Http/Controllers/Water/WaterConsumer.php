<?php

namespace App\Http\Controllers\Water;

use App\Http\Controllers\Controller;
use App\Models\Water\WaterConsumer as WaterWaterConsumer;
use App\Models\Water\WaterConsumerDemand;
use App\Models\Water\WaterConsumerInitialMeter;
use App\Models\Water\WaterConsumerMeter;
use App\Repository\Water\Interfaces\IConsumer;
use Exception;
use Illuminate\Http\Request;

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
            $refConsumerId = $request->ConsumerId;

            $consumerDemand['consumerDemands'] = $mWaterConsumerDemand->getConsumerDemand($refConsumerId);
            $consumerDemand['totalSumDemand'] = collect($consumerDemand['consumerDemands'])->map(function ($value, $key) {
                return $value['balance_amount'];
            })->sum();
            $consumerDemand['totalPenalty'] = collect($consumerDemand['consumerDemands'])->map(function ($value, $key) {
                return $value['penalty'];
            })->sum();

            $consumerDemand['meterDetails'] = $mWaterConsumerMeter->getMeterDetailsByConsumerId($refConsumerId)->first();

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
        | Working
     */
    public function deactivateConsumer(Request $request)
    {
        try{
            
        }
        catch(Exception $e)
        {
            return responseMsgs(true,$e->getMessage(),$e->getFile(),"","01",".ms","POST","");
        }
    }
}
