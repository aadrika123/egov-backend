<?php

namespace App\Http\Controllers\Water;

use App\Http\Controllers\Controller;
use App\Models\Water\WaterConsumer as WaterWaterConsumer;
use App\Models\Water\WaterConsumerDemand;
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
     */
    public function listConsumerDemand(Request $request)
    {
        $request->validate([
            'ConsumerId' => 'required|',
        ]);
        try {
            $WaterConsumerDemand = new WaterConsumerDemand();
            $consumerDemand = $WaterConsumerDemand->getConsumerDemand($request->ConsumerId);
            $consumerDemand['totalSumDemand'] = collect($consumerDemand)->map(function ($value, $key) {
                return $value['amount'];
            })->sum();
            return $consumerDemand['sumDemand'] = collect($consumerDemand)->map(function ($value, $key) {
                return $value;
            });
            // return $consumerDemand;
            return responseMsgs(true, "List of Consumer Demand!", $consumerDemand, "", "01", "ms", "POST", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", "ms", "POST", "");
        }
    }
}
