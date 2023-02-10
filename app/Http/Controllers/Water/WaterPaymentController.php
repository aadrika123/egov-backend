<?php

namespace App\Http\Controllers\Water;

use App\Http\Controllers\Controller;
use App\Models\Water\WaterConsumer;
use App\Models\Water\WaterTran;
use Exception;
use Illuminate\Http\Request;

/**
 * | ----------------------------------------------------------------------------------
 * | Water Module |
 * |-----------------------------------------------------------------------------------
 * | Created On-10-02-2023
 * | Created By-Sam kerketta 
 * | Created For-Water related Transaction and Payment Related operations
 */

class WaterPaymentController extends Controller
{
    // water transaction Details

    /**
     * | Get Consumer Payment History 
     * | Collect All the transaction relate to the respective Consumer 
     * | @param ApplicationId
     * | @var 
     * | @return 
     */
    public function getConsumerPaymentHistory(Request $request)
    {
        $request->validate([
            'consumerId' => 'required|digits_between:1,9223372036854775807'
        ]);
        try {
            $mWaterTran = new WaterTran();
            $mWaterConsumer = new WaterConsumer();

            $transactions = array();

            $waterDtls = $mWaterConsumer->getConsumerDetailById($request->consumerId);
            if (!$waterDtls)
                throw new Exception("Water Consumer Not Found!");

            $waterTrans = $mWaterTran->ConsumerTransaction($request->consumerId)->get();         // Water Consumer Payment History
            if (!$waterTrans || $waterTrans->isEmpty())
                throw new Exception("No Transaction Found!");

            $applicationId = $waterDtls->apply_connection_id;
            if (!$applicationId)
                throw new Exception("This Property has not ApplicationId!!");

            $connectionTran[] = $mWaterTran->getTransNo($applicationId, null)->first();                        // Water Connection payment History

            if (!$connectionTran)
                throw new Exception("Water Application Tran Details not Found!!");

            $transactions['Consumer'] = collect($waterTrans)->sortByDesc('id')->values();
            $transactions['connection'] = collect($connectionTran)->sortByDesc('id');

            return responseMsgs(true, "", remove_null($transactions), "", "01", "ms", "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", "ms", "POST", "");
        }
    }
}
