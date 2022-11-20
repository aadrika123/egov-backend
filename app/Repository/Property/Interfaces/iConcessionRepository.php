<?php

namespace App\Repository\Property\Interfaces;

use Illuminate\Http\Request;

/**
 * | Created On-17-11-2022 
 * | Created By-Mrinal Kumar
 **/

interface iConcessionRepository
{
    //apply concession
    public function applyConcession(Request $request);

    //post Holding
    public function postHolding(Request $request);
}
