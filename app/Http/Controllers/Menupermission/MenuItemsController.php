<?php

namespace App\Http\Controllers\Menupermission;

use App\Http\Controllers\Controller;
use App\Repository\MenuPermission\Concrete\EloquentMenuItems;
use App\Repository\MenuPermission\Interface\iMenuItemsRepository;
use Illuminate\Http\Request;

class MenuItemsController extends Controller
{
    //defining a constructer as ($a) 
    protected $a;
    public function __construct(iMenuItemsRepository $a)
    {
        $this->EUlb = $a;
    }

    //get data
    public function getMenuItems()
    {
        return $this->EUlb->view();
    }

    //add data in table
    function addMenuItems(Request $request)
    {
        return $this->EUlb->add($request);
    }

    //updating details in table
    function updateMenuItems(Request $request, $id)
    {
        return $this->EUlb->update($request, $id);
    }

    //delete the data in table
    function deleteMenuItems($id)
    {
        return $this->EUlb->delete($id);
    }

    //data of join
    function menuGroupWiseItems(Request $request)
    {
        return $this->EUlb->menuGroupWiseItems($request);
    }
    //data of the second join2
    function menuGroupAndRoleWiseItems(Request $request)
    {
        return $this->EUlb->menuGroupAndRoleWiseItems($request);
    }
    //data of join 3
    function ulbWiseMenuRole(Request $request)
    {
        return $this->EUlb->ulbWiseMenuRole($request);
    }
    //data of join
    //  function allmenuitems()
    //  {
    //     return $this->EUlb->allmenuitems();
    //  }
    //   //data of join
    //   function listmenugroups()
    //   {
    //      return $this->EUlb->listmenugroups();
    //   }
}
