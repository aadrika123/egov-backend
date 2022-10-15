<?php

namespace App\Repository\MenuPermission\Concrete;


use App\Repository\MenuPermission\Interface\iMenuGroupsRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\MenuPermission\MenuGroups;


use Exception;


class EloquentMenuGroups implements iMenuGroupsRepository
{
    public function view()
    {    
        $data = MenuGroups::get();
        return response()->json(["data" => $data]);
    }
    //add data
    function add(Request $request)
    {
        $validateUser = Validator::make(
            $request->all(),
            [
                'group_name'=>'required',                             
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
        $device = new MenuGroups();
        //operation
        $device->group_name = $request->group_name;
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
        $register = MenuGroups::find($id);
        //operation
        $register->group_name = $request->group_name;
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
        $device = MenuGroups::find($id);
        $result = $device->delete();
        if ($result) {
            return ["result" => "deleted"];
        }
        return ["result" => "not deleated"];
    }
    
}