<?php

namespace App\Repository\MenuPermission\Concrete;

use App\Models\MenuPermission\MenuGroups;
use App\Repository\MenuPermission\interface\iMenuItemsRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\MenuPermission\MenuItems;
use App\Models\MenuPermission\MenuUlbroles;
use Exception;


class EloquentMenuItems implements iMenuItemsRepository
{
    public function view()
    {
        $data = MenuItems::get();
        return response()->json(["data" => $data]);
    }
    //add data
    function add(Request $request)
    {
        $validateUser = Validator::make(
            $request->all(),
            [
                'serial' => 'required',
                'menu_groupid' => 'required',
                'parent_id' => 'required',
                'menu_name' => 'required',
                'display_string' => 'required',
                'icon_name' => 'required',
                'component_name' => 'required',
                'deleated' => 'required',
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
        $device = new MenuItems();
        //operation
        $device->serial           = $request->serial;
        $device->menu_groupid     = $request->menu_groupid;
        $device->parent_id        = $request->parent_id;
        $device->menu_name        = $request->menu_name;
        $device->display_string   = $request->display_string;
        $device->icon_name        = $request->icon_name;
        $device->component_name   = $request->component_name;
        $device->deleated         = $request->deleated;
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
        $register = MenuItems::find($id);
        //operation
        $register->serial           = $request->serial;
        $register->menu_groupid     = $request->menu_groupid;
        $register->parent_id        = $request->parent_id;
        $register->menu_name        = $request->menu_name;
        $register->display_string   = $request->display_string;
        $register->icon_name        = $request->icon_name;
        $register->component_name   = $request->component_name;
        $register->deleated         = $request->deleated;
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
        $device = MenuItems::find($id);
        $result = $device->delete();
        if ($result) {
            return ["result" => "deleted"];
        }
        return ["result" => "not deleated"];
    }
    ///////////////////////////////////////
    //show MenuItems based on menu_group_id
    ///////////////////////////////////////
    public function listmenuitembygroupid(Request $request)
    {
        $menuItem = MenuItems::where('menu_groupid', $request->menu_groupid)
            ->get('menu_name');
        return response()->json(["data" => $menuItem]);
    }
    ///////////////////////////////////////
    //show allmenuItems
    ///////////////////////////////////////
    public function allmenuitems()
    {
        $menuItem = MenuItems::get();
        return response()->json(["data" => $menuItem]);
    }
    ////////////////////////////////////////
    //listin of all menu groups
    ////////////////////////////////////////
    public function listmenugroups()
    {
        $menuGroups = MenuGroups::count('all');
        return response()->json(["list" => $menuGroups]);
    }

    ///////////////////////////////
    //show menuGroups wis Items request(ulb_id)
    ///////////////////////////////
    public function menuGroupWiseItems(Request $request)
    {
        //validation
        $validateUser = Validator::make(
            $request->all(),
            [
                'ulbid' => 'required'
            ]
        );

        if ($validateUser->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'validation error',
                'errors' => $validateUser->errors()
            ], 401);
        }
        //data of the menu_items
        try {
            $data =  MenuUlbroles::where('ulb_id', $request->ulbid)
                ->get('id');
            //condition check
            if (!$data) {
                return response()->json(["message" => "Data Do Not Exist"]);
            }
            //data of menu_groups
            $groups = MenuUlbroles::where('ulb_id', $request->ulbid)
                ->join('menu_maps', 'menu_maps.ulb_menuroleid', '=', 'menu_ulbroles.id')
                ->join('menu_items', 'menu_items.id', '=', 'menu_maps.menu_itemid')
                ->join('menu_groups', 'menu_groups.id', '=', 'menu_items.menu_groupid')
                ->get('menu_groups.*');

            // dd($groups);
            return response()->json(["menugroups" => $groups]);
        }
        //catch error
        catch (Exception $e) {
            echo $e->getLine();
            return response()->json(["error" => $e->getMessage()]);
        }
    }

    ////////////////////////////////////////////////
    //data for the menugroup and RoleWiseitems requeats are (ulb_id and roleid)
    ////////////////////////////////////////////////
    public function menuGroupAndRoleWiseItems(Request $request)
    {
        try {
            // data of menu_roles
            // $roles = MenuUlbroles::where('ulb_id', $request->ulbid)
            //     ->where('menu_roleid', $request->menuroles)
            //     ->join('menu_roles', 'menu_roles.id', '=', 'menu_ulbroles.menu_roleid')
            //     ->get('menu_roles.*');
            //data of menu_groups
            $groups = MenuUlbroles::join('menu_maps', 'menu_maps.ulb_menuroleid', '=', 'menu_ulbroles.id')
                                ->join('menu_items', 'menu_items.id', '=', 'menu_maps.menu_itemid')
                                ->join('menu_groups', 'menu_groups.id', '=', 'menu_items.menu_groupid')
                                ->where('ulb_id', $request->ulbid)
                                ->where('menu_roleid', $request->menuroles);
            if(isset($request->roleId))
                $groups = $groups->where('menu_roleid', $request->roleId);
            $groups = $groups->get('menu_groups.*', 'menu_items.*');
            
            //echo "<pre/>"; print_r($groups);
            // $a = $groups[0]->id;

            // //data of the menu_items
            
            //foreach loop for the roles wise items    
            $items = array();
            foreach ($groups as $group) {
                $items['groupName'] = $group->group_name;
                $item =  MenuItems::where('menu_groupid', $group->id)
                        ->get();
                $items['items'] = $item;                                 // $collectItems=$items->first();
            }
            // //assigning keys to the to $items and $groups
            // $groups['groupWiseItems'] = $items;
            $roles['menuGroup'] = $items;
           
            //data return
            return response()->json(["menuRoles" => $roles]);
        }
        //collecting the errors in the code in $e 
        catch (Exception $e) {
            echo $e->getLine();
            return response()->json(["error" => $e->getMessage()]);
        }
    }
    ////////////////////////////////////////////////
    //data for the menurole requeats are (ulb_id and roleid)
    ////////////////////////////////////////////////
    public function ulbWiseMenuRole(Request $request)
    {
        $validateUser = Validator::make(
            $request->all(),
            [
                'ulbid' => 'required',
                'menugroups' => 'required',
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
            // data of menu_roles
            $roles = MenuUlbroles::where('ulb_id', $request->ulbid)
                ->join('menu_maps', 'menu_maps.ulb_menuroleid', '=', 'menu_ulbroles.id')
                ->join('menu_items', 'menu_items.id', '=', 'menu_maps.menu_itemid')
                ->join('menu_groups', 'menu_groups.id', '=', 'menu_items.menu_groupid')
                ->join('menu_roles', 'menu_roles.id', '=', 'menu_ulbroles.menu_roleid')
                ->get('menu_roles.*');

            //data return
            return response()->json(["menuRoles" => $roles]);
        }
        //collecting the errors in the code in $e 
        catch (Exception $e) {
            echo $e->getLine();
            return response()->json(["error" => $e->getMessage()]);
        }
    }
}