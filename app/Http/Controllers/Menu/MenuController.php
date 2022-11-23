<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Repository\Menu\Interface\iMenuRepo;

class MenuController extends Controller
{
    protected $_repo;
    public function __construct(iMenuRepo $repo)
    {
        $this->_repo = $repo;
    }
    /**
     * | Created On-23-11-2022 
     * | Created By-Anshu Kumar
     * | Created for the Menus Operations
     */
    public function getAllMenues()
    {
        return $this->_repo->getAllMenues();
    }
}
