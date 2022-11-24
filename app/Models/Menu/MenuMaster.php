<?php

namespace App\Models\Menu;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuMaster extends Model
{
    use HasFactory;

    public function fetchAllMenues()
    {
        return MenuMaster::orderByDesc('id')
            ->get();
    }

    /**
     * | Add Data of Menu in Menu Master
     * | @param request
     * | Query Run Time - 332ms 
     * | status- open
     * | rating-1
     */
    public function addNewMenues($request)
    {
        try {
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
            return responseMsg(true, "Data Saved!", "");
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }
}
