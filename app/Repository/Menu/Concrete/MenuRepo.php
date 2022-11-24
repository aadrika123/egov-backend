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
            $menuMaster = new MenuMaster();
            $menues = $menuMaster->fetchAllMenues();
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
            $roleMenus = new WfRolemenu();
            Redis::del('menu-by-role-' . $req->roleId);                                 // Flush Key of the User Role Permission

            $readRoleMenus = $roleMenus->getMenues($req);

            if ($readRoleMenus) {                                                           // If Data Already Existing
                switch ($req->status) {
                    case 1;
                        $readRoleMenus->status = 1;
                        $readRoleMenus->save();
                        return responseMsg(true, "Successfully Enabled the Menu Permission for the Role", "");
                        break;
                    case 0;
                        $readRoleMenus->status = 0;
                        $readRoleMenus->save();
                        return responseMsg(true, "Successfully Disabled the Menu Permission for the Role", "");
                        break;
                }
            }


            $roleMenus->role_id = $req->roleId;
            $roleMenus->menu_id = $req->menuId;
            $roleMenus->save();

            return responseMsg(true, "Successfully Enabled the Menu Permission for the Role", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | user->roles->menu getting userRole wise menues
     * | @param request 
     * | Query Run Time = 328ms 
     * | Status- open
     * | Rating-3
     */
    public function getRoleWiseMenu()
    {
        try {
            $userId=auth()->user()->id;
            $menuDetails = WfRolemenu::join('wf_roleusermaps','wf_roleusermaps.wf_role_id','=','wf_rolemenus.role_id')
            ->join('menu_masters','menu_masters.id','=','wf_rolemenus.menu_id')
            ->where('wf_roleusermaps.user_id',$userId)
            ->select(
                'menu_masters.menu_string AS menuName',
                'menu_masters.route',
            )
            ->get();
            if(!empty($menuDetails['0'])){
            return responseMsg(true,"Data according to roles",$menuDetails);
            }
            return responseMsg(false,"Data not Found!","");
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!",$error->getMessage());
        }
    }
}
