<?php

namespace App\Repository\Menu\Concrete;

use App\Models\Menu\MenuMaster;
use App\Models\Menu\WfRolemenu;
use App\Repository\Menu\Interface\iMenuRepo;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Razorpay\Api\Request;
use stdClass;

use function PHPUnit\Framework\isJson;

/**
 * | Created On-23-11-2022 
 * | Created By-Anshu Kumar
 * | Updated By-Sam Kerketta
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
     * |------------------------------- fetching the details of Menues from table  ---------------------------------|
     * | @var menuMaster/ Obj
     * | @var menues
        | Serial No : 01
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
     * |-------------------------------------------- Get All the Menu By Roles ---------------------------------------------------------|
     * | @param req
     * | @var query
     * | @var menues
     * | Query Time - 343ms 
     * | status-Closed
     * | rating-2
        |  Serial No : 02
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
                            (CASE 
                                WHEN r.role_id IS NOT NULL THEN TRUE 
                                ELSE 
                                FALSE
                            END) AS permission_status
                            FROM menu_masters AS m
                    
                    LEFT JOIN (SELECT * FROM wf_rolemenus WHERE role_id=$req->roleId AND status=1) AS r ON r.menu_id=m.id";
            $menues = DB::select($query);
            $this->_redis->set('menu-by-role-' . $req->roleId, json_encode($menues));               // Caching the data should be flush while adding new menu to the role

            return responseMsg(true, "Permission Menues", remove_null($menues));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    /**
     * |------------------------------------------ update role menues ------------------------------------------------------|
     * | @param req
     * | @var roleMenus / Obj
     * | @var readRoleMenus
     * | Query Time - 366 ms 
     * | Status-Closed 
     * | Rating-2
        |  Serial No : 03
     */
    public function updateMenuByRole($req)
    {
        try {
            $roleMenus = new WfRolemenu();                               // Flush Key of the User Role Permission

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
     * |------------------------------------------- user->roles->menu getting userRole wise menues ----------------------------------------------------------|
     * | @param request 
     * | Query Time = 328ms 
     * | Status- Closed
     * | Rating- 2 
        | Serial No : 04
     */
    public function getRoleWiseMenu()
    {
        try {
            $userId = auth()->user()->id;
            $menuDetails = WfRolemenu::select(
                'menu_masters.menu_string AS menuName',
                'menu_masters.route',
            )
                ->join('wf_roleusermaps', 'wf_roleusermaps.wf_role_id', '=', 'wf_rolemenus.role_id')
                ->join('menu_masters', 'menu_masters.id', '=', 'wf_rolemenus.menu_id')
                ->join('wf_roles', 'wf_roles', '=', 'wf_rolemenus.role_id')
                ->where('wf_roleusermaps.user_id', $userId)
                ->where('wf_rolemenus.is_suspended', false)
                ->where('wf_roleusermaps.is_suspended', false)
                ->get();

            if (!empty($menuDetails['0'])) {
                return responseMsg(true, "Data according to roles", $menuDetails);
            }
            return responseMsg(false, "Data not Found!", "");
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }


    /**
     * |---------------------- Algorithem for the generation of the menu  paren/childeran structure -------------------|
     * | @param req
     * | @var menuMaster / Obj
     * | @var menues
     * | @var data
     * | @var itemsByReference
     * | @var item
     * | Query Time = 308ms 
     * | Rating- 4
     * | Status- Closed
        | Serial No : 05  
     */
    public function generateMenuTree($req)
    {
        try {

            $menuMaster = new MenuMaster();
            $menues = $menuMaster->fetchAllMenues();

            $data = collect($menues)->map(function ($value, $key) {
                $return = array();
                $return['id'] = $value['id'];
                $return['parentId'] = $value['parent_serial'];
                $return['name'] = $value['menu_string'];
                $return['children'] = array();
                return ($return);
            });

            $data = (objToArray($data));

            $itemsByReference = array();

            foreach ($data as $key => &$item) {
                $itemsByReference[$item['id']] = &$item;
            }

            # looping for the generation of child nodes / operation will end if the parentId is not match to id 
            foreach ($data as $key => &$item)
                if ($item['id'] && isset($itemsByReference[$item['parentId']]))
                    $itemsByReference[$item['parentId']]['children'][] = &$item;

            # this loop is to remove the external loop of the child node ie. not allowing the child node to create its own treee
            foreach ($data as $key => &$item) {
                if ($item['parentId'] && isset($itemsByReference[$item['parentId']]))
                    unset($data[$key]);
            }
            $data = collect($data)->values();
            return responseMsgs(true, "OPERATION OK!", $data, "", "01", "308.ms", "POST", $req->deviceId);
        } catch (Exception $error) {
            return responseMsgs(false, $error->getMessage(), $error->getLine(), "", "01", ".ms", "POST", $req->deviceId);
        }
    }
}
