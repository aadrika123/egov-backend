<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropTranDtl extends Model
{
    use HasFactory;
    protected $guarded = [];

    // Store Prop Tran Dtls
    public function store($req)
    {
        $metaReq = [
            'tran_id' => $req->tranId,
            'saf_demand_id' => $req->safDemandId,
            'total_demand' => $req->totalDemand
        ];
        PropTranDtl::create($metaReq);
    }
}
