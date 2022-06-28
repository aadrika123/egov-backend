<?php

namespace App\Traits\Role;

trait UserRole
{
    static public function userRoleSuccess()
    {
        return response()->json(['Status' => 'Successfully Saved'], 201);
    }

    static public function failure()
    {
        return response()->json(['Status' => false, 'Message' => 'Role Already Existing For this User'], 400);
    }
}
