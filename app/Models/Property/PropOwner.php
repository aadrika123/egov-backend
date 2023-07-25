<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PropOwner extends Model
{
    use HasFactory;
    protected $guarded = [];

    //owner details by ownerId
    public function getOwnerDetail($request)
    {
        return PropOwner::select(
            'prop_owners.id',
            'prop_owners.owner_name',
            'prop_owners.mobile_no',
            'corr_address',
            'corr_city',
            'corr_dist',
            'corr_pin_code',
            'corr_state'
        )
            ->join('prop_properties', 'prop_properties.id', '=', 'prop_owners.property_id')
            ->where('prop_owners.id', $request->ownerId)
            ->first();
    }

    // Get Owners by Property Id
    public function getOwnersByPropId($propertyId)
    {
        return DB::table('prop_owners')
            ->where('property_id', $propertyId)
            ->where('status', 1)
            ->orderBy('id')
            ->get();
    }

    /**
     * | Get The Owner by Property Id
     */
    public function getOwnerByPropId($propId)
    {
        return PropOwner::where('property_id', $propId)
            ->select(
                'id',
                'owner_name as ownerName',
                'mobile_no as mobileNo',
                'guardian_name as guardianName',
                'email',
                'gender',
                'is_armed_force',
                'is_specially_abled'
            )
            ->where('status', 1)
            ->orderBy('id')
            ->get();
    }

    /**
     * | Get The first Owner by Property Id
     */
    public function getOwnerByPropIds($propIds)
    {
        return PropOwner::whereIn('property_id', $propIds)
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


    /**
     * | 1st owner by property Id
     */
    public function getfirstOwner($propertyId)
    {
        return PropOwner::select(
            'owner_name as applicant_name',
            'mobile_no'
        )
            ->where('property_id', $propertyId)
            ->orderBy('id')
            ->first();
    }

    /**
     * | Get Owner Details by Property Owner Id
     */
    public function getOwnerByPropOwnerId($propOwnerId)
    {
        return PropOwner::where('id', $propOwnerId)
            ->where('status', 1)
            ->get();
    }

    /**
     * | Get Owner by Owner Id
     * | function used in replicate saf function
     */
    public function getPropOwnerByOwnerId($ownerId)
    {
        return PropOwner::find($ownerId);
    }


    /**
     * | Request for Post Owner Details or Edit
     */
    public function reqOwner($req)
    {
        return [
            'property_id' => $req->property_id,
            'saf_id' => $req->saf_id,
            'owner_name' => $req->owner_name,
            'guardian_name' => $req->guardian_name,
            'relation_type' => $req->relation_type,
            'mobile_no' => $req->mobile_no,
            'email' => $req->email,
            'pan_no' => $req->pan_no,
            'aadhar_no' => $req->aadhar_no,
            'gender' => $req->gender,
            'dob' => $req->dob,
            'is_armed_force' => $req->is_armed_force,
            'is_specially_abled' => $req->is_specially_abled,
            'user_id' => $req->user_id,
            'saf_owner_id' => $req->id
        ];
    }

    /**
     * | Edit Owner
     */
    public function editOwner($safOwner)
    {
        $owner = PropOwner::find($safOwner->id);
        $req = $this->reqOwner($safOwner);
        $owner->update($req);
    }

    /**
     * | Post New Owner
     */
    public function postOwner($safOwner)
    {
        $owner = new PropOwner();
        $req = $this->reqOwner($safOwner);
        $owner->create($req);
    }

    /**
     * | Edit Prop Owner
     */
    public function editPropOwner($safOwner)
    {
        $owner = PropOwner::find($safOwner->id);
        $req = [
            'owner_name' => $safOwner->owner_name,
            'guardian_name' => $safOwner->guardian_name,
            'relation_type' => $safOwner->relation_type,
            'mobile_no' => $safOwner->mobile_no,
            'email' => $safOwner->email,
            'pan_no' => $safOwner->pan_no,
            'aadhar_no' => $safOwner->aadhar_no
        ];
        $owner->update($req);
    }

    /**
     * | 1st owner by property Id
     */
    public function firstOwner($propertyId)
    {
        return PropOwner::select(
            'owner_name',
            'mobile_no',
        )
            ->where('property_id', $propertyId)
            ->orderBy('id')
            ->first();
    }
}
