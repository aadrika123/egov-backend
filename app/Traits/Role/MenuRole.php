<?php

namespace App\Traits\Role;

/**
 * Created By-Anshu Kumar
 * Created On-28-06-2022 
 * Purpose giving Static msg by Checking if The Menu already Existing or Not
 */

trait MenuRole
{
    static public function success()
    {
        return response()->json(['Status' => true, 'Message' => 'Successfully Saved'], 200);
    }

    static public function falseRoleMenuLog()
    {
        return response()->json(['Status' => false, 'Message' => 'Menu Already Existing For this Role'], 400);
    }
}
