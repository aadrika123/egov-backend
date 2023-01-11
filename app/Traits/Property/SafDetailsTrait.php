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
            ['displayString' => 'Ward No', 'key' => 'wardNo', 'value' => $data->old_ward_no],
            ['displayString' => 'New Ward No', 'key' => 'newWardNo', 'value' => $data->new_ward_no],
            ['displayString' => 'Ownership Type', 'key' => 'ownershipType', 'value' => $data->ownership_type],
            ['displayString' => 'Property Type', 'key' => 'propertyType', 'value' => $data->property_type],
            ['displayString' => 'Zone', 'key' => 'zone', 'value' => ($data->zone_mstr_id == 1) ? 'Zone 1' : 'Zone 2'],
            ['displayString' => 'Property has Mobile Tower(s) ?', 'key' => 'isMobileTower', 'value' => ($data->is_mobile_tower == false) ? 'No' : 'Yes'],
            ['displayString' => 'Property has Hoarding Board(s) ?', 'key' => 'isHoardingBoard', 'value' => ($data->is_hoarding_board == false) ? 'No' : 'Yes']
        ]);
    }

    /**
     * | Generating Property Details
     */
    public function generatePropertyDetails($data)
    {
        return new Collection([
            ['displayString' => 'Khata No.', 'key' => 'khataNo', 'value' => $data->khata_no],
            ['displayString' => 'Plot No.', 'key' => 'plotNo', 'value' => $data->plot_no],
            ['displayString' => 'Village/Mauja Name', 'key' => 'villageMaujaName', 'value' => $data->village_mauja_name],
            ['displayString' => 'Area of Plot', 'key' => 'areaOfPlot', 'value' => $data->area_of_plot],
            ['displayString' => 'Road Width', 'key' => 'roadWidth', 'value' => $data->road_width],
            ['displayString' => 'City', 'key' => 'city', 'value' => $data->prop_city],
            ['displayString' => 'District', 'key' => 'district', 'value' => $data->prop_dist],
            ['displayString' => 'State', 'key' => 'state', 'value' => $data->prop_state],
            ['displayString' => 'Pin', 'key' => 'pin', 'value' => $data->prop_pin_code],
            ['displayString' => 'Locality', 'key' => 'locality', 'value' => $data->prop_address],
        ]);
    }

    /**
     * | Generate Corresponding Details
     */
    public function generateCorrDtls($data)
    {
        return new Collection([
            ['displayString' => 'City', 'key' => 'corrCity', 'value' => $data->corr_city],
            ['displayString' => 'District', 'key' => 'corrDistrict', 'value' => $data->corr_dist],
            ['displayString' => 'State', 'key' => 'corrState', 'value' => $data->corr_state],
            ['displayString' => 'Pin', 'key' => 'corrPin', 'value' => $data->corr_pin_code],
            ['displayString' => 'Locality', 'key' => 'corrLocality', 'value' => $data->corr_address],
        ]);
    }

    /**
     * | Generate Electricity Details
     */
    public function generateElectDtls($data)
    {
        return new Collection([
            ['displayString' => 'Electricity K. No', 'key' => 'electKNo', 'value' => $data->elect_consumer_no],
            ['displayString' => 'ACC No.', 'key' => 'accNo', 'value' => $data->elect_acc_no],
            ['displayString' => 'BIND/BOOK No.', 'key' => 'bindBookNo', 'value' => $data->elect_bind_book_no],
            ['displayString' => 'Electricity Consumer Category', 'key' => 'electConsumerCategory', 'value' => $data->elect_cons_category],
            ['displayString' => 'Building Plan Approval No.', 'key' => 'buildingApprovalNo', 'value' => $data->building_plan_approval_no],
            ['displayString' => 'Building Plan Approval Date', 'key' => 'buildingApprovalDate', 'value' => $data->building_plan_approval_date],
            ['displayString' => 'Water Consumer No.', 'key' => 'waterConsumerNo', 'value' => $data->water_conn_no],
            ['displayString' => 'Water Connection Date', 'key' => 'waterConnectionDate', 'value' => $data->water_conn_date]
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
    public function generateCardDetails($req, $ownerDetails)
    {
        $owners = collect($ownerDetails)->implode('owner_name', ',');
        return new Collection([
            ['displayString' => 'Ward No', 'key' => 'wardNo', 'value' => $req->old_ward_no],
            ['displayString' => 'SAF No.', 'key' => 'safNo', 'value' => $req->saf_no],
            ['displayString' => 'Owner Name', 'key' => 'ownerName', 'value' => $owners],
            ['displayString' => 'Property Type', 'key' => 'propertyType', 'value' => $req->property_type],
            ['displayString' => 'Assessment Type', 'key' => 'assessmentType', 'value' => $req->assessment_type],
            ['displayString' => 'Ownership Type', 'key' => 'ownershipType', 'value' => $req->ownership_type],
            ['displayString' => 'Apply-Date', 'key' => 'applyDate', 'value' => $req->application_date],
            ['displayString' => 'Plot-Area(sqt)', 'key' => 'plotArea', 'value' => $req->area_of_plot],
            ['displayString' => 'Is-Water-Harvesting', 'key' => 'isWaterHarvesting', 'value' => ($req->is_water_harvesting == true) ? 'Yes' : 'No'],
            ['displayString' => 'Is-Hoarding-Board', 'key' => 'isHoardingBoard', 'value' => ($req->is_hoarding_board == true) ? 'Yes' : 'No']
        ]);
    }
}
