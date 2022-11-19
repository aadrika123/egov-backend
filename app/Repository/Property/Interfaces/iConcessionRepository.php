<?php

namespace App\Repository\Property\Interfaces;

use Illuminate\Http\Request;

/**
 * | Created On-17-11-2022 
 * | Created By-Mrinal Kumar
 **/

interface iConcessionRepository
{
    //Concession Detail Update
    public function UpdateConDetail(Request $request);

    //documents upload
    public function UpdateDocuments(Request $request, $id);
}
