<?php

namespace App\Http\Controllers\Menupermission;

use App\Http\Controllers\Controller;
use App\Repository\MenuPermission\Concrete\EloquentMenuGroups;
use App\Repository\MenuPermission\Interface\iMenuGroupsRepository;
use Illuminate\Http\Request;

class MenuGroupsController extends Controller
{
    //
    protected $a;
    public function __construct(iMenuGroupsRepository $a)
    {
        $this->EUlb = $a;
    }

    //get data
    public function getAllMenuGroups()
    {
        return $this->EUlb->view();
    }

    //add data in table
    function addMenuGroups(Request $request)
    {
        return $this->EUlb->add($request);
    }

    //updating details in table
    function updateMenuGroups(Request $request, $id)
    {
        return $this->EUlb->update($request, $id);
    }

    //delete the data in table
    function deleteMenuGroups($id)
    {
        return $this->EUlb->delete($id);
    }
}
