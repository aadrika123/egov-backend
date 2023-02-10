<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterConnectionCharge extends Model
{
    use HasFactory;


    /**
     * |---------------------------------- Save water connection charges ---------------------------------------------|
     * | @param 
     * | @var
        | 
     */
    public function saveWaterCharge($applicationId, $req, $newConnectionCharges)
    {
        $saveCharges = new WaterConnectionCharge();
        $saveCharges->application_id = $applicationId;
        $saveCharges->charge_category = $req->connectionTypeId;
        $saveCharges->paid_status = 0;
        $saveCharges->status = 1;
        $saveCharges->penalty = $newConnectionCharges['conn_fee_charge']['penalty'];
        $saveCharges->conn_fee = $newConnectionCharges['conn_fee_charge']['conn_fee'];
        $saveCharges->amount = $newConnectionCharges['conn_fee_charge']['amount'];
        $saveCharges->rule_set = $newConnectionCharges['ruleSete'];
        $saveCharges->save();
    }

    /**
     * |----------------------------------- Get Water Charges By ApplicationId ------------------------------|
     * | @param request
     */
    public function getWaterchargesById($applicationId)
    {
        return WaterConnectionCharge::select(
            'id',
            'amount',
            'charge_category',
            'penalty',
            'conn_fee',
            'rule_set'
        )
            ->where('application_id', $applicationId)
            ->where('water_connection_charges.status', 1);
    }

    /**
     * |-------------- Delete the Water Application Connection Charges -------------|
        | Recheck
     */
    public function deleteWaterConnectionCharges($applicationId)
    {
        WaterConnectionCharge::where('application_id', $applicationId)
            ->delete();
    }

    /**
     * | Deactivate the application In the process of editing
     */
    public function deactivateCharges($applicationId)
    {
        WaterConnectionCharge::where('application_id', $applicationId)
            ->update([
                'status' => false
            ]);
    }
}
