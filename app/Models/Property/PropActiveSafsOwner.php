<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $owner = PropActiveSafsOwner::find($req->safOwnerId);

        $reqs = [
            'owner_name' => strtoupper($req->ownerName),
            'guardian_name' => strtoupper($req->guardianName),
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
        return PropActiveSafsOwner::select(
            'prop_active_safs_owners.*'
        )
            ->where('saf_id', $safId)
            ->where('status', 1)
            ->get();
    }

    /**
     * | Get Owner Dtls by Saf Id
     */
    public function getOwnerDtlsBySafId($safId)
    {
        return PropActiveSafsOwner::where('saf_id', $safId)
            ->select(
                'owner_name as ownerName',
                'mobile_no as mobileNo',
                'guardian_name as guardianName',
                'email',
                'gender',
                'is_armed_force',
                'is_specially_abled'
            )
            ->orderBy('id')
            ->get();
    }

    /***
     * | Get First Saf Owner By SafId
     */
    public function getOwnerDtlsBySafId1($safId)
    {
        return PropActiveSafsOwner::where('saf_id', $safId)
            ->select(
                'owner_name',
                'mobile_no',
                'dob',
                'guardian_name',
                'email',
                'is_armed_force',
                'is_specially_abled'
            )
            ->orderBy('id')
            ->first();
    }

    /**
     * | Get First Owner By Saf Id
     */
    public function getFirstOwnerBySafId($safId)
    {
        return PropActiveSafsOwner::where('saf_id', $safId)
            ->first();
    }

    /**
     * | Citizen Owner Edit
     */
    public function ownerEdit($req, $citizenId)
    {
        $req = new Request($req);
        $owner = PropActiveSafsOwner::find($req->ownerId);

        $reqs = [
            'owner_name' => strtoupper($req->ownerName),
            'guardian_name' => strtoupper($req->guardianName),
            'relation_type' => $req->relation,
            'mobile_no' => $req->mobileNo,
            'aadhar_no' => $req->aadhar,
            'pan_no' => $req->pan,
            'email' => $req->email,
            'gender' => $req->gender,
            'dob' => $req->dob,
            'is_armed_force' => $req->isArmedForce,
            'is_specially_abled' => $req->isSpeciallyAbled,
            'user_id' => $citizenId,
        ];

        $owner->update($reqs);
    }

    public function addOwner($req, $safId, $citizenId)
    {

        $owner = new  PropActiveSafsOwner();
        $owner->saf_id = $safId;
        $owner->owner_name = strtoupper($req['ownerName']);
        $owner->guardian_name = strtoupper($req['guardianName'])  ?? null;
        $owner->relation_type = $req['relation'] ?? null;
        $owner->mobile_no = $req['mobileNo'] ?? null;
        $owner->aadhar_no = $req['aadhar'] ??  null;
        $owner->pan_no = $req['pan'] ?? null;
        $owner->email = $req['email'] ?? null;
        $owner->gender = $req['gender'] ?? null;
        $owner->dob = $req['dob'] ?? null;
        $owner->is_armed_force = $req['isArmedForce'] ?? null;
        $owner->is_specially_abled = $req['isSpeciallyAbled'] ?? null;
        $owner->user_id = $citizenId;
        $owner->prop_owner_id = $req['propOwnerDetailId'] ?? null;
        $owner->save();
    }

    /**
     * | Get Owner Dtls by Saf Id
     */
    public function getOwnerDtlsBySafIds($safIds)
    {
        return PropActiveSafsOwner::whereIn('saf_id', $safIds)
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
