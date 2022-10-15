<?php

namespace App\Repository\MenuPermission\Interface;
use Illuminate\Http\Request;

interface iMenuGroupsRepository
{
    public function view();
    public function add(Request $request);
    public function update(Request $request, $id);
    public function delete($id);
}