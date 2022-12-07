<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\Property\PropActiveSafsDoc;
use Illuminate\Http\Request;
use App\Repository\Property\Interfaces\iDocumentRepository;

class DocumentController extends Controller
{
    /**
     * | Created On-07-12-2022 
     * | Created By-Mrinal Kumar
     * --------------------------------------------------------------------------------------
     * | Controller regarding with Property Document 
     */


    //document verification
    public function safDocStatus(Request $req)
    {
        $obj = new PropActiveSafsDoc();
        return $obj->safDocStatus($req);
    }
}
