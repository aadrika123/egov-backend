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
        $user->password = Hash::make($request->password);
        $user->user_type = $request->userType;
        $user->ulb_id = $request->ulb;
        $user->roll_id = $request->role;
        $user->description = $request->description;
        $user->workflow_participant = $request->workflowParticipant;
        $token = Str::random(80);                       //Generating Random Token for Initial
        $user->remember_token = $token;
    }

    /**
     * Saving Extra User Credentials
     */

    public function savingExtras($user, $request)
    {
        $user->suspended = $request->Suspended;
        $user->super_user = $request->SuperUser;
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
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'remember_token' => $token,
                'user_type' => $emailInfo->user_type,
                'roll_id' => $emailInfo->roll_id,
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

    public function Workflow_candidate($redis,$user_id ,$Workflow_candidate)
    {
        // dd($user_id);die;
        $redis->set(
                'workflow_candidate:'.$user_id ,
                json_encode([
                    'id' => $Workflow_candidate->id,
                    'module_id' => $Workflow_candidate->module_id,
                ])
            );
    }
     
}
