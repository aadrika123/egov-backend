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
        $ref = '{
			"assessmentType":"ReAssessment",
			"ward":1,
			"propertyType":1,
			"dateOfPurchase":"2021-11-01",
			"ownershipType":1,
			"roadType":41,
			"areaOfPlot":750,
			"isMobileTower":0,
			"mobileTower":{
					"area":100,
					"dateFrom":"2014-10-07"
			},	
			"towerArea":222.1,
			"towerInstallationDate":"",
			"isHoardingBoard":0,
			"hoardingBoard":{
					"area":100,
					"dateFrom":"2016-10-07"
			},	
			"isPetrolPump":0,
			"petrolPump":{
					"area":100,
					"dateFrom":"2014-10-07"
			},
			"landOccupationDate":"",
			"isWaterHarvesting":0,
			"previousHoldingId":"1",
			"holdingNo":"sadf474",
			"zone":1,
			"floor":[	
				{
					"floorNo":1,
					"useType":1,
					"constructionType":1,
					"occupancyType":2,
					"buildupArea":800,
					"dateFrom":"2022-10-01",
					"dateUpto":""
				},
				{
					"floorNo":2,
					"useType":1,
					"constructionType":1,
					"occupancyType":2,
					"buildupArea":800,
					"dateFrom":"2022-10-01",
					"dateUpto":""
				}
			]';

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
        }

        $safCalculation = new SafCalculation();
        $request = new Request($array);
        $safTaxes = $safCalculation->calculateTax($request);
        return $safTaxes;
    }
}
