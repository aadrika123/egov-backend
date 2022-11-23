<?php

namespace App\Repository\Menu\Concrete;

use App\Models\Menu\MenuMaster;
use App\Models\Menu\WfRolemenu;
use App\Repository\Menu\Interface\iMenuRepo;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * | Created On-23-11-2022 
 * | Created By-Anshu Kumar
 * | Repository for the Menu Permission
 */

class MenuRepo implements iMenuRepo
{
    private $_redis;
    public function __construct()
    {
        $this->_redis = Redis::connection();
    }
    /**
     * | Get All the Menues
     */
    public function getAllMenues()
    {
        try {
            $menues = MenuMaster::orderByDesc('id')
                ->get();
            return responseMsg(true, "Menu Masters", remove_null($menues));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Get All the Menu By Roles
     * | @param req
     * | Query Run Time - 343ms 
     * | status-Closed
     * | rating-2
     */
    public function getMenuByRoles($req)
    {
        try {
            $menues = json_decode(Redis::get('menu-by-role-' . $req->roleId));
            if (!$menues) {
                $query = "SELECT 
                            m.id AS menu_id,
                            m.serial,
                            m.description, 
                            m.menu_string,
                            m.parent_serial,
                            r.role_id,
                            (CASE 
                                WHEN r.role_id IS NOT NULL THEN TRUE 
                                ELSE 
                                FALSE
                            END) AS permission_status
                            FROM menu_masters AS m
                    
                    LEFT JOIN (SELECT * FROM wf_rolemenus WHERE role_id=$req->roleId AND status=1) AS r ON r.menu_id=m.id";
                $menues = DB::select($query);
                $this->_redis->set('menu-by-role-' . $req->roleId, json_encode($menues));               // Caching the data should be flush while adding new menu to the role
            }
            return responseMsg(true, "Permission Menues", remove_null($menues));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    /**
     * | update role menues
     * | @param request $req
     * | Query Run Time=366 ms 
     * | Status-Closed 
     * | Rating-2
     */
    public function updateMenuByRole($req)
    {
        try {
            Redis::del('menu-by-role-' . $req->roleId);                                 // Flush Key of the User Role Permission

            $roleMenus = WfRolemenu::where('role_id', $req->roleId)
                ->where('menu_id', $req->menuId)
                ->first();

            if ($roleMenus) {                                                           // If Data Already Existing
                switch ($req->status) {
                    case 1;
                        $roleMenus->status = 1;
                        $roleMenus->save();
                        return responseMsg(true, "Successfully Enabled the Menu Permission for the Role", "");
                        break;
                    case 0;
                        $roleMenus->status = 0;
                        $roleMenus->save();
                        return responseMsg(true, "Successfully Disabled the Menu Permission for the Role", "");
                        break;
                }
            }

            $roleMenus = new WfRolemenu();
            $roleMenus->role_id = $req->roleId;
            $roleMenus->menu_id = $req->menuId;
            $roleMenus->save();

            return responseMsg(true, "Successfully Enabled the Menu Permission for the Role", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
}
