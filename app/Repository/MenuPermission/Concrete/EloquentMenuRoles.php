<?php

namespace App\Repository\MenuPermission\Concrete;


use App\Repository\MenuPermission\interface\IMenuRolesRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\MenuPermission\MenuRoles;
use Exception;


class EloquentMenuRoles implements IMenuRolesRepository
{
    public function view()
    {
        $data = MenuRoles::get();
        return response()->json(["data" => $data]);
    }
    //add data
    function add(Request $request)
    {
        $validateUser = Validator::make(
            $request->all(),
            [
                'role_name' => 'required',
                'suspended' => 'required'
            ]
        );

        if ($validateUser->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'validation error',
                'errors' => $validateUser->errors()
            ], 401);
        }

        //data collection in variable "$device"
        $device = new MenuRoles();
        //operation
        $device->role_name   = $request->role_name;
        $device->suspended   = $request->suspended;
        //data saved
        $result = $device->save();
        if ($result) {
            return ["Result" => "data has been saved"];
        }
        return ["Result" => "data not saved"];
    }

    //updating details in table
    function update(Request $request, $id)
    {
        //data collection in variable "$register"
        $register = MenuRoles::find($id);
        //operation
        $register->role_name   = $request->role_name;
        $register->suspended   = $request->suspended;
        //data saved
        $register->save();
        if ($register) {
            return ["Result" => "data is updated"];
        }
        return ["Result" => "not updated"];
    }

    //delete the data in table
    function delete($id)
    {
        $device = MenuRoles::find($id);
        $result = $device->delete();
        if ($result) {
            return ["result" => "deleted"];
        }
        return ["result" => "not deleated"];
    }
    
}