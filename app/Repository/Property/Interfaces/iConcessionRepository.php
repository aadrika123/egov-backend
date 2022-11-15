<?php

namespace App\Repository\Property\Interfaces;

use Illuminate\Http\Request;

interface iConcessionRepository
{
    //Concession Detail Update
    public function UpdateConDetail(Request $request);

    //documents upload
    public function UpdateDocuments(Request $request);
}
