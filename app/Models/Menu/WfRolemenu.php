<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WfRolemenu extends Model
{
    use HasFactory;

    // Get All Menus by Role Id
    public function getMenues($req)
    {
        return WfRolemenu::where('role_id', $req->roleId)
            ->where('menu_id', $req->menuId)
            ->first();
    }
}
