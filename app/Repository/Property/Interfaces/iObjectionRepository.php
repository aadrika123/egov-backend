<?php

namespace App\Repository\Property\Interfaces;

use Illuminate\Http\Request;

/**
 * | Created On-17-11-2022 
 * | Created By-Mrinal Kumar
 **/

interface iObjectionRepository
{
    public function ClericalMistake(Request $request);
    public function getOwnerDetails(Request $request);
}
