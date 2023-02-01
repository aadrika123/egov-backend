<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Property\PropTransaction;
use App\Models\Trade\TradeTransaction;
use App\Models\Water\WaterTran;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * | Created On-31-01-2023 
 * | Created by-Mrinal Kumar
 * | Payment Cash Verification
 */

class CashVerificationController extends Controller
{
    public function cashVerificationList(Request $request)
    {
        try {
            $ulbId =  authUser()->ulb_id;
            $userId =  $request->id;
            $date = date('Y-m-d', strtotime($request->date));
            // if (isset($request->date)) {


            $date = date('Y-m-d', strtotime($request->date));

            DB::enableQueryLog();
            $propTraDtl = PropTransaction::select(
                'users.id',
                'users.user_name',
                DB::raw("sum(prop_transactions.amount) as amount,'property' as module"),
            )
                ->join('users', 'users.id', 'prop_transactions.user_id')
                ->whereNotNull('property_id')
                ->where('tran_date', $date)
                ->where('payment_mode', '!=', 'ONLINE')
                ->groupBy(["users.id", "users.user_name"]);

            $tradeDtl  = TradeTransaction::select(
                'users.id',
                'users.user_name',
                DB::raw("sum(trade_transactions.paid_amount) as amount,'trade' as module"),
            )
                ->join('users', 'users.id', 'trade_transactions.emp_dtl_id')
                ->where('tran_date', $date)
                ->where('payment_mode', '!=', 'ONLINE')
                ->groupBy(["users.id", "users.user_name"]);

            $waterDtl = WaterTran::select(
                'users.id',
                'users.user_name',
                DB::raw("sum(water_trans.amount) as amount,'water' as module"),
            )
                ->join('users', 'users.id', 'water_trans.emp_dtl_id')
                ->where('tran_date', $date)
                ->where('payment_mode', '!=', 'ONLINE')
                ->groupBy(["users.id", "users.user_name"]);
            if ($userId) {
                $propTraDtl = $propTraDtl->where('user_id', $userId);
                $tradeDtl = $tradeDtl->where('emp_dtl_id', $userId);
                $waterDtl = $waterDtl->where('emp_dtl_id', $userId);
            }
            $propTraDtl1 = $propTraDtl;
            $collection = $propTraDtl1
                ->union($tradeDtl)
                ->union($waterDtl)
                ->get();
            $collection = collect($collection->groupBy("id")->all());
            $data = $collection->map(function ($val) {
                $total =  $val->sum('amount');
                $prop = $val->where("module", "property")->sum('amount');
                $trad = $val->where("module", "trade")->sum('amount');
                $water = $val->where("module", "water")->sum('amount');
                return [
                    "user_name" => $val[0]['user_name'],
                    "id" => $val[0]['id'],
                    "property" => $prop,
                    "water" => $water,
                    "trade" => $trad,
                    "total" => $total,
                ];
            });
            return responseMsgs(true, "List cash Verification", $data, "010201", "1.0", "", "POST", $request->deviceId ?? "");



            // -----------------------------------------------------------------


            if ($userId) {
                $propDtl = PropTransaction::select('prop_transactions.*', 'users.user_name')
                    ->join('users', 'users.id', 'prop_transactions.user_id')
                    ->where('tran_date', $date)
                    ->where('payment_mode', '!=', 'ONLINE')
                    ->where('user_id', $userId)
                    ->orderBy('tran_date')
                    ->get();

                $amount['property'] = collect($propDtl)->map(function ($value) {
                    return $value['amount'];
                });

                $tradeDtl  = TradeTransaction::select('trade_transactions.*', 'users.user_name')
                    ->join('users', 'users.id', 'trade_transactions.emp_dtl_id')
                    ->where('tran_date', $date)
                    ->where('payment_mode', '!=', 'ONLINE')
                    ->orderBy('tran_date')
                    ->where('emp_dtl_id', $userId)
                    ->get();

                $amount['trade'] = collect($tradeDtl)->map(function ($value) {
                    return $value['paid_amount'];
                });

                $waterDtl = WaterTran::select('water_trans.*', 'users.user_name')
                    ->join('users', 'users.id', 'water_trans.emp_dtl_id')
                    ->where('tran_date', $date)
                    ->where('payment_mode', '!=', 'ONLINE')
                    ->orderBy('tran_date')
                    ->where('emp_dtl_id', $userId)
                    ->get();
                $amount['water'] = collect($waterDtl)->map(function ($value) {
                    return $value['amount'];
                });
            }

            $propDtl = PropTransaction::select('prop_transactions.*', 'users.user_name')
                ->join('users', 'users.id', 'prop_transactions.user_id')
                ->where('tran_date', $date)
                ->where('payment_mode', '!=', 'ONLINE')
                ->orderBy('tran_date')
                ->get();

            $amount['property'] = collect($propDtl)->map(function ($value) {
                return $value['amount'];
            });

            $tradeDtl  = TradeTransaction::select('trade_transactions.*', 'users.user_name')
                ->join('users', 'users.id', 'trade_transactions.emp_dtl_id')
                ->where('tran_date', $date)
                ->where('payment_mode', '!=', 'ONLINE')
                ->orderBy('tran_date')
                ->get();

            $amount['trade'] = collect($tradeDtl)->map(function ($value) {
                return $value['paid_amount'];
            });

            $waterDtl = WaterTran::select('water_trans.*', 'users.user_name')
                ->join('users', 'users.id', 'water_trans.emp_dtl_id')
                ->where('tran_date', $date)
                ->where('payment_mode', '!=', 'ONLINE')
                ->orderBy('tran_date')
                ->get();
            $amount['water'] = collect($waterDtl)->map(function ($value) {
                return $value['amount'];
            });


            $total['trade'] =  $amount['trade']->sum();
            $total['water'] =  $amount['water']->sum();
            $total['property'] =  $amount['property']->sum();
            $total['total'] = collect($total)->sum();

            $total['propDtl'] =  $propDtl;
            $total['tradeDtl'] =  $tradeDtl;
            $total['waterDtl'] =  $waterDtl;


            // return $total;

            // // return $amount;
            // return  $amount = $amount['trade'] + $amount['water'] + $amount['property'];
            // return  $amount = $amount['property'] + $amount['trade'] + $amount['water'];

            return responseMsgs(true, "List cash Verification", $total, "010201", "1.0", "", "POST", $request->deviceId ?? "");
        }
        // else {
        //     return responseMsgs(false, "Undefined Parameter", "", "010201", "1.0", "", "POST", $request->deviceId ?? "");
        // }
        // }
        catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010201", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }
}
