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
                DB::raw("sum(prop_transactions.amount) as amount,'property' as module,
                sum(case when prop_transactions.verify_status = 1 then prop_transactions.amount else 0 end ) as verified_amount,
                string_agg((case when prop_transactions.verify_status = 1 then 1 else 0 end)::text,',') As verify_status
                "),
            )
                ->join('users', 'users.id', 'prop_transactions.user_id')
                // ->whereNotNull('property_id')
                ->where('tran_date', $date)
                ->where('prop_transactions.status', '<>', 0)
                ->where('payment_mode', '!=', 'ONLINE')
                ->groupBy(["users.id", "users.user_name"]);

            $tradeDtl  = TradeTransaction::select(
                'users.id',
                'users.user_name',
                DB::raw("sum(trade_transactions.paid_amount) as amount,'trade' as module , 
                sum(case when trade_transactions.is_verified is true  then trade_transactions.paid_amount else 0 end ) as verified_amount,
                string_agg((case when trade_transactions.is_verified is true then 1 else 0 end)::text,',') As verify_status
                "),
            )
                ->join('users', 'users.id', 'trade_transactions.emp_dtl_id')
                ->where('tran_date', $date)
                ->where('trade_transactions.status', '<>', 0)
                ->where('payment_mode', '!=', 'ONLINE')
                ->groupBy(["users.id", "users.user_name"]);

            $waterDtl = WaterTran::select(
                'users.id',
                'users.user_name',
                DB::raw("sum(water_trans.amount) as amount,'water' as module,
                sum(case when water_trans.verify_status =1  then water_trans.amount else 0 end ) as verified_amount,
                string_agg((case when water_trans.verify_status =1 then 1 else 0 end)::text,',') As verify_status
                "),
            )
                ->join('users', 'users.id', 'water_trans.emp_dtl_id')
                ->where('tran_date', $date)
                ->where('water_trans.status', '<>', 0)
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
            // dd($collection);
            $data = $collection->map(function ($val) use ($date) {
                $total =  $val->sum('amount');
                $verified_amount =  $val->sum('verified_amount');
                $prop = $val->where("module", "property")->sum('amount');
                $trad = $val->where("module", "trade")->sum('amount');
                $water = $val->where("module", "water")->sum('amount');
                $is_verified = in_array(0, (objToArray(collect(explode(',', ($val->implode("verify_status", ',')))))));
                return [
                    "user_name" => $val[0]['user_name'],
                    "id" => $val[0]['id'],
                    "property" => $prop,
                    "water" => $water,
                    "trade" => $trad,
                    "total" => $total,
                    "is_verified" => !$is_verified,
                    "verified_amount" => $verified_amount,
                    "GB_saf" => 0,
                    "date" => $date
                ];
            });
            $data = (array_values(objtoarray($data)));
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


    /**
     * 
     */
    public function tcCollectionDtl(Request $request)
    {
        $request->validate([
            "date" => "required|date",
            "id" => "required|numeric",

        ]);

        $userId = $request->id;
        $date = date('Y-m-d', strtotime($request->date));




        $sql =   "WITH 
            prop_transactions AS 
        (
            SELECT prop_transactions.id,assessment_type AS application_type, saf_no AS application_no,tran_no,
            payment_mode,amount,verify_status,verified_by,verify_date,ward_name,tran_date,
                    prop_active_safs.ward_mstr_id AS ward_id , owner_name,'activ_saf' AS tbl
                FROM prop_transactions
                inner join prop_active_safs on prop_active_safs.id = prop_transactions.saf_id
                inner join ulb_ward_masters on ulb_ward_masters.id = prop_active_safs.ward_mstr_id
                LEFT JOIN (
                    SELECT prop_active_safs_owners.saf_id,string_agg(owner_name,',') AS owner_name
                    FROM prop_active_safs_owners
                    JOIN prop_transactions ON prop_transactions.saf_id = prop_active_safs_owners.saf_id
                    WHERE prop_active_safs_owners.status = 1
                        AND prop_transactions.status = 1
                        AND prop_transactions.tran_date = '" . $date . "'
                        AND payment_mode != 'netbanking'
                        AND prop_transactions.payment_mode != 'ONLINE'
                    GROUP BY prop_active_safs_owners.saf_id
                ) owners ON owners.saf_id = prop_active_safs.id
                WHERE prop_transactions.status = 1 
                    AND prop_transactions.tran_date = '" . $date . "'
                    AND prop_transactions.payment_mode != 'ONLINE'
                    AND payment_mode != 'netbanking'
                    AND prop_transactions.user_id = $userId
                
            union
                (
                    SELECT prop_transactions.id,assessment_type AS application_type, saf_no AS application_no,tran_no,
                    payment_mode,amount,verify_status,verified_by,verify_date,ward_name,tran_date,
                        prop_rejected_safs.ward_mstr_id AS ward_id , owner_name,'rejected_saf' AS tbl
                    FROM prop_transactions
                    inner join prop_rejected_safs on prop_rejected_safs.id = prop_transactions.saf_id
                    inner join ulb_ward_masters on ulb_ward_masters.id = prop_rejected_safs.ward_mstr_id
                    LEFT JOIN (
                        SELECT prop_rejected_safs_owners.saf_id,string_agg(owner_name,',') AS owner_name
                        FROM prop_rejected_safs_owners
                        JOIN prop_transactions ON prop_transactions.saf_id = prop_rejected_safs_owners.saf_id
                        WHERE prop_rejected_safs_owners.status = 1
                            AND prop_transactions.status = 1
                            AND prop_transactions.tran_date = '2023-02-01'
                            AND payment_mode != 'netbanking'
                            AND prop_transactions.payment_mode != 'ONLINE'
                        GROUP BY prop_rejected_safs_owners.saf_id
                    ) owners ON owners.saf_id = prop_rejected_safs.id
                    WHERE prop_transactions.status = 1 
                        AND prop_transactions.tran_date = '" . $date . "'
                        AND prop_transactions.payment_mode != 'ONLINE'
                        AND payment_mode != 'netbanking'
                        AND prop_transactions.user_id = $userId
                )
            union
                (
                    SELECT prop_transactions.id,assessment_type AS application_type, saf_no AS application_no,
                    tran_no,payment_mode,amount,verify_status,verified_by,verify_date,ward_name,tran_date,
                        prop_safs.ward_mstr_id AS ward_id , owner_name,'prop_saf' AS tbl
                    FROM prop_transactions
                    inner join prop_safs on prop_safs.id = prop_transactions.saf_id
                    inner join ulb_ward_masters on ulb_ward_masters.id = prop_safs.ward_mstr_id
                    LEFT JOIN (
                        SELECT prop_safs_owners.saf_id,string_agg(owner_name,',') AS owner_name
                        FROM prop_safs_owners
                        JOIN prop_transactions ON prop_transactions.saf_id = prop_safs_owners.saf_id
                        WHERE prop_safs_owners.status = 1
                            AND prop_transactions.status = 1
                            AND prop_transactions.tran_date = '" . $date . "'
                            AND payment_mode != 'netbanking'
                            AND prop_transactions.payment_mode != 'ONLINE'
                        GROUP BY prop_safs_owners.saf_id
                    ) owners ON owners.saf_id = prop_safs.id
                    WHERE prop_transactions.status = 1 
                        AND prop_transactions.tran_date = '" . $date . "'
                        AND prop_transactions.payment_mode != 'ONLINE'
                        AND payment_mode != 'netbanking'
                        AND prop_transactions.user_id = $userId
                )
                
            union
                (
                    SELECT prop_transactions.id,assessment_type AS application_type, holding_no AS application_no,tran_no,
                    payment_mode,amount,verify_status,verified_by,verify_date,ward_name,tran_date,
                        prop_properties.ward_mstr_id AS ward_id , owner_name,'prop_properties' AS tbl
                    FROM prop_transactions
                    inner join prop_properties on prop_properties.id = prop_transactions.property_id
                    inner join ulb_ward_masters on ulb_ward_masters.id = prop_properties.ward_mstr_id
                    LEFT JOIN (
                        SELECT prop_owners.property_id,string_agg(owner_name,',') AS owner_name
                        FROM prop_owners
                        JOIN prop_transactions ON prop_transactions.property_id = prop_owners.property_id
                        WHERE prop_owners.status = 1
                            AND prop_transactions.status = 1
                            AND prop_transactions.tran_date = '" . $date . "'
                            AND prop_transactions.payment_mode != 'ONLINE'
                            AND payment_mode != 'netbanking'
                        GROUP BY prop_owners.property_id
                    ) owners ON owners.property_id = prop_properties.id
                    WHERE prop_transactions.status = 1 
                        AND prop_transactions.tran_date = '" . $date . "'
                        AND prop_transactions.payment_mode != 'ONLINE'
                        AND payment_mode != 'netbanking'
                        AND prop_transactions.user_id = $userId
                )
        )select * from  prop_transactions;";

        //trade
        $trade =   "WITH 
            trade_transaction AS 
        (
            SELECT trade_transactions.id,tran_no,
                payment_mode,paid_amount,is_verified,verify_by,verify_date,ward_name,application_no,
                tran_type,tran_date,owner_name,'active_trade_licences' AS tbl
            FROM trade_transactions
            inner join active_trade_licences on active_trade_licences.id = trade_transactions.temp_id
            inner join ulb_ward_masters on ulb_ward_masters.id = trade_transactions.ward_id
            LEFT JOIN (
                SELECT active_trade_owners.temp_id,string_agg(owner_name,',') AS owner_name
                FROM active_trade_owners
                JOIN trade_transactions ON trade_transactions.temp_id = active_trade_owners.temp_id
                WHERE active_trade_owners.is_active = true
                    AND trade_transactions.status = 1
                    AND trade_transactions.tran_date = '" . $date . "'
                    AND payment_mode != 'netbanking'
                    AND trade_transactions.payment_mode != 'ONLINE'
                GROUP BY active_trade_owners.temp_id
            ) owners ON owners.temp_id = active_trade_licences.id
            WHERE trade_transactions.status = 1 
                AND trade_transactions.tran_date = '" . $date . "'
                AND trade_transactions.payment_mode != 'ONLINE'
                AND payment_mode != 'netbanking'
                AND emp_dtl_id = $userId
            
        union
            (
            SELECT trade_transactions.id,tran_no,
                payment_mode,paid_amount,is_verified,verify_by,verify_date,ward_name,application_no,
                tran_type,tran_date,owner_name,'trade_licences' AS tbl
            FROM trade_transactions
            inner join trade_licences on trade_licences.id = trade_transactions.temp_id
            inner join ulb_ward_masters on ulb_ward_masters.id = trade_transactions.ward_id
            LEFT JOIN (
                SELECT trade_owners.temp_id,string_agg(owner_name,',') AS owner_name
                FROM trade_owners
                JOIN trade_transactions ON trade_transactions.temp_id = trade_owners.temp_id
                WHERE trade_owners.is_active = true
                    AND trade_transactions.status = 1
                    AND trade_transactions.tran_date = '" . $date . "'
                    AND payment_mode != 'netbanking'
                    AND trade_transactions.payment_mode != 'ONLINE'
                GROUP BY trade_owners.temp_id
            ) owners ON owners.temp_id = trade_licences.id
            WHERE trade_transactions.status = 1 
                AND trade_transactions.tran_date = '" . $date . "'
                AND trade_transactions.payment_mode != 'ONLINE'
                AND payment_mode != 'netbanking'
                AND emp_dtl_id = $userId
            )
        union
            (
            SELECT trade_transactions.id,tran_no,
                payment_mode,paid_amount,is_verified,verify_by,verify_date,ward_name,application_no,
                tran_type,tran_date,owner_name,'rejected_trade_licences' AS tbl
            FROM trade_transactions
            inner join rejected_trade_licences on rejected_trade_licences.id = trade_transactions.temp_id
            inner join ulb_ward_masters on ulb_ward_masters.id = trade_transactions.ward_id
            LEFT JOIN (
                SELECT rejected_trade_owners.temp_id,string_agg(owner_name,',') AS owner_name
                FROM rejected_trade_owners
                JOIN trade_transactions ON trade_transactions.temp_id = rejected_trade_owners.temp_id
                WHERE rejected_trade_owners.is_active = true
                    AND trade_transactions.status = 1
                    AND trade_transactions.tran_date = '" . $date . "'
                    AND payment_mode != 'netbanking'
                    AND trade_transactions.payment_mode != 'ONLINE'
                GROUP BY rejected_trade_owners.temp_id
            ) owners ON owners.temp_id = rejected_trade_licences.id
            WHERE trade_transactions.status = 1 
                AND trade_transactions.tran_date = '" . $date . "'
                AND trade_transactions.payment_mode != 'ONLINE'
                AND payment_mode != 'netbanking'
                AND emp_dtl_id = $userId
            )
        )select * from  trade_transaction;";

        //water
        $water =   "WITH 
            water_transaction AS 
        (
            SELECT water_trans.id,tran_no,
            payment_mode,amount,verify_status,verified_by,verified_date,ward_name,tran_date,application_no,tran_type,
                    owner_name,'water_active' AS tbl
                FROM water_trans
                inner join water_applications on water_applications.id = water_trans.related_id
                inner join ulb_ward_masters on ulb_ward_masters.id = water_trans.ward_id
                LEFT JOIN (
                    SELECT water_applicants.application_id,string_agg(applicant_name,',') AS owner_name
                    FROM water_applicants
                    JOIN water_trans ON water_trans.related_id = water_applicants.application_id
                    WHERE water_applicants.status = true
                        AND water_trans.status = 1
                        AND water_trans.tran_date = '" . $date . "'
                        AND payment_mode != 'netbanking'
                        AND water_trans.payment_mode != 'Online'
                    GROUP BY water_applicants.application_id
                ) owners ON owners.application_id = water_applications.id
                WHERE water_trans.status = 1 
            		AND water_trans.tran_date = '" . $date . "'
                    AND water_trans.payment_mode != 'Online'
                    AND payment_mode != 'netbanking'
                    AND emp_dtl_id = $userId
            
        union
            (
            
            SELECT water_trans.id,tran_no,
            payment_mode,amount,verify_status,verified_by,verified_date,ward_name,tran_date,application_no,tran_type,
                    owner_name,'water_approved' AS tbl
                FROM water_trans
                inner join water_approval_application_details on water_approval_application_details.id = water_trans.related_id
                inner join ulb_ward_masters on ulb_ward_masters.id = water_trans.ward_id
                LEFT JOIN (
                    SELECT water_approval_applicants.application_id,string_agg(applicant_name,',') AS owner_name
                    FROM water_approval_applicants
                    JOIN water_trans ON water_trans.related_id = water_approval_applicants.application_id
                    WHERE water_approval_applicants.status = true
                        AND water_trans.status = 1
                        AND water_trans.tran_date = '" . $date . "'
                        AND payment_mode != 'netbanking'
                        AND water_trans.payment_mode != 'Online'
                    GROUP BY water_approval_applicants.application_id
                ) owners ON owners.application_id = water_approval_application_details.id
                WHERE water_trans.status = 1 
            		AND water_trans.tran_date = '" . $date . "'
                    AND water_trans.payment_mode != 'Online'
                    AND payment_mode != 'netbanking'
                    AND emp_dtl_id = $userId
            )
        union
            (
                
            SELECT water_trans.id,tran_no,
            payment_mode,amount,verify_status,verified_by,verified_date,ward_name,tran_date,application_no,tran_type,
                    owner_name,'water_rejected' AS tbl
                FROM water_trans
                inner join water_rejection_application_details on water_rejection_application_details.id = water_trans.related_id
                inner join ulb_ward_masters on ulb_ward_masters.id = water_trans.ward_id
                LEFT JOIN (
                    SELECT water_rejection_applicants.application_id,string_agg(applicant_name,',') AS owner_name
                    FROM water_rejection_applicants
                    JOIN water_trans ON water_trans.related_id = water_rejection_applicants.application_id
                    WHERE water_rejection_applicants.status = true
                        AND water_trans.status = 1
                        AND water_trans.tran_date = '" . $date . "'
                        AND payment_mode != 'netbanking'
                        AND water_trans.payment_mode != 'Online'
                    GROUP BY water_rejection_applicants.application_id
                ) owners ON owners.application_id = water_rejection_application_details.id
                WHERE water_trans.status = 1 
            		AND water_trans.tran_date = '" . $date . "'
                    AND water_trans.payment_mode != 'Online'
                    AND payment_mode != 'netbanking'
                    AND emp_dtl_id = $userId
            )
            
        union
            (
                
            SELECT water_trans.id,tran_no,
            payment_mode,amount,verify_status,verified_by,verified_date,ward_name,tran_date,consumer_no,tran_type,
                    owner_name,'water_consumer' AS tbl
                FROM water_trans
                inner join water_consumers on water_consumers.id = water_trans.related_id
                inner join ulb_ward_masters on ulb_ward_masters.id = water_trans.ward_id
                LEFT JOIN (
                    SELECT water_consumer_owners.consumer_id,string_agg(applicant_name,',') AS owner_name
                    FROM water_consumer_owners
                    JOIN water_trans ON water_trans.related_id = water_consumer_owners.consumer_id
                    WHERE water_consumer_owners.status = true
                        AND water_trans.status = 1
                        AND water_trans.tran_date = '" . $date . "'
                        AND payment_mode != 'netbanking'
                        AND water_trans.payment_mode != 'Online'
                    GROUP BY water_consumer_owners.consumer_id
                ) owners ON owners.consumer_id = water_consumers.id
                WHERE water_trans.status = 1 
            		AND water_trans.tran_date = '" . $date . "'
                    AND water_trans.payment_mode != 'Online'
                    AND payment_mode != 'netbanking'
                    AND emp_dtl_id = $userId
            )
        )select * from  water_transaction;";



        $data['property'] =  DB::select($sql);
        $data['trade'] =  DB::select($trade);
        $data['water'] =  DB::select($water);

        return responseMsgs(true, "TC Collection", $data, "010201", "1.0", "", "POST", $request->deviceId ?? "");





        $propDtl = PropTransaction::select('prop_transactions.*', 'users.user_name')
            ->join('users', 'users.id', 'prop_transactions.user_id')
            // ->join('prop_active_safs', 'prop_active_safs')
            // ->join('ulb_ward_masters','ulb_ward_masters.id','')
            ->where('tran_date', $date)
            ->where('payment_mode', '!=', 'ONLINE')
            ->where('user_id', $userId)
            ->orderBy('tran_date')
            ->get();

        $tradeDtl  = TradeTransaction::select(
            'trade_transactions.*',
            'users.user_name',
            DB::raw("string_agg(owner_name::text,',') As owner_name"),
            'license_no',
            'provisional_license_no',
            'application_no',
            'ward_name'
        )
            ->join('users', 'users.id', 'trade_transactions.emp_dtl_id')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'trade_transactions.ward_id')
            ->join('active_trade_licences', 'active_trade_licences.id', 'trade_transactions.temp_id')
            ->join('active_trade_owners', 'active_trade_owners.temp_id', 'active_trade_licences.id')
            ->where('tran_date', $date)
            ->where('payment_mode', '!=', 'ONLINE')
            ->where('emp_dtl_id', $userId)
            ->groupBy(
                'trade_transactions.id',
                'users.user_name',
                'license_no',
                'provisional_license_no',
                'application_no',
                'ward_name',
            )
            ->get();



        $waterDtl = WaterTran::select('water_trans.*', 'users.user_name')
            ->join('users', 'users.id', 'water_trans.emp_dtl_id')
            ->where('tran_date', $date)
            ->where('payment_mode', '!=', 'ONLINE')
            ->orderBy('tran_date')
            ->where('emp_dtl_id', $userId)
            ->get();

        // $data['property'] = $propDtl;
        $data['trade'] = $tradeDtl;
        $data['water'] = $waterDtl;

        return responseMsgs(true, "TC Collection", $data, "010201", "1.0", "", "POST", $request->deviceId ?? "");
    }
}
