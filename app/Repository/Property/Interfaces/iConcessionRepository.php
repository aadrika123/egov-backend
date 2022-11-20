<?php

namespace App\Repository\Property\Interfaces;

use Illuminate\Http\Request;

/**
 * | Created On-17-11-2022 
 * | Created By-Mrinal Kumar
 **/

interface iConcessionRepository
{

    public function applyConcession(Request $request);                      //apply concession
    public function postHolding(Request $request);                          //post Holding
    public function inbox();                                                // Concession Inbox
    public function outbox();                                               // Concession Outbox List
}
