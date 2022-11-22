<?php

namespace App\Http\Controllers;

use App\Repository\Property\Interfaces\iObjectionRepository;
use Illuminate\Http\Request;

class ObjectionController extends Controller
{
    protected $objection;
    public function __construct(iObjectionRepository $objection)
    {
        $this->Repository = $objection;
    }


    //
    public function getOwnerDetails(Request $request)
    {
        return $this->Repository->getOwnerDetails($request);
    }

    //Objection for Clerical Mistake
    public function applyObjection(Request $request)
    {
        return $this->Repository->applyObjection($request);
    }

    //objection type list
    public function objectionType(Request $request)
    {
        return $this->Repository->objectionType($request);
    }
}
