<?php

namespace App\Http\Controllers\Menupermission;

use App\Http\Controllers\Controller;
use App\Repository\MenuPermission\Concrete\EloquentMenuUlbroles;
use App\Repository\MenuPermission\Interface\IMenuUlbrolesRepository;
use Illuminate\Http\Request;

class MenuUlbrolesController extends Controller
{
    //
    /*CRUD operations
    date:
    opeartions:
    model:
    methods:
*/
    protected $a;
    public function __construct(IMenuUlbrolesRepository $a)
    {
        $this->EUlb = $a;
    }

    //get data
    public function getMenuUlbroles()
    {
        return $this->EUlb->view();
    }

    //add data in table
    function addMenuUlbroles(Request $request)
    {
        return $this->EUlb->add($request);
    }

    //updating details in table
    function updateMenuUlbroles(Request $request, $id)
    {
        return $this->EUlb->update($request, $id);
    }

    //delete the data in table
    function deleteMenuUlbroles($id)
    {
        return $this->EUlb->delete($id);
    }
}
