<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * | Created On- 17-11-2022 
 * | Created By- Anshu Kumar
 * | Controller for SAF Reassessment Apply Section
 */

class SafReassessmentController extends Controller
{
    // Apply For Reassessment
    public function applyReassessment(Request $req)
    {
        dd($req->all());
    }
}
