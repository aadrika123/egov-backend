<?php

namespace App\Repository\MenuPermission\Interface;
use Illuminate\Http\Request;

interface IMenuMapRepository
{ 
    //error
    public function view($id);
    public function add(Request $request);
    public function update(Request $request, $id);
    public function delete($id);

}