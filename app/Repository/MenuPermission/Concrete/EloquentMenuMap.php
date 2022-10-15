<?php

namespace App\Repository\MenuPermission\Concrete;


use App\Repository\MenuPermission\Interface\iMenuMapRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\MenuPermission\MenuMaps;

class EloquentMenuMap implements iMenuMapRepository
{
    /**
     * | Getting all Menu Data 
     * | Fetching Data using Eloquent
     * | Using Interfce (iMenuMapRepository)
     * | #check > fetching data of ('general_permission','admin_permission') from the Table (Menu_Map) 
     * | #resultOne > Fetching All the Data of ('general_permission') form variable #check
     * | #resultTwo > Fetching All the Data of ('admin_permission') form variable #check
     */
    public function view($id)
    {
        //////////////////////////////////////////////////////////////////////
        // $check = MenuMaps::where('id', $id)
        //     ->get(['general_permission', 'admin_permission']);
        // // returning 
        // foreach ($check as $check) {
        //     $result_one = $check->general_permission;
        //     $result_two = $check->admin_permission;
        //     //condition checking for the permission
        //     if ($result_one == false && $result_two == false) {
        //         return response()->json(["not authorised"], 400);
        //     }
        // }
        //////////////////////////////////////////////////////////////////////
        //else section
        $data = MenuMaps::find($id);
        return response()->json(["data" => $data]);
    }
    /**
     * | Adding Data to Menu_Map Table 
     * | Savins data according to the permission
     * | Fetching Data using Eloquent
     * | Using Interfce (iMenuMapRepository)
     * | #request > collectiong data 
     * | #data > saved data
     */
    function add(Request $request)
    {
        /////////////////////////////////////////////////
        $validateUser = Validator::make(
            $request->all(),
            [
                'ulb_menuroleid' => 'required',
                'menu_itemid' => 'required',

            ]
        );

        if ($validateUser->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'validation error',
                'errors' => $validateUser->errors()
            ], 401);
        }

        ///////////////////////////////////////////////////////////////////////////////////
        //checking for the permission for the admin and general
        // if ($request->general_permission == false && $request->admin_permission == false) {
        //     return response()->json(["not authorised"], 400);
        // }
        //data collection in variable "$device"
        // if ($request->admin_permission == true) {
        ///////////////////////////////////////////////////////////////////////////////////
        $device = new MenuMaps();
        //operation
        $device->ulb_menuroleid            = $request->ulb_menuroleid;
        $device->menu_itemid               = $request->menu_itemid;
        $device->general_permission        = $request->general_permission;
        $device->admin_permission          = $request->admin_permission;
        //data saved
        $data = $device->save();
        if ($data) {
            return response()->json(["message" => "Success"], 200);
        }
        return response()->json(["message" => "not permitted"], 400);
        /////////////////////////////////////////////////////////////////////////
        // }
        // return response()->json(["message" => "not permitted"], 400);
        /////////////////////////////////////////////////////////////////////////
    }
    /**
     * | Updating Data to Menu_Map Table 
     * | Savins data according to the permission
     * | Fetching Data using Eloquent
     * | Using Interfce (iMenuMapRepository)
     * | #check > collectiong data of('admin_permission', 'admin_permission') from Table Menu_Map
     * | #result > collecting data of ('general_permission') from variable ('#check')
     * | #result_two >  collecting data of ('admin_permission') from variable ('#check')
     * | #register > using to find the req ('id') and store data to the respected ('id') ('Menu_Map)
     */
    function update(Request $request, $id)
    {
        //////////////////////////////////////////////////////////////////////////////////
        //checking for the permission for the admin and general
        // $check = MenuMaps::where('id', $id)
        //     ->get(['general_permission', 'admin_permission']);

        // // returning 
        // foreach ($check as $check) {
        //     $result = $check->general_permission;
        //     $result_two = $check->admin_permission;
        //     //checkin for the permission
        //     if ($result == false && $result_two == false) {
        //         return response()->json(["not authorised"], 400);
        //     }
        //////////////////////////////////////////////////////////////////////////////////

        //data collection in variable "$register"
        $register = MenuMaps::find($id);
        //operation
        $register->ulb_menuroleid            = $request->ulb_menuroleid;
        $register->menu_itemid               = $request->menu_itemid;
        $register->general_permission        = $request->general_permission;
        $register->admin_permission          = $request->admin_permission;
        //data saved
        $register->save();
        if ($register) {
            return response()->json(["message" => "Success"], 200);
        }
        return response()->json(["message" => "error"], 400);
        ////////////////////////////////////////////////////////////
        // }
        ///////////////////////////////////////////////////////////
    }
    /** 
     * | delete Data of Menu_Map Table according to the respected id
     * | Savins data according to the permission
     * | Fetching Data using Eloquent
     * | Using Interfce (iMenuMapRepository)
     * | #check > collectiong data of('admin_permission', 'admin_permission') from Table Menu_Map
     * | #result > collecting data of ('general_permission') from variable ('#check')
     * | #result_two >  collecting data of ('admin_permission') from variable ('#check')
     * | #register > using to find the req ('id') and store data to the respected ('id') ('Menu_Map)
     */
    function delete($id)
    {
        ////////////////////////////////////////////////////////////////////////////////////
        //checking for the permission for the admin and general
        // $check = MenuMaps::where('id', $id)
        //     ->get(['general_permission', 'admin_permission']);

        // // returning 
        // foreach ($check as $check) {
        //     $result = $check->general_permission;
        //     $result_two = $check->admin_permission;
        //     //checkin for the permission
        //     if ($result == false && $result_two == false) {
        //         return response()->json(["not authorised"], 400);
        //     }
        ///////////////////////////////////////////////////////////////////////////////////
        $device = MenuMaps::find($id);
        $result = $device->delete();
        if ($result) {
            return ["result" => "deleted"];
        }
        return ["result" => "not deleated"];
    }
    ////////////////////////////////////////////////////////
    // }
    ////////////////////////////////////////////////////////
}
