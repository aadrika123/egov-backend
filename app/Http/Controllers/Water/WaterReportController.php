<?php

namespace App\Http\Controllers\Water;

use App\Http\Controllers\Controller;
use App\Models\Water\WaterTran;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

/**
 * | ----------------------------------------------------------------------------------
 * | Water Module |
 * |-----------------------------------------------------------------------------------
 * | Created On-14-04-2023
 * | Created By-Mrinal Kumar 
 * | Created For-Water Related Reports
 */

class WaterReportController extends Controller
{
    /**
     * | Water count of online payment
     */
    public function onlinePaymentCount(Request $req)
    {
        try {
            $year = Carbon::now()->year;

            if (isset($req->fyear))
                $year = substr($req->fyear, 0, 4);

            $financialYearStart = $year;
            if (Carbon::now()->month < 4) {
                $financialYearStart--;
            }

            $fromDate =  $financialYearStart . '-04-01';
            $toDate   =  $financialYearStart + 1 . '-03-31';

            if ($req->financialYear) {
                $fy = explode('-', $req->financialYear);
                $strtYr = collect($fy)->first();
                $endYr = collect($fy)->last();
                $fromDate =  $strtYr . '-04-01';
                $toDate   =  $endYr . '-03-31';;
            }


            $waterTran = WaterTran::select('id')
                ->where('payment_mode', 'Online')
                ->whereBetween('tran_date', [$fromDate, $toDate]);

            $totalCount['waterCount'] = $waterTran->count();

            return responseMsgs(true, "Online Payment Count", remove_null($totalCount), "", '', '01', '314ms', 'Post', '');
        } catch (Exception $e) {
            return responseMsgs(false, "Online Payment Count", remove_null($totalCount), "", '', '01', '314ms', 'Post', '');
        }
    }
}
