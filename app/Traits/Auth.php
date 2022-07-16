<?php

namespace App\Traits;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Trait for saving and editing the Users 
 * Created for reducing the duplication for the Saving and editing codes
 * --------------------------------------------------------------------------------------------------------
 * Created by-Anshu Kumar
 * Created On-16-07-2022 
 * --------------------------------------------------------------------------------------------------------
 */

trait Auth
{
    public function saving($user, $request)
    {
        $user->user_name = $request->Name;
        $user->mobile = $request->Mobile;
        $user->email = $request->email;
        $user->password = Hash::make($request->Password);
        $user->user_type = $request->UserType;
        $user->ulb_id = $request->Ulb;
        $user->roll_id = $request->Role;
        $user->description = $request->Description;
        $user->workflow_participant = $request->WorkflowParticipant;
        $token = Str::random(80);                       //Generating Random Token for Initial
        $user->remember_token = $token;
    }

    public function savingExtras($user, $request)
    {
        $user->suspended = $request->Suspended;
        $user->super_user = $request->SuperUser;
    }
}
