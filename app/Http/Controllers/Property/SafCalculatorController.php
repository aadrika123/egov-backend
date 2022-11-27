<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repository\Property\Interfaces\iSafRepository;
use App\EloquentClass\Property\SafCalculation;

class SafCalculatorController extends Controller
{
    public function __construct(iSafRepository $repository)
    {
        $this->Repository = $repository;
    }
    public function calculateSaf(Request $req)
    {
        $array = array();

        $data = $this->Repository->details($req);
        $req = $data->original['data'];
        // return $req;
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
            $array['floor'][$key]['floorNo'] = $refFloor['id'];
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
            $array['owner'][$key]['dob'] = $refFloor['dob'];
        }

        $safCalculation = new SafCalculation();
        $request = new Request($array);
        $safTaxes = $safCalculation->calculateTax($request);
        return $safTaxes;
    }
}
