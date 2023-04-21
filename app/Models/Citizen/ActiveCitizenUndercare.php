<?php

namespace App\Models\Citizen;

use App\Models\Property\PropProperty;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActiveCitizenUndercare extends Model
{
    use HasFactory;
    protected $guarded = [];

    /**
     * | Store
     */
    public function store(array $req)
    {
        ActiveCitizenUndercare::create($req);
    }

    /**
     * | 
     */
    public function getDetailsForUnderCare($userId, $consumerId)
    {
        return ActiveCitizenUndercare::where('citizen_id', $userId)
            ->where('consumer_id', $consumerId)
            ->where('deactive_status', false)
            ->first();
    }

    /**
     * | Save caretaker Details 
     */
    public function saveCaretakeDetails($applicationId, $mobileNo, $userId)
    {
        $mActiveCitizenUndercare = new ActiveCitizenUndercare();
        $mActiveCitizenUndercare->consumer_id           = $applicationId;
        $mActiveCitizenUndercare->date_of_attachment    = Carbon::now();
        $mActiveCitizenUndercare->mobile_no             = $mobileNo;
        $mActiveCitizenUndercare->citizen_id            = $userId;
        $mActiveCitizenUndercare->save();
    }

    /**
     * | Get Details according to user Id
     * | @param 
     */
    public function getDetailsByCitizenId()
    {
        $user = authUser();
        return ActiveCitizenUndercare::where('citizen_id', $user->id)
            ->where('deactive_status', false)
            ->get();
    }

    /**
     * | Get Property By Citizen Id
     */
    public function getTaggedProperties($propId)
    {
        return ActiveCitizenUndercare::where('property_id', $propId)
            ->get();
    }

    /**
     * | Get Trade By Trade Id
     */
    public function getTaggedTrades($licenseNo)
    {
        return ActiveCitizenUndercare::where('license_id', $licenseNo)
            ->get();
    }

    /**
     * | Get Tagged Property by Citizen Id
     */
    public function getTaggedPropsByCitizenId($citizenId)
    {
        return ActiveCitizenUndercare::where('citizen_id', $citizenId)
            ->get();
    }
}
