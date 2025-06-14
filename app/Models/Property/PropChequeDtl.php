<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropChequeDtl extends Model
{
    use HasFactory;
    protected $guarded = [];

    /**
     * | Post Cheque Details
       | Common Function
     */
    public function postChequeDtl($req)
    {
        $mPropChequeDtl = new PropChequeDtl();
        $mPropChequeDtl->create($req);
    }

    /**
     * | Get Cheque Details by ID
       | Reference Function : chequeDtlById
     */
    public function chequeDtlById($request)
    {
        return PropChequeDtl::select('*')
            ->where('id', $request->chequeId)
            ->where('status', 2)
            ->first();
    }
}
