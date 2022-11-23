<?php

namespace App\Repository\Menu\Concrete;

use App\Models\Menu\MenuMaster;
use App\Repository\Menu\Interface\iMenuRepo;
use Exception;

/**
 * | Created On-23-11-2022 
 * | Created By-Anshu Kumar
 * | Repository for the Menu Permission
 */

class MenuRepo implements iMenuRepo
{
    /**
     * | Get All the Menues
     */
    public function getAllMenues()
    {
        try {
            $menues = MenuMaster::orderByDesc('id')
                ->get();
            return responseMsg(true, "Menu Masters", remove_null($menues));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
}
