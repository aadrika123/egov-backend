<?php

namespace App\Repository\MenuPermission\Interface;

use Illuminate\Http\Request;

interface iMenuItemsRepository
{
    public function view();
    public function add(Request $request);
    public function update(Request $request, $id);
    public function delete($id);
    //////////////////////////////////////////////////
    public function listmenuitembygroupid(Request $request);
    public function menuGroupWiseItems(Request $request);
    public function menuGroupAndRoleWiseItems(Request $request);
    public function ulbWiseMenuRole(Request $request);
}
