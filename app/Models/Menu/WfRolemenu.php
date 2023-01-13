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

    /**
     * | Get menu By RoleId 
     */
    public function getMenuDetailsByRoleId($roleIds)
    {
        return WfRolemenu::join('menu_masters', 'menu_masters.id', '=', 'wf_rolemenus.menu_id')
            ->where('wf_rolemenus.role_id', $roleIds)
            ->where('wf_rolemenus.status', 1)
            ->select(
                'menu_masters.menu_string AS menuName',
                'menu_masters.route AS menuPath',
            )
            ->orderByDesc('menu_masters.id')
            ->get();
    }
}
