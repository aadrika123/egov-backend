<?php

namespace App\Traits;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Trait for saving and editing the Users and Citizen register also
 * Created for reducing the duplication for the Saving and editing codes
 * --------------------------------------------------------------------------------------------------------
 * Created by-Anshu Kumar
 * Created On-16-07-2022 
 * --------------------------------------------------------------------------------------------------------
 */

trait Auth
{
    /**
     * Saving User Credentials 
     */
    public function saving($user, $request)
    {
        $user->user_name = $request->name;
        $user->mobile = $request->mobile;
        $user->email = $request->email;
        $user->ulb_id = $request->ulb;
        if ($request->userType) {
            $user->user_type = $request->userType;
        }
        if ($request->description) {
            $user->description = $request->description;
        }
        if ($request->workflowParticipant) {
            $user->workflow_participant = $request->workflowParticipant;
        }
        $token = Str::random(80);                       //Generating Random Token for Initial
        $user->remember_token = $token;
    }

    /**
     * Saving Extra User Credentials
     */

    public function savingExtras($user, $request)
    {
        if ($request->suspended) {
            $user->suspended = $request->suspended;
        }
        if ($request->superUser) {
            $user->super_user = $request->superUser;
        }
    }

    /**
     * Save User Credentials On Redis 
     */
    public function redisStore($redis, $emailInfo, $request, $token)
    {
        $redis->set(
            'user:' . $emailInfo->id,
            json_encode([
                'id' => $emailInfo->id,
                'name' => $emailInfo->user_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'remember_token' => $token,
                'mobile' => $emailInfo->mobile,
                'user_type' => $emailInfo->user_type,
                'ulb_id' => $emailInfo->ulb_id,
                'created_at' => $emailInfo->created_at,
                'updated_at' => $emailInfo->updated_at
            ])
        );
    }

    /**
     * | response Messages for Success Login
     * | @param BearerToken $token
     * | @return Response
     */
    public function tResponseSuccess($token)
    {
        $response = ['status' => true, 'data' => $token, 'message' => 'You Have Logged In!!'];
        return $response;
    }

    /**
     * | response messages for failure login
     * | @param Msg The conditional messages 
     */
    public function tResponseFail($msg)
    {
        $response = ['status' => false, 'data' => '', 'message' => $msg];
        return $response;
    }

    /**     
     * Save put Workflow_candidate On User Credentials On Redis 
     */

    public function WorkflowCandidateSet($redis, $user_id, $Workflow_candidate)
    {
        $redis->set(
            'workflow_candidate:' . $user_id,
            json_encode([
                'id' => $Workflow_candidate->id,
                'module_id' => $Workflow_candidate->module_id,
            ])
        );
        $redis->expire('workflow_candidate:' . $user_id, 18000);
    }

    public function WardPermissionSet($redis, $user_id, array $Workflow_candidate)
    {
        $redis->set(
            'WardPermission:' . $user_id,
            json_encode($Workflow_candidate)
        );
        $redis->expire('WardPermission:' . $user_id, 18000);
    }

    public function WorkFlowRolesSet($redis, $user_id, array $workflow_rolse, $work_flow_id)
    {
        $redis->set(
            'WorkFlowRoles:' . $user_id . ":" . $work_flow_id,
            json_encode($workflow_rolse)
        );
        $redis->expire('WorkFlowRoles:' . $user_id . ":" . $work_flow_id, 18000);
    }
    public function AllVacantLandRentalRateSet($redis, array $rentalVal)
    {
        $redis->set(
            'AllVacantLandRentalRate',
            json_encode($rentalVal)
        );
        $redis->expire('AllVacantLandRentalRate', 18000);
    }
    public function AllRentalValueSet($redis, int $ulb_id, array $rentalVal)
    {
        $redis->set(
            "AllRentalValue:$ulb_id",
            json_encode($rentalVal)
        );
        $redis->expire("AllRentalValue:$ulb_id", 18000);
    }
    public function AllBuildingUsageFacterSet($redis, array $rentalVal)
    {
        $redis->set(
            "AllBuildingUsageFacter",
            json_encode($rentalVal)
        );
        $redis->expire("AllBuildingUsageFacter", 18000);
    }
    public function AllBuildingRentalValueSet($redis, int $ulb_id, array $rentalVal)
    {
        $redis->set(
            "AllBuildingRentalValue:$ulb_id",
            json_encode($rentalVal)
        );
        $redis->expire("AllBuildingRentalValue:$ulb_id", 18000);
    }
    public function OccuPencyFacterSet($redis, array $OccuPencyFacter)
    {
        $redis->set(
            "OccuPencyFacter",
            json_encode($OccuPencyFacter)
        );
        $redis->expire("OccuPencyFacter", 18000);
    }
    public function AllCircleRateSet($redis, int $ulb_id, array $OccuPencyFacter)
    {
        $redis->set(
            "AllCircleRate:$ulb_id",
            json_encode($OccuPencyFacter)
        );
        $redis->expire("AllCircleRate:$ulb_id", 18000);
    }

    /**
     * | query for save ulb and role on user login
     */
    public function query($id)
    {
        $query = "SELECT 
        u.id,
        u.user_name AS NAME,
        u.mobile AS mobile,
        u.email AS email,
        u.ulb_id,
        um.ulb_name
            FROM users u 
            
            LEFT JOIN ulb_masters um ON um.id=u.ulb_id
            WHERE u.id=$id";
        return $query;
    }
}
