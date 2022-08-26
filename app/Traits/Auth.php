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
        if ($request->role) {
            $user->roll_id = $request->role;
        }
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
    public function redisStore($redis, $emailInfo, $request, $token, $ulb_role)
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
                'role_id' => $emailInfo->roll_id,
                'role_name' => $ulb_role[0]->role_name,
                'ulb_id' => $emailInfo->ulb_id,
                'ulb_name' => $ulb_role[0]->ulb_name,
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

    public function WorkFlowRolesSet($redis, $user_id, array $workflow_rolse)
    {
        $redis->set(
            'WorkFlowRoles:' . $user_id,
            json_encode($workflow_rolse)
        );
        $redis->expire('WorkFlowRoles:' . $user_id, 18000);
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
        u.roll_id AS role_id,
        r.role_name,
        u.ulb_id,
        um.ulb_name
            FROM users u 
            
            LEFT JOIN ulb_masters um ON um.id=u.ulb_id
            LEFT JOIN role_masters r ON r.id=u.roll_id
            WHERE u.id=$id";
        return $query;
    }
}
