<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuMaster extends Model
{
    use HasFactory;

    /**
     * | get All list of Menues form the master table of menues
     */
    public function fetchAllMenues()
    {
        return MenuMaster::where('is_deleted', false)
            ->orderByDesc('id')
            ->get();
    }


    /**
     * | Add Data of Menu in Menu Master
     * | @param request
     * | Query Run Time - 332ms 
     * | status- open
     * | rating-1
     */
    public function putNewMenues($request)
    {
        $newMenues = new MenuMaster();
        $newMenues->menu_string  =  $request->menuName;
        $newMenues->top_level  =  $request->topLevel;
        $newMenues->sub_level  =  $request->subLevel;
        $newMenues->parent_serial  =  $request->parentSerial;
        $newMenues->description  =  $request->description;
        $newMenues->serial = $request->serial;
        $newMenues->route = $request->route;
        $newMenues->icon = $request->icon;
        $newMenues->save();
    }


    /**
     * | Delete the details of the Menu master 
     * | @param menuID
     * | Query Run Time - ms 
     * | status- open
     * | rating-1
     */
    public function softDeleteMenues($menuId)
    {
        MenuMaster::where('id', $menuId)
            ->update(['is_deleted' => true]);
    }

    /**
     * | Get menu by Role Id
     */
    public function getMenuByRole($roleId)
    {
        $a = MenuMaster::select(
            'menu_masters.id'
        )
            ->join('wf_rolemenus', 'wf_rolemenus.menu_id', '=', 'menu_masters.id')
            ->where('menu_masters.is_deleted', false)
            ->where('wf_rolemenus.role_id', $roleId)
            ->orderByDesc('menu_masters.id')
            ->get();
        return  objToArray($a);
    }

    /**
     * | Get Parent Menues
     */
    public function getParentMenue()
    {
        return MenuMaster::select(
            'id',
            'menu_string'
        )
        ->where('parent_serial',0);
    }

    /**
     * | Get Menues By Id
     */
    public function getMenuById($id)
    {
        return MenuMaster::find($id)
        ->first();
    }
}
