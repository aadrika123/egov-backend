<?php

namespace App\Traits\Property;

use App\Models\Ward\WardUser;
use App\Models\WorkflowCandidate;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;

/**
 * Trait for get ward permission of current user and other Common Data for Saf workflow and Objection workFlow also
 * Created for redusing query exicution and storing data in redis codes
 * --------------------------------------------------------------------------------------------------------
 * Created by-Sandeep Bara
 * Created On-28-08-2022 
 * --------------------------------------------------------------------------------------------------------
 */
trait WardPermission
{
   public function work_flow_candidate($user_id,$ulb_id)
   {
        $redis=Redis::connection();
        $work_flow_candidate = json_decode(Redis::get('workflow_candidate:' . $user_id),true)??null;        
        if(!$work_flow_candidate)
        {
            $work_flow_candidate = WorkflowCandidate::select('workflow_candidates.id',"ulb_workflow_masters.module_id")
                                        ->join('ulb_workflow_masters','ulb_workflow_masters.id','workflow_candidates.ulb_workflow_id')
                                        ->where('workflow_candidates.user_id',$user_id)
                                        ->where('ulb_workflow_masters.ulb_id',$ulb_id )
                                        ->first();
            if(!$work_flow_candidate)
            {
                $message=["status"=>false,"data"=>[],"message"=>"Your Are Not Authoried"];
                return response()->json($message,200);
            }
            $this->WorkflowCandidateSet($redis,$user_id,$work_flow_candidate);   

        }
        return $work_flow_candidate;
   }
   public function WardPermission($user_id)
   { 
        $redis=Redis::connection();
        $ward_permission = json_decode(Redis::get('WardPermission:' . $user_id),true)??null; 
        if(!$ward_permission)
        { 
            Redis::del('WardPermission:' . $user_id);
            $ward_permission =WardUser::select("ulb_ward_id")
                                     ->where('user_id',$user_id)
                                     ->orderBy('ulb_ward_id')
                                     ->get();
            $ward_permission = adjToArray($ward_permission);
            $this->WardPermissionSet($redis,$user_id, $ward_permission);
        }
        return $ward_permission;
   }

   public function getRoleUsersForBck($ulb_id,$work_flow_id,$role_id,$finisher=null) //curernt user Roll id
   {  
        $roll_id = Config::get("PropertyConstaint.ROLES.INDEX"."$ulb_id"."_$work_flow_id".".$role_id");
        if(is_null($role_id))
        {
            return Config::get("PropertyConstaint.ROLES".".$ulb_id"."_$work_flow_id");
        }    
        $backWord = Config::get("PropertyConstaint.ROLES".".$ulb_id"."_$work_flow_id".".".($roll_id-1))??[];
        $forWord = Config::get("PropertyConstaint.ROLES".".$ulb_id"."_$work_flow_id".".".($roll_id+1))??[];        
        return ['backward'=>$backWord,"forward"=>($finisher==$role_id?[]:$forWord),'btc'=>
                 (!in_array($roll_id,[1,6])) ?(Config::get("PropertyConstaint.ROLES".".$ulb_id"."_$work_flow_id".".0")):[]
                
            ];
   }

   public function getWorkFlowCondidate()
   {
    
   }
}