<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class PropActiveSafsOwner extends Model
{
    use HasFactory;
    protected $fillable = [
        'owner_name',
        'guardian_name',
        'relation_type',
        'mobile_no',
        'aadhar_no',
        'pan_no',
        'email'
    ];

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
}
