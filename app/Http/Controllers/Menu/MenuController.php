<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Models\Menu\MenuMaster;
use App\Repository\Menu\Interface\iMenuRepo;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    /**
     * | Created On-23-11-2022 
     * | Created By-Anshu Kumar
     * | Updated By-Sam Kerketta
     * | Created for the Menus Operations
     */
    protected $_repo;
    public function __construct(iMenuRepo $repo)
    {
        $this->_repo = $repo;
    }

    // Get All menues
    public function getAllMenues()
    {
        return $this->_repo->getAllMenues();
    }

    // Get All the Menu By roles
    public function getMenuByRoles(Request $req)
    {
        return $this->_repo->getMenuByRoles($req);
    }

    // Enable or Disable Menu By Role
    public function updateMenuByRole(Request $req)
    {
        $req->validate([
            'roleId' => 'required|integer',
            'menuId' => 'required|integer',
            'status' => 'required|bool'
        ]);
        return $this->_repo->updateMenuByRole($req);
    }

    // adding new menu in menu master
    public function addNewMenues(Request $request)
    {
        $request->validate([
            'menuName' => 'required',
            'route' => 'required|unique:menu_masters,route',
        ]);
        return $this->_repo->addNewMenues($request);
    }

    // Getting userRole wise menus
    public function getRoleWiseMenu(Request $request)
    {
        return $this->_repo->getRoleWiseMenu($request);
    }

    // Soft Delition of the Menu in Menu Master
    public function deleteMenuesDetails(Request $request)
    {
        return $this->_repo->deleteMenuesDetails($request);
    }

    // Generate the menu tree srtucture
    public function getTreeStructureMenu(Request $request)
    {
        return $this->_repo->generateMenuTree($request);
    }
}
