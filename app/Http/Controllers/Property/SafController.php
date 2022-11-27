<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SafController extends Controller
{
    /**
     * | Created On-27-11-2022 
     * | Created By-Anshu Kumar
     * | Created for read all the SAF data
     */

    public function __construct()
    {
    }

    /**
     * | Get SAF Demand By SAF Id
     * | @param request $req
     */
    public function getDemandBySafId(Request $req)
    {
        $req->validate([
            'id' => 'required|integer'
        ]);
    }
}
