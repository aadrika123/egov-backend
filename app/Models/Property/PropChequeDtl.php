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
     */
    public function postChequeDtl($req)
    {
        $mPropChequeDtl = new PropChequeDtl();
        $mPropChequeDtl->create($req);
    }
}
