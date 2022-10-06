<?php

namespace App\Repository\Property;

use App\Models\PropertyObjection;
use App\Models\PropFloorDetail;
use App\Models\PropOwner;
use App\Models\PropPropertie;
use App\Models\Saf;
use App\Models\UlbWorkflowMaster;
use App\Repository\Property\ObjectionRepository;

use App\Traits\Auth;
use App\Traits\Property\WardPermission;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;

/**
 * | Created On-24-08-2022
 * | Created By-Sandeep Bara
 * -----------------------------------------------------------------------------------------
 * | Objection all operations 
 */

class EloquentObjection implements ObjectionRepository
{
    use Auth;   
    use WardPermission;         
    #=============== Objection Apply Start ===================
    /**
        * Objection No. formate
        * "OBP".objection_id 
        * ==============Referance Table==========================
        * ------------------------------------------------------------
        * PropPropertie
        * Saf
        * PropFloorDetail
        * PropOwner        
        * PropertyObjection
        * PropParamFloorType
        * PropParamUsageType
        * PropParamOccupancyType
        * PropParamConstructionType
        *------------------------------------------------------------
        * ============ Referance Constaint And Tables ==============
        * -----------------------------------------------------------
        * Config/PorpertyConstaint.php
        * constaint Name            Tables
        * ==============          ==================
        * OBJECTION          ->   ObjectionTypeMstr 
        * PROPERTY-TYPE      ->   PropParamPropertyType
        * FLOOR-TYPE         ->   PropParamFloorType
        * OCCUPENCY-TYPE     ->   PropParamOccupancyType
        * USAGE-TYPE         ->   PropParamUsageType
        * CONSTRUCTION-TYPE  ->   PropParamConstructionType
        * -----------------------------------------------------------
        * ===== PropertyObjection
        * #user_id               = User.id;
        * #ulb_id                = User.ulb_id;
        * #roll_id               = User.role_id;
        * #objection             = PropertyObjection()
        * #objection.prop_dtl_id = PropPropertie.id
        * #objection.saf_dtl_id  = PropPropertie.saf_id;    
        * #objection.holding_no  = PropPropertie.holding_no;            
        * #objection.ward_id     = PropPropertie.ward_mstr_id;
        * #objection.user_id     = user_id;
        * #objection_id          = objection.id;
        * #no                    = "OBP".objection_id;
        * #objection.objection_no= no;
        * ------------------------------------------------------------
        * Request
        * ------------------------------------------------------------
        * ===== PropertyObjectionDetail ================  
        * inserObjection(array inputs)      
        *  -----------------------------------------------------------------------------
        * FOR  Rainwater Harvesting 2
        * | #objdtl["objection_id"]          = objection_id                             |
        * | #objdtl["objection_type_id"]     = 2                                        |
        * | #objdtl["according_assessment"]  = PropPropertie.is_water_harvesting        |
        * | #objdtl["according_applicant"]   = $request->harvestingObjValue?"t":'f'    |
        * | #objdtl["objection_by"]          = "Citizen"                                |
        * | #objdtl["user_id"]               = user_id                                  |
        * inserObjection(objdtl)
        * ------------------------------------------------------------------------------
        * ------------------------------------------------------------------------------
        * FOR Road Width 3
        * | #objdtl["objection_id"]           = objection_id                            |
        * | #objdtl["objection_type_id"]      = 3                                       |
        * | #objdtl["according_assessment"]   = PropPropertie.road_type_mstr_id         |
        * | #objdtl["according_applicant"]    = $request->roadWidthObjValue               |
        * | #objdtl[ "objection_by"]          = "Citizen"                               |
        * | #objdtl["user_id"]                = user_id                                 |
        * inserObjection(objdtl)
        * ------------------------------------------------------------------------------
        * ------------------------------------------------------------------------------
        *  FOR Property Type 4
        * | #objdtl["objection_id"]            = objection_id                           |
        * | #objdtl["objection_type_id"]       = 4                                      |
        * | #objdtl["according_assessment"]    = PropPropertie.prop_type_mstr_id        |
        * | #objdtl["according_applicant"]     = $request->propertyTypeObjValue           |
        * | #objdtl["objection_by"]            = "Citizen"                              |
        * | #objdtl["user_id"]                 = user_id                                |
        * inserObjection(objdtl)
        * ------------------------------------------------------------------------------
        *-------------------------------------------------------------------------------
        *  FOR Area of plot5
        * | #objdtl["objection_id"]        = objection_id                               |
        * | #objdtl["objection_type_id"]   = 5                                          |
        * | #objdtl["according_assessment] = PropPropertie.area_of_plot                 |              
        * | #objdtl["according_applicant"] = $request->plotAreaObjValue                 |              
        * | #objdtl["objection_by"]        = "Citizen"                                  |
        * | #objdtl["user_id"]             = user_id                                    |
        * inserObjection(objdtl)
        * ------------------------------------------------------------------------------
        * ------------------------------------------------------------------------------
        *  Mobile Tower 6
        * | #objdtl["objection_id"]          = objection_id                             |
        * | #objdtl["objection_type_id"]     = '6'                                      |
        * | #objdtl["according_assessment"]  = PropPropertie.is_mobile_tower            |
        * | #objdtl["assess_area"]           = PropPropertie.tower_area                 |
        * | #objdtl["assess_date"]           = PropPropertie.tower_installation_date    |
        * | #objdtl["according_applicant"]   = request->mobileTowerObjValue?'t':'f'        |
        * | #objdtl["applicant_area"]        = request->mobileTowerObjArea                 |
        * | #objdtl["applicant_date"]        = request->mobileTowerObjDate                 |
        * | #objdtl["objection_by"]          = "Citizen"                                |
        * | #objdtl["user_id"]               = user_id                                  |
        * inserObjection(objdtl)
        * ------------------------------------------------------------------------------
        *-------------------------------------------------------------------------------
        * FOR Hording Board 7
        * | #objdtl["objection_id"]            = objection_id                           |
        * | #objdtl["objection_type_id"]       = '7'                                    |
        * | #objdtl["according_assessment"]    = PropPropertie.is_hoarding_board        |
        * | #objdtl["assess_area"]             = PropPropertie.hoarding_area            |
        * | #objdtl["assess_date"]             = PropPropertie.hoarding_installation_date|
        * | #objdtl["according_applicant"]     = $request->hoardingObjValue ?'t':'f'  |
        * | #objdtl["applicant_area"]          = $request->hoardingObjArea            |
        * | #objdtl["applicant_date"]          = $request->hoardingObjDate            |
        * | #objdtl["objection_by"]            = "Citizen"                              |
        * | #objdtl["user_id"]                 = user_id                                |
        * inserObjection(objdtl)
        * ------------------------------------------------------------------------------
        * ------------------------------------------------------------------------------
        * # Floor Details 9
        
        * =========================== helpers use =====================================
        * remove_null() -> Helpers\utility_helper.php
        * ConstToArray() -> Helpers\utility_helper.php

    */
   #apply Objection Holding
   public function propertyObjection(Request $request)
   {   
        $user_id = auth()->user()->id;
        $ulb_id = auth()->user()->ulb_id;
        $roll_id = auth()->user()->role_id;        
        DB::beginTransaction();
        try{
            $workflow_id = Config::get('workflow-constants.PROPPERTY_OBJECTION_ID');
            $workflows = UlbWorkflowMaster::select('initiator', 'finisher')
                ->where('ulb_id', $ulb_id)
                ->where('workflow_id', $workflow_id)
                ->first();
            if(!$workflows)
            { 
                $message='Workflow Not Available';
                return responseMsg(false,$message,'');
            }
            $property = PropPropertie::find($request->id);
            if(!$property)
            {
                return responseMsg(false,"Property Not Found",$request->all());
            }
            $count = PropertyObjection::where('prop_dtl_id',$request->id) 
                                        ->where('status',1)->count();            
            if($count)
            {
                return responseMsg(false,"Objection is Allredy Apply For This Property",$count);
            }
            if($request->getMethod()=='GET')
            {  
                $approved_saf = Saf::where('id',$property->saf_id)->where('status',1)->first();
                if(!$approved_saf)
                {
                    return responseMsg(false,"Saf Not Approved","");
                }
                else
                {
                    $floors = PropFloorDetail::select("prop_floor_details.*",
                                                        "prop_param_floor_types.floor_name",
                                                        "prop_param_usage_types.usage_type",
                                                        "prop_param_occupancy_types.occupancy_type",
                                                        "prop_param_construction_types.construction_type"
                                            )
                                            ->join('prop_param_floor_types',function($join){
                                                $join->on("prop_param_floor_types.id","prop_floor_details.floor_mstr_id")
                                                ->whereAnd("prop_param_floor_types.status",1);
                                            })
                                            ->join('prop_param_usage_types',function($join){
                                                $join->on("prop_param_usage_types.id","prop_floor_details.usage_type_mstr_id")
                                                ->whereAnd("prop_param_usage_types.status",1);
                                            })
                                            ->join('prop_param_occupancy_types',function($join){
                                                $join->on("prop_param_occupancy_types.id","prop_floor_details.occupancy_type_mstr_id")
                                                ->whereAnd("prop_param_usage_types.status",1);
                                            })
                                            ->join('prop_param_construction_types',function($join){
                                                $join->on("prop_param_construction_types.id","prop_floor_details.const_type_mstr_id")
                                                ->whereAnd("prop_param_usage_types.status",1);
                                            })
                                            ->where('prop_floor_details.status',1)
                                            ->where('prop_floor_details.property_id',$request->id)
                                            ->get();
                    $owneres = PropOwner::where('status',1)
                                            ->where('property_id',$request->id)
                                            ->get();
                    $data = remove_null($property);
                    $owneres = remove_null($owneres);
                    $data['owneres']= $owneres;
                    $floors = remove_null($floors); 
                    $data['prop_floors']= $floors; 

                    $objection_type = Config::get('PropertyConstaint.OBJECTION');   
                    $objection_type =ConstToArray($objection_type,"type");                             
                    $data['objection_master']= remove_null($objection_type);

                    $property_type = Config::get('PropertyConstaint.PROPERTY-TYPE');
                    $property_type  =ConstToArray($property_type ,"type") ;
                    $data['property_master']= remove_null($property_type);

                    $floor_type = Config::get('PropertyConstaint.FLOOR-TYPE');
                    $floor_type  =ConstToArray($floor_type ,"name") ;
                    $data['floor_master'] = remove_null($floor_type);

                    $occupancy_types = Config::get('PropertyConstaint.OCCUPANCY-TYPE');
                    $occupancy_types  =ConstToArray($occupancy_types ,"type") ;
                    $data['occupancy_master'] = remove_null($occupancy_types);

                    $usage_types = Config::get('PropertyConstaint.USAGE-TYPE');
                    $usage_types  =ConstToArray($usage_types ,"type") ;
                    $data['usage_master'] = remove_null($usage_types);

                    $construction_type = Config::get('PropertyConstaint.CONSTRUCTION-TYPE');
                    $construction_type  =ConstToArray($construction_type ,"type") ;
                    $data['construction_master'] = remove_null($construction_type);
                    return responseMsg(true,"",$data);
                }
            }
            elseif($request->getMethod()=='POST')
            {                
                $rules = [
                    "saf_dtl_id"=>"required",
                    // "objection_form"=>"required",
                    // "evidence_document"=>"required",               
                   
                    'harvestingToggleStatus'=>"required|bool",
    
                    "roadWidthToggleStatus"=>"required|bool",
    
                    "propertyTypeToggleStatus"=>"required|bool",
    
                    "ploatAreaToggleStatus"=>"required|bool",
    
                    "mobileTowerToggleStatus"=>"required|bool",
    
                    "hoardigToggleStatus"=>"required|bool",
    
                    "floorToggleStatus"=>"required|bool",
                ];
                if($request->harvestingToggleStatus)
                {
                    $rules['harvestingObjValue']= "required|bool";
                    // $rules['RanHarwestingId']= "required|int";
                }
                if($request->roadWidthToggleStatus)
                {
                    $rules['roadWidthObjValue']= "required";
                    // $rules['RoadWidthId']= "required|int";
                }
                if($request->propertyTypeToggleStatus)
                {
                    $rules['propertyTypeObjValue']= "required";
                    // $rules['PropertyTypeId']= "required|int";
                }
                if($request->ploatAreaToggleStatus)
                {
                    $rules['plotAreaObjValue']= "required";
                    // $rules['AreaOfPlotId']= "required|int";
                }
                if($request->mobileTowerToggleStatus)
                {
                    $rules['mobileTowerObjValue']= "required";
                    $rules['mobileTowerObjArea']= "required|numeric";
                    $rules['mobileTowerObjDate']= "required|date";
                    // $rules['MobileTowerId']= "required|int";
                }
                if($request->hoardigToggleStatus)
                {
                    $rules['hoardingObjValue']= "required";
                    $rules['hoardingObjArea'] = "required|numeric";
                    $rules['hoardingObjDate'] = "required|date";
                    // $rules['HoardingBoardId']= "required|int";
                }
                if($request->floorToggleStatus)
                {
                    $rules['floorObjValues']= "required|array";
                    // $rules['FloorDetailId']= "required|int";
                }
    
                $validator = Validator::make($request->all(),$rules);  
                if($validator->fails())
                {
                    $messages["message"] = $validator->errors();
                    return responseMsg(false,$validator->errors(),$request->all());
                }
                    // 1	Typographical Error
                    // 2	Rainwater Harvesting
                    // 3	Road Width
                    // 4	Property Type
                    // 5	Area of Plot
                    // 6	Mobile Tower
                    // 7	Hoarding Board
                    // 8	Other
                    // 9	Floor Detail                
                            
                $objection = new PropertyObjection ;
                $objection->prop_dtl_id  = $request->id;
                $objection->saf_dtl_id   = $request->saf_dtl_id;    
                $objection->holding_no   = $request->holding_no;            
                $objection->ward_id      = $request->ward_id;
                $objection->user_id      = $user_id;

                // $objection->current_user = $workflows->initiator;
                // $objection->initiator_id = $workflows->initiator;
                // $objection->finisher_id  = $workflows->finisher;
                // $objection->workflow_id  = $workflow_id;
                // $objection->objection_form_doc=$request->saf_dtl_id;    
                // $objection->evidence_document=$request->holding_no;
                $objection->save();
                $objection_id = $objection->id;
                $no= "OBP".$objection_id;
                $objection->objection_no=$no;
                $objection->save();                
                # Rainwater Harvesting 2
                if($request->harvestingToggleStatus)
                {
                    $objdtl=[
                                "objection_id"=> $objection_id,
                                "objection_type_id"=> '2',
                                "according_assessment"=> 't',
                                "according_applicant"=> $request->harvestingObjValue?"t":'f',
                                "objection_by"=> "Citizen",
                                "user_id"=> $user_id,
                    ];
                    if(!$this->inserObjection($objdtl))
                    {
                        return responseMsg(false,'Some Error Ocures Plese Contact To Admin',$request->all());
                    }                    
                }
    
                # Road Width 3
                if($request->roadWidthToggleStatus)
                {
                    $objdtl=[
                                "objection_id"         => $objection_id,
                                "objection_type_id"    => '3',
                                "according_assessment" => 't',
                                "according_applicant"  => $request->roadWidthObjValue?"t":'f',
                                "objection_by"         => "Citizen",
                                "user_id"              => $user_id,
                    ];
                    if(!$this->inserObjection($objdtl))
                    {
                        return responseMsg(false,'Some Error Ocures Plese Contact To Admin',$request->all());
                    }
                }
    
                # Property Type 4
                if($request->propertyTypeToggleStatus)
                {
                    $objdtl=[
                                "objection_id"        => $objection_id,
                                "objection_type_id"   => '4',
                                "according_assessment"=> 1,
                                "according_applicant" => 1,
                                "objection_by"        => "Citizen",
                                "user_id"             => $user_id,
                    ];
                    if(!$this->inserObjection($objdtl))
                    {
                        return responseMsg(false,'Some Error Ocures Plese Contact To Admin',$request->all());
                    }
                }
    
                # Area of plot 5
                if($request->ploatAreaToggleStatus)
                {
                    $objdtl=[
                                "objection_id"=> $objection_id,
                                "objection_type_id"=> '5',
                                "according_assessment"=> 500,                                
                                "according_applicant"=> 200,                                
                                "objection_by"=> "Citizen",
                                "user_id"=> $user_id,
                    ];
                    if(!$this->inserObjection($objdtl))
                    {
                        return responseMsg(false,'Some Error Ocures Plese Contact To Admin',$request->all());
                    }
                    
                }
    
                # Mobile Tower 6
                if($request->mobileTowerToggleStatus)
                {
                    
                    $objdtl=[
                                "objection_id"=> $objection_id,
                                "objection_type_id"=> '6',
                                "according_assessment"=> $property->is_mobile_tower,
                                "assess_area"=> $property->tower_area,
                                "assess_date"=> $property->tower_installation_date,
                                "according_applicant"=> $request->mobileTowerObjValue?'t':'f',
                                "applicant_area"=> $request->mobileTowerObjArea,
                                "applicant_date"=>  $request->mobileTowerObjDate,
                                "objection_by"=> "Citizen",
                                "user_id"=> $user_id,
                    ];
                    if(!$this->inserObjection($objdtl))
                    {
                        return responseMsg(false,'Some Error Ocures Plese Contact To Admin',$request->all());
                    }
                    
                }
    
                # Hording Board 7
                if($request->hoardigToggleStatus)
                {
                    $objdtl=[
                                "objection_id"=> $objection_id,
                                "objection_type_id"=> '7',
                                "according_assessment"=> $property->is_hoarding_board,
                                "assess_area"=> $property->hoarding_area,
                                "assess_date"=> $property->hoarding_installation_date,
                                "according_applicant"=> $request->hoardingObjValue?'t':'f',
                                "applicant_area"=> $request->hoardingObjArea,
                                "applicant_date"=> $request->hoardingObjArea,
                                "objection_by"=> "Citizen",
                                "user_id"=> $user_id,
                    ];
                    if(!$this->inserObjection($objdtl))
                    {
                        return responseMsg(false,'Some Error Ocures Plese Contact To Admin',$request->all());
                    }
                }
    
                # Floor Details 9
                // if($request->FloorDetailStatus)
                // {
                //     $floor_detail = $request['floor'];
                //     $inputs = $floor_detail;
                //     $i=0;
                //     foreach($prop_floors as $floor)
                //     {
                //         $floordtl=[
                //                     "prop_dtl_id"=> $floor["prop_dtl_id"],
                //                     "objection_id"=> $objection_id,
                //                     "objection_type_id"=> '9',
                //                     "prop_floor_dtl_id"=> $floor["id"],
                //                     "floor_mstr_id"=> $floor["floor_mstr_id"],
                //                     "usage_type_mstr_id"=> $floor["usage_type_mstr_id"],
                //                     "occupancy_type_mstr_id"=> $floor["occupancy_type_mstr_id"],
                //                     "const_type_mstr_id"=> $floor["const_type_mstr_id"],
                //                     "builtup_area"=> $floor["builtup_area"],
                //                     "carpet_area"=> $floor["carpet_area"],
                //                     "date_from"=> $floor["date_from"],
                //                     "date_upto"=> $floor["date_upto"],
                //                     "remarks"=> null,
                //                     "objection_by"=> 'Assessment',
                //                 ];
                //         $this->inserObjectionFloor($floordtl);
                        
                //         if($inputs["usage_type_mstr_id"][$i]==1)
                //             $objection_carpet_area=$inputs["builtup_area"][$i]*0.7;
                //         else
                //             $objection_carpet_area=$inputs["builtup_area"][$i]*0.8;
                        
                        
                //         $floordtlobj=[
                //             "prop_dtl_id"=> $floor["prop_dtl_id"],
                //             "objection_id"=> $objection_id,
                //             "objection_type_id"=> Config::get('workflow-constants.OBJECTION')['FloorDetail'],
                //             "prop_floor_dtl_id"=> $floor["id"],
                //             "floor_mstr_id"=> $inputs["floor_mstr_id"][$i],
                //             "usage_type_mstr_id"=> $inputs["usage_type_mstr_id"][$i],
                //             "occupancy_type_mstr_id"=> $inputs["occupancy_type_mstr_id"][$i],
                //             "const_type_mstr_id"=> $inputs["const_type_mstr_id"][$i],
                //             "builtup_area"=> $inputs["builtup_area"][$i],
                //             "carpet_area"=> $objection_carpet_area,
                //             "date_from"=> $floor["date_from"],
                //             "date_upto"=> $floor["date_upto"],
                //             "remarks"=> null,
                //             "objection_by"=> 'Citizen',
                //         ];
                //         $this->inserObjectionFloor($floordtlobj);
                //         $i++;
                //     }
                // }
                //DB::commit();
                return responseMsg(true,'Objection Is Successfully Apply',$no);
                
            }  
        }
        catch(Exception $e)
        {
            DB::rollBack();
            return $e;
        }
   }

   public function inserObjection( array $data)
   {
        try{
            
            return $lastInsertId = DB::table('property_objection_details')->insertGetId($data);
        }
        catch(Exception $e)
        {
            return $e;
        }
   }
   public function inserObjectionFloor( array $data)
   {
        try{
            
            return $lastInsertId = DB::table('property_objection_floor_details')->insertGetId($data);
        }
        catch(Exception $e)
        {
            return $e;
        }
   }
   #=============== Objection Apply End =====================

   #=============== Objection WorkFlow Start ================
   public function propObjectionInbox($key)
   {
        try{
            $user_id = auth()->user()->id;
            $redis=Redis::connection();  // Redis Connection
            $redis_data = json_decode(Redis::get('user:' . $user_id),true);
            $ulb_id = $redis_data['ulb_id']??auth()->user()->ulb_id;;
            $roll_id =  $redis_data['role_id']??($this->getUserRoll($user_id,'Property Objection')->role_id??-1);
            $workflow_id = Config::get('workflow-constants.PROPPERTY_OBJECTION_ID');
            $work_flow_candidate = $this->work_flow_candidate($user_id,$ulb_id);
            if(!$work_flow_candidate || $roll_id==-1)
            {
                $message=["status"=>false,"data"=>[],"message"=>"Your Are Not Authoried"];
                return response()->json($message,200);
            }        
            $work_flow_candidate = collect($work_flow_candidate);         
            $ward_permission = $this->WardPermission($user_id);
            $ward_ids = array_map(function($val)
                        {
                            return $val['ulb_ward_id'];
                        },$ward_permission); 
            
            $data =PropertyObjection::select(DB::raw("property_objections.id as objection_id,
                                                    property_objections.created_at as apply_date,
                                                    prop_properties.id as property_id,
                                                    ulb_ward_masters.ward_name as ward_no
                                            "), 
                                            'property_objections.objection_no',
                                            'prop_properties.new_holding_no' ,
                                            'owners.owner_name',
                                            'owners.mobile_no',
                                        )
                                        ->join('prop_properties','property_objections.prop_dtl_id','prop_properties.id')
                                        ->join('ulb_ward_masters','ulb_ward_masters.id','prop_properties.ward_mstr_id')
                                        ->leftjoin(DB::raw("(SELECT prop_owners.property_id,
                                                                string_agg(prop_owners.owner_name,', ') as owner_name,
                                                                string_agg(prop_owners.guardian_name,', ') as guardian_name,
                                                                string_agg(prop_owners.mobile_no::text,', ') as mobile_no
                                                        FROM prop_owners 
                                                        WHERE prop_owners.status = 1
                                                        GROUP BY prop_owners.property_id
                                                        )owners
                                                            "),function($join){
                                            $join->on('owners.property_id','=','prop_properties.id');
                                        })
                                        ->where("property_objections.current_user", $roll_id) 
                                        ->where("prop_properties.ulb_id",$ulb_id)  
                                        ->whereIn('prop_properties.ward_mstr_id',$ward_ids)
                                        ->whereIn('prop_properties.ward_mstr_id',$ward_ids) ;
                            if($key)
                            {
                                $data= $data->where(function($query) use($key)
                                                {
                                                    $query->orwhere('prop_properties.holding_no', 'ILIKE', '%'.$key.'%')
                                                    ->orwhere('property_objections.objection_no', 'ILIKE', '%'.$key.'%')
                                                    ->orwhere('owners.owner_name', 'ILIKE', '%'.$key.'%')
                                                    ->orwhere('owners.guardian_name', 'ILIKE', '%'.$key.'%')
                                                    ->orwhere('owners.mobile_no', 'ILIKE', '%'.$key.'%');
                                                });
                            } 
                            $data = $data->get(); 
            $data = remove_null($data);
            return responseMsg(true,'',$data);
        }
        catch(Exception $e)
        {
            return $e;
        }
   }
   public function propObjectionOutbox($key)
   {
        try{
            $user_id = auth()->user()->id; 
            $redis=Redis::connection();  // Redis Connection
            $redis_data = json_decode(Redis::get('user:' . $user_id),true);
            $ulb_id = $redis_data['ulb_id']??auth()->user()->ulb_id;;
            $roll_id =  $redis_data['role_id']??($this->getUserRoll($user_id,'Property Objection')->role_id??-1);
            $workflow_id = Config::get('workflow-constants.PROPPERTY_OBJECTION_ID');
            $work_flow_candidate = $this->work_flow_candidate($user_id,$ulb_id);
            if(!$work_flow_candidate || $roll_id==-1)
            {
                $message=["status"=>false,"data"=>[],"message"=>"Your Are Not Authoried"];
                return response()->json($message,200);
            }        
            $work_flow_candidate = collect($work_flow_candidate);         
            $ward_permission = $this->WardPermission($user_id);
            $ward_ids = array_map(function($val)
                        {
                            return $val['ulb_ward_id'];
                        },$ward_permission); 
            
            $data =PropertyObjection::select(DB::raw("property_objections.id as objection_id,
                                                    property_objections.created_at as apply_date,
                                                    prop_properties.id as property_id,
                                                    ulb_ward_masters.ward_name as ward_no
                                            "), 
                                            'property_objections.objection_no',
                                            'prop_properties.new_holding_no' ,
                                            'owners.owner_name',
                                            'owners.mobile_no',
                                        )
                                        ->join('prop_properties','property_objections.prop_dtl_id','prop_properties.id')
                                        ->join('ulb_ward_masters','ulb_ward_masters.id','prop_properties.ward_mstr_id')
                                        ->leftjoin(DB::raw("(SELECT prop_owners.property_id,
                                                                string_agg(prop_owners.owner_name,', ') as owner_name,
                                                                string_agg(prop_owners.guardian_name,', ') as guardian_name,
                                                                string_agg(prop_owners.mobile_no::text,', ') as mobile_no
                                                        FROM prop_owners 
                                                        WHERE prop_owners.status = 1
                                                        GROUP BY prop_owners.property_id
                                                        )owners
                                                            "),function($join){
                                            $join->on('owners.property_id','=','prop_properties.id');
                                        })
                                        ->where(
                                            function($query) use($roll_id){
                                                return $query
                                                ->where('property_objections.current_user', '<>', $roll_id)
                                                ->orwhereNull('property_objections.current_user');
                                        }) 
                                        ->where("prop_properties.ulb_id",$ulb_id)  
                                        ->whereIn('prop_properties.ward_mstr_id',$ward_ids)
                                        ->whereIn('prop_properties.ward_mstr_id',$ward_ids) ;
                            if($key)
                            {
                                $data= $data->where(function($query) use($key)
                                                {
                                                    $query->orwhere('prop_properties.holding_no', 'ILIKE', '%'.$key.'%')
                                                    ->orwhere('property_objections.objection_no', 'ILIKE', '%'.$key.'%')
                                                    ->orwhere('owners.owner_name', 'ILIKE', '%'.$key.'%')
                                                    ->orwhere('owners.guardian_name', 'ILIKE', '%'.$key.'%')
                                                    ->orwhere('owners.mobile_no', 'ILIKE', '%'.$key.'%');
                                                });
                            } 
                            $data = $data->get(); 
            $data = remove_null($data);
            return responseMsg(true,'',$data);
        }
        catch(Exception $e)
        {
            return $e;
        }
   }
   #Inbox  special category
   public function specialObjectionInbox($key)
   { 
        try{
            $user_id = auth()->user()->id;
            $redis=Redis::connection();  // Redis Connection
            $redis_data = json_decode(Redis::get('user:' . $user_id),true);
            $ulb_id = $redis_data['ulb_id']??auth()->user()->ulb_id;;
            $roll_id =  $redis_data['role_id']??($this->getUserRoll($user_id,'Property Objection')->role_id??-1);
            $workflow_id = Config::get('workflow-constants.PROPPERTY_OBJECTION_ID');
            $work_flow_candidate = $this->work_flow_candidate($user_id,$ulb_id);
            if(!$work_flow_candidate || $roll_id==-1 )
            {
                $message=["status"=>false,"data"=>[],"message"=>"Your Are Not Authoried"];
                return response()->json($message,200);
            }        
            $work_flow_candidate = collect($work_flow_candidate);         
            $ward_permission = $this->WardPermission($user_id);
            $ward_ids = array_map(function($val)
                        {
                            return $val['ulb_ward_id'];
                        },$ward_permission); 
            DB::enableQueryLog();
            $data =PropertyObjection::select(DB::raw("property_objections.id as objection_id,
                                                    property_objections.created_at as apply_date,
                                                    prop_properties.id as property_id,
                                                    ulb_ward_masters.ward_name as ward_no
                                            "), 
                                            'property_objections.objection_no',
                                            'prop_properties.new_holding_no' ,
                                            'owners.owner_name',
                                            'owners.mobile_no',
                                        )
                                        ->join('prop_properties','property_objections.prop_dtl_id','prop_properties.id')
                                        ->join('ulb_ward_masters','ulb_ward_masters.id','prop_properties.ward_mstr_id')
                                        ->leftjoin(DB::raw("(SELECT prop_owners.property_id,
                                                                string_agg(prop_owners.owner_name,', ') as owner_name,
                                                                string_agg(prop_owners.guardian_name,', ') as guardian_name,
                                                                string_agg(prop_owners.mobile_no::text,', ') as mobile_no
                                                        FROM prop_owners 
                                                        WHERE prop_owners.status = 1
                                                        GROUP BY prop_owners.property_id
                                                        )owners
                                                            "),function($join){
                                            $join->on('owners.property_id','=','prop_properties.id');
                                        })
                                        ->where("property_objections.current_user", $roll_id) 
                                        ->where("property_objections.is_escalate", 1) 
                                        ->where("prop_properties.ulb_id",$ulb_id) 
                                        ->whereIn('prop_properties.ward_mstr_id',$ward_ids) ;
                            if($key)
                            {
                                $data= $data->where(function($query) use($key)
                                                {
                                                    $query->orwhere('prop_properties.holding_no', 'ILIKE', '%'.$key.'%')
                                                    ->orwhere('property_objections.objection_no', 'ILIKE', '%'.$key.'%')
                                                    ->orwhere('owners.owner_name', 'ILIKE', '%'.$key.'%')
                                                    ->orwhere('owners.guardian_name', 'ILIKE', '%'.$key.'%')
                                                    ->orwhere('owners.mobile_no', 'ILIKE', '%'.$key.'%');
                                                });
                            } 
                            $data = $data->get();
            $data = remove_null($data);
            return responseMsg(true,'',$data);
        }
        catch(Exception $e)
        {
            return $e;
        }
   }

   #============== Objection WorkFlow End ==================

}