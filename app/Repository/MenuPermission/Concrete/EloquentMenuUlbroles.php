<?php

namespace App\Repository\MenuPermission\Concrete;


use App\Repository\MenuPermission\Interface\IMenuUlbrolesRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\MenuPermission\MenuUlbroles;
use Exception;


class EloquentMenuUlbroles implements IMenuUlbrolesRepository
{
    public function view()
    {
        $data = MenuUlbroles::get();
        return response()->json(["data" => $data]);
    }
    //add data
    function add(Request $request)
    {
       //validation
        $validateUser = Validator::make(
            $request->all(),
            [
                'ulb_id' => 'required',
                'menu_roleid' => 'required',
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
        try {
                $checkExisting = MenuUlbroles::where('ulb_id', $request->ulb_id)
                    ->first();
                if ($checkExisting) {
                    return response(false, "data already exist");
                }
                // Add the Data
                $roleUser = new MenuUlbroles();
                $roleUser->ulb_id = $request->ulb_id;
                $roleUser->menu_roleid = $request->menu_roleid;
                $roleUser->suspended = $request->suspended;
                $roleUser->save();
                return response(true, "Successfully added data");
            }          
        catch (Exception $error) 
            {
                return response()->json($error, 400);
            }
    }
    //////////////////////////////

    //////////////////////////////
    //updating details in table
    function update(Request $request, $id)
    {         
        //data collection in variable "$register"
        $register = MenuUlbroles::find($id);
        //operation
        $register->ulb_id      = $request->ulb_id;
        $register->menu_roleid = $request->menu_roleid;
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
        $device = MenuUlbroles::find($id);
        $result = $device->delete();
        if ($result) {
            return ["result" => "deleted"];
        }
        return ["result" => "not deleated"];
    }

    
}