<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropApartmentDtl extends Model
{
    use HasFactory;

    /**
     * |
     */
    public function apartmentList($req)
    {
        return PropApartmentDtl::select('id', 'apt_code', 'apartment_name')
            ->where('ward_mstr_id', $req->wardMstrId)
            ->where('ulb_id', $req->ulbId)
            ->get();
    }
}
