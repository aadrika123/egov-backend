<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UserNotification extends Model
{
    use HasFactory;
    protected $updated_at = false;
    protected $guarded = [];
    protected $connection = 'pgsql_master';

    /**
     * | Get notification of logged in user
     */
    public function userNotification()
    {
        return UserNotification::select('*', DB::raw("Replace(category, ' ', '_') AS category"))
            ->where('status', 1)
            ->orderByDesc('id');
    }

    /**
     * | Add Notifications 
     */
    public function addNotification($req)
    {
        $req = $req->toarray();
        $notification =  UserNotification::create($req);
        return $notification->id;
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
