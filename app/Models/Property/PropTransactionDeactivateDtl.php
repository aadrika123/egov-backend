<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropTransactionDeactivateDtl extends Model
{
    use HasFactory;
    protected $guarded = [];
    public $timestamps = false;

    public function getDetails($fromDate, $uptoDate)
    {
        return self::select(
            'p.holding_no',
            'p.applicant_name',
            // 'users.name as deactivate_by',
            'prop_transaction_deactivate_dtls.deactive_date',
            'prop_transaction_deactivate_dtls.reason',
            'ulb_ward_masters.ward_name as ward_no',
            // 'prop_safs.saf_no',
        )
            ->join('prop_transactions as pt', 'pt.id', 'prop_transaction_deactivate_dtls.tran_id')
            // ->join("users as u", "u.id", "prop_transaction_deactivate_dtls.deactivated_by")
            ->leftjoin("prop_properties as p", "p.id", "pt.property_id")
            ->leftjoin("ulb_ward_masters", "ulb_ward_masters.id", "p.ward_mstr_id")
            // ->join("users as u", "u.id", "prop_transaction_deactivate_dtls.deactivate_by")
            ->where("prop_transaction_deactivate_dtls.deactive_date", '>=', $fromDate)
            ->where("prop_transaction_deactivate_dtls.deactive_date", '<=', $uptoDate)
            ->get();
    }
}
