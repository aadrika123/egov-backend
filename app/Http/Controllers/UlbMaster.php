<?php

namespace App\Http\Controllers;

use App\Models\UlbMaster as ModelsUlbMaster;
use Illuminate\Http\Request;
use App\Models\UlbWardMaster;

class UlbMaster extends Controller
{
    public function getAllWards()
    {
        $obj = new UlbWardMaster;
        return $obj->getAllWards();
    }
}
