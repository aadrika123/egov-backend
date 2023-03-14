<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PropProperty extends Model
{
    use HasFactory;
    protected $guarded = [];

    // Get Property Of the Citizen
    public function getUserProperties($userId)
    {
        return PropProperty::where('user_id', $userId)
            ->get();
    }

    // get Prpoperty id
    public function getPropertyId($holdingNo)
    {
        return PropProperty::where('holding_no', $holdingNo)
            ->orWhere('new_holding_no', $holdingNo)
            ->select('id')
            ->first();
    }

    // Get Property by propID
    public function getPropById($id)
    {
        return PropProperty::find($id);
    }

    // Get SAf id by Prop Id
    public function getSafByPropId($propId)
    {
        return PropProperty::select('saf_id')
            ->where('id', $propId)
            ->first();
    }

    // Get SAf Id by Holding No
    public function getSafIdByHoldingNo($holdingNo)
    {
        return PropProperty::select('saf_id', 'id')
            ->where('holding_no', $holdingNo)
            ->orWhere('new_holding_no', $holdingNo)
            ->first();
    }

    /**
     * | Get Property Details
     */
    public function getPropDtls()
    {
        return DB::table('prop_properties')
            ->select(
                'prop_properties.*',
                'prop_properties.assessment_type as assessment',
                'w.ward_name as old_ward_no',
                'nw.ward_name as new_ward_no',
                'o.ownership_type',
                'ref_prop_types.property_type',
                'r.road_type',
                'a.apartment_name',
                'a.apt_code as apartment_code'
            )
            ->join('ulb_ward_masters as w', 'w.id', '=', 'prop_properties.ward_mstr_id')
            ->leftJoin('ulb_ward_masters as nw', 'nw.id', '=', 'prop_properties.new_ward_mstr_id')
            ->leftJoin('ref_prop_ownership_types as o', 'o.id', '=', 'prop_properties.ownership_type_mstr_id')
            ->leftJoin('ref_prop_types', 'ref_prop_types.id', '=', 'prop_properties.prop_type_mstr_id')
            ->leftJoin('ref_prop_road_types as r', 'r.id', '=', 'prop_properties.road_type_mstr_id')
            ->leftJoin('prop_apartment_dtls as a', 'a.id', '=', 'prop_properties.apartment_details_id')
            ->where('prop_properties.status', 1);
    }

    /**
     * | Get Property Basic Dtls
     */
    public function getPropBasicDtls($propId)
    {
        return $this->getPropDtls()
            ->where('prop_properties.id', $propId)
            ->first();
    }

    /**
     * | Get Property Full Details
     * | Used for Calculation Parameter
     * | @param propId Property Id
     */
    public function getPropFullDtls($propId)
    {
        $mPropOwners = new PropOwner();
        $mPropFloors = new PropFloor();
        $details = array();
        $details = PropProperty::find($propId);
        $owners = $mPropOwners->getOwnersByPropId($propId);
        $details['owners'] = $owners;
        $floors = $mPropFloors->getFloorsByPropId($propId);
        $details['floors'] = $floors;
        return $details;
    }

    /**
     * | Get Property Details
     */
    public function getPropByHoldingNo($holdingNo)
    {
        return PropProperty::select(
            'prop_properties.id',
            'prop_properties.holding_no',
            'ward_name',
            'prop_address'
        )
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'prop_properties.ward_mstr_id')
            ->where('prop_properties.holding_no', 'LIKE', '%' . $holdingNo . '%')
            ->orWhere('prop_properties.new_holding_no', 'LIKE', '%' . $holdingNo . '%')
            ->get();
    }

    /**
     * | Get Proprty Details By Holding No
     */
    public function getPropByHolding($holdingNo, $ulbId)
    {
        $oldHolding = PropProperty::select(
            'prop_properties.id',
            'prop_properties.holding_no',
            'prop_properties.new_holding_no',
            'prop_properties.ward_mstr_id',
            'prop_properties.new_ward_mstr_id',
            'prop_properties.elect_consumer_no',
            'prop_properties.elect_acc_no',
            'prop_properties.elect_bind_book_no',
            'prop_properties.elect_cons_category',
            'prop_properties.prop_pin_code',
            'prop_properties.corr_pin_code',
            'prop_properties.prop_address',
            'prop_properties.corr_address',
            'prop_properties.area_of_plot as total_area_in_desimal',
            'ulb_ward_masters.ward_name as old_ward_no',
            'u.ward_name as new_ward_no',
        )
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'prop_properties.ward_mstr_id')
            ->leftJoin('ulb_ward_masters as u', 'u.id', '=', 'prop_properties.new_ward_mstr_id')
            ->where('prop_properties.holding_no', $holdingNo)
            ->where('prop_properties.ulb_id', $ulbId)
            ->first();

        if ($oldHolding) {
            return $oldHolding;
        }

        $newHolding = PropProperty::select(
            'prop_properties.id',
            'prop_properties.holding_no',
            'prop_properties.new_holding_no',
            'prop_properties.ward_mstr_id',
            'prop_properties.new_ward_mstr_id',
            'prop_properties.elect_consumer_no',
            'prop_properties.elect_acc_no',
            'prop_properties.elect_bind_book_no',
            'prop_properties.elect_cons_category',
            'prop_properties.prop_pin_code',
            'prop_properties.corr_pin_code',
            'prop_properties.prop_address',
            'prop_properties.corr_address',
            'prop_properties.area_of_plot as total_area_in_desimal',
            'ulb_ward_masters.ward_name as old_ward_no',
            'u.ward_name as new_ward_no',
        )
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'prop_properties.ward_mstr_id')
            ->leftJoin('ulb_ward_masters as u', 'u.id', '=', 'prop_properties.new_ward_mstr_id')
            ->where('prop_properties.new_holding_no', $holdingNo)
            ->where('prop_properties.ulb_id', $ulbId)
            ->first();
        return $newHolding;
    }

    /**
     * | get property details by userId and ulbId
     */
    public function getpropByUserUlb($request)
    {
        return PropProperty::select(
            'new_holding_no',
            'holding_no'
        )
            ->where('user_id', auth()->user()->id)
            ->where('ulb_id', $request->ulbId)
            ->get();
    }

    /**
     * | Search holding
     */
    public function searchHolding($holdingNo)
    {
        return PropProperty::leftjoin('prop_owners', 'prop_owners.saf_id', '=', 'prop_properties.saf_id')
            ->join('ref_prop_types', 'ref_prop_types.id', '=', 'prop_properties.prop_type_mstr_id')
            ->select(
                'prop_properties.new_ward_mstr_id AS wardId',
                'prop_properties.prop_address AS address',
                'ref_prop_types.property_type AS propertyType',
                'prop_properties.new_holding_no as holding_no',
                DB::raw("string_agg(prop_owners.owner_name,',') as ownerName"),
                DB::raw("string_agg(prop_owners.mobile_no::VARCHAR,',') as mobileNo"),
                'prop_properties.holding_no as holdingNo'
            )
            ->where('prop_properties.holding_no', 'LIKE', '%' . $holdingNo)
            ->orWhere('prop_properties.new_holding_no', 'LIKE', '%' . $holdingNo)
            ->where('prop_properties.status', 1)
            ->where('ulb_id', auth()->user()->ulb_id)
            ->groupBy('prop_properties.id', 'ref_prop_types.property_type')
            ->get();
    }

    /**
     * | Search prop Details by Cluster Id
     */
    public function searchPropByCluster($clusterId)
    {
        return  PropProperty::leftjoin('prop_owners', 'prop_owners.property_id', '=', 'prop_properties.id')
            ->join('ref_prop_types', 'ref_prop_types.id', '=', 'prop_properties.prop_type_mstr_id')
            ->select(
                'prop_properties.id',
                'prop_properties.new_ward_mstr_id AS wardId',
                DB::raw("string_agg(prop_owners.owner_name,',') as ownerName"),
                DB::raw("string_agg(prop_owners.mobile_no::VARCHAR,',') as mobileNo"),
                'prop_properties.prop_address AS address',
                'ref_prop_types.property_type AS propertyType',
                'prop_properties.cluster_id',
                'prop_properties.holding_no as holdingNo'
            )
            ->where('prop_properties.cluster_id', $clusterId)
            ->where('prop_properties.status', 1)
            ->where('ref_prop_types.status', 1)
            ->groupBy('prop_properties.id', 'ref_prop_types.property_type')
            ->get();
    }

    /**
     * | Collective holding search
     */
    public function searchCollectiveHolding($holdingArray)
    {
        return PropProperty::whereIn('new_holding_no', $holdingArray)
            ->where('status', 1)
            ->get();
    }

    /**
     * | Get Property id by saf id
     * | Function Used On Approval Rejection Saf
     */
    public function getPropIdBySafId($safId)
    {
        return PropProperty::select('id')
            ->where('saf_id', $safId)
            ->firstOrFail();
    }

    /**
     * | Replicate Saf 
     */
    public function replicateVerifiedSaf($propId, $fieldVerifiedSaf)
    {
        $property = PropProperty::find($propId);
        $reqs = [
            'prop_type_mstr_id' => $fieldVerifiedSaf->prop_type_id,
            'road_type_mstr_id' => $fieldVerifiedSaf->road_type_id,
            'area_of_plot' => $fieldVerifiedSaf->area_of_plot,
            'ward_mstr_id' => $fieldVerifiedSaf->ward_id,
            'is_mobile_tower' => $fieldVerifiedSaf->has_mobile_tower,
            'tower_area' => $fieldVerifiedSaf->tower_area,
            'tower_installation_date' => $fieldVerifiedSaf->tower_installation_date,
            'is_hoarding_board' => $fieldVerifiedSaf->has_hoarding,
            'hoarding_area' => $fieldVerifiedSaf->hoarding_area,
            'hoarding_installation_date' => $fieldVerifiedSaf->hoarding_installation_date,
            'is_petrol_pump' => $fieldVerifiedSaf->is_petrol_pump,
            'under_ground_area' => $fieldVerifiedSaf->underground_area,
            'petrol_pump_completion_date' => $fieldVerifiedSaf->petrol_pump_completion_date,
            'is_water_harvesting' => $fieldVerifiedSaf->has_water_harvesting,
            'zone_mstr_id' => $fieldVerifiedSaf->zone_id,
            'new_ward_mstr_id' => $fieldVerifiedSaf->new_ward_id,
            'ulb_id' => $fieldVerifiedSaf->ulb_id
        ];
        $property->update($reqs);
    }


    /**
     * | Request made to save or replicate property
     */
    public function reqProp($req)
    {
        $reqs = [
            'ulb_id' => $req->ulb_id,
            'cluster_id' => $req->cluster_id,
            'saf_id' => $req->saf_id,
            'holding_no' => $req->holding_no,
            'applicant_name' => $req->applicant_name,
            'application_date' => $req->application_date,
            'ward_mstr_id' => $req->ward_mstr_id,
            'ownership_type_mstr_id' => $req->ownership_type_mstr_id,
            'prop_type_mstr_id' => $req->prop_type_mstr_id,
            'appartment_name' => $req->appartment_name,
            'no_electric_connection' => $req->no_electric_connection,
            'elect_consumer_no' => $req->elect_consumer_no,
            'elect_acc_no' => $req->elect_acc_no,
            'elect_bind_book_no' => $req->elect_bind_book_no,
            'elect_cons_category' => $req->elect_cons_category,
            'building_plan_approval_no' => $req->building_plan_approval_no,
            'building_plan_approval_date' => $req->building_plan_approval_date,
            'water_conn_no' => $req->water_conn_no,
            'water_conn_date' => $req->water_conn_date,
            'khata_no' => $req->khata_no,
            'plot_no' => $req->plot_no,
            'village_mauja_name' => $req->village_mauja_name,
            'road_type_mstr_id' => $req->road_type_mstr_id,
            'area_of_plot' => $req->area_of_plot,
            'prop_address' => $req->prop_address,
            'prop_city' => $req->prop_city,
            'prop_dist' => $req->prop_dist,
            'prop_pin_code' => $req->prop_pin_code,
            'prop_state' => $req->prop_state,
            'corr_address' => $req->corr_address,
            'corr_city' => $req->corr_city,
            'corr_dist' => $req->corr_dist,
            'corr_pin_code' => $req->corr_pin_code,
            'corr_state' => $req->corr_state,
            'is_mobile_tower' => $req->is_mobile_tower,
            'tower_area' => $req->tower_area,
            'tower_installation_date' => $req->tower_installation_date,
            'is_hoarding_board' => $req->is_hoarding_board,
            'hoarding_area' => $req->hoarding_area,
            'hoarding_installation_date' => $req->hoarding_installation_date,
            'is_petrol_pump' => $req->is_petrol_pump,
            'under_ground_area' => $req->under_ground_area,
            'petrol_pump_completion_date' => $req->petrol_pump_completion_date,
            'is_water_harvesting' => $req->is_water_harvesting,
            'land_occupation_date' => $req->land_occupation_date,
            'new_ward_mstr_id' => $req->new_ward_mstr_id,
            'entry_type' => $req->entry_type,
            'zone_mstr_id' => $req->zone_mstr_id,
            'new_holding_no' => $req->new_holding_no,
            'flat_registry_date' => $req->flat_registry_date,
            'assessment_type' => $req->assessment_type,
            'holding_type' => $req->holding_type,
            'is_old' => $req->is_old,
            'apartment_details_id' => $req->apartment_details_id,
            'ip_address' => $req->ip_address,
            'user_id' => $req->user_id,
            'road_width' => $req->road_width,
            'old_prop_id' => $req->old_prop_id,
            'citizen_id' => $req->citizen_id,
            'saf_no' => $req->saf_no,
            'pt_no' => $req->pt_no
        ];
        return $reqs;
    }

    /**
     * | Edit Property By Saf
     * | Used To Edit Prop Dtls While Reassessment and Mutation Case
     * | Functions Used replicateSaf()
     */
    public function editPropBySaf($propId, $safDtls)
    {
        $property = PropProperty::find($propId);
        $reqs = $this->reqProp($safDtls);
        $property->update($reqs);
    }

    /**
     * | verify holding No
     */
    public function verifyHolding($req)
    {
        return PropProperty::select('*')
            ->where('holding_no', $req->holdingNo)
            ->ORwhere('new_holding_no', $req->holdingNo)
            ->where('ulb_id', $req->ulbId)
            ->first();
    }

    /**
     * | Get Comparative Demand Details
     */
    public function getComparativeBasicDtls($propId)
    {
        return DB::table('prop_properties as p')
            ->select(
                'p.holding_no',
                'p.new_holding_no',
                'p.prop_address',
                'p.road_width',
                'p.ulb_id',
                'p.prop_type_mstr_id',
                'p.is_mobile_tower',
                'p.tower_area',
                'p.tower_installation_date',
                'p.is_hoarding_board',
                'p.hoarding_area',
                'p.hoarding_installation_date',
                'p.is_petrol_pump',
                'p.under_ground_area',
                'p.petrol_pump_completion_date',
                'w.ward_name as old_ward_no',
                'nw.ward_name as new_ward_no',
                DB::raw("(SELECT owner_name FROM prop_owners WHERE property_id=$propId order by id LIMIT 1)"),
                DB::raw("(SELECT guardian_name FROM prop_owners WHERE property_id=$propId order by id LIMIT 1)"),
                'f.id as floor_id',
                'f.builtup_area',
                'f.floor_mstr_id',
                'f.usage_type_mstr_id',
                'f.const_type_mstr_id',
                'f.occupancy_type_mstr_id',
                'f.date_from',
                'f.date_upto',
                'f.carpet_area',
            )
            ->join('ulb_ward_masters as w', 'w.id', '=', 'p.ward_mstr_id')
            ->leftJoin('ulb_ward_masters as nw', 'nw.id', '=', 'p.new_ward_mstr_id')
            ->leftJoin('prop_floors as f', 'f.property_id', '=', 'p.id')
            ->where('p.id', $propId)
            ->where('p.status', 1)
            ->get();
    }

    /**
     * | Get citizen holdings
     */
    public function getCitizenHoldings($citizenId, $ulbId)
    {
        return PropProperty::select('id', 'holding_no', 'new_holding_no', 'pt_no')
            ->where('ulb_id', $ulbId)
            ->where('citizen_id', $citizenId)
            ->get();
    }
}
