<?php

namespace App\Http\Controllers\Menupermission;

use App\Http\Controllers\Controller;
use App\Repository\MenuPermission\Concrete\EloquentMenuMap;
use App\Repository\MenuPermission\Interface\iMenuMapRepository;
use Illuminate\Http\Request;

class MenuMapController extends Controller
{
    //
    protected $a;
    public function __construct(iMenuMapRepository $a)
    {
        $this->EUlb = $a;
    }

    //get data
    public function getMenuMap($id)
    {
        return $this->EUlb->view($id);
    }

    //add data in table
    function addMenuMap(Request $request)
    {
        return $this->EUlb->add($request);
    }

    //updating details in table
    function updateMenuMap(Request $request, $id)
    {
        return $this->EUlb->update($request, $id);
    }

    //delete the data in table
    function deleteMenuMap($id)
    {
        return $this->EUlb->delete($id);
    }
}
