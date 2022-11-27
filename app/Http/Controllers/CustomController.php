<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CustomDetail;

class CustomController extends Controller
{
    public function getCustomDetails()
    {
        $obj = new CustomDetail();
        return $obj->getCustomDetails();
    }

    //post custom details
    public function postCustomDetails(Request $request)
    {
        $obj = new CustomDetail();
        return $obj->postCustomDetails($request);
    }
}
