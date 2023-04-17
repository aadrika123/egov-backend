<?php

namespace App\Traits\Property;

use Exception;
use Illuminate\Support\Facades\DB;

/**
 * | Created On - 22-03-2023 
 * | Created By - Mrinal Kumar
 */
trait Report
{
    public function gbSafCollectionQuery($table, $fromDate, $uptoDate)
    {
        return DB::table($table)
            ->select(
                't.id',
                'pp.id as property_id',
                'pp.holding_no',
                'ward_name as ward_no',
                $table . '.saf_no',
                $table . '.ward_mstr_id',
                $table . '.prop_address',
                'tran_date',
                'payment_mode as transaction_mode',
                't.user_id as tc_id',
                'user_name as emp_name',
                'tran_no',
                'cheque_no',
                'bank_name',
                'branch_name',
                'amount',
                DB::raw("CONCAT (from_fyear,'(',from_qtr,')','/',to_fyear,'(',to_qtr,')') AS from_upto_fy_qtr"),
            )
            ->join('prop_transactions as t', 't.saf_id', $table . '.id')
            // ->join('prop_gbofficers')
            ->leftjoin('prop_properties as pp', 'pp.id', 't.property_id')
            ->join('users', 'users.id', 't.user_id')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', $table . '.ward_mstr_id')
            ->leftJoin('prop_cheque_dtls', 'prop_cheque_dtls.transaction_id', 't.id')
            ->where($table . '.is_gb_saf', true)
            ->whereBetween('tran_date', [$fromDate, $uptoDate]);
    }
}
