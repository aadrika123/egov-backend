<?php

namespace App\Repository\Property\Interfaces;

use Illuminate\Http\Request;

/**
 * | Created On-19-11-2022 
 * | Created By-Sandeep Bara
 **/

interface IPropertyBifurcation
{  
    public function addRecord(Request $request);
    public function inbox(Request $request);
}
