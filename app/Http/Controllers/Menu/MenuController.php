<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Models\Menu\MenuMaster;
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

    // Get All menues
    public function getAllMenues()
    {
        try {
            $menuMaster = new MenuMaster();
            $menues = $menuMaster->fetchAllMenues();
            return responseMsgs(true, "List of Menues!", $menues, "", "02", "", "GET", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // Get All the Menu By roles
    public function getMenuByRoles(Request $req)
    {
        try {
            return $this->_repo->getMenuByRoles($req);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // Enable or Disable Menu By Role
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

    // adding new menu in menu master
    public function addNewMenues(Request $request)
    {
        try {
            $request->validate([
                'menuName' => 'required',
                'route' => 'required|unique:menu_masters,route',
                'parentSerial' => 'required',
            ]);
            $menuMaster = new MenuMaster();
            $menuMaster->putNewMenues($request);
            return responseMsg(true, "Data Saved!", "");
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


    // List the Menu Parent
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
}
