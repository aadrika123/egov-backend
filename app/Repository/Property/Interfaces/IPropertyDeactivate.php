<?php

namespace App\Repository\Property\Interfaces;

use Illuminate\Http\Request;

/**
 * | Created On-19-11-2022 
 * | Created By-Sandeep Bara
 **/

interface IPropertyDeactivate
{
   public function readHoldigbyNo(Request $request); 
   public function deactivatProperty($propId,Request $request);
}
