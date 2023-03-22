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
                'ward_name as ward_no',
                'saf_no as application_no',
                'ward_mstr_id',
                'prop_address',
                'tran_date',
                'payment_mode as transaction_mode',
                't.user_id as tc_id',
                'user_name as emp_name',
                'tran_no',
                'cheque_no',
                'bank_name',
                'branch_name',
                'amount',
                DB::raw("CONCAT (from_fyear,'(',from_qtr,')','/',to_fyear,'(',to_qtr,')') AS payment_year"),
            )
            ->join('prop_transactions as t', 't.saf_id', $table . '.id')
            ->join('users', 'users.id', 't.user_id')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', $table . '.ward_mstr_id')
            ->leftJoin('prop_cheque_dtls', 'prop_cheque_dtls.transaction_id', 't.id')
            ->where('is_gb_saf', true)
            ->whereBetween('tran_date', [$fromDate, $uptoDate]);
    }
}
