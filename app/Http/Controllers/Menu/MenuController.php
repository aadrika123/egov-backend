<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Models\Menu\MenuMaster;
use App\Repository\Menu\Concrete\MenuRepo;
use App\Repository\Menu\Interface\iMenuRepo;
use Exception;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    /**
     * | Created On-23-11-2022 
     * | Created By-Anshu Kumar
     * | Updated By-Sam Kerketta
     * | Created for the Menus Operations
     * | Status : Open
     */

    protected $_repo;
    public function __construct(iMenuRepo $repo)
    {
        $this->_repo = $repo;
    }

    /**
     * |--------------------- Get the list of menues that are child Nodes ---------------------|
     * | @param 
     * | @var mMenuMaster model
     * | @var refmenues get menu list
     * | @var menues shorted menues
     * | @var listedMenues collecting the menu Parent
     * | @var value final List of menues
     * | @return listedMenues returning values
        | Serial No : 01
        | 
     */
    public function getAllMenues()
    {
        try {
            $mMenuMaster = new MenuMaster();
            $refmenues = $mMenuMaster->fetchAllMenues();
            $menues = $refmenues->sortByDesc("id");
            $listedMenues = collect($menues)->map(function ($value, $key) use ($mMenuMaster) {
                if ($value['parent_serial'] != 0) {
                    $parent = $mMenuMaster->getMenuById($value['parent_serial']);
                    $parentName = $parent['menu_string'];
                    $value['parentName'] = $parentName;
                    return $value;
                }
                return $value;
            })->values();
            return responseMsgs(true, "List of Menues!", $listedMenues, "", "02", "", "GET", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    /**
     * |--------------------- Get Menu according to Roles ---------------------|
     * | @param req roleId
        | Serial No : 02
     */
    public function getMenuByRoles(Request $req)
    {
        try {
            return $this->_repo->getMenuByRoles($req);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    /**
     * |--------------------- Enable or Desable the menu for roles ---------------------|
     * | @param req
        | Serial No : 03
     */
    public function updateMenuByRole(Request $req)
    {
        try {
            $req->validate([
                'roleId' => 'required|integer',
                'menuId' => 'required|integer',
                'status' => 'required|bool'
            ]);
            return $this->_repo->updateMenuByRole($req);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    /**
     * |--------------------- Adding new Menu ---------------------|
     * | @param request menuName,Route
     * | @var mMenuMaster Model
        | Serial NO : 04
     */
    public function addNewMenues(Request $request)
    {
        try {
            $request->validate([
                'menuName'      => 'required',
                'route'         => 'required',
            ]);
            $mMenuMaster = new MenuMaster();
            $mMenuMaster->putNewMenues($request);
            return responseMsgs(true, "Data Saved!", "", "", "02", "", "POST", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // Getting userRole wise menus
    public function getRoleWiseMenu(Request $request)
    {
        try {
            return $this->_repo->getRoleWiseMenu($request);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // Soft Delition of the Menu in Menu Master
    public function deleteMenuesDetails(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required'
            ]);
            $menuDeletion = new MenuMaster();
            $menuDeletion->softDeleteMenues($request->id);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // Generate the menu tree srtucture
    public function getTreeStructureMenu(Request $request)
    {
        try {
            return $this->_repo->generateMenuTree($request);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // List all the Parent Menu
    public function listParentSerial()
    {
        try {
            $mMenuMaster = new MenuMaster();
            $parentMenu = $mMenuMaster->getParentMenue()->get();
            return responseMsgs(true, "parent Menu!", $parentMenu, "", "", "", "POST", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // git the child of the menu 
    public function getChildrenNode(Request $request)
    {
        try {
            $mMenuMaster = new MenuMaster();
            $listedChild = $mMenuMaster->getChildrenNode($request->id)->get();
            return responseMsgs(true, "child Menu!", $listedChild, "", "", "", "POST", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // Upload menu master 
    public function updateMenuMaster(Request $request)
    {
        $request->validate([
            'id' => 'required'
        ]);
        try {
            $mMenuMaster = new MenuMaster();
            $mMenuMaster->updateMenuMaster($request);
            return responseMsgs(true, "Menu Updated!", "", "", "", "", "POST", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * |--------------------- Get menu by Menu Id ---------------------|
     * | @param request menuId
     * | @var mMenuMaster model
     * | @var menues menu list
     * | @var parent list of parent 
     * | @var parentName collect the name of the parent node 
     * | @return menues list of menu according to menu id
        | Serial No :
        | Open
     */
    public function getMenuById(Request $request)
    {
        $request->validate([
            'menuId' => 'required|int'
        ]);
        try {
            $mMenuMaster = new MenuMaster();
            $menues = $mMenuMaster->getMenuById($request->menuId);
            if ($menues['parent_serial'] == 0) {
                return responseMsgs(true, "Menu List!", $menues, "", "01", "", "POST", "");
            }
            $parent = $mMenuMaster->getMenuById($menues['parent_serial']);
            $parentName = $parent['menu_string'];
            $menues['parentName'] = $parentName;
            return responseMsgs(true, "Menu List!", $menues, "", "01", "", "POST", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
}
