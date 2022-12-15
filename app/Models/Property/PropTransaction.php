<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PropTransaction extends Model
{
    use HasFactory;

    public function getPropTransactions($id, $key)
    {
        return PropTransaction::where("$key", $id)
            ->get();
    }

    // Get Property Transaction by SAF Id
    public function getPropTransbySafId($safId)
    {
        return DB::table('prop_transactions')
            ->select('prop_transactions.*', 'a.saf_no', 'p.holding_no')
            ->leftJoin('prop_active_safs as a', 'a.id', '=', 'prop_transactions.saf_id')
            ->leftJoin('prop_properties as p', 'p.id', '=', 'prop_transactions.property_id')
            ->where('prop_transactions.status', 1)
            ->where('prop_transactions.saf_id', $safId)
            ->orderByDesc('prop_transactions.id')
            ->get();
    }
}
