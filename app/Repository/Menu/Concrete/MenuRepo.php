<?php

namespace App\Repository\Menu\Concrete;

use App\Models\Menu\MenuMaster;
use App\Repository\Menu\Interface\iMenuRepo;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * | Created On-23-11-2022 
 * | Created By-Anshu Kumar
 * | Repository for the Menu Permission
 */

class MenuRepo implements iMenuRepo
{
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
     */
    public function getMenuByRoles($req)
    {
        try {
            $query = "SELECT 
                            m.id AS menu_id,
                            m.serial,
                            m.description, 
                            m.menu_string,
                            m.parent_serial,
                            r.role_id,
                            r.menu_id,
                            (CASE 
                                WHEN r.role_id IS NOT NULL THEN TRUE 
                                ELSE 
                                FALSE
                            END) AS permission_status
                            FROM menu_masters AS m
                    
                    LEFT JOIN (SELECT * FROM wf_rolemenus WHERE role_id=$req->roleId) AS r ON r.menu_id=m.id";
            $menues = DB::select($query);
            return responseMsg(true, "Permission Menues", remove_null($menues));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
}
