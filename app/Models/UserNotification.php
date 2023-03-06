<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    use HasFactory;
    protected $updated_at = false;

    /**
     * | Get notification of logged in user
     */
    public function notificationByUserId($userId, $ulbId)
    {
        return UserNotification::where('user_id', $userId)
            ->where('ulb_id', $ulbId)
            ->where('status', 1)
            ->orderByDesc('id')
            ->get();
    }

    /**
     * | Add Notifications 
     */
    public function addNotification($userId, $ulbId, $req)
    {
        $notification = new UserNotification();
        $notification->user_id = $req->userId;
        $notification->user_type = $req->userType;
        $notification->notification = $req->notification;
        $notification->send_by = $req->sender;
        $notification->sender_id = $userId ?? NULL;
        $notification->ulb_id = $ulbId;
        $notification->save();
    }

    /**
     * | deactivate Notifications 
     */
    public function deactivateNotification($req)
    {
        $notification = UserNotification::find($req->id);
        $notification->status = 0;
        $notification->save();
    }
}
