<?php

namespace App\BLL;

use App\Models\MirrorUserNotification;
use App\Models\UserNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * | Add Notification
 * | Created By-Mrinal Kumar
 * | Created On-08-06-2023 
 * | Status: Open
 */

class AddNotification
{
    public function notificationAddition($req, $notificationId)
    {
        $mMirrorUserNotification = new MirrorUserNotification();
        $mUserNotification = new UserNotification();

        if ($req['citizen_id']) {
            $userMirrorNotifications = $mMirrorUserNotification->mirrorNotification()
                ->where('citizen_id', $req['citizen_id'])
                ->get();
        } else
            $userMirrorNotifications = $mMirrorUserNotification->mirrorNotification()
                ->where('user_id', $req['user_id'])
                ->get();

        if ($userMirrorNotifications->isEmpty()) {
            $this->addMirrorNotification($req, $notificationId);
        } else
            $userNotifications = $mUserNotification->userNotification()
                ->where('citizen_id', $req['citizen_id'])
                ->take(10)
                ->get();

        // $flag = 1;
        // foreach ($userMirrorNotifications as $userMirrorNotification) {
        //     $id = $userMirrorNotification->id;
        // }
        // foreach ($userNotifications as $userNotification) {

        //     $changeStatus = 0;
        //     if ($id != null) {
        //         $this->updateMirrorNotification($userNotification, $notificationId, $id);
        //         $changeStatus = 1;
        //         break;
        //     }
        // }
        // if ($changeStatus == 0) {
        //     $flag = 0;
        // }

        // if ($flag == 0)
        //     $this->addMirrorNotification($req, $notificationId);

        $indexedData = [];
        foreach ($userNotifications as $item) {
            $indexedData[$item['id']] = $item;
        }

        // Update the original data with values from the new data
        foreach ($userMirrorNotifications as $item) {
            $id = $item['id'];
            if (isset($id)) {
                $indexedData[$id] = $this->updateMirrorNotification($item, $notificationId, $id);
            } else {
                $indexedData[$id] = $this->addMirrorNotification($req, $notificationId);;
            }
        }
    }

    /**
     * | Add Mirror Notification
     */
    public function addMirrorNotification($req, $notificationId)
    {
        $mMirrorUserNotification = new MirrorUserNotification();
        $mreq = new Request([
            "user_id" => $req->user_id,
            "citizen_id" => $req->citizen_id,
            "notification" => $req->notification,
            "send_by" => $req->send_by,
            "category" => $req->category,
            "sender_id" => $req->user_id,
            "ulb_id" => $req->ulb_id,
            "module_id" => $req->module_id,
            "event_id" => $req->event_id,
            "generation_time" => Carbon::now(),
            "ephameral" => $req->ephameral,
            "require_acknowledgment" => $req->require_acknowledgment,
            "expected_delivery_time" => $req->expected_delivery_time,
            "created_at" => Carbon::now(),
            "notification_id" => $notificationId,
        ]);
        $mMirrorUserNotification->addNotification($mreq);
    }

    /**
     * | Update Mirror Notification
     */
    public function updateMirrorNotification($req, $notificationId, $id)
    {
        $mMirrorUserNotification = new MirrorUserNotification();
        $mreq = new Request([
            "user_id" => $req->user_id,
            "citizen_id" => $req->citizen_id,
            "notification" => $req->notification,
            "send_by" => $req->send_by,
            "category" => $req->category,
            "sender_id" => $req->user_id,
            "ulb_id" => $req->ulb_id,
            "module_id" => $req->module_id,
            "event_id" => $req->event_id,
            "generation_time" => Carbon::now(),
            "ephameral" => $req->ephameral,
            "require_acknowledgment" => $req->require_acknowledgment,
            "expected_delivery_time" => $req->expected_delivery_time,
            "created_at" => Carbon::now(),
            "notification_id" => $notificationId,
        ]);
        $mMirrorUserNotification->editNotification($mreq, $id);
    }
}
