<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
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
}
