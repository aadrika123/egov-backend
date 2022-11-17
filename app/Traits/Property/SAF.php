<?php

namespace App\Traits\Property;

use Illuminate\Support\Facades\DB;

/**
 * | Created On-17-10-2022 
 * | Created By - Anshu Kumar
 * | Created for - Code Reausable for SAF Repository
 */

trait SAF
{
    // SAF Inbox 
    public function getSaf()
    {
        $data = DB::table('active_safs')
            ->join('active_safs_owner_dtls as o', 'o.saf_id', '=', 'active_safs.id')
            ->join('prop_m_property_types as p', 'p.id', '=', 'active_safs.prop_type_mstr_id')
            ->join('ulb_ward_masters as ward', 'ward.id', '=', 'active_safs.ward_mstr_id')
            ->select(
                'active_safs.saf_no',
                'active_safs.id',
                'active_safs.ward_mstr_id',
                'ward.ward_name as ward_no',
                'active_safs.prop_type_mstr_id',
                'active_safs.appartment_name',
                DB::raw("string_agg(o.id::VARCHAR,',') as owner_id"),
                DB::raw("string_agg(o.owner_name,',') as owner_name"),
                'p.property_type',
                'active_safs.assessment_type'
            );
        return $data;
    }

    /**
     * | Get SAF Request Details for SAF Calculation by SAF ID
     */

    public function generateSafRequest($req)
    {
        $array = array();

        $array['ward'] = $req['ward_mstr_id'];
        $array['propertyType'] = $req['property_type'];
        $array['dateOfPurchase'] = $req['ward_mstr_id'];
        $array['ownershipType'] = $req['ownership_type_mstr_id'];
        $array['roadType'] = $req['road_type_mstr_id'];
        $array['areaOfPlot'] = $req['area_of_plot'];
        $array['isMobileTower'] = $req['is_mobile_tower'];
        $array['mobileTower']['area'] = $req['tower_area'];
        $array['mobileTower']['dateFrom'] = $req['tower_installation_date'];
        $array['isHoardingBoard'] = $req['is_hoarding_board'];
        $array['hoardingBoard']['area'] = $req['hoarding_area'];
        $array['hoardingBoard']['dateFrom'] = $req['hoarding_installation_date'];
        $array['isPetrolPump'] = $req['is_petrol_pump'];
        $array['petrolPump']['area'] = $req['under_ground_area'];
        $array['petrolPump']['dateFrom'] = $req['petrol_pump_completion_date'];
        $array['isWaterHarvesting'] = $req['is_water_harvesting'];
        $array['zone'] = $req['zone_mstr_id'];
        $refFloors = $req['floors'];

        foreach ($refFloors as $key => $refFloor) {
            $array['floor'][$key]['floorNo'] = $refFloor['floor_mstr_id'];
            $array['floor'][$key]['useType'] = $refFloor['usage_type_mstr_id'];
            $array['floor'][$key]['constructionType'] = $refFloor['const_type_mstr_id'];
            $array['floor'][$key]['occupancyType'] = $refFloor['occupancy_type_mstr_id'];
            $array['floor'][$key]['buildupArea'] = $refFloor['builtup_area'];
            $array['floor'][$key]['dateFrom'] = $refFloor['date_from'];
            $array['floor'][$key]['dateUpto'] = $refFloor['date_upto'];
        }

        $refFloors = $req['owners'];

        foreach ($refFloors as $key => $refFloor) {
            $array['owner'][$key]['ownerName'] = $refFloor['owner_name'];
            $array['owner'][$key]['gender'] = $refFloor['gender'];
            $array['owner'][$key]['guardianName'] = $refFloor['guardian_name'];
            $array['owner'][$key]['relation'] = $refFloor['relation_type'];
            $array['owner'][$key]['mobileNo'] = $refFloor['mobile_no'];
            $array['owner'][$key]['email'] = $refFloor['email'];
            $array['owner'][$key]['aadhar'] = $refFloor['aadhar_no'];
            $array['owner'][$key]['isArmedForce'] = $refFloor['is_armed_force'];
            $array['owner'][$key]['isSpeciallyAbled'] = $refFloor['is_specially_abled'];
        }

        return $array;
    }
}
