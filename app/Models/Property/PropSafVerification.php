<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PropSafVerification extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function getVerificationsData($safId)
    {
        return DB::table('prop_saf_verifications')
            ->select('prop_saf_verifications.*', 'p.property_type', 'r.road_type', 'u.ward_name as ward_no')
            ->join('ref_prop_types as p', 'p.id', '=', 'prop_saf_verifications.prop_type_id')
            ->join('ref_prop_road_types as r', 'r.id', '=', 'prop_saf_verifications.road_type_id')
            ->join('ulb_ward_masters as u', 'u.id', '=', 'prop_saf_verifications.ward_id')
            ->where('prop_saf_verifications.saf_id', $safId)
            ->where('prop_saf_verifications.agency_verification', true)
            ->first();
    }

    // Store
    public function store($req)
    {
        $metaReqs = [
            'saf_id' => $req->safId,
            'agency_verification' => $req->agencyVerification ?? null,
            'ulb_verification' => $req->ulbVerification ?? null,
            'prop_type_id' => $req->propertyType,
            'road_type_id' => $req->roadTypeId,
            'area_of_plot' => $req->areaOfPlot,
            'ward_id' => $req->wardId,
            'has_mobile_tower' => $req->isMobileTower,
            'tower_area' => $req->mobileTowerArea,
            'tower_installation_date' => $req->mobileTowerDate,
            'has_hoarding' => $req->isHoardingBoard,
            'hoarding_area' => $req->hoardingArea,
            'hoarding_installation_date' => $req->hoardingDate,
            'is_petrol_pump' => $req->isPetrolPump,
            'underground_area' => $req->petrolPumpUndergroundArea,
            'petrol_pump_completion_date' => $req->petrolPumpDate,
            'has_water_harvesting' => $req->isHarvesting,
            'zone_id' => $req->zone,
            'user_id' => $req->userId
        ];

        return PropSafVerification::create($metaReqs)->id;
    }

    /**
     * | Deactivate Verifications
     */
    public function deactivateVerifications($safId)
    {
        $safVerifications = PropSafVerification::where('saf_id', $safId)
            ->get();

        collect($safVerifications)->map(function ($safVerification) {
            $safVerification->status = 0;
            $safVerification->save();
        });
    }
}
