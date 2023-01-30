<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class PropActiveSafsOwner extends Model
{
    use HasFactory;
    protected $guarded = [];

    /**
     * | Update Owner Basic Details
     */
    public function edit($req)
    {
        $req = new Request($req);
        $owner = PropActiveSafsOwner::find($req->ownerId);

        $reqs = [
            'owner_name' => $req->ownerName,
            'guardian_name' => $req->guardianName,
            'relation_type' => $req->relation,
            'mobile_no' => $req->mobileNo,
            'aadhar_no' => $req->aadhar,
            'pan_no' => $req->pan,
            'email' => $req->email,
        ];

        $owner->update($reqs);
    }

    /**
     * | Get Owners by SAF Id
     */
    public function getOwnersBySafId($safId)
    {
        return PropActiveSafsOwner::where('saf_id', $safId)
            ->get();
    }

    /**
     * | Get Owner Dtls by Saf Id
     */
    public function getOwnerDtlsBySafId($safId)
    {
        return PropActiveSafsOwner::where('saf_id', $safId)
            ->select(
                'owner_name',
                'mobile_no',
                'guardian_name',
                'email',
                'is_armed_force',
                'is_specially_abled'
            )
            ->orderBy('id')
            ->get();
    }
}
