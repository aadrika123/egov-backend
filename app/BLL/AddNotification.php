<?php

namespace App\BLL;

use App\Models\MirrorUserNotification;
use App\Models\UserNotification;

/**
 * | Add Notification
 * | Created By-Mrinal Kumar
 * | Created On-08-06-2023 
 * | Status: Open
 */

class AddNotification
{
    public function notificationAddition($req)
    {
        $mMirrorUserNotification = new MirrorUserNotification();
        $mUserNotification = new UserNotification();

        if ($req->citizenId) {
            $userMirrorNotification = $mMirrorUserNotification->mirrorNotification()
                ->where('citizen_id', $req->citizenId)
                ->get();
        } else
            $userMirrorNotification = $mMirrorUserNotification->mirrorNotification()
                ->where('user_id', $req->userId)
                ->get();

        // if($userMirrorNotification->isEmpty())

    }
}
