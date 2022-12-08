<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CustomDetail;

class CustomController extends Controller
{
    public function getCustomDetails(Request $request)
    {
        $obj = new CustomDetail();
        return $obj->getCustomDetails($request);
    }

    //post custom details
    public function postCustomDetails(Request $request)
    {
        $obj = new CustomDetail();
        return $obj->postCustomDetails($request);
    }
}
