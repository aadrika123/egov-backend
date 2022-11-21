<?php

namespace App\Repository\Property\Interfaces;

use Illuminate\Http\Request;

/**
 * | Created On-17-11-2022 
 * | Created By-Mrinal Kumar
 **/

interface iObjectionRepository
{

    public function getOwnerDetails(Request $request);
    public function rectification(Request $request);
    public function objectionNo($propertyId);
    public function objectionType();
}
