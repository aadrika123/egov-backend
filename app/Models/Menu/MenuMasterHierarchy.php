<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuMasterHierarchy extends Model
{
    use HasFactory;

    protected $fillable = ['menu_name', 'serial_no', 'parent_join', 'description', 'route', 'icon', 'module_id'];

    /**
     * | get All list of Menus from the master table of menus
     */
    public function fetchAllMenus()
    {
        return MenuMasterHierarchy::where('status', 1)
            ->get();
    }

    /**
     * | Get Menues By Id
     */
    public function getMenuById($id)
    {
        return MenuMasterHierarchy::where('id', $id)
            ->firstOrFail();
    }

    /**
     * | Create Menus
     */
    public function store($request)
    {
        $menu = MenuMasterHierarchy::create($request);
        return response()->json([
            'menuName' => $menu->menu_name
        ]);
    }

    /**
     * | Update Menus
     */
    public function edit($request)
    {
        $menu = MenuMasterHierarchy::find($request['id']);
        $menu->update($request);
    }

    /**
     * | Get Parent Menues
     */
    public function getParentMenu()
    {
        return MenuMasterHierarchy::select(
            'id',
            'menu_name',
            'parent_join',
            'serial_no'
        )
            ->where('parent_join', 0)
            ->orderBy("serial_no");
    }

    public function getMenuByModuleId($req)
    {
        return MenuMasterHierarchy::select(
            '*'
        )
            ->where('module_id', $req->moduleId)
            ->orderBy("serial_no")
            ->get();
    }
}
