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
            'topLevel' => 'required|integer',
            'subLevel' => 'required|integer',
            'parentSerial' => 'required|integer',
            'serial' => 'required|integer',
        ]);
        $menuMaster = new MenuMaster();
        return $menuMaster->addNewMenues($request);
    }
}
