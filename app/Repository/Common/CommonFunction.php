<?php
namespace App\Repository\Common;

use App\Models\Ward\WardUser;
use App\Models\WfMaster;
use App\Models\WfRole;
use App\Traits\Auth;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class CommonFunction implements ICommonFunction
{    
    use Auth;
    public function WardPermission($user_id)
    { 
            $redis=Redis::connection();
            $ward_permission = json_decode(Redis::get('WardPermission:' . $user_id),true)??null; 
            if(!$ward_permission)
            { 
                Redis::del('WardPermission:' . $user_id);
                $ward_permission =WardUser::select("ulb_ward_id",
                                    DB::raw("ulb_ward_masters.ward_name as ward_no")
                                    )
                                    ->join("ulb_ward_masters","ulb_ward_masters.id","=","ward_users.ulb_ward_id")
                                    ->where('user_id',$user_id)
                                    ->orderBy('ulb_ward_id')
                                    ->get();
                $ward_permission = adjToArray($ward_permission);
                $this->WardPermissionSet($redis,$user_id, $ward_permission);
            }
            return $ward_permission;
    }
    public function getWorkFlowRoles( $user_id,int $ulb_id, int $work_flow_id)
    {
            $redis =Redis::connection();
            $workflow_rolse = json_decode(Redis::get('WorkFlowRoles:' . $user_id.":".$work_flow_id),true)??null;
            if(!$workflow_rolse)
            {
                $workflow_rolse = WfMaster::select(
                                DB::raw("wf_roles.id as role_id,wf_roles.role_name,forward_role_id,
                                        backward_role_id,is_initiator,is_finisher,
                                        wf_masters.workflow_name,wf_masters.id as workflow_id,
                                        wf_workflows.ulb_id"
                                )
                            )
                            ->join("wf_workflows","wf_workflows.wf_master_id","wf_masters.id")
                            ->join(DB::raw("(SELECT distinct(wf_role_id) as wf_role_id ,workflow_id
                                            FROM wf_workflowrolemaps 
                                            WHERE  wf_workflowrolemaps.is_suspended = false 
                                            GROUP BY workflow_id,wf_role_id
                                            ) wf_workflowrolemaps "),
                                            function($join) use($ulb_id)
                                            {
                                                $join->on("wf_workflowrolemaps.workflow_id","wf_workflows.id");
                                            }
                            )
                            ->join("wf_roles","wf_roles.id","wf_workflowrolemaps.wf_role_id")
                            ->where("wf_roles.is_suspended",false)
                            ->orderBy("wf_roles.id")
                            ->get();
                $workflow_rolse = adjToArray($workflow_rolse);
                $this->WorkFlowRolesSet($redis,$user_id, $workflow_rolse,$work_flow_id);
            }
            return $workflow_rolse;
    }

    public function getForwordBackwordRoll($user_id,int $ulb_id, int $work_flow_id,int $role_id,$finisher=null)
    {
            $retuns = [];
            $workflow_rolse = $this-> getWorkFlowRoles($user_id,$ulb_id,$work_flow_id);
            $backwordForword = array_filter($workflow_rolse,function($val)use($role_id){
                return $val['id']==$role_id;
            });
            $backwordForword =array_values($backwordForword)[0]??[];
            if( $backwordForword)
            {
                $data = array_map(function($val) use($backwordForword){
                    if($val['id']==$backwordForword['forward_id'])
                    {
                        return ['forward'=>['id'=>$val['id'],'role_name'=>$val['role_name']]];
                    }
                    if($val['id']==$backwordForword['backward_id'])
                    {
                        return ['backward'=>['id'=>$val['id'],'role_name'=>$val['role_name']]];
                    }
                },$workflow_rolse);
                $data = array_filter($data,function($val){
                    return is_array($val);
                }); 
                $data = array_values($data);

                $forward = array_map(function($val){
                    return $val['forward']??false;
                },$data);

                $forward =array_filter($forward,function($val){
                    return is_array($val);
                }); 
                $forward = array_values($forward)[0]??[];

                $backward = array_map(function($val){
                    return $val['backward']??false;
                },$data);

                $backward =array_filter($backward,function($val){
                    return is_array($val);
                }); 
                $backward = array_values($backward)[0]??[];
                // dd($backward);
                $retuns["backward"]=$backward;
                $retuns["forward"]=$forward;
                
            }        
            return $retuns;
    }

    public function getAllRoles($user_id,int $ulb_id, int $work_flow_id,int $role_id)
    {
            try{
                $data = $this->getWorkFlowRoles($user_id,$ulb_id, $work_flow_id,$role_id);
                $curentUser = array_filter($data,function($val)use($role_id){
                    return $val['id']==$role_id;
                });
                $curentUser=array_values($curentUser)[0];
                $data = array_filter($data,function($val)use($curentUser){
                    return (!in_array($val['id'],[$curentUser['forward_id'],$curentUser['backward_id']]));
                });
                return($data);
            }
            catch(Exception $e)
            {
                return response()->json($e, 400);
            }
            
    }
    public function getUserRoll($user_id,$ulb_id,$workflow_id)
    { 
        try{//
            $data = WfRole::select(DB::raw("wf_roles.id as role_id,wf_roles.role_name,
                                            wf_roles.is_initiator, wf_roles.is_finisher,
                                            wf_roles.forward_role_id,forword.role_name as forword_name,
                                            wf_roles.backward_role_id,backword.role_name as backword_name,
                                            wf_masters.id as workflow_id,wf_masters.workflow_name,
                                            ulb_masters.id as ulb_id, ulb_masters.ulb_name,
                                            ulb_masters.ulb_type")
                                    )
                        ->leftjoin("wf_roles AS forword","forword.id","=","wf_roles.forward_role_id")
                        ->leftjoin("wf_roles AS backword","backword.id","=","wf_roles.backward_role_id")
                        ->join("wf_roleusermaps","wf_roleusermaps.wf_role_id","=","wf_roles.id")
                        ->join("users","users.id","=","wf_roleusermaps.user_id")
                        ->join("wf_workflowrolemaps","wf_workflowrolemaps.wf_role_id","=","wf_roleusermaps.wf_role_id")
                        ->join("wf_workflows","wf_workflows.id","=","wf_workflowrolemaps.workflow_id")
                        ->join("wf_masters","wf_masters.id","=","wf_workflows.wf_master_id")
                        ->join("ulb_masters","ulb_masters.id","=","wf_workflows.ulb_id")
                        ->where("wf_roles.is_suspended",false)
                        ->where("wf_roleusermaps.user_id",$user_id)
                        ->where("wf_workflows.ulb_id",$ulb_id)
                        ->where("wf_masters.id",strtoupper($workflow_id))
                        ->orderBy("wf_roleusermaps.id","desc")
                        ->first();
            return $data;
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }
    }
   
   
}