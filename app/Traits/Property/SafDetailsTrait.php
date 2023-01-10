<?php

namespace App\Traits\Property;

use Illuminate\Database\Eloquent\Collection;

/**
 * | Trait Created for Gettting Dynamic Saf Details
 */
trait SafDetailsTrait
{
    /**
     * | Get Basic Details
     */
    public function generateBasicDetails($data)
    {
        return new Collection([
            ['keyString' => 'Ward No', 'value' => $data->old_ward_no],
            ['keyString' => 'New Ward No', 'value' => $data->new_ward_no],
            ['keyString' => 'Ownership Type', 'value' => $data->ownership_type],
            ['keyString' => 'Property Type', 'value' => $data->property_type],
            ['keyString' => 'Zone', 'value' => ($data->zone_mstr_id == 1) ? 'Zone 1' : 'Zone 2'],
            ['keyString' => 'Property has Mobile Tower(s) ?', 'value' => ($data->is_mobile_tower == false) ? 'No' : 'Yes'],
            ['keyString' => 'Property has Hoarding Board(s) ?', 'value' => ($data->is_hoarding_board == false) ? 'No' : 'Yes']
        ]);
    }

    /**
     * | Generating Property Details
     */
    public function generatePropertyDetails($data)
    {
        return new Collection([
            ['keyString' => 'Khata No.', 'value' => $data->khata_no],
            ['keyString' => 'Plot No.', 'value' => $data->plot_no],
            ['keyString' => 'Village/Mauja Name', 'value' => $data->village_mauja_name],
            ['keyString' => 'Area of Plot', 'value' => $data->area_of_plot],
            ['keyString' => 'Road Width', 'value' => $data->road_width],
            ['keyString' => 'City', 'value' => $data->prop_city],
            ['keyString' => 'District', 'value' => $data->prop_dist],
            ['keyString' => 'State', 'value' => $data->prop_state],
            ['keyString' => 'Pin', 'value' => $data->prop_pin_code],
            ['keyString' => 'Locality', 'value' => $data->prop_address],
        ]);
    }

    /**
     * | Generate Corresponding Details
     */
    public function generateCorrDtls($data)
    {
        return new Collection([
            ['keyString' => 'City', 'value' => $data->corr_city],
            ['keyString' => 'District', 'value' => $data->corr_dist],
            ['keyString' => 'State', 'value' => $data->corr_state],
            ['keyString' => 'Pin', 'value' => $data->corr_pin_code],
            ['keyString' => 'Locality', 'value' => $data->corr_address],
        ]);
    }

    /**
     * | Generate Electricity Details
     */
    public function generateElectDtls($data)
    {
        return new Collection([
            ['keyString' => 'Electricity K. No', 'value' => $data->elect_consumer_no],
            ['keyString' => 'ACC No.', 'value' => $data->elect_acc_no],
            ['keyString' => 'BIND/BOOK No.', 'value' => $data->elect_bind_book_no],
            ['keyString' => 'Electricity Consumer Category', 'value' => $data->elect_cons_category],
            ['keyString' => 'Building Plan Approval No.', 'value' => $data->building_plan_approval_no],
            ['keyString' => 'Building Plan Approval Date', 'value' => $data->building_plan_approval_date],
            ['keyString' => 'Water Consumer No.', 'value' => $data->water_conn_no],
            ['keyString' => 'Water Connection Date', 'value' => $data->water_conn_date]
        ]);
    }

    /**
     * | Generate Owner Details
     */
    public function generateOwnerDetails($ownerDetails)
    {
        return collect($ownerDetails)->map(function ($ownerDetail, $key) {
            return [
                $key + 1,
                $ownerDetail['owner_name'],
                $ownerDetail['gender'],
                $ownerDetail['dob'],
                $ownerDetail['guardian_name'],
                $ownerDetail['relation_type'],
                $ownerDetail['mobile_no'],
                $ownerDetail['aadhar_no'],
                $ownerDetail['pan_no'],
                $ownerDetail['email'],
                ($ownerDetail['is_armed_force'] == true ? 'Yes' : 'No'),
                ($ownerDetail['is_specially_abled'] == true ? 'Yes' : 'No'),
            ];
        });
    }

    /**
     * | Generate Floor Details
     */
    public function generateFloorDetails($floorDetails)
    {
        return collect($floorDetails)->map(function ($floorDetail, $key) {
            return [
                $key + 1,
                $floorDetail->floor_name,
                $floorDetail->usage_type,
                $floorDetail->occupancy_type,
                $floorDetail->construction_type,
                $floorDetail->builtup_area,
                $floorDetail->date_from,
                $floorDetail->date_upto
            ];
        });
    }

    /**
     * | Generate Card Details
     */
    public function generateCardDetails($req)
    {
        return new Collection([
            ['keyString' => 'Ward No', 'value' => $req->old_ward_no],
            ['keyString' => 'SAF No.', 'value' => $req->saf_no],
            ['keyString' => 'Owner Name', 'value' => "demo,demo"],
            ['keyString' => 'Property Type', 'value' => $req->property_type],
            ['keyString' => 'Assessment Type', 'value' => $req->assessment_type],
            ['keyString' => 'Ownership Type', 'value' => $req->ownership_type],
            ['keyString' => 'Apply-Date', 'value' => $req->application_date],
            ['keyString' => 'Plot-Area(sqt)', 'value' => $req->area_of_plot],
            ['keyString' => 'Is-Water-Harvesting', 'value' => ($req->is_water_harvesting == true) ? 'Yes' : 'No'],
            ['keyString' => 'Is-Hoarding-Board', 'value' => ($req->is_hoarding_board == true) ? 'Yes' : 'No']
        ]);
    }
}
