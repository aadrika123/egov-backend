<?php

namespace App\Http\Controllers\Menupermission;

use App\Http\Controllers\Controller;
use App\Repository\MenuPermission\Concrete\EloquentMenuRoles;
use Illuminate\Http\Request;

class MenuRolesController extends Controller
{
    // protected $a;
    public function __construct(EloquentMenuRoles $a)
    {
        $this->EUlb = $a;
    }

    //get data
    public function getMenuRoles()
    {
        return $this->EUlb->view();
    }

    //add data in table
    function addMenuRoles(Request $request)
    {
        return $this->EUlb->add($request);
    }

    //updating details in table
    function updateMenuRoles(Request $request, $id)
    {
        return $this->EUlb->update($request, $id);
    }

    //delete the data in table
    function deleteMenuRoles($id)
    {
        return $this->EUlb->delete($id);
    }
}
