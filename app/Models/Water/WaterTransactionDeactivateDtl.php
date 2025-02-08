<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WaterTransactionDeactivateDtl extends Model
{
    use HasFactory;
    protected $guarded = [];
    public $timestamps = false;
    protected $connection = 'pgsql_water';

    public function getDetails($fromDate, $uptoDate)
    {
        return  self::select(
            DB::raw("
                water_transaction_deactivate_dtls.tran_id,
                water_transaction_deactivate_dtls.reference_no,
                p.id AS ref_property_id,
                ulb_ward_masters.ward_name AS ward_no,
                'property' AS type,
                p.holding_no,
                owner_detail.owner_name,
                owner_detail.mobile_no,
                water_transaction_deactivate_dtls.deactive_date,
                water_transaction_deactivate_dtls.reason,
                pt.tran_no,
                pt.tran_date,
                pt.payment_mode,
                prop_cheque_dtls.cheque_no,
                prop_cheque_dtls.cheque_date,
                prop_cheque_dtls.bank_name,
                prop_cheque_dtls.branch_name,
                users.name,
                prop_safs.saf_no
            ")
        )
            ->join("prop_transactions as pt", "pt.id", "=", "water_transaction_deactivate_dtls.tran_id")
            ->join("prop_properties as p", "p.id", "=", "pt.property_id")
            ->Join("users", "users.id", "=", "water_transaction_deactivate_dtls.deactivated_by")
            ->leftJoin("prop_safs", "prop_safs.id", "=", "pt.saf_id")
            ->join(
                DB::raw("(
            SELECT 
                STRING_AGG(owner_name, ', ') AS owner_name, 
                STRING_AGG(mobile_no::TEXT, ', ') AS mobile_no, 
                prop_owners.property_id 
            FROM prop_owners 
            WHERE status = 1 
            GROUP BY prop_owners.property_id
            ) AS owner_detail"),
                function ($join) {
                    $join->on("owner_detail.property_id", "=", "p.id");
                }
            )

            ->leftJoin("ulb_ward_masters", "ulb_ward_masters.id", "=", "p.ward_mstr_id")
            ->leftJoin("prop_cheque_dtls", "prop_cheque_dtls.transaction_id", "=", "pt.id")
            ->whereBetween("water_transaction_deactivate_dtls.deactive_date", [$fromDate, $uptoDate]);
        // ->get();

        // return $data;
    }
}
